<?php

class ClientEmailAddress extends BaseRecord {
	public function setTableDefinition() {
		#add the table definitions from the parent table
		parent::setTableDefinition();
		
		$this->setTableName('clientemailaddress');
		$this->hasColumn('id', 'integer', 11, array('primary' => true, 'autoincrement' => true));
		$this->hasColumn('clientid','integer', 11, array('notnull' => true, 'notblank' => true));
		$this->hasColumn('emailaddress', 'string', 100, array('notnull' => true, 'notblank' => true));

	}
	/**
	 * Contructor method for custom functionality - add the fields to be marked as dates
	 */
	public function construct() {
		parent::construct();
		// set the custom error messages
       	$this->addCustomErrorMessages(array(
			"clientid.notblank" => $this->translate->_("client_clientid_error"),
	       	"emailaddress.notblank" => $this->translate->_("client_email_error")
		));
	}
	/**
	 * Relationships for the model
	 */
	public function setUp() {
		parent::setUp(); 
		$this->hasOne('Client as client',
							array('local' => 'clientid',
								 	'foreign' => 'id'
							)
						);	
	}
	
}
?>
