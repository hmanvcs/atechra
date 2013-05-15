<?php

class Timesheet extends BaseReport {
	
	/**
	 * Array of timesheet hours for each project and each day  
	 * @var Array 
	 */
	private $timesheetdetails = array(); 
	
	public function setTableDefinition() {
		#add the table definitions from the parent table
		parent::setTableDefinition();
		
		$this->setTableName('timesheet');
		$this->hasColumn('employeeid','integer', 11, array('notnull' => true, 'notblank' => true));
		$this->hasColumn('weekendingdate', 'date', null, array('notnull' => true, 'notblank' => true, 'default' => date("Y-m-d", strtotime("this friday"))));
		$this->hasColumn('status', 'integer', 11, array('default' => 0, 'notnull' => true, 'notblank' => true));
		$this->hasColumn('datesubmitted', 'date');
		 
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
       		"employeeid.notblank" => $this->translate->_("statusreport_employee_error"),
			"projectid.notblank" => $this->translate->_("statusreport_project_error"),
       		"clientid.notblank" => $this->translate->_("statusreport_client_error"),
       		"weekendingdate.notblank" => $this->translate->_("statusreport_weekendingdate_error"),
       		"status.notblank" => $this->translate->_("statusreport_status_error")
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
		$this->hasMany('TimesheetDetail as timesheetdetails',
							array('local' => 'id',
								 	'foreign' => 'timesheetid'
							)
						);
	}
	/**
	 * Get the date for the start of the timesheet week, which starts on a Monday and ends on a Friday
	 *
	 * @return Integer The time stamp for the week ending date
	 */
	function getMondayTimestamp() {
		// the week ending date is always a Friday 
		return strtotime("-4 day ", $this->getWeekEndingDateTimestamp()); 
	}
	/**
	 * The timestamp for the weekending date which is a Friday
	 * 
	 * @return Integer 
	 */
	function getWeekEndingDateTimestamp() {
		return strtotime(changeMySQLDateToPageFormat($this->getWeekEndingDate()));
	}
	/**
	 * Get the timestamp for Sunday, since the week ends on Friday
	 *
	 * @return Integer 
	 */
	function getSundayTimestamp() {
		# get the Monday date and add 6 days
		return strtotime("+2 day ", $this->getWeekEndingDateTimestamp());
	}
	/**
	 * 
	 * Get all projects currently assigned to an employee
	 * 
	 */
	function getEmployeeProjectAssignments(){
		$q = Doctrine_Query::create()->from("ProjectAssignment")->where("employeeid = ?", $this->getEMployeeID());
		//debugMessage($q->getSQLQuery());
		return $q->execute();
	}
	/**
	 * Build a list of classes for styling the input field 
	 *
	 * @param String $thedate The date for the input field 
	 */
	function getInputClassNames($thedate) {
		$day_date_ts = strtotime($thedate);
		$inputclasslist = ' numberfield '.strtolower(date("l", $day_date_ts));
		# disable if the day is after today to prevent entry of time in the future
		if (time() < $day_date_ts) {
			$inputclasslist .= " disabledfield ";
		}
		
		return $inputclasslist;
	}
	/**
	*
	* Generate an array of the dates in the timesheet week
	* 
	* @return Array
	*
	*/
	function getDatesForDaysOfTheWeek(){
		$fridaydate = new DateTime($this->getWeekendingDate());
		$fridaydate->modify("-4 day");
		$weekstartdate = $fridaydate; 
		
		$days = array();
		// add the date for Monday
		$days[] = $weekstartdate->format("Y-m-d");
		
		for ($i = 0; $i < 6; $i++) {
			$weekstartdate->modify("+1 day");
			$days[] = $weekstartdate->format("Y-m-d");
		}
		return $days;
	}
	/**
	 * Generate an array containing the details of the hours submitted on each project on each day in the week
	 * 
	 * @return Array 
	 */
	
