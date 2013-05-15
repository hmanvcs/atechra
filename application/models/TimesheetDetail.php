<?php

class TimesheetDetail extends BaseRecord {
	public function setTableDefinition() {
		#add the table definitions from the parent table
		parent::setTableDefinition();
		
		$this->setTableName('timesheetdetail');
		$this->hasColumn('timesheetid','integer', 11, array('notnull' => true, 'notblank' => true));
		$this->hasColumn('employeeid','integer', 11, array('notnull' => true, 'notblank' => true));
		$this->hasColumn('projectid','integer', 11, array('notnull' => true, 'notblank' => true));
		$this->hasColumn('clientid','integer', 11, array('notnull' => true, 'notblank' => true));
		$this->hasColumn('workday', 'date', null, array('notnull' => true, 'notblank' => true));
		$this->hasColumn('hours', 'decimal', 10, array('scale' => 2));
		$this->hasColumn('comments', 'string', 255);

	}
	/**
	 * Contructor method for custom functionality - add the fields to be marked as dates
	 */
	public function construct() {
		parent::construct();
		// specify the date columns
		$this->addDateFields(array("weekendingdate"));
		
		// set the custom error messages
       	$this->addCustomErrorMessages(array(
       		"timesheetid.notblank" => $this->translate->_("timesheetid_employee_error"),
       		"employeeid.notblank" => $this->translate->_("timesheetdetail_employee_error"),
			"projectid.notblank" => $this->translate->_("timesheetdetail_project_error"),
       		"clientid.notblank" => $this->translate->_("timesheetdetail_client_error"),
       		"hours.notblank" => $this->translate->_("timesheetdetail_hours_error")
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
		$this->hasOne('Project as project',
							array('local' => 'projectid',
								 	'foreign' => 'id'
							)
						);
		$this->hasOne('Client as client',
							array('local' => 'clientid',
								 	'foreign' => 'id'
							)
						);
		$this->hasOne('Timesheet as timesheet',
							array('local' => 'timesheetid',
								 	'foreign' => 'id'
							)
						);
	}
}
?>
