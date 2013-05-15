<?php

require_once APPLICATION_PATH.'/includes/commonfunctions.php';

require_once 'eventdispatcher/sfEventDispatcher.php';
require_once 'eventdispatcher/sfEvent.php';

# event hander functionality
require_once APPLICATION_PATH.'/includes/eventhandlerfunctions.php';

require_once 'doctrine/doctrine.compiled.php';

/**
 * Contains extensions to the Doctrine Record to provide commonly used functions
 * 
 */
abstract class BaseRecord extends Doctrine_Record {
	private $datefields = array();
	/**
	 * Dispatcher to handle events
	 *
	 * @var sfEventDispatcher
	 */
	protected $eventdispatcher; 
	
	/**
	 * The configuration instance for the class
	 *
	 * @var Zend_Config_Ini
	 */
	public $config;
	/**
	 * The translate/language instance for the class
	 *
	 * @var Zend_Translate_Ini
	 */
	public $translate;
	/**
	 * The custom error messages for the class
	 *
	 * @var Array
	 */  
	private $customerrormessages = array(); 
	/**
	 * The Logger instance for the class
	 *
	 * @var Logger
	 */
	protected $logger; 
	
	/**
	 * Add custom validation messages
	 *
	 * @param Array $newmessages Array containing the custom error messages, with the field name as the key and the custom error message as the value
	 */
	function addCustomErrorMessages($newmessages) {
		$this->customerrormessages = array_merge_maintain_keys($this->customerrormessages, $newmessages);
	}
	/**
	 * Return the custom error messages
	 *
	 * @return Array Array containing the custom error messages, with the field name as the key and the custom error message as the value
	 */
	function getCustomErrorMessages() {
		return $this->customerrormessages; 
	}
	/**
	 * Return the custom error message for a field and constraint
	 *
	 * @param String $key The key for the error message, of the form field.constraint, e.g email.unique, email.blank
	 * 
	 * @return String The custom error message for the specified key, or an empty string if the error message does not exist
	 */
	function getCustomErrorMessageForField($key) {
		return $this->customerrormessages[$key]; 
	}

    /*
     * Constructor Method
     */
    public function construct() {
    	$this->config = Zend_Registry::get('config');
    	$this->translate = Zend_Registry::get('translate');
        //$this->addDateFields(array("datecreated", "lastupdatedate"));
    	$this->eventdispatcher = initializeSFEventDispatcher();

    	// initialize the logger instance
    	$this->logger = Zend_Registry::get('logger');
    }
	/**
	 * Return the array of fields containing dates
	 * 
	 * @return Array array of the fields containing dates
	 */
	function getDateFields() {
		return $this->datefields;
	}
	
	/**
	 * Set the names of the fields containing date values
	 *
	 * @param Array $array Array of names of fields containing dates
	 */
	function setDateFields($array) {
		$this->datefields = $array;
	}
	
	/**
	 * Add more date format fields to the registry
	 *
	 * @param Array $array array containing the name of the additional date fields
	 */
	function addDateFields($array) {
		$this->setDateFields(array_unique(array_merge($this->datefields, $array)));
	}
	/**
	 * Clear the error stack
	 *
	 */
	function clearErrorStack() {
		$this->_errorStack = null;
	}
	/**
	 * Whether or not the object has an error
	 *
	 * @return boolean true if the object has an error, otherwise false
	 */
	function hasError() {
		return !isEmptyString($this->getErrorStackAsString());
	}
	/**
	 * Populate the attributes of the object with the data. Also add the auditing data, createdby and lastupdatedby to 
	 * the relationships if not specified
	 *
	 * @param Array $post_array The array of data values to populate the object attributes
	 * @return boolean true if the object validates after populaton, or false if there are any validation errors
	 */
	function processPost($post_array) {
		// $post_array = array_remove_empty($post_array);
		# format the date fields
		foreach ($this->datefields as $fieldname) {
			if (isArrayKeyAnEmptyString($fieldname, $post_array)) {
                // remove the empty string from the array otherwise the date will be set to January 1, 1970
                unset($post_array[$fieldname]); 
            } else {
				$post_array[$fieldname] = changeDateFromPageToMySQLFormat($post_array[$fieldname]);
				// TODO: SM - 09/17/10 - Cleanup this funny hack for dates, 
				// I am not sure why new values for the dates are not picked up, 
				// make the dates an empty string so that they can be updated
				$this->_set($fieldname, ""); 
			}
		}
		$relations = $this->getTable()->getRelations();
		# loop through each relation and add auditing fields
		foreach ($relations as $alias => $arelation) {
			if (!isArrayKeyAnEmptyString($alias, $post_array)) {
				foreach ($post_array[$alias] as $key => $value) {
					# force the loading of only the hasOne relationships to conserve memory
					if ($arelation['type'] == 0) {
					   $this->$alias; 
					} 
					if (!isArrayKeyAnEmptyString('createdby', $post_array)) {
						// add createdby to each relation
						if ($arelation['type'] == 0) {
							// hasOne so only one item in the relation
							$post_array[$alias]['createdby'] = $post_array['createdby']; 
						} else {
							// hasMany, use a key to populate each item in the relation 
							$post_array[$alias][$key]['createdby'] = $post_array['createdby'];
						}
					} 
					// check if the createdby field is still empty then use the lastupdatedby field since the relation is new
					if (isArrayKeyAnEmptyString('createdby', $post_array[$alias])) {
						if (!isArrayKeyAnEmptyString('lastupdatedby', $post_array)) {
							if ($arelation['type'] == 0) {
								// hasOne so only one item in the relation
								$post_array[$alias]['createdby'] = $post_array['lastupdatedby'];
							} else {
								// hasMany, use a key to populate each item in the relation 
								$post_array[$alias][$key]['createdby'] = $post_array['lastupdatedby'];
							}
						} 
					}
					if (!isArrayKeyAnEmptyString('lastupdatedby', $post_array)) {
						// add lastupdatedby to each data item
						if ($arelation['type'] == 0) {
							// hasOne so only one item in the relation
							$post_array[$alias]['lastupdatedby'] = $post_array['lastupdatedby']; 
						} else {
							// hasMany, use a key to populate each item in the relation 
							$post_array[$alias][$key]['lastupdatedby'] = $post_array['lastupdatedby']; 
						}
					}
					
					// remove any empty id columns
					if ($arelation['type'] == 0) {
						// unset any empty id columns which have been sent - hasOne
						if (isArrayKeyAnEmptyString('id', $post_array[$alias])) {
							// remove the empty id field
							unset($post_array[$alias]['id']);  
						} 
					} else {
						// unset any empty id columns which have been sent - hasMany
						if (isArrayKeyAnEmptyString('id', $post_array[$alias][$key])) {
							// remove the empty id field
							unset($post_array[$alias][$key]['id']);  
						}
					}
				}
			} // end check whether alias exists in the data for population to eliminate PHP warnings 
		}
		# remove the ID field if it is empty
		if (isArrayKeyAnEmptyString('id', $post_array)) {
			unset($post_array['id']);
		}
		$this->synchronizeWithArray($post_array, true);
		return $this->isValid(true);
	}
	
