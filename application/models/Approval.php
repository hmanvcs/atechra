<?php

/**
 * Model for a Timesheet or Status Report approval 
 *
 */
class Approval extends BaseEntity  {
	
	public function setTableDefinition() {
		#add the table definitions from the parent table
		parent::setTableDefinition();
		$this->setTableName('approval');
		$this->hasColumn('timesheetid','integer');		
		$this->hasColumn('statusreportid','integer');
		$this->hasColumn('vacationid','integer');
		$this->hasColumn('status','integer', 11, array('notnull' => true, 'notblank' => true));
		$this->hasColumn('notes', 'string', 255, array('default' => ''));
	}
	/**
	 * Contructor method for custom functionality
	 */
	public function construct() {
		parent::construct();
		// set the custom error messages
       	$this->addCustomErrorMessages(array(
       									"status.notblank" => $this->translate->_("approval_status_error")       									
       	       						));
	}
	/*
	 * Relationships for the model
	 */
	public function setUp() {
		parent::setUp(); 
		$this->hasOne('Timesheet as timesheet',
							array('local' => 'timesheetid',
									'foreign' => 'id'
							)
						);
		$this->hasOne('StatusReport as statusreport',
							array('local' => 'statusreportid',
									'foreign' => 'id'
							)
						);
		$this->hasOne('Vacation as vacation',
							array('local' => 'vacationid',
									'foreign' => 'id'
							)
						);
	}
	/**
	 * Whether or not this is an approval
	 * 
	 * @return bool
	 */
	function isApproval() {
		return ($this->getStatus() == 1);  
	}
	/**
	 * Whether or not this is a rejection
	 * 
	 * @return bool
	 */
	function isRejection() {
		return ($this->getStatus() == 0);  
	}
	/**
	 * Determine the number of days between two dates 
	 * @param date $beginDate
	 * @param date $endDate
	 */
	function dateDiff($beginDate, $endDate) {
		$start_ts = strtotime($beginDate);
		$end_ts = strtotime($endDate);
		$diff = $end_ts - $start_ts;
		return round($diff / 86400);
	}
	function afterSave() {
		// send email notifications to the employee for either approvals or rejections 
		$this->sendEmailNotificationtoEmployee();
		
		// send email notifications to the client when a status report is approved
		if ($this->isApproval() and isNotAnEmptyString($this->getStatusReportID())){
			$this->sendStatusReportEmailNotificationtoClient();
		}
		
		// create a timesheet and mark off the hours  
		if (isNotAnEmptyString($this->getVacationID()) and $this->isApproval()){
			$this->createTimesheetRecordFromVacationApproval();
		}
	}
	/**
	 * Send the employee an email notification on the approval or rejection of their request
	 * 
	 */
	function sendEmailNotificationtoEmployee() {
		$template = new EmailTemplate(); 
		// create mail object
		$mail = getMailInstance(); 
	
		$useraccount = new UserAccount();
		
		// configure base stuff
		$default_sender = $mail->getDefaultFrom();
		//$mail->addCc($default_sender['email']);
		
		if (isNotAnEmptyString($this->getTimesheetID())){
			$timesheet = new Timesheet();
			$timesheet->populate($this->getTimesheetID());
			
			$useraccount->populate($timesheet->getEmployeeID());
			
			$template->assign('type', "timesheet");
			$template->assign('weekendingdate', changeMySQLDateToPageFormat($timesheet->getWeekEndingDate()));
			$template->assign('approvalstatus', $timesheet->getStatusDescription());
			$template->assign('urltoview', array("controller" => "timesheet", "action"=> "view", "id" => encode($this->getTimesheetID())));
			
			// check the status so that we can switch the email subject
			if ($this->isApproval()) {
				$mail->setSubject(sprintf($this->translate->_('timesheet_timesheet_approve_email_subject'), changeMySQLDateToPageFormat($timesheet->getWeekEndingDate())));
			} else {
				$mail->setSubject(sprintf($this->translate->_('timesheet_timesheet_reject_email_subject'), changeMySQLDateToPageFormat($timesheet->getWeekEndingDate())));
				$template->assign('reason', $this->getNotes());
			}
			
			// assign values
			$mail->addTo($useraccount->getEmail());
			$template->assign('firstname', $useraccount->getFirstName());
			$template->assign('employeename', $useraccount->getName());
		
			// render the view as the body of the email
			$mail->setBodyHtml($template->render('timesheetrejectionandapproval.phtml'));
		} else if (isNotAnEmptyString($this->getStatusReportID())){
			$statusreport = new StatusReport();
			$statusreport->populate($this->getStatusReportID());
			$useraccount->populate($statusreport->getEmployeeID());
			
			$template->assign('type', "status report");
			$template->assign('weekendingdate', changeMySQLDateToPageFormat($statusreport->getWeekEndingDate()));
			$template->assign('approvalstatus', $statusreport->getStatusDescription());
			$template->assign('urltoview', array("controller" => "statusreport", "action"=> "view", "id" => encode($this->getStatusReportID())));
			
			// check the status so that we can switch the email subject
			if ($this->isApproval()) {
				$mail->setSubject(sprintf($this->translate->_('statusreport_approve_email_subject'), changeMySQLDateToPageFormat($statusreport->getWeekEndingDate())));
			} else {
				$mail->setSubject(sprintf($this->translate->_('statusreport_reject_email_subject'), changeMySQLDateToPageFormat($statusreport->getWeekEndingDate())));
				$template->assign('reason', $this->getNotes());
			}
			
			// assign values
			$mail->addTo($useraccount->getEmail());
			$template->assign('firstname', $useraccount->getFirstName());
			$template->assign('employeename', $useraccount->getName());
			
			// render the view as the body of the email
			$mail->setBodyHtml($template->render('timesheetrejectionandapproval.phtml'));
		} else if (isNotAnEmptyString($this->getVacationID())){
			$vacation = new Vacation();
			$vacation->populate($this->getVacationID());
			$useraccount->populate($vacation->getEmployeeID());
			
			$template->assign('type', "vacation/sick time");
			$template->assign('startdate', changeMySQLDateToPageFormat($vacation->getStartDate()));
			$template->assign('enddate', changeMySQLDateToPageFormat($vacation->getEndDate()));
			$template->assign('approvalstatus', $vacation->getStatusDescription());
			$template->assign('urltoview', array("controller" => "vacation", "action"=> "view", "id" => encode($this->getVacationID())));
			
			// check the status so that we can switch the email subject
			if ($this->isApproval()) {
				$mail->setSubject(sprintf($this->translate->_('vacation_email_subject_approved'), $vacation->getEmployee()->getName()));
			} else {
				$mail->setSubject(sprintf($this->translate->_('vacation_email_subject_rejected'), $vacation->getEmployee()->getName()));
				$template->assign('reason', $this->getNotes());
			}
			
			// assign values
			$mail->addTo($useraccount->getEmail());
			$template->assign('firstname', $useraccount->getFirstName());
			$template->assign('employeename', $useraccount->getName());
			
			// render the view as the body of the email
			$mail->setBodyHtml($template->render('vacationtimerejectionandapproval.phtml'));			
		}
		
		$mail->send();
		return true;
	}
	/**
	 * 
	 * Send the client an email notification when the status report is approved
	 * 
	 */
	function sendStatusReportEmailNotificationtoClient() {
		$template = new EmailTemplate(); 
		// create mail object
		$mail = getMailInstance(); 
	
		$useraccount = new UserAccount();
		
		// configure base stuff
		$default_sender = $mail->getDefaultFrom();
		$mail->addCc($default_sender['email']);
		
		// get the status report details
		$statusreport = new StatusReport();
		$statusreport->populate($this->getStatusReportID());
		$useraccount->populate($statusreport->getEmployeeID());
		
		// get the client details
		$client = new Client();
		$client->populate($statusreport->getClientID());
		
		// assign values
		$mail->addTo($client->getEmail());
		$additional_client_emails = $client->getEmailAddresses();
		$additional_emailaddress_count = $client->getEmailAddresses()->count();
		if ($additional_emailaddress_count != 0){
			foreach ($additional_client_emails as $email){
				$mail->addCc($email->getEmailAddress());
			}
		}
		$template->assign('clientname', $client->getName());
		$template->assign('employeename', $useraccount->getName());
		$template->assign('projectname', $statusreport->getProject()->getTitle());
		$template->assign('type', "status report");
		$template->assign('weekendingdate', changeMySQLDateToPageFormat($statusreport->getWeekEndingDate()));
		$template->assign('approvalstatus', $statusreport->getStatusDescription());
		$template->assign('urltoview', array("controller" => "statusreport", "action"=> "view", "id" => encode($this->getStatusReportID())));
		
		$baseUrl = Zend_Controller_Front::getInstance()->getBaseUrl();
		//debugMessage("Base URL: ".$baseUrl);
		
		// generate the temp file name to store the HTML contents
		$timestamp = time().rand(100, 5000);
		$temp_file_name = md5($timestamp).".pdf";
		//debugMessage($temp_file_name);
	  	
		require_once(APPLICATION_PATH.'/../'.PUBLICFOLDER.'/dompdf/dompdf_config.inc.php');
		/*$files = glob(APPLICATION_PATH."/../public/dompdf/include/*.php");
		foreach($files as $file) {
			//include_once($file);
		}*/
		$html = $statusreport->getStatusReportHTMLForPDF();
		
		$dompdf = new DOMPDF();
		$dompdf->load_html($html);
	    $dompdf->render();
	    $output = $dompdf->output();
	    file_put_contents(APPLICATION_PATH.'/../'.PUBLICFOLDER.'/dompdf/temp/'.$temp_file_name, $output);
	    //debugMessage(APPLICATION_PATH.'/../public/dompdf/temp/'.$temp_file_name);
		
		$file = (APPLICATION_PATH.'/../'.PUBLICFOLDER.'/dompdf/temp/'.$temp_file_name);
		//debugMessage("File: ".$file);

		/*$at = new Zend_Mime_Part($file);
		//$at->filename = str_replace(" ", "_", $statusreport->getProject()->getTitle())."_".changeMySQLDateToPageFormat($statusreport->getWeekendingDate()).".pdf";
		$at->filename = $temp_file_name;
		$at->disposition = Zend_Mime::DISPOSITION_ATTACHMENT;
		$at->encoding = Zend_Mime::ENCODING_BASE64;        
		$mail->addAttachment($at);*/
		
		$at = $mail->createAttachment(file_get_contents($file));
		$at->type        = 'application/pdf';
		$at->disposition = Zend_Mime::DISPOSITION_ATTACHMENT;
		$at->encoding    = Zend_Mime::ENCODING_BASE64;
		$at->filename    = str_replace(" ", "_", $statusreport->getProject()->getTitle())."_".changeMySQLDateToPageFormat($statusreport->getWeekendingDate()).".pdf";
		
		// check the status so that we can switch the email subject
		$mail->setSubject(sprintf($this->translate->_('statusreport_approve_client_email_subject'), $statusreport->getProject()->getTitle(), changeMySQLDateToPageFormat($statusreport->getWeekEndingDate())));
		
		// render the view as the body of the email
		$mail->setBodyHtml($template->render('clientstatusreportapprovalnotification.phtml'));
		//debugMessage($mail);
		//exit();
		$mail->send();
		return true;
	}
	/**
	 * 
	 * Create a timesheet record when a vacation request is approved
	 * 
	 */
	function createTimesheetRecordFromVacationApproval(){
		$vacation = new Vacation();
		$vacation->populate($this->getVacationID());
		$useraccount = new UserAccount();
		$useraccount->populate($vacation->getEmployeeID());
		$projectids = $useraccount->getEmployeeProjectIDs();
		//debugMessage($useraccount->toArray());
		//debugMessage($this->toArray());
		
		$timesheet = new Timesheet();
		$timesheet_details_array = array();
		$timesheetdetails = array();
		$errors = array();

		// build the timesheet details array
		$timesheet_details_array['employeeid'] = $this->getVacation()->getEmployeeID();
		$timesheet_details_array['status'] = 0; 
		$timesheet_details_array['datecreated'] = date("Y-m-d");
		$timesheet_details_array['createdby'] = $this->getVacation()->getEmployeeID();
		//debugMessage($this->getVacation()->getStartDate());
		//debugMessage($this->getVacation()->getEndDate());
		// the number of days between the startdate and today
		$diff = $this->dateDiff(changeMySQLDateToPageFormat($this->getVacation()->getStartDate()), changeMySQLDateToPageFormat($this->getVacation()->getEndDate()));
		//debugMessage("No of days: ".$diff);
		$weekendingdate = getdate(strtotime($this->getVacation()->getStartDate()));
		//debugMessage($weekendingdate);
		if ($weekendingdate['weekday'] == "Friday"){
			//debugMessage("It's Friday!!");
			$timesheet_details_array['weekendingdate'] = $this->getVacation()->getStartDate();
		} else {
			$temp_date = new DateTime($this->getVacation()->getStartDate());
			$temp_date->modify('next friday');
			//debugMessage("The next Friday is ".$temp_date->format('Y-m-d'));
			$timesheet_details_array['weekendingdate'] = $temp_date->format('Y-m-d');
		}
		
		if(intval($diff) == 0){ // meaning 1 day of vacation
			//debugMessage("Just one day");					
		} else {
			// generate an array of the dates off
			$firstdate = new DateTime($this->getVacation()->getStartDate());
			$weekstartdate = $firstdate;
			$days = array();
			// add the date for Monday
			$days[] = $weekstartdate->format("Y-m-d");
			
			for ($i = 0; $i < $diff; $i++) {
				$weekstartdate->modify("+1 day");
				$days[] = $weekstartdate->format("Y-m-d");
			}
			//debugMessage($days);
			
			//debugMessage("More than one day");
			$timesheet->setWeekendingDate($timesheet_details_array['weekendingdate']);
			$days_of_the_week = $timesheet->getDatesForDaysOfTheWeek();
			//debugMessage($days_of_the_week);
			
			$counter = 1;
			$projects_counter = 1;
			foreach ($days_of_the_week as $key=>$workday){
				foreach ($projectids as $projectid){
					$timesheetdetails[md5($counter)] = array(
													"projectid" => $projectid, 
													"clientid" => $this->getVacation()->getProject()->getClientID(), 
													"employeeid" => $this->getVacation()->getEmployeeID(), 
													"workday" => $workday,
												);	
												
												// set 8 hours for the vacation days selected, otherwise default to zero
												if (($this->getVacation()->getProjectID() == $projectid) and (array_search($workday, $days))){
													debugMessage("Project ID: ".$projectid);
													debugMessage("Work Day: ".$workday);
													$timesheetdetails[md5($counter)]["hours"] = "8.00";
												} else {
													$timesheetdetails[md5($counter)]["hours"] = "0.00";
												}
												//debugMessage("counter: ".$counter);
												$counter++;
				}
				
			}
		}
		//debugMessage($timesheetdetails);
		$timesheet_details_array['timesheetdetails'] = $timesheetdetails;
		//debugMessage($timesheet_details_array);
		$timesheet->processPost($timesheet_details_array);
		//exit();
		// now save the details to the DB
		try {
			debugMessage($timesheet->toArray());
			$timesheet->save();
			//debugMessage("Created timesheet successfully");
		} catch (Exception $e) {
			debugMessage("There was an error creating the timesheet.<br /><br />".$e->getMessage());
			$session = SessionWrapper::getInstance(); 
			//$session->setVar(ERROR_MESSAGE, $this->_translate->translate('timesheet_submission_failure').$e->getMessage());
		}
		//exit();
	}
}

?>