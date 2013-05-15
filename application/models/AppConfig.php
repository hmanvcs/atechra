<?php

class AppConfig extends BaseEntity {
	public function setTableDefinition() {
		#add the table definitions from the parent table
		parent::setTableDefinition();
		
		$this->setTableName('appconfig');
		$this->hasColumn('optionname', 'string', 50, array('notnull' => true, 'notblank' => true));
		$this->hasColumn('optionvalue', 'string', 50, array('notnull' => true, 'notblank' => true));
		$this->hasColumn('section', 'string', 50, array('notnull' => true, 'notblank' => true));
		$this->hasColumn('description', 'string', 255);
		$this->hasColumn('optiontype', 'string', 255);
		$this->hasColumn('active', 'enum', null, array('values' => array(0 => 'Y', 1 => 'N'), 'default' => 'Y'));
	}
	/**
	 * Contructor method for custom functionality - add the fields to be marked as dates
	 */
	public function construct() {
		parent::construct();
		// set the custom error messages
       	$this->addCustomErrorMessages(array(
       									"optionname.notblank" => $this->translate->_("appconfig_optionname_error"),								
       									"optionvalue.unique" => $this->translate->_("appconfig_optionvalue _error"),
       									"section.notblank" => $this->translate->_("appconfig_section_error")
       	       						));
	}
	/*
	 * Process object
	 */
	function processPost($formvalues){
		// check if the active is not specified and set to default value
		if (isArrayKeyAnEmptyString('active', $formvalues)) {
			$formvalues['active'] = 'Y';
		}
		# debugMessage($formvalues);
		parent::processPost($formvalues);
	}
	/*
	 * Invalidate the cached application configuration
	 */
	function afterUpdate() {
		$cache = Zend_Registry::get('cache');
		$cache->remove('config');
		return true;
	}
	
}
?>
