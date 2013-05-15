<?php

class UserAccount extends BaseEntity {
	public function setTableDefinition() {
		#add the table definitions from the parent table
		parent::setTableDefinition();
		
		$this->setTableName('useraccount');
		$this->hasColumn('firstname', 'string', 255, array( 'notnull' => true, 'notblank' => true));
		$this->hasColumn('lastname', 'string', 255, array('notnull' => true, 'notblank' => true));
		$this->hasColumn('othername', 'string', 255);
		$this->hasColumn('email', 'string', 50, array('unique' => true, 'notnull' => true, 'notblank' => true));
		$this->hasColumn('phonenumber', 'string', 15);
		$this->hasColumn('password', 'string', 50);
		$this->hasColumn('notes', 'string', 1000);
		$this->hasColumn('securityquestion', 'string', 255);
		$this->hasColumn('securityanswer', 'string', 255);
		$this->hasColumn('changepassword', 'enum', null, array('values' => array(0 => 'Y', 1 => 'N'), 'default' => 'N'));
		$this->hasColumn('activationkey', 'string', 50);
		$this->hasColumn('isactive', 'integer', null, array('default' => '1'));
		$this->hasColumn('nextpasswordchangedate','date');	
		$this->hasColumn('lastlogindate','date');
		$this->hasColumn('loginretries', 'int');
		$this->hasColumn('activationdate', 'date');
		$this->hasColumn('companyname', 'string', 255, array('notnull' => true, 'notblank' => true));
		$this->hasColumn('companyaddress', 'string', 255, array('notnull' => true, 'notblank' => true));
		$this->hasColumn('jobtitle', 'string', 255, array('notnull' => true, 'notblank' => true));
		$this->hasColumn('receivetimesheetreminders', 'integer', null, array('default' => '1'));
		$this->hasColumn('receivestatusreportreminders', 'integer', null, array('default' => '1'));
		$this->hasColumn('maximumtimeoffhoursperyear', 'integer', null, array('default' => '80'));
		$this->hasColumn('receivestatusreportreminders', 'integer', null, array('default' => '1'));
		$this->hasColumn('viewfilesandfolders', 'integer', null, array('default' => '1'));
  		$this->hasColumn('uploadfiles', 'integer', null, array('default' => '1'));
  		$this->hasColumn('createfolders', 'integer', null, array('default' => '0'));
  		$this->hasColumn('deletefiles', 'integer', null, array('default' => '0'));
  		$this->hasColumn('deletefolders', 'integer', null, array('default' => '0'));
		// override the not null and not blank properties for the createdby column in the BaseEntity
		$this->hasColumn('createdby', 'integer', 11);
	}
	/**
	 * Contructor method for custom functionality - add the fields to be marked as dates
	 */
	public function construct() {
		parent::construct();
		// set the default for nextpasswordchangedate
		# $this->setNextPasswordChangeDate(date('Y-m-d', strtotime(" + ".$this->config->password->daystoexpire." days"))); 
		
		// set the custom error messages
       	$this->addCustomErrorMessages(array(
       									"firstname.notblank" => $this->translate->_("useraccount_firstname_error"),
       									"lastname.notblank" => $this->translate->_("useraccount_lastname_error"),
       									"email.notblank" => $this->translate->_("useraccount_email_error"),
       									"email.email" => $this->translate->_("useraccount_email_invalid_error")
       	       						));
	}
	
