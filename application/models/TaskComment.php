<?php

class TaskComment extends BaseRecord {
	public function setTableDefinition() {
		#add the table definitions from the parent table
		parent::setTableDefinition();
		
		$this->setTableName('taskcomment');
		$this->hasColumn('id', 'integer', 11, array('primary' => true, 'autoincrement' => true));
		$this->hasColumn('taskid', 'integer', null, array('notnull' => true, 'notblank'=>true));
		$this->hasColumn('content', 'string', 65535, array("notblank" => true, "notnull" => true));
		$this->hasColumn('originalfilename', 'string', 255);
		$this->hasColumn('newfilename', 'string', 255);
		$this->hasColumn('createdby', 'integer', null, array('notnull' => true, 'notblank'=>true));
		$this->hasColumn('datecreated', 'timestamp', array("notblank" => true, "notnull" => true));
	}
	/**
	 * Contructor method for custom functionality - add the fields to be marked as dates
	 */
	public function construct() {
		parent::construct();
		
		// set the custom error messages
       	$this->addCustomErrorMessages(array(
       									"taskid.notblank" => $this->translate->_("taskcomment_taskid_error"),
       									"content.notblank" => $this->translate->_("taskcomment_content_error")
       	       						));
	}
	/**
	 * Relationships for the model
	 */
	public function setUp() {
		parent::setUp(); 
		// automatically set timestamp on datecreated
		$this->actAs('Timestampable', 
						array('created' => array(
												'name' => 'datecreated',    
											),
							 'updated' => array(
												'name'     =>  'datecreated',    
												'onInsert' => false,
												'options'  =>  array('notnull' => false)
											)
						)
					);
		$this->hasOne('Task as task', 
								array(
									'local' => 'taskid',
									'foreign' => 'id'
								)
						);
		$this->hasOne('UserAccount as employee',
							array('local' => 'createdby',
								 	'foreign' => 'id'
							)
						);
	}
	/**
	 * send a notification email when a new comment is posted
	 * @see BaseRecord::afterSave()
	 */
	function afterSave() {
		// send email notifications for either approvals or rejections 
		$this->sendTaskCommentNotificationEmail(); 
	}
	/**
     * Send emails to all assignees about the new assignment
     * 
     */
    function sendTaskCommentNotificationEmail(){
    	// configure base stuff
    	$template = new EmailTemplate(); 
		
		$mail = getMailInstance(); 

		// assign values
		$template->assign('commentemailintro', sprintf($this->translate->_('taskcomment_comment_intro'), $this->getEmployee()->getName()));
		// set email content as the actual Comment 
		$template->assign('emailcontent', $this->getContent());
		// path to omment thread depending on the comment type. See sub class for implementation	
		$template->assign('emaillink', array("controller" => "taskcomment", "action" => "list", "tc".HTML_TABLE_COLUMN_SEPARATOR."taskid" => $this->getTaskID()));
		
		$mail->setSubject(sprintf($this->translate->_('task_comment_email_subject_notification'), $this->getEmployee()->getName()));
		
		// get all assigness on the task
		$assingees = $this->getTask()->getAssineeIDs();
		// remove the person who has commented from the people to receive the email
		if (array_search($this->getEmployee()->getID(), $assingees)){
			echo "Hooray, it is here: ".array_search($this->getEmployee()->getID(), $assingees);
			// remove the person who has commented from the list of those to receive emails
			$key = array_search($this->getEmployee()->getID(), $assingees);
			unset($assingees[$key]);
		} 
		// make sure there is at least one assigned
		if (count($assingees > 0)) {
			foreach($assingees as $assingee) {
				$useraccount = new UserAccount();
				$useraccount->populate($assingee);
				// check if the user has turned on email assignments notification
				//if($participant['emailmeoncomment'] == 1){
					$template->assign('firstname', $useraccount->getFirstName());
					// render the view as the body of the email
					$mail->setBodyHtml($template->render('taskcommentnotification.phtml'));
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