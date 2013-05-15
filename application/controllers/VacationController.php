<?php

class VacationController extends SecureController  {
	function getResourceForACL() {
		return "Vacation";
	}
	/**
	 * @see SecureController::getActionforACL()
	 * 
	 * The dashboard can only be viewed, however the default is create for the index.phtml file. 
	 *
	 * @return String
	 */
	function getActionforACL() {
		return ACTION_VIEW; 
	}
	/**
	 * Action to approve a vacation
	 * 
	 * @see IndexController::approveAction()
	 */
	public function approveAction() {
		$session = SessionWrapper::getInstance(); 
		if (isEmptyString($this->_getParam(URL_FAILURE))) {
			// use the encoded value of the ledgerid
			$this->_setParam(URL_FAILURE, encode($this->view->baseUrl('vacation/view/id/'.$this->_getParam('vacationid'))));
		} 
		if (isEmptyString($this->_getParam(URL_SUCCESS))) {
			// use the encoded value of the ledgerid 
			$this->_setParam(URL_SUCCESS, encode($this->view->baseUrl('vacation/list/l'.HTML_TABLE_COLUMN_SEPARATOR.'status/1/')));
		} 
		$this->_setParam('entityname', 'Approval'); 
		$this->_setParam('status', '1');
		$this->_setParam(SUCCESS_MESSAGE, 'vacation_approve_success'); 
		$this->_setParam('vacationid', decode($this->_getParam('vacationid')));  
		$this->_setParam('action', ACTION_CREATE); 
		//debugMessage($this->_getAllParams());
		// set the status in the timesheet to approved
		$this->_setParam('vacation', array('status' => 2)); 
		parent::createAction(); 
	}
	
	/**
	 * Action  to confirm rejection of a Timesheet
	 * 
	 * @see IndexController::confirmrejectAction()
	 */
	public function confirmrejectAction() {}
	
	/**
	 * Action to reject a Timesheet 
	 * 
	 * @see IndexController::rejectAction()
	 */
	public function rejectAction() {
		$session = SessionWrapper::getInstance(); 
		if (isEmptyString($this->_getParam(URL_FAILURE))) {
			// use the encoded value of the timesheetid 
			$this->_setParam(URL_FAILURE, encode($this->view->baseUrl('vacation/view/id/'.$this->_getParam('vacationid'))));
		}  
		if (isEmptyString($this->_getParam(URL_SUCCESS))) {
			// use the encoded value of the timesheetid 
			$this->_setParam(URL_SUCCESS, encode($this->view->baseUrl('vacation/list/l'.HTML_TABLE_COLUMN_SEPARATOR.'vacationid/1/')));
		} 
		$this->_setParam('entityname', 'Approval'); 
		$this->_setParam('status', '0');
		$this->_setParam('vacationid', decode($this->_getParam('vacationid'))); 
		$this->_setParam('action', ACTION_CREATE); 
		
		// set the status in the timesheet to rejected
		$this->_setParam('vacation', array('status' => 3)); 
		parent::createAction(); 
	}
	function viewAction(){
		if (!isEmptyString($this->_getParam('vacationid'))) {
			// redirect to the view page
			$this->_redirect($this->view->baseUrl("vacation/view/id/".$this->_getParam('vacationid'))); 
		}
	}
	function indexAction(){
		if (!isEmptyString($this->_getParam('id'))) {
			$session = SessionWrapper::getInstance();
			
			$vacation = new Vacation();
			$vacation->populate(decode($this->_getParam('id')));
			
			if ($vacation->isApproved()) {
				// show a message to the user
				$session->setVar(SUCCESS_MESSAGE, $this->_translate->translate('vacation_already_approved_message'));
				// redirect to the view page
				$this->_redirect($this->view->baseUrl("vacation/view/id/".$this->_getParam('id'))); 
			}
		}
	}
}