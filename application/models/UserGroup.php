<?php

/**
 * The relationship between a User and a Group
 */
class UserGroup extends BaseRecord  {
	public function setTableDefinition() {
		parent::setTableDefinition();
		$this->setTableName('aclusergroup');
		$this->hasColumn('id', 'integer', 11, array('primary' => true, 'autoincrement' => true));
		$this->hasColumn('userid', 'integer', 11);
		$this->hasColumn('groupid', 'integer', 11, array("notblank" => true));
	}
	
	public function setUp() {
		parent::setUp(); 
		$this->hasOne('UserAccount as user',
							array('local' => 'userid',
									'foreign' => 'id'
							)
						);	
		$this->hasOne('AclGroup as group',
							array('local' => 'groupid',
									'foreign' => 'id'
							)
						);	
	}
	
   /**
	 * Contructor method for custom functionality - add the fields to be marked as dates
	 */
	public function construct() {
		parent::construct();
		
		// set the custom error messages
       	$this->addCustomErrorMessages(array(
       									"groupid.notblank" => $this->translate->_("usergroup_groupid_error"),
       	       						));
	}
}