	function getTimesheetDetailsDataArray(){
		if (count($this->timesheetdetails) > 0) {
			return $this->timesheetdetails; 
		}
		$conn = Doctrine_Manager::connection();
		$query = "SELECT p.employeeid, p.projectid, p.clientid, td.workday, td.hours, td.comments, td.timesheetid, CONCAT(p.projectid, '.', td.workday) AS thekey FROM projectassignment AS p LEFT JOIN timesheetdetail AS td ON p.employeeid = td.employeeid AND p.projectid = td.projectid AND TO_DAYS(`td`.`workday`) BETWEEN TO_DAYS('".date("Y-m-d", $this->getMondayTimestamp())."') AND TO_DAYS('".date("Y-m-d", $this->getSundayTimestamp())."') WHERE (p.employeeid = '".$this->getEmployeeID()."')";
		//debugMessage($query);
		$timesheetdetailsquery_result = $conn->fetchAll($query);
		
		$data = array();
		// array containing the hours by day and by project
		$hours_by_day_and_project = array();
		foreach ($timesheetdetailsquery_result as $line){
			// add the time for each day and project 
			$data[$line['thekey']] = $line;
			
			// add the hours by day and project by project to enable totals later  
			$hours_by_day_and_project[$line['workday']][] = $line['hours'];
			$hours_by_day_and_project[$line['projectid']][] = $line['hours']; 
		}
		$data['categorizedhours'] = $hours_by_day_and_project;
		$this->timesheetdetails = $data; 
		return $this->timesheetdetails;
	}
	/**
	 * Compute the total hours worked on a single day 
	 * 
	 * @param Integer $theday The date for which the hours are computed 
	 * 
	 * @return float
	 */
	function getWeekdayTotalHours($theday){
		$timesheetdetails_array = $this->getTimesheetDetailsDataArray();
		return formatNumber(array_sum($timesheetdetails_array['categorizedhours'][$theday]));
	}
	/**
	 * Compute the total hours for a project on the timesheet 
	 * 
	 * @param Integer $projectid
	 * 
	 * @return float
	 */
	function getProjectTotalHours($projectid) {
		$timesheetdetails_array = $this->getTimesheetDetailsDataArray();
		return formatNumber(array_sum($timesheetdetails_array['categorizedhours'][$projectid]));
	}
	/**
	 * Load the timesheet using the employeeid and weekending date
	 */
	function populateFromEmployeeIDAndWeekendingDate() {
		$result = $this->getTable()->findOneByEmployeeidAndWeekendingdate($this->getEmployeeID(), changeDateFromPageToMySQLFormat($this->getWeekendingDate()));
		if(!$result){
			# Entity not found, do nothing
			return; 
		}
		$this->synchronizeWithArray($result->toArray());
	}
	/**
	 * send a notification email after the timesheet is submitted for approval
	 * @see BaseRecord::afterSave()
	 */
	function afterSave() {
		if ($this->getStatus() == "1")
		// send email notifications for either approvals or rejections 
		$this->sendTimesheetSubmissionEmail(); 
	}
	/**
	 * send a notification email after the timesheet is updated and submitted for approval
	 * @see BaseRecord::afterUpdate()
	 */
	function afterUpdate() {
		return $this->afterSave();
	}
	/**
	 * Send an email with the timesheet details on submission
	 */
	function sendTimesheetSubmissionEmail() {
		$template = new EmailTemplate(); 
		// create mail object
		$mail = getMailInstance(); 
		
		$useraccount = new UserAccount();
		$useraccount->populate($this->getEmployeeID());
		
		// configure base stuff
		$default_sender = $mail->getDefaultFrom();
		$mail->addTo($useraccount->getEmail());
		$mail->addCc($default_sender['email']);
		$mail->setSubject(sprintf($this->translate->_('timesheet_timesheet_submitted_email_subject'), changeMySQLDateToPageFormat($this->getWeekEndingDate())));
		
		// assign values
		$template->assign('firstname', $useraccount->getFirstName());
		$template->assign('employeename', $useraccount->getName());
		$template->assign('weekendingdate', changeMySQLDateToPageFormat($this->getWeekEndingDate()));
		$template->assign('approvalstatus', $this->timesheet_timesheet_approve_email_subject());
		$template->assign('timesheeturl', array("controller" => "timesheet", "action"=> "view", "id" => encode($this->getID())));
		
		// render the view as the body of the email
		$mail->setBodyHtml($template->render('timesheetsubmission.phtml'));
		
		$mail->send();
		return true;
   }
}
?>