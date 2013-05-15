<?php

class Vacation extends BaseReport {
	/**
	 * The available hours that can be used for the request 
	 * @var Float  
	 */
	public $vacationhoursavailable = null;
	/**
	 * The hours that have been used todate 
	 * @var Float  
	 */
	private $vacationhourstaken = null;
	/**
	 * The hours that have been used todate 
	 * @var Float  
	 */
	private $approvedvacationhours = null;
	/**
	 * The description of the status value 
	 * @var String 
	 */
	private $statusdescriptionvalues = null; 
	
	public function setTableDefinition() {
		#add the table definitions from the parent table
		parent::setTableDefinition();
		
		$this->setTableName('vacation');
		$this->hasColumn('employeeid','integer', 11, array('notnull' => true, 'notblank' => true));
		$this->hasColumn('projectid','integer', 11, array('default' => 7, 'notnull' => true, 'notblank' => true));
		$this->hasColumn('startdate', 'date', array('notnull' => true, 'notblank' => true));
		$this->hasColumn('enddate', 'date', array('notnull' => true, 'notblank' => true));
		$this->hasColumn('hours', 'decimal', 11, array('scale' => 2, 'notnull' => true, 'notblank' => true));
		$this->hasColumn('notes', 'string', 500);
		$this->hasColumn('status', 'integer', 11, array('default' => 1, 'notnull' => true, 'notblank' => true));

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
       		"employeeid.notblank" => $this->translate->_("vacation_employee_error"),
			"projectid.notblank" => $this->translate->_("vacation_type_of_absence_error"),
       		"startdate.notblank" => $this->translate->_("vacation_startdate_error"),
       		"enddate.notblank" => $this->translate->_("vacation_enddate_error"),
       		"status.notblank" => $this->translate->_("vacation_status_error")
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
	}
	/**
	 * send a notification email after the Vacation/Sick Time Request is submitted for approval
	 * @see BaseRecord::afterSave()
	 */
	function afterSave() {
		// send email notifications for either approvals or rejections 
		$this->sendVacationTimeRequestEmail(); 
		$this->sendVacationSubmissionEmailToApprover();
	}
	/**
	 * send a notification email after the timesheet is updated and submitted for approval
	 * @see BaseRecord::afterUpdate()
	 */
	function afterUpdate() {
		return $this->afterSave();
	}
	/**
	 * Send an email with the Vacation/Sick Time Request details on submission
	 */
	function sendVacationTimeRequestEmail() {
		$template = new EmailTemplate(); 
		// create mail object
		$mail = getMailInstance(); 
		
		$useraccount = new UserAccount();
		$useraccount->populate($this->getEmployeeID());
		
		// configure base stuff
		$default_sender = $mail->getDefaultFrom();
		$mail->addTo($useraccount->getEmail());
		//$mail->addCc($default_sender['email']);
		$mail->setSubject(sprintf($this->translate->_('vacation_email_subject_submission'), $useraccount->getName()));
		
		// assign values
		$template->assign('firstname', $useraccount->getFirstName());
		$template->assign('employeename', $useraccount->getName());
		$template->assign('startdate', changeMySQLDateToPageFormat($this->getStartDate()));
		$template->assign('enddate', changeMySQLDateToPageFormat($this->getEndDate()));
		$template->assign('requestedhours', $this->getFormattedRequestHours());
		$template->assign('notes', $this->getNotes());
		$template->assign('vacationrequesturl', array("controller" => "vacation", "action"=> "view", "id" => encode($this->getID())));
		
		// render the view as the body of the email
		$mail->setBodyHtml($template->render('vacationrequest.phtml'));
		//debugMessage($mail);
		//exit();
		$mail->send();
		
		return true;
   }
	/**
	 * Send an email with the details of the request to the employee who is to approve the request
	 */
	function sendVacationSubmissionEmailToApprover() {
		$useraccount = new UserAccount();
		$useraccount->populate($this->getEmployeeID());
		
		$template = new EmailTemplate(); 
		$mail = getMailInstance(); 
		
		// configure base stuff
		$default_sender = $mail->getDefaultFrom();
		$mail->addTo($default_sender['email']);
		
		// assign values
		$template->assign('employee', $useraccount->getName());
		$template->assign('startdate', changeMySQLDateToPageFormat($this->getStartDate()));
		$template->assign('enddate', changeMySQLDateToPageFormat($this->getEndDate()));
		$template->assign('requestedhours', $this->getFormattedRequestHours());
		$template->assign('notes', $this->getNotes());
		$template->assign('approvallink', array("controller" => "vacation", "action"=> "view", "id" => encode($this->getID()))); 
		
		$mail->setSubject(sprintf($this->translate->_('vacation_email_subject_forapproval'), $this->getEmployee()->getName()));
		// render the view as the body of the email
		$mail->setBodyHtml($template->render('vacationsubmissionforapproval.phtml'));
		//debugMessage($mail);
		//exit();
		$mail->send();
		
		return true;
	}
	/**
	 * validate that the total hours requested do not exceed the total number allowed per employee
	 */
	function validateTimeOffHours(){
		if (floatval($this->getHours()) > floatval($this->getVacationHoursAvailable())){
			$this->getErrorStack()->add('vacation.duplicate', sprintf($this->translate("vacation_hours_available_error"), floatval($this->getVacationHoursAvailable())));
		}
	}
	/**
	 * Validate that no other overlapping request for the same employee has been submitted
	 **/
	function validateDateRange() {
		// check that a request for the same period has not already been made in the same period
		// if the request is being submitted or updated
		if ($this->getStatus() == 1) {
			$current_req_filter = ""; 
			if (!isEmptyString($this->getID())) {
				$current_req_filter = " AND id <> '".$this->getID()."' ";
			}
			$sql = "SELECT COUNT(id) FROM vacation WHERE ((TO_DAYS('".$this->getStartDate()."') BETWEEN TO_DAYS(startdate) AND TO_DAYS(enddate)) OR (TO_DAYS('".$this->getEndDate()."') BETWEEN TO_DAYS(startdate) AND TO_DAYS(enddate))) AND status <> '3' AND employeeid = '".$this->getEmployeeID()."' ".$current_req_filter." GROUP BY employeeid";
			//debugMessage($sql);
			$conn = Doctrine_Manager::connection(); 
			$result = $conn->fetchOne($sql); 
			if (intval($result) > 0) {
				$this->getErrorStack()->add('vacation.duplicate', sprintf($this->translate->_('vacation_duplicate_error'), changeMySQLDateToPageFormat($this->getStartDate()), changeMySQLDateToPageFormat($this->getEndDate())));
			}
		} 
	}
	/**
	 * Custom validation 
	 * @see Doctrine_Record::validate()
	 */
	public function validate() {
		parent::validate(); 
		
		// validate the date range
		$this->validateDateRange(); 
		
		// validate that the requested hours are within the limits
		//$this->validateTimeOffHours();
	}
	/**
	 * Format the hours 
	 * 
	 * @param Float $hours
	 * 
	 * @return String The formatted hours 
	 */
	static function formatHours($hours) {
		if (isEmptyString($hours)) {
			$hours = 0; 
		}
		return $hours." hours (".number_format(($hours/8), 1)." days)";
	}
	/**
	 * Compute the hours off from the data from the screen
	 * 
	 * @param Array $formvalues The data from the POST 
	 */
	function computeRequestHours($formvalues) {
		$days = strtotime($formvalues['enddate']) - strtotime($formvalues['startdate']); 
	}
	/**
	 * 
	 * get the total number of vacation/sick time hours allowed for the employee per year
	 * 
	 *  @return floatval the number of hours allowed per year
	 */
	function getTotalVacationHoursAllowed(){
		$useraccount = new UserAccount();
		$useraccount->populate($this->getEmployeeID());
		return $useraccount->getMaximumTimeOffHoursPerYear();
	}
	/**
	 * Compute the hours available for PTO accural for the employee 
	 * 
	 * @return Float the number of PTO hours that are available excluding this request
	 */
	function getVacationHoursAvailable() {
		$this->vacationhoursavailable = floatval($this->getTotalVacationHoursAllowed()) - (floatval($this->getVacationTimeTakenToDate()) + floatval($this->getApprovedVacationHours()));
		return $this->vacationhoursavailable; 
	}
	/**
	 * 
	 * get the total vacation hours taken todate
	 * 
	 * @return float the number of total hours taken
	 */
	function getVacationTimeTakenToDate(){
		$sql = "SELECT SUM(td.hours) AS vacationhourstodate FROM timesheetdetail AS td ";
		
		// filter by the employee
		$sql .= " WHERE td.employeeid = '".$this->getEmployeeID()."' AND td.projectid IN (5, 6, 7) "; // 5 - Sick, 6 - Maternity, 7 - Vacation
		
		$sql .= " GROUP BY td.employeeid, YEAR(td.workday)"; 
		//debugMessage($sql);
		$conn = Doctrine_Manager::connection(); 
		
		$vacationhourstaken = $conn->fetchOne($sql);  
		
		return $vacationhourstaken; 
	}
	/**
	 * 
	 * get the total vacation hours taken todate
	 * 
	 * @return float the number of total hours approved
	 */
	function getApprovedVacationHours(){
		$sql = "SELECT SUM(v.hours) AS approvedvacationhours FROM vacation AS v ";
		
		// filter by the employee
		$sql .= " WHERE v.employeeid = '".$this->getEmployeeID()."' AND v.status IN (1, 2) "; // only approved and submitted requests
		
		$sql .= " GROUP BY v.employeeid"; 
		//debugMessage($sql);
		$conn = Doctrine_Manager::connection(); 
		
		$approvedvacationhours = $conn->fetchOne($sql);  
		
		return $approvedvacationhours; 
	}
	/**
	 * Format the available hours, uses precomputed hours if available, for performance reasons 
	 * 
	 * @return String
	 */
	function getFormattedHoursAvailable() {
		if (is_null($this->vacationhoursavailable)) {
			$this->vacationhoursavailable = $this->getVacationHoursAvailable(); 
		}
		return $this->formatHours($this->vacationhoursavailable);  
	}
	/**
	 * Format the requested hours
	 * 
	 * @return String
	 */
	function getFormattedRequestHours() {
		return $this->formatHours($this->getHours()); 
	}
	/**
	 * Get the description of the status value from the lookup values. For performance improvement, the value is cached for reuse later
	 * 
	 * @return String 
	 */
	function getStatusDescription() {
		//load the status description values 
		if (is_null($this->statusdescriptionvalues)) {
				// load the status description from the database
				$l = new LookupType();
				$l->setName('TIMESHEET_STATUSREPORT_STATUS'); 
				$this->statusdescriptionvalues = $l->getOptionValuesAndDescription(); 
		}
		return $this->statusdescriptionvalues[$this->getStatus()];
	}
}
?>
