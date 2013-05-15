<?php

class SessionWrapper {
    protected static $_instance;
    protected  $namespace = null;

	private function __construct() {
			/* Explicitly start the session */
			$config = array(
			    'name'           => 'session',
			    'primary'        => 'id',
			    'modifiedColumn' => 'modified',
			    'dataColumn'     => 'data',
			    'lifetimeColumn' => 'lifetime',
			    'db'             => Zend_Registry::get("dbAdapter")
			);
			Zend_Session::setSaveHandler(new Zend_Session_SaveHandler_DbTable($config));
			 
			//start your session
			Zend_Session::start();

			/* Create our Session namespace - using 'Default' namespace */
			$this->namespace = new Zend_Session_Namespace();

			/** Check that our namespace has been initialized - if not, regenerate the session id
			 * Makes Session fixation more difficult to achieve
 			 */
			if (!isset($this->namespace->initialized)) {
			    Zend_Session::regenerateId();
			    $this->namespace->initialized = true;
			}
	}

	/**
	 * Implementation of the singleton design pattern
	 * See http://www.talkphp.com/advanced-php-programming/1304-how-use-singleton-design-pattern.html
	 * 
	 * @return SessionWrapper
	 */
	public static function getInstance() {
        if (null === self::$_instance) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     * Retrieve a value stored in the session
     * 
     * return $default if $var not found in session namespace
     * @param $var string
     * @param $default string
     * @return string
     */
    public function getVar($var, $default=null){
    	return (isset($this->namespace->$var)) ? $this->namespace->$var : $default;
    }

    /**
     * Save a value to the session
     * @param $var string The name of the session variable
     * @param $value string The value 
     */
    public function setVar($var, $value){
    	if (!empty($var)){
    		$this->namespace->$var = $value;
    	}
    }
    /**
     * Add values to the session from an array 
     * 
     * @param Array $vars the array containing the name value pairs for the session variables
     * @return bool FALSE the parameter passed is not an array
     */
    function addVars($vars) {
    	if (!is_array($vars)) {
    		return false; 
    	}
    	foreach ($vars as $key => $value) {
    		$this->setVar($key, $value); 
    	}
    }
    /**
     * Clear all the session variables 
     */
    public function clearSession() {
    	$this->namespace->unsetAll(); 
    }
    /**
     * Return all session variables as an array with the name of the variable as the array key
     * 
     *  @return Array 
     */
    public function getAllValues() {
    	return $this->namespace->getIterator()->getArrayCopy(); 
    }
   
}