	/**
	 * Execute getters and setters on the instance if they do not exist
	 *
	 * @param String $method The method to execute
	 * @param Array $args The method arguments
	 * @return unknown
	 */
	function __call($method, $args){
		$attribute = strtolower(substr($method, 3, 1).substr($method, 4));
		$prefix = substr($method, 0, 3);

		switch ($prefix)  {
			 case "get":
				# return the value of the attribute
				return $this->get($attribute, true);
				break;
			 case "set": 
			 	# set the value of the attribute
            	$this->set($attribute, $args[0], true);
            break; 
         default:
         	# do nothing
            break;
		}
	}
	
	/**
	 * Return the name of the table that the class is mapped to
	 *
	 * @return String the Name of the table that the class is mapped to
	 */
	function getTableName() {
		return $this->getTable()->getTableName();
	}
	
	/**
	 * Load the object instance from the database
	 *
	 * @param Integer $id The system generated identifier for the object
	 */
	function populate($id) {
		// first trim the id of any leading or trailing spaces
		$id = trim($id); 
		$result = $this->getTable()->find($id);
		if(!$result){
			# Entity not found, do nothinig
			return; 
		}
		$this->synchronizeWithArray($result->toArray());
		// also assign the identifier for the object so that it can be updated
		$this->assignIdentifier($id); 
	}

    /**
     *
     * Return the checked attribute for an HTML radio button or check box by comparing the
     * expected value of the control with the actual value
     * 
     * @param String $expected The expected value
     * @param String $actual The actual value
     * @return String the checked attribute if the specified values are equal or an empty string if they are not
     */
    function getCheckedAttributeForValue($expected, $actual) {
        return getCheckedAttribute((strtolower($expected) == strtolower($actual)) ? true : false);
    }	
    /**
     * Format the validation errors in a human readable format
     *
     * @see Doctrine_Record#getErrorStackAsString()
     * @return String 
     */
	public function getErrorStackAsString()   {
        $errorStack = $this->getErrorStack();
        if ($errorStack->count() > 0) {
	        $message = 'The following errors occured:<ul>';
	        foreach ($errorStack as $field => $errors) {
	           		foreach ($errors as $value) {
	           			$message.= "<li>".$this->getCustomErrorMessage($field, $value)."</li>"; 
	           		}
	        }
            return $message ."</ul>";
        } else {
            return false;
        }
    }
    /**
     * Get the custom error message for the field validation
     *
     * @param String $field The name of the field
     * @param String $validationrule The name of the validation rule
     */
    function getCustomErrorMessage($field, $validationrule) {
    	if (!array_key_exists($field.".".$validationrule, $this->customerrormessages)) {
    		return $validationrule; 
    	}
    	return $this->customerrormessages[$field.".".$validationrule];
    }
    /**
     * Notify all listeners of the event, through the event dispatcher instance for the class. This is just a convenience method to
     * avoid accessing the event dispatcher directly
     *
     * @param sfEvent $event The event that has occured
     */
    function notify($event) {
    	$this->eventdispatcher->notify($event); 
    }
    
    /**
     * Application Event Hooks, these are different from the Doctrine event hooks and are used
     * for application specific event processing
     * 
     * @return Boolean TRUE or FALSE if an error occured
     */
    function afterSave() {
    	// do nothing
    	return true; 
    }
    /**
     * Application Event Hooks, these are different from the Doctrine event hooks and are used
     * for application specific event processing
     * 
     * @return Boolean TRUE or FALSE if an error occured
     */
    function afterUpdate() {
    	// do nothing
    	return true; 
    }
 	/**
     * Application Event Hooks, these are different from the Doctrine event hooks and are used
     * for application specific event processing
     * 
     * @return Boolean TRUE or FALSE if an error occured
     */
    function afterDelete() {
    	// do nothing
    	return true; 
    }
}
?>