<?php

class TaskAssignee extends BaseRecord {
	public function setTableDefinition() {
		#add the table definitions from the parent table
		parent::setTableDefinition();
		
		$this->setTableName('taskassignee');
		$this->hasColumn('id', 'integer', 11, array('primary' => true, 'autoincrement' => true));
		$this->hasColumn('taskid','integer', 11, array('notnull' => true, 'notblank' => true));
		$this->hasColumn('employeeid', 'integer', 11, array('notnull' => true, 'notblank' => true));

	}
	/**
	 * Contructor method for custom functionality - add the fields to be marked as dates
	 */
	public function construct() {
		parent::construct();
		
		// set the custom error messages
       	$this->addCustomErrorMessages(array(
			"taskid.notblank" => $this->translate->_("task_name_error"),
       		"employeeid.notblank" => $this->translate->_("task_assignee_error")
       	));
	}
	/**
	 * Relationships for the model
	 */
	public function setUp() {
		parent::setUp(); 
		$this->hasOne('UserAccount as employee',
							array('local' => 'employeeid',
								 	'foreign' => 'id'
							)
						);	
		$this->hasOne('Task as task',
							array('local' => 'taskid',
								 	'foreign' => 'id'
							)
						);
	}
}
?>
