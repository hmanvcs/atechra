<?php
class SignupController extends IndexController   {
	
	function signupAction() {
		// the group to which the user is to be added
		$this->_setParam("usergroups_groupid", array(2)); 
		$this->_setParam('entityname', 'UserAccount');
		$this->_setParam(URL_SUCCESS, encode($this->view->baseUrl("signup/processpayment")));
		$this->_setParam(URL_FAILURE, encode($this->view->baseUrl("signup/index"))); 
		$this->_setParam("action", ACTION_CREATE); 
		parent::createAction();
	}
	/**
	 * Page displayed when a user completes the signup 
	 *
	 */
	function confirmAction() {}
	/**
	 * Page displayed when a user completes the signup and is being forwarded to the payment processor
	 *
	 */
	function processpaymentAction() {}
	
	function paymentconfirmationAction() {
		$user = new UserAccount();
		$user->populate(decode($this->_getParam('id')));
		$user->sendSignupNotification();
	}
	
	/**
	 * Active the useraccount 
	 */
	function activateAction() {
		$user = new UserAccount(); 
		$user->populate(decode($this->_getParam("id")));
		$this->view->result = $user->activateAccount($this->_getParam('actkey'));
		if (!$this->view->result) {
			// activation failed
			$this->view->message = $user->getErrorStackAsString();
		}
	}
	
	function activateaccountAction() {
		$session = SessionWrapper::getInstance(); 
		// replace the decoded id with an undecoded value which will be used during processPost() 
		$id = decode($this->_getParam('id')); 
		$this->_setParam('id', $id); 
		
		$user = new UserAccount(); 
		$user->populate($id);
		$user->processPost($this->_getAllParams());
		
		if ($user->hasError()) {
			$session->setVar(FORM_VALUES, $this->_getAllParams());
    		$session->setVar(ERROR_MESSAGE, $user->getErrorStackAsString()); 
			$this->_helper->redirector->gotoUrl(decode($this->_getParam(URL_FAILURE)));
		}
		
		$result = $user->activateAccount($this->_getParam('activationkey'));
		
		if ($result) {
			// go to sucess page 
			$this->_helper->redirector->gotoUrl(decode($this->_getParam(URL_SUCCESS))); 
		} else {
			$session->setVar(FORM_VALUES, $this->_getAllParams());
    		$session->setVar(ERROR_MESSAGE, $user->getErrorStackAsString()); 
			$this->_helper->redirector->gotoUrl(decode($this->_getParam(URL_FAILURE)));
		}
	}
	
	function activationerrorAction() {}
	function activationconfirmationAction() {}
	public function employeeAction() {
		$session = SessionWrapper::getInstance(); 
		
		$employee = new UserAccount(); 
		$employee->populate(decode($this->_getParam("id")));
		# check if activation key matches 
		if($employee->getActivationKey() != $this->_getParam('actkey')){
			// activation key failed
			$session->setVar(ERROR_MESSAGE, $employee->getErrorStackAsString()); 
			$this->_helper->redirector->gotoSimple('activationerror','signup');
		}
	}
	public function employeeactivateAction() {
		$session = SessionWrapper::getInstance(); 
		// replace the decoded id with an undecoded value which will be used during processPost()
		$this->_setParam("isactive", '1');
		$this->_setParam("activationkey", '');
		$this->_setParam("activationdate", date("Y-m-d H:i:s"));
		$this->_setParam("id", decode($this->_getParam('id')));
		
		$useraccount = new UserAccount();
		$useraccount->populate($this->_getParam('id'));
		$useraccount->processPost($this->_getAllParams());
		debugMessage($useraccount->toArray());
		# if an error occurs in, redirect to error page
		if($useraccount->hasError()){
			$session->setVar(ERROR_MESSAGE, $useraccount->getErrorStackAsString()); 
			$this->_helper->redirector->gotoUrl(decode($this->_getParam(URL_FAILURE)));
		}
		
		try {
			# update the user account
			$useraccount->save();
		} catch (Exception $e) {
			$session->setVar(ERROR_MESSAGE, $useraccount->getErrorStackAsString()); 
			$this->_helper->redirector->gotoUrl(decode($this->_getParam(URL_FAILURE)));
			$this->_helper->redirector->gotoUrl(decode($this->_getParam(URL_FAILURE)));
		}
		
		$this->_helper->redirector->gotoUrl(decode($this->_getParam(URL_SUCCESS)));
	}
	public function employeeconfirmationAction() {}
}

