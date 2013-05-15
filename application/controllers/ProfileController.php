<?php

class ProfileController extends SecureController  {
	
	/**
	 * @see SecureController::getResourceForACL()
	 *
	 * @return String
	 */
	function getResourceForACL() {
		return "User Account";
	}
	
	/**
	 * Override unknown actions to enable ACL checking 
	 * 
	 * @see SecureController::getActionforACL()
	 *
	 * @return String
	 */
	function getActionforACL() {
		$action = strtolower($this->getRequest()->getActionName()); 
		if ($action == "changepassword") {
			return ACTION_EDIT; 
		}
		if ($action == "resetpassword") {
			return ACTION_EDIT; 
		}
		return parent::getActionforACL(); 
	}
	
	
    public function changepasswordAction()  {
    	if(!isEmptyString($this->_getParam('password'))){
	        $user = new UserAccount(); 
	    	$user->populate(decode($this->_getParam('id')));
	    	
	    	# Change password
	    	if ($user->changePassword($this->_getParam('oldpassword'), $this->_getParam('password') )) {
	    		// clear the session
       			$session = SessionWrapper::getInstance(); 
        		$session->clearSession();
	   			// send a link to enable the user to recover their password 
	   			$this->_helper->redirector("changepasswordconfirmation", "user");
	   		} else {
	   			// send an error message that no user with that email was found 
	   			$session = SessionWrapper::getInstance(); 
	   			$session->setVar(ERROR_MESSAGE, $user->getErrorStackAsString());
	   			$session->setVar(FORM_VALUES, $this->_getAllParams());
	   		}
    	}
    }
    
    public function resetpasswordAction(){}
    
}