	public function setUp() {
		parent::setUp(); 
		// copied directly from BaseEntity since the createdby can be NULL when a user signs up 
		// automatically set timestamp column values for datecreated and lastupdatedate 
		$this->actAs('Timestampable', 
						array('created' => array(
												'name' => 'datecreated',    
											),
							 'updated' => array(
												'name'     =>  'lastupdatedate',    
												'onInsert' => false,
												'options'  =>  array('notnull' => false)
											)
						)
					);
		$this->hasMany('UserGroup as usergroups',
							array('local' => 'id',
									'foreign' => 'userid'
							)
						);
		$this->hasOne('UserAccount as creator', 
								array(
									'local' => 'createdby',
									'foreign' => 'id',
								)
						);
		$this->hasMany('ProjectAssignment as projectassignments',
							array('local' => 'id',
								 	'foreign' => 'employeeid'
							)
						);
		$this->hasMany('StatusReport as statusreports',
							array('local' => 'id',
								 	'foreign' => 'employeeid'
							)
						);
	}
	/*
	 * 
	 */
	function validate() {
		// add the address for the unique email
		$this->addCustomErrorMessages(array("email.unique" => sprintf($this->translate->_("useraccount_email_unique_error"), $this->getEmail())));
		// execute the column validation 
		parent::validate();

		// check that at least one group has been specified
		if ($this->getUserGroups()->count() == 0) {
			$this->getErrorStack()->add("groups", $this->translate->_("useraccount_group_error"));
		}
	}
	/*
	 * 
	 */
	function processPost($formvalues){
		// if the passwords are not changed , set value to null
		if(isArrayKeyAnEmptyString('password', $formvalues)){
			unset($formvalues['password']); 
		} else {
			$formvalues['password'] = sha1($formvalues['password']); 
		}
		# force setting of default none string column values. enum, int and date 	
		if(isArrayKeyAnEmptyString('nextpasswordchangedate', $formvalues)){
			unset($formvalues['nextpasswordchangedate']); 
		}
		if(isArrayKeyAnEmptyString('changepassword', $formvalues)){
			$formvalues['changepassword'] = "Y"; 
		}
		if(isArrayKeyAnEmptyString('lastlogindate', $formvalues)){
			unset($formvalues['lastlogindate']); 
		}
		if(isArrayKeyAnEmptyString('loginretries', $formvalues)){
			unset($formvalues['loginretries']); 
		}
		if(isArrayKeyAnEmptyString('isactive', $formvalues)){
			$formvalues['isactive'] = "1"; 
		}
		if(isArrayKeyAnEmptyString('receivetimesheetreminders', $formvalues)){
			$formvalues['receivetimesheetreminders'] = "0"; 
		}
		if(isArrayKeyAnEmptyString('receivestatusreportreminders', $formvalues)){
			$formvalues['receivestatusreportreminders'] = "0"; 
		}
		// move the data from $formvalues['usergroups_groupid'] into $formvalues['usergroups'] array
		// the key for each group has to be the groupid
		if (array_key_exists('usergroups_groupid', $formvalues)) {
			$groupids = $formvalues['usergroups_groupid']; 
			$usergroups = array(); 
			foreach ($groupids as $id) {
				$usergroups[]['groupid'] = $id; 
			}
			$formvalues['usergroups'] = $usergroups; 
			// remove the usergroups_groupid array, it will be ignored, but to be on the safe side
			unset($formvalues['usergroups_groupid']); 
		}
		
		// add the userid if the useraccount is being edited
		if (!isArrayKeyAnEmptyString('id', $formvalues)) {
			if (array_key_exists('usergroups', $formvalues)) {
				$usergroups = $formvalues['usergroups']; 
				foreach ($usergroups as $key=>$value) {
					$formvalues['usergroups'][$key]["userid"] = $formvalues["id"];
				}
			} 
		}
		// move the data from $formvalues['projectassignments_projectid'] into $formvalues['projectassignments'] array
		// the key for each group has to be the groupid
		if (array_key_exists('projectassignments_projectid', $formvalues)) {
			$ids = $formvalues['projectassignments_id']; 
			$projectids = $formvalues['projectassignments_projectid']; 
			$clientids = $formvalues['projectassignments_clientid']; 
			$employeeprojectassignments = array(); 
			//foreach ($clientids as $clientid) {
				foreach ($projectids as $key=>$projectid) {
					if (!isArrayKeyAnEmptyString('id', $formvalues)){
						$employeeprojectassignments[md5($key)]['employeeid'] = $formvalues['id']; 
					}
					//$employeeprojectassignments[md5($key)]['id'] = $ids[$key];
					$employeeprojectassignments[md5($key)]['clientid'] = $clientids[$key];
					$employeeprojectassignments[md5($key)]['projectid'] = $projectid;  
				}
			//}
			$formvalues['projectassignments'] = $employeeprojectassignments; 
			// remove the projectassignments array, it will be ignored, but to be on the safe side
			unset($formvalues['projectassignments_id']);
			unset($formvalues['projectassignments_projectid']);
			unset($formvalues['projectassignments_clientid']);  
		}
		
		// add the userid if the useraccount is being edited
		if (!isArrayKeyAnEmptyString('id', $formvalues)) {
			if (array_key_exists('projectassignments', $formvalues)) {
				$projectassignments = $formvalues['projectassignments']; 
				foreach ($projectassignments as $key=>$value) {
					$formvalues['projectassignments'][$key]["employeeid"] = $formvalues["id"];
				}
			} 
		}
		if(isArrayKeyAnEmptyString('viewfilesandfolders', $formvalues)){
			$formvalues['viewfilesandfolders'] = "0"; 
		}
		if(isArrayKeyAnEmptyString('uploadfiles', $formvalues)){
			$formvalues['uploadfiles'] = "0"; 
		}
		if(isArrayKeyAnEmptyString('createfolders', $formvalues)){
			$formvalues['createfolders'] = "0"; 
		}
		if(isArrayKeyAnEmptyString('deletefiles', $formvalues)){
			$formvalues['deletefiles'] = "0"; 
		}
		if(isArrayKeyAnEmptyString('deletefolders', $formvalues)){
			$formvalues['deletefolders'] = "0"; 
		}
		// debugMessage($formvalues);
		
		parent::processPost($formvalues);
	}	
	/**
	 * Reset the password for  the user. All checks and validations have been completed
	 * 
	 * @param String $newpassword The new password. If the new password is not defined, a new random password is generated
	 *
	 * @return Boolean TRUE if the password is changed, FALSE if it fails to change the user's password.
	 */
	 function resetPassword($newpassword = "") {
	 	// check if the password is empty 
	 	if (isEmptyString($newpassword)) {
	 		// generate a new random password
	 		$newpassword = $this->generatePassword(); 
	 	}
	 	return $this->changePassword("", $newpassword, false); 
	}
	/**
	 * Change the password for the user. All checks and validations have been completed
	 * 
	 * @param String $providedpassword The password provided on the screen
	 * @param String $newpassword The new password
     * @param boolean $verify Verify whether the provided password is the same as the user's current password
	 *
	 * @return TRUE if the password is changed, FAlSE if it fails to change the user's password.
	 */
	function changePassword($providedpassword, $newpassword, $verify = true){
		// check if the provided password is the same as that for the user
      	if ($verify) {
          if ($this->getPassword() != sha1($providedpassword)) {
              $this->getErrorStack()->add("oldpassword.invalid", $this->translate->_("useraccount_oldpassword_invalid_error"));
              return false;
          }
     	}
		// now change the password
		$this->setPassword(sha1($newpassword));
      	$this->setActivationKey('');
      	$this->setNextPasswordChangeDate(date('Y-m-d', strtotime("+ ".$this->config->password->daystoexpire." days"))); 
      	
      	try {
      	$this->save();
      	
      	# Log to audit trail that a password has been changed.
			$audit_values = array("transactiontype" => USER_CHANGE_PASSWORD, "userid" => $this->getID(), "executedby" => $this->getID(), "success" => 'Y');
			$audit_values['transactiondetails'] = $this->getName()." changed account password";
			$this->notify(new sfEvent($this, USER_CHANGE_PASSWORD, $audit_values));
      	
      	} catch (Exception $e){
      		# Log to audit trail that user has failed to change password
			$audit_values = array("transactiontype" => USER_CHANGE_PASSWORD, "userid" => $this->getID(), "executedby" => $this->getID(), "success" => 'N');
			$audit_values['transactiondetails'] = $this->getName()." failed to change account password". $e->getMessage();
			$this->notify(new sfEvent($this, USER_CHANGE_PASSWORD, $audit_values));
      	}
		return true;
	}
	/*
	 * Reset the user's password and send a notification to complete the recovery  
	 *
	 * @return Boolean TRUE if resetting is successful and FALSE if emailaddress security questions and answer is invalid or has no record in the database
	 */
	function recoverPassword() {
      // save to the audit trail
		$audit_values = array("transactiontype" => USER_RECOVER_PASSWORD); 
		// set the updater of the account only when specified
		if (!isEmptyString($this->getLastUpdatedBy())) {
			$audit_values['executedby'] = $this->getLastUpdatedBy();
		}
		
		# check that the user's email exists and that they are signed up
		if(!$this->findByEmail($this->getEmail())){
			$audit_values['transactiondetails'] = "Recovery of password for '".$this->getEmail()."' failed - user not found";
			$this->notify(new sfEvent($this, USER_RECOVER_PASSWORD, $audit_values));
			return false;
		}
			
		# reset the password and set the next password change date
		$this->setNextPasswordChangeDate(date('Y-m-d')); 
		$this->setActivationKey($this->generateActivationKey());
		
		# save the activation key for the user 
		$this->save();
		
		# Send the user the reset password email
		$this->sendRecoverPasswordEmail();
		
		// save the audit trail record
		// the transaction details is the email address being used to
		$audit_values['userid'] = $this->getID(); 
		$audit_values['transactiondetails'] = "Password Recovery link for '".$this->getEmail()."' sent to '".$this->getEmail()."'";
		$audit_values['success'] = 'Y';
		$this->notify(new sfEvent($this, USER_RECOVER_PASSWORD, $audit_values));
		
		return true;
	}
	/**
	 * Send an email with a link to activate the members' account
	 */
	function sendRecoverPasswordEmail() {
		$template = new EmailTemplate(); 
		// create mail object
		$mail = getMailInstance(); 

		// assign values
		$template->assign('firstname', $this->getFirstName());
		// just send the parameters for the activationurl, the actual url will be built in the view 
		$template->assign('resetpasswordurl', array("controller"=> "user","action"=> "resetpassword", "actkey" => $this->getActivationKey(), "id" => encode($this->getID())));
		
		// configure base stuff
		$mail->addTo($this->getEmail());
		$mail->setSubject($this->translate->_('useraccount_email_subject_recoverpassword'));
		// render the view as the body of the email
		$mail->setBodyHtml($template->render('recoverpassword.phtml'));
		$mail->send();
		
		return true;
   }
	/**
	 * Generate a new password incase a user wants a new password
	 * 
	 * @return String a random password 
	 */
    function generatePassword() {
		return $this->generateRandomString($this->config->password->minlength);
    }
	/**
	 * Generate a 10 digit activation key  
	 * 
	 * @return String An activation key
	 */
    function generateActivationKey() {
		return substr(md5(uniqid(mt_rand(), 1)), 0, 10);
    }
   /**
    * Find a user account either by their email address 
    * 
    * @param String $email The email
    * @return UserAccount or FALSE if the user with the specified email does not exist 
    */
	function findByEmail($email) {
		# query active user details using email
		$q = Doctrine_Query::create()->from('UserAccount u')->where('u.email = ?', $email);
		$result = $q->fetchOne(); 
		
		// check if the user exists 
		if(!$result){
			// user with specified email does not exist, therefore is valid
			$this->getErrorStack()->add("user.invalid", $this->translate->_("useraccount_user_invalid_error"));
			return false;
		}
		
		$data = $result->toArray(); 

		// merge all the data including the user groups 
		$this->merge($data);
		// also assign the identifier for the object so that it can be updated
		$this->assignIdentifier($data['id']); 
		
		return true; 
	}
	/**
	 * Return the user's full names, which is a concatenation of the first and last names
	 *
	 * @return String The full name of the user
	 */
	function getName() {
		return $this->getFirstName()." ".$this->getLastName();
	}		
	/*
	 * TODO Put proper comments
	 */
	function generateRandomString($length) {
		$rand_array = array("a","b","c","d","e","f","g","h","i","j","k","l","m","n","o","p","q","r","s","t","u","v","w","x","y","z","0","1","2","3","4","5","6","7","8","9");
		$rand_id = "";
		for ($i = 0; $i <= $length; $i++) {
			$rand_id .= $rand_array[rand(0, 35)];
		}
		return $rand_id;
	}
 	/**
     * Return an array containing the IDs of the groups that the user belongs to
     *
     * @return Array of the IDs of the groups that the user belongs to
     */
    function getGroupIDs() {
        $ids = array();
        $groups = $this->getUserGroups();
        //debugMessage($groups->toArray());
        foreach($groups as $thegroup) {
            $ids[] = $thegroup->getGroupID();
        }
        return $ids;
    }
    /**
     * Display a list of groups that the user belongs
     *
     * @return String HTML list of the groups that the user belongs to
     */
    function displayGroups() {
        $groups = $this->getUserGroups();
        $str = "";
        if ($groups->count() == 0) {
            return $str;
        }
        $str .= '<ul class="list">';
        foreach($groups as $thegroup) {
            $str .= "<li>".$thegroup->getGroup()->getName()."</li>"; 
        }
        $str .= "</ul>";
        return $str; 
    }
	function afterSave(){
		// send a notification email to the employee about their account
		$this->sendSignupNotification();
	}
	/**
	 * Send a notification to a employee that their account has been setup and instructions on how to activate it
	 * 
	 * @return Boolean whether or not the signup notification email has been sent
	 *
	 */
	function sendSignupNotification() {
		$template = new EmailTemplate(); 
		// create mail object
		$mail = getMailInstance(); 

		// assign values
		$template->assign('firstname', $this->getFirstName());
		// just send the parameters for the activationurl, the actual url will be built in the view 
		$template->assign('activationurl', array("controller" => "signup", "action"=> "employee", "actkey" => $this->getActivationKey(), "id" => encode($this->getID())));
		
		// configure base stuff
		$mail->addTo($this->getEmail());
		$default_sender = $mail->getDefaultFrom();
		//$mail->addCc($default_sender['email']); // send email to the admin as well  
		$mail->setSubject(sprintf($this->translate->_('useraccount_email_subject_signup'), $this->translate->_('appname')));
		
		// render the view as the body of the email
		$mail->setBodyHtml($template->render('signupnotification.phtml'));

		// send the email
		$mail->send();
		
		return true;
	}
	function getEmployeeProjectAssignments(){
		$conn = Doctrine_Manager::connection();
		// get all projects currently assigned to the employee
		$all_current_employee_project_assignments_query = "SELECT pa.id, pa.employeeid, pa.projectid, pa.clientid, CONCAT(u.firstname, ' ', u.lastname) AS `Employee`, c.`name` AS Client, p.title AS `Project Title`, p.jobnumber as `Job Number`, lv.lookupvaluedescription as `Type` FROM projectassignment AS pa INNER JOIN client AS c ON pa.clientid = c.id INNER JOIN project AS p ON pa.projectid = p.id INNER JOIN useraccount AS u ON pa.employeeid = u.id INNER JOIN lookuptypevalue AS lv ON (p.`type` = lv.lookuptypevalue AND lv.lookuptypeid = 5) WHERE pa.employeeid = '".$this->getID()."'";
		//debugMessage($all_current_employee_project_assignments_query);
		$all_current_employee_project_assignments = $conn->fetchAll($all_current_employee_project_assignments_query);
		return $all_current_employee_project_assignments;
	}
	/**
	 * 
	 * Function to get the total number of projects assigned to an employee
	 * 
	 * @return int the number of projects assigned
	 * 
	 */
	function getNumberOfProjects(){
		$projectsarray = $this->getProjectAssignments();
		if (!$projectsarray) {
			# there are not projects assigned
			return 0;
		}
		return count($projectsarray);
	}
	/**
	 * 
	 * Function to get an array of project IDs assigned to an employee
	 * 
	 * @return array of Project IDs
	 */
	function getEmployeeProjectIDs(){
		$projectsarray = $this->getProjectAssignments();
		$projectids = array();
		//debugMessage($projectsarray->toArray());
		foreach ($projectsarray as $project){
			$projectids[] = $project->getProjectID();
		}
		//debugMessage($projectids);
		return $projectids;
	}
	/**
	 * Determine if user can view corporate files and folders
	 */
	function canViewFilesAndFolders(){
		return $this->getViewFilesAndFolders() == 0 ? false : true;
	}
	/**
	 * Determine if user can create folders
	 */
	function canCreateFolders(){
		return $this->getCreateFolders() == 0 ? false : true;
	}
	/**
	 * Determine if user can upload files
	 */
	function canUploadFiles(){
		return $this->getUploadFiles() == 0 ? false : true;
	}
	/**
	 * Determine if user can delete folders
	 */
	function canDeleteFolders(){
		return $this->getDeleteFolders() == 0 ? false : true;
	}
	/**
	 * Determine if user can delete files
	 */
	function canDeleteFiles(){
		return $this->getDeleteFiles() == 0 ? false : true;
	}
	# check if user has access to a specific folder
	function hasFolderAccess($folderid) {
		$folder = new Folder();
		$folder->populate($folderid);
		$folder->setCreatedBy($this->getID());
		 
		$hasaccess = true;
		if(!$folder->isAllowedToView()){
			$hasaccess = false;
		}
		return $hasaccess;
	}
	# check if user has access to a specific file
	function hasFileAccess($fileid) {
		$file = new CorporateFile();
		$file->populate($fileid);
		$file->setCreatedBy($this->getID());
		 
		$hasaccess = true;
		if(!$file->isAllowedToView()){
			$hasaccess = false;
		}
		return $hasaccess;
	}
	# check for all users
	function populateAll(){
		$q = Doctrine_Query::create()->from('UserAccount')->where("id <> '' ");
		$result = $q->execute();
		
		return $result;
	}
}
?>
