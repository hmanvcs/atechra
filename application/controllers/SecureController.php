<?php

/**
 * Controls access to only users who have logged in and checks the access control for the different pages 
 * 
 */
class SecureController extends IndexController  {
	
	public function init() {
		// initialize the parent controller 
		parent::init();
		$session = SessionWrapper::getInstance(); 
		// check whether the user is logged in
		if (isEmptyString($session->getVar('userid'))) {
	         // clear the session
			 $this->_helper->redirector->gotoSimpleAndExit("login", "user", $this->getRequest()->getModuleName(), 
													array('redirecturl' => encode(Zend_Controller_Front::getInstance()->getRequest()->getRequestUri())));
		}
		
		$cache = Zend_Registry::get('cache');
		// load the acl instance
		$acl = getACLInstance(); 
		
		if (!$acl->checkPermission($this->getResourceForACL(), $this->getActionforACL())) { 
			// redirect to the access denied page 
			 $this->_helper->redirector->gotoSimpleAndExit("accessdenied", "index");
		}
	}
	
	/**
	 * Get the name of the resource being accessed 
	 *
	 * @return String 
	 */
	function getResourceForACL() {
		return strtolower($this->getRequest()->getControllerName()); 
	}
	
	/**
	 * Get the name of the action being accessed 
	 *
	 * @return String 
	 */
	function getActionforACL() {
		$action = strtolower($this->getRequest()->getActionName()); 
		if ($action == ACTION_INDEX) {
			return ACTION_CREATE; 
		}
		if ($action == ACTION_CREATE) {
			// check whether this is an update action or a create action 
			if (!isEmptyString($this->getRequest()->id)) {
				return ACTION_EDIT; 
			} else {
				return ACTION_CREATE; 
			}
		}
		if ($action == "new") {
			return ACTION_CREATE; 
		}
	        if ($action == "returntolist") {
			return ACTION_LIST; 
		}
		if ($action == "overview") {
			return ACTION_VIEW; 
		}
		
		if ($action == "selectchain") {
			return ACTION_LIST; 
		}
		
		if ($action == "listsearch") {
			return ACTION_LIST; 
		}
		
		if ($action == "reject") {
			return ACTION_APPROVE; 
		}
		
		if ($action == "export") {
			return  ACTION_EXPORT; 
		}
		return $action; 
	}
}