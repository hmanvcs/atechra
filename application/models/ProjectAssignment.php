<?php

class ProjectAssignment extends BaseEntity {
	public function setTableDefinition() {
		#add the table definitions from the parent table
		parent::setTableDefinition();
		
		$this->setTableName('projectassignment');
		$this->hasColumn('employeeid','integer', 11);
		$this->hasColumn('projectid','integer', 11);
		$this->hasColumn('clientid','integer', 11);
		$this->hasColumn('startdate', 'date');
		$this->hasColumn('enddate', 'date');
		$this->hasColumn('notes', 'string', 500);

	}
	/**
	 * Contructor method for custom functionality - add the fields to be marked as dates
	 */
	public function construct() {
		parent::construct();
		// specify the date columns
		$this->addDateFields(array("startdate", "enddate"));
		
		// set the custom error messages
       	$this->addCustomErrorMessages(array(
       		"employeeid.notblank" => $this->translate->_("projectassignment_employee_error"),
			"projectid.notblank" => $this->translate->_("projectassignment_project_error"),
       		"clientid.notblank" => $this->translate->_("projectassignment_client_error"),
       		"startdate.notblank" => $this->translate->_("projectassignment_startdate_error"),
       		"enddate.notblank" => $this->translate->_("projectassignment_enddate_error")
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
	}
	function validate(){
		// execute the column validation 
		parent::validate();
		
		//query active user details using the employeeid, clientid and projectid
		$q = Doctrine_Query::create()->from('ProjectAssignment pa')->where('pa.employeeid = ?', $this->getEmployeeID())->andWhere('pa.clientid = ?', $this->getClientID())->andWhere('pa.projectid = ?', $this->getProjectID());
		$result = $q->fetchOne(); 
		
		// check that the project has not been assigned to the employee before
		if($result){
			// add the error message for a project that has already been assigned
			$this->addCustomErrorMessages(array("employeeid.projectid.unique" => $this->translate->_("projectassignment_employee_project_unique_error")));
			//$this->addCustomErrorMessages(array("employeeid.projectid.unique" => sprintf($this->translate->_("projectassignment_employee_project_unique_error"), $this->Project->getTitle())));
			return false;
		}
	}
	function getAllProjectsAndClientsArray(){
		$conn = Doctrine_Manager::connection();
		$allprojectsandclients = $conn->fetchAll("SELECT id, clientid FROM project");
		return $allprojectsandclients;
	}
	function getBlankTimesheetRecord(){
		return array("projectid" => $this->getProjectID(), "clientid" => $this->getClientID(), "employeeid" => $this->getEmployeeID(), "hours" => "0.00");
	}
}
?>
