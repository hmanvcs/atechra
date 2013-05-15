<?php

class StatusReport extends BaseReport {
	public function setTableDefinition() {
		#add the table definitions from the parent table
		parent::setTableDefinition();
		
		$this->setTableName('statusreport');
		$this->hasColumn('employeeid','integer', 11, array( 'notnull' => true, 'notblank' => true));
		$this->hasColumn('projectid','integer', 11, array( 'notnull' => true, 'notblank' => true));
		$this->hasColumn('clientid','integer', 11, array( 'notnull' => true, 'notblank' => true));
		$this->hasColumn('weekendingdate', 'date', null, array('notnull' => true, 'notblank' => true));
		$this->hasColumn('activities', 'text', array( 'notnull' => true, 'notblank' => true));
		$this->hasColumn('accomplishments', 'text', array( 'notnull' => true, 'notblank' => true));
		$this->hasColumn('meetingsandpresentations', 'text', array( 'notnull' => true, 'notblank' => true));
		$this->hasColumn('issues', 'text', array( 'notnull' => true, 'notblank' => true));
		$this->hasColumn('itemsforcotr', 'text', array( 'notnull' => true, 'notblank' => true));
		$this->hasColumn('status', 'integer', 11, array('default' => 0, 'notnull' => true, 'notblank' => true));

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
       		"activities.notblank" => $this->translate->_("statusreport_activities_error"),
       		"accomplishments.notblank" => $this->translate->_("statusreport_accomplishments_error"),
       		"meetingsandpresentations.notblank" => $this->translate->_("statusreport_meetingsandpresentations_error"),
       		"issues.notblank" => $this->translate->_("statusreport_issues_error"),
       		"itemsforcotr.notblank" => $this->translate->_("statusreport_itemsforcotr_error")
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
	function getAllProjectsAndClientsArray(){
		$conn = Doctrine_Manager::connection();
		$allprojectsandclients = $conn->fetchAll("SELECT id, clientid FROM project");
		return $allprojectsandclients;
	}
	/**
	 * Load the status report using the employeeid and weekending date
	 */
	function populateFromEmployeeIDAndWeekendingDate() {
		$result = $this->getTable()->findOneByEmployeeidAndWeekendingdateAndProjectid($this->getEmployeeID(), $this->getProjectID(), changeDateFromPageToMySQLFormat($this->getWeekendingDate()));
		if(!$result){
			# Entity not found, do nothinig
			return; 
		}
		$this->synchronizeWithArray($result->toArray());
	}
	/**
	 * send a notification email after the status report is sibmitted for approval
	 * @see BaseRecord::afterSave()
	 */
	function afterSave() {
		if ($this->getStatus() == "1") {
			// send email notifications for either approvals or rejections 
			$this->sendStatusReportSubmissionEmail(); 
		}
	}
	/**
	 * send a notification email after the status report is updated and submitted for approval
	 * @see BaseRecord::afterUpdate()
	 */
	function afterUpdate() {
		return $this->afterSave();
	}
	/**
	 * Send an email with the status report details on submission
	 */
	function sendStatusReportSubmissionEmail() {
		$template = new EmailTemplate(); 
		// create mail object
		$mail = getMailInstance(); 
		
		$useraccount = new UserAccount();
		$useraccount->populate($this->getEmployeeID());
		
		// configure base stuff
		$default_sender = $mail->getDefaultFrom();
		$mail->addTo($useraccount->getEmail());
		$mail->addCc($default_sender['email']);
		$mail->setSubject(sprintf($this->translate->_('statusreport_submitted_email_subject'), changeMySQLDateToPageFormat($this->getWeekEndingDate())));
		
		// assign values
		$template->assign('firstname', $useraccount->getFirstName());
		$template->assign('employeename', $useraccount->getName());
		$template->assign('weekendingdate', changeMySQLDateToPageFormat($this->getWeekEndingDate()));
		$template->assign('approvalstatus', $this->getStatusDescription());
		$template->assign('statusreporturl', array("controller" => "statusreport", "action"=> "view", "id" => encode($this->getID())));
		
		// render the view as the body of the email
		$mail->setBodyHtml($template->render('statusreportsubmission.phtml'));
		
		$mail->send();
		return true;
   }
	/** 
	 * Remove any slashes which may be inserted by PHP 
	 */
	public function processPost($post_array) {
		// remove the extra slashes from specific fields
		$post_array['activities'] = stripslashes($post_array['activities']); 
		$post_array['accomplishments'] = stripslashes($post_array['accomplishments']);
		$post_array['meetingsandpresentations'] = stripslashes($post_array['meetingsandpresentations']);
		$post_array['issues'] = stripslashes($post_array['issues']);
		$post_array['itemsforcotr'] = stripslashes($post_array['itemsforcotr']);
		
		parent::processPost($post_array); 
	}
	/**
	 * 
	 * Function that returns the HTML that is used to generate PDF
	 */
	function getStatusReportHTMLForPDF(){
		$statusreport_html = "";
		$statusreport_html = '<table class="formtable">
		  <thead>
		    <tr>
		     <td class="label">'.$this->translate->_("statusreport_project_label").':</td>
		     <td>'.$this->getProject()->getTitle().'</td>
		  </tr>
		  </thead>
		  <tr>
		    <td class="label">'.$this->translate->_("statusreport_weekendingdate_label").':</td>
		    <td>'.changeMySQLDateToPageFormat($this->getWeekendingDate()).'</td>
		  </tr>
		  <tr>
		    <td class="label">'.$this->translate->_("statusreport_status_label").':</td>
		    <td>'.$this->getStatusDescription().'</td>
		  </tr>
		  <tr>
		    <td class="label">'.$this->translate->_("statusreport_activities_label").':</td>
		    <td>'.nl2br($this->getActivities()).'</td>
		  </tr>
		  <tr>
		    <td class="label">'.$this->translate->_("statusreport_accomplishments_label").':</td>
		    <td>'.nl2br($this->getAccomplishments()).'</td>
		  </tr>
		  <tr>
		    <td class="label">'.$this->translate->_("statusreport_meetingsandpresentations_label").':</td>
		    <td>'.nl2br($this->getMeetingsAndPresentations()).'</td>
		  </tr>
		  <tr>
		    <td class="label">'.$this->translate->_("statusreport_issues_label").':</td>
		    <td>'.nl2br($this->getIssues()).'</td>
		  </tr>
		  <tr>
		    <td class="label">'.$this->translate->_("statusreport_itemsforcotr_label").':</td>
		    <td>'.nl2br($this->getItemsForCotr()).'</td>
		  </tr>
		</table>';
		$statusreport_html .= "<br /><br /><br /><span style=\"font-size:8px;\">Report generated from www.staff-atechra.com on ".date($this->config->dateandtime->reportgenerationtimestamp).". For more information contact support@staff-atechra.com</span>";
		
		// Add the CSS to style the report for PDF generation 
		$style_sheet = '<html><head><meta http-equiv="Content-Type" content="text/html; charset=iso-8859-15"><style type="text/css">body, select, textarea, button, input, td {
					font-family: Arial, Helvetica, Tahoma, sans-serif;	
					font-size:14px;
					color:#555555;
					line-height: 1.2em;
					}</style></head>'; 
		return $style_sheet."<body>".$statusreport_html."</body></html>"; 
	}
}
?>
