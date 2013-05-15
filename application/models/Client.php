<?php

class Client extends BaseEntity {
	public function setTableDefinition() {
		#add the table definitions from the parent table
		parent::setTableDefinition();
		
		$this->setTableName('client');
		$this->hasColumn('name', 'string', 255, array('notnull' => true, 'notblank' => true, 'unique' => true));
		$this->hasColumn('city', 'string', 255, array('notnull' => true, 'notblank' => true)); 
		$this->hasColumn('state', 'string', 2, array('usstate' => true, 'notnull' => true, 'notblank' => true));
		$this->hasColumn('zipcode', 'string', 10, array('notnull' => true, 'notblank' => true));
		$this->hasColumn('phonenumber', 'string', 45);
		$this->hasColumn('email', 'string', 100, array('notnull' => true, 'notblank' => true));
		$this->hasColumn('contactperson', 'string', 100);
		$this->hasColumn('address', 'string', 255, array('notnull' => true, 'notblank' => true)); 
		$this->hasColumn('fax', 'string', 45); 

	}
	/**
	 * Contructor method for custom functionality - add the fields to be marked as dates
	 */
	public function construct() {
		parent::construct();
		// set the custom error messages
       	$this->addCustomErrorMessages(array(
			"name.notblank" => $this->translate->_("client_name_error"),
       		"name.unique" => $this->translate->_("client_name_unique_error"),
	       	"address.notblank" => $this->translate->_("client_address_error"),
	       	"city.notblank" => $this->translate->_("client_city_error"),
	       	"state.notblank" => $this->translate->_("client_state_error"),
	       	"zipcode.notblank" => $this->translate->_("client_zipcode_error")
		));
	}
	/**
	 * Relationships for the model
	 */
	public function setUp() {
		parent::setUp(); 
		$this->hasMany('Project as projects',
							array('local' => 'id',
								 	'foreign' => 'clientid'
							)
						);	
		$this->hasMany('ProjectAssignment as projectassignments',
							array('local' => 'id',
								 	'foreign' => 'clientid'
							)
						);
		$this->hasMany('StatusReport as statusreports',
							array('local' => 'id',
								 	'foreign' => 'clientid'
							)
						);
		$this->hasMany('ClientEmailAddress as emailaddresses',
							array('local' => 'id',
								 	'foreign' => 'clientid'
							)
						);
	}
	public function getStateLongName(){
		// load the type description from the dropdown lists
		$allstates = getStates(); 
		return $allstates[$this->getState()];
	}
	public function deleteCurrentEmailAddresses(){
		$conn = Doctrine_Manager::connection();
		$delete_query = "DELETE FROM clientemailaddress WHERE clientid = '".$this->getID()."'";
		//debugMessage($delete_query);
		$conn->execute($delete_query);
		return true;
	}
}
?>
