<?php

class LookupType extends BaseEntity  {
	
	public function setTableDefinition() {
		parent::setTableDefinition();
		
		$this->setTableName('lookuptype');
		$this->hasColumn('name', 'string', 50, array('notnull' => true, 'unique' => true, 'notblank' => true));
		$this->hasColumn('description', 'string', 255, array('notnull' => true, 'default' => '', 'length' => '255'));
	}
	
	public function setUp() {
		parent::setUp();
		$this->hasMany("LookupTypeValue as values", 
							array(
								'local' => 'id',
								'foreign' => 'lookuptypeid'
							)); 
	}
	
	/**
	 * Return the values of the options for the lookup type
	 * 
	 * @return Array containing the lookup types for the values or false if an error occurs
	 *
	 */
	function getOptionValues() {		
		$optionvaluesquery = "SELECT lv.lookuptypevalue as optiontext, lv.lookuptypevalue as optionvalue FROM lookuptype AS l , 
		lookuptypevalue AS lv WHERE l.id =  lv.lookuptypeid AND l.name ='".$this->getName()."' ORDER BY optiontext ";
		return getOptionValuesFromDatabaseQuery($optionvaluesquery);
	}
	
  /**
	 * Return the values of the options for the lookup type
	 * 
	 * @param String $orderby The column to order the results by, either optiontext - the text or optionvalue the value 
	 * 
	 * @return Array containing the lookup types for the values or false if an error occurs
	 *
	 */
	function getOptionValuesAndDescription($orderby = "optiontext") {		
		$optionvaluesquery = "SELECT lv.lookupvaluedescription as optiontext, lv.lookuptypevalue as optionvalue FROM lookuptype AS l , 
		lookuptypevalue AS lv WHERE l.id =  lv.lookuptypeid AND l.name ='".$this->getName()."' ORDER BY ".$orderby;
		return getOptionValuesFromDatabaseQuery($optionvaluesquery);
	}
	/**
	 * Return the values of the options for the lookup type
	 * 
	 * @return Array containing the lookup types for the values or false if an error occurs
	 *
	 */
	function getOptionValuesFromQuery() {		
		# get the query to execute
		$conn = Doctrine_Manager::connection(); 
		$query = $conn->fetchRow("SELECT querystring FROM lookupquery WHERE name = '".$this->getName()."'");
		# debugMessage($query); 
		if (isEmptyString($query['querystring'])) {
			return array(); 
		} else {
			return getOptionValuesFromDatabaseQuery($query['querystring']);
		}
	}
	/**
	 * Return the option values for a number of type names
	 *
	 * @param Array $typenames The names of the lookup types
	 * @return Array Containing the values of the lookup types
	 */
	function getOptionValuesFromMultipleTypes($typenames) {
		// get the values for each type
		$values = array(); 
		foreach($typenames as $name) {
			$this->setName($name); 
			$values = array_merge_maintain_keys($values, $this->getOptionValues());
			
		}
		// sort the option values alphabetically
		sort($values); 
		return $values; 
	}
	
	/**
	 * Get the description of a lookup value 
	 * 
	 * @param String $lookuptype The lookup type 
	 * @param String $lookuptypevalue The current value of the lookup type whose description is to be loaded
	 * 
	 * @return String 
	 */
	static function getLookupValueDescription($lookuptype, $lookuptypevalue) {
		$sql = "SELECT lv.lookupvaluedescription FROM lookuptypevalue lv INNER JOIN lookuptype l ON (lv.lookuptypeid = l.id AND l.`name` = '".$lookuptype."' AND lv.lookuptypevalue = '".$lookuptypevalue."')";
		$conn = Doctrine_Manager::connection(); 
		
		return $conn->fetchOne($sql); 
	}
	
}
?>