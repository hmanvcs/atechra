<?php

class Task extends BaseEntity {
	public function setTableDefinition() {
		#add the table definitions from the parent table
		parent::setTableDefinition();
		
		$this->setTableName('task');
		$this->hasColumn('projectid','integer', 11, array('notnull' => true, 'notblank' => true));
		$this->hasColumn('name', 'varchar', 255, array('notnull' => true, 'notblank' => true));
		$this->hasColumn('duedate', 'date');
		$this->hasColumn('dateclosed', 'date');
		$this->hasColumn('notes', 'string', 500);
		$this->hasColumn('status', 'integer', 11, array('default' => 0, 'notnull' => true, 'notblank' => true));

	}
	/**
	 * Contructor method for custom functionality - add the fields to be marked as dates
	 */
	public function construct() {
		parent::construct();
		// specify the date columns
		$this->addDateFields(array("duedate", "dateclosed"));
		
		// set the custom error messages
       	$this->addCustomErrorMessages(array(
			"projectid.notblank" => $this->translate->_("task_project_error"),
         	"name.notblank" => $this->translate->_("task_name_error"),
       		"duedate.notblank" => $this->translate->_("task_duedate_error")
       	));
	}
	/**
	 * Relationships for the model
	 */
	public function setUp() {
		parent::setUp(); 

		$this->hasOne('Project as project',
						array('local' => 'projectid',
								 	'foreign' => 'id'
							)
						);
		$this->hasMany('TaskAssignee as assignees',
						array('local' => 'id',
									 	'foreign' => 'taskid'
							)
						);
		$this->hasMany('TaskComment as taskcomments',
						array('local' => 'id',
									 	'foreign' => 'taskid'
							)
						);
	}
	/**
	 * send a notification email after the task is assigned
	 * @see BaseRecord::afterSave()
	 */
	function afterSave() {
		// send email notifications for either approvals or rejections 
		$this->sendTaskAssignmentNotificationEmail(); 
	}
	/**
	 * send a notification email after the task 
	 * @see BaseRecord::afterUpdate()
	 */
	function afterUpdate() {
		// send the task update email
		$this->sendTaskUpdateNotificationEmail();
	}
	function processPost($formvalues){
		//debugMessage($formvalues);
		// move the data from $formvalues['usergroups_groupid'] into $formvalues['usergroups'] array
		// the key for each group has to be the groupid
		if (array_key_exists('task_assigneeid', $formvalues)) {
			$assigneeids = $formvalues['task_assigneeid']; 
			$assignees = array(); 
			foreach ($assigneeids as $id) {
				$assignee = array(); 
				$assignee['employeeid'] = $id; 
				if (!isArrayKeyAnEmptyString('id', $formvalues)){
					$assignee['taskid'] = $formvalues['id']; 
				}
				
				$assignees[md5($id)] = $assignee; 
			}
			$formvalues['assignees'] = $assignees; 
			// remove the usergroups_groupid array, it will be ignored, but to be on the safe side
			unset($formvalues['task_assigneeid']); 
		}
		//debugMessage($formvalues['assignees']);
		// set the date completed to today if the task status is set to complete
		if($formvalues['status'] == "3" and isEmptyString($formvalues['dateclosed'])){
			$formvalues['dateclosed'] = date("Y-m-d");
		}
		//debugMessage($formvalues);
		//exit();
		parent::processPost($formvalues);
	}
	/**
     * Return an array containing the IDs of the employees that have been assigned to the task
     *
     * @return Array of the IDs of the employees that have been assigned to the task
     */
    function getAssineeIDs() {
        $ids = array();
        $assignees = $this->getAssignees();
        //debugMessage($assignees->toArray());
        foreach($assignees as $theassignee) {
            $ids[] = $theassignee->getEmployeeID();
        }
        $creator_id = $this->getCreatedBy();
        $ids[] = $this->getCreatedBy();
        //debugMessage($ids);
        return $ids;
    }
   /**
     * Display a list of groups that the user belongs
     *
     * @return String HTML list of the groups that the user belongs to
     */
    function displayAssignees() {
        $assignees = $this->getAssignees();
        $str = "";
        if ($assignees->count() == 0) {
            return $str;
        }
        $str .= '<ul class="list">';
        foreach($assignees as $theassignee) {
            $str .= "<li>".$theassignee->getEmployee()->getName()."</li>"; 
        }
        $str .= "</ul>";
        return $str; 
    }
    /**
     * Send emails to all assignees about the new assignment
     * 
     */
    function sendTaskAssignmentNotificationEmail(){
    	// configure base stuff
    	$template = new EmailTemplate(); 
		
		$mail = getMailInstance(); 

		// assign values
		$template->assign('taskname', $this->getName());
		$template->assign('taskurl', array("controller" => "task", "action"=> "view", "id" => encode($this->getID())));

		$mail->setSubject(sprintf($this->translate->_('task_assignment_email_subject_notification'), $this->getName()));
		
		// get all assigness on the task
		$assingees = $this->getAssineeIDs();
		// make sure there is at least one assigned
		if (count($assingees > 0)) {
			foreach($assingees as $assingee) {
				$useraccount = new UserAccount();
				$useraccount->populate($assingee);
				// check if the user has turned on email assignments notification
				//if($participant['emailmeoncomment'] == 1){
					$template->assign('firstname', $useraccount->getFirstName());
					// render the view as the body of the email
					$mail->setBodyHtml($template->render('taskassignmentnotification.phtml'));
					$mail->addTo($useraccount->getEmail(), $useraccount->getName());
					$mail->send();
					// clear body and recipient in each email
					$mail->clearRecipients();
					$mail->setBodyHtml('');
				//}
			}
		}
		return true;
    }
	/**
     * Send emails to all assignees about the update to the task details assignment
     * 
     */
    function sendTaskUpdateNotificationEmail(){
    	// configure base stuff
    	$template = new EmailTemplate(); 
		
		$mail = getMailInstance(); 

		// assign values
		$template->assign('taskname', $this->getName());
		$template->assign('taskurl', array("controller" => "task", "action"=> "view", "id" => encode($this->getID())));

		$mail->setSubject(sprintf($this->translate->_('task_assignment_email_subject_notification'), $this->getName()));
		
		// get all assigness on the task
		$assingees = $this->getAssineeIDs();
		// make sure there is at least one assigned
		if (count($assingees > 0)) {
			foreach($assingees as $assingee) {
				$useraccount = new UserAccount();
				$useraccount->populate($assingee);
				// check if the user has turned on email assignments notification
				//if($participant['emailmeoncomment'] == 1){
					$template->assign('firstname', $useraccount->getFirstName());
					// render the view as the body of the email
					$mail->setBodyHtml($template->render('taskupdatenotification.phtml'));
					$mail->addTo($useraccount->getEmail(), $useraccount->getName());
					$mail->send();
					// clear body and recipient in each email
					$mail->clearRecipients();
					$mail->setBodyHtml('');
				//}
			}
		}
		return true;
    }
}
?>
