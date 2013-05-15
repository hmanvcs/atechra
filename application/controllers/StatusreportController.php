<?php

class StatusreportController extends SecureController  {
	function getResourceForACL() {
		return "Status Report";
	}
	function getActionforACL() {
		$action = strtolower($this->getRequest()->getActionName()); 
		if ($action == "confirmreject") {
			return ACTION_APPROVE; 
		}
		if ($action == "submitforapproval") {
			return ACTION_CREATE; 
		}
		return parent::getActionforACL();
	}
	/**
	 * Action to approve a Status Report
	 * 
	 * @see IndexController::approveAction()
	 */
	public function approveAction() {
		$session = SessionWrapper::getInstance(); 
		if (isEmptyString($this->_getParam(URL_FAILURE))) {
			// use the encoded value of the statusreportid
			$this->_setParam(URL_FAILURE, encode($this->view->baseUrl('statusreport/view/id/'.$this->_getParam('statusreportid'))));
		} 
		if (isEmptyString($this->_getParam(URL_SUCCESS))) {
			// use the encoded value of the statusreportid 
			$this->_setParam(URL_SUCCESS, encode($this->view->baseUrl('statusreport/list/sr'.HTML_TABLE_COLUMN_SEPARATOR.'status/1/')));
		} 
		$this->_setParam('entityname', 'Approval'); 
		$this->_setParam('status', '1');
		$this->_setParam(SUCCESS_MESSAGE, 'statusreport_approve_success'); 
		$this->_setParam('statusreportid', decode($this->_getParam('statusreportid')));  
		$this->_setParam('action', ACTION_CREATE); 
		
		// set the status in the timesheet to approved
		$this->_setParam('statusreport', array('status' => 2)); 
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
			// use the encoded value of the ledgerid 
			$this->_setParam(URL_FAILURE, encode($this->view->baseUrl('request/view/id/'.$this->_getParam('statusreportid'))));
		}  
		if (isEmptyString($this->_getParam(URL_SUCCESS))) {
			// use the encoded value of the ledgerid 
			$this->_setParam(URL_SUCCESS, encode($this->view->baseUrl('statusreport/list/sr'.HTML_TABLE_COLUMN_SEPARATOR.'status/1/')));
		} 
		$this->_setParam('entityname', 'Approval'); 
		$this->_setParam('status', '0');
		$this->_setParam('statusreportid', decode($this->_getParam('statusreportid'))); 
		$this->_setParam('action', ACTION_CREATE); 
		
		// set the status in the status report to rejected
		$this->_setParam('statusreport', array('status' => 3)); 
		parent::createAction(); 
	}
	
	function viewAction(){
		if (!isEmptyString($this->_getParam('statusreportid'))) {
			// redirect to the view page
			$this->_redirect($this->view->baseUrl("statusreport/view/id/".$this->_getParam('statusreportid'))); 
		}
	}
	
	function createAction() {
		// add a status value when the user clicks the submit for approval action
		$this->getStatusFromSubmitButtons();
		parent::createAction(); 
	}
	function editAction() {
		// add a status value when the user clicks the submit for approval action
		$this->getStatusFromSubmitButtons();
		parent::editAction(); 
	}
	/**
	 * Set the status value depending on which submit button is clicked 
	 */
	function getStatusFromSubmitButtons() {
		if ($this->_getParam('saveforlater') == "1") {
			$this->_setParam("status", 0); 
		}
		if ($this->_getParam('submitforapproval') == "1") {
			$this->_setParam("status", 1); 
		}
	}
	/**
	 * Action to submit a Timesheet for approval
	 * 
	 */
	public function submitforapprovalAction() {
		$session = SessionWrapper::getInstance(); 
		
		$statusreport = new StatusReport();
		$statusreport->populate(decode($this->_getParam('id')));
		$statusreport->setStatus(1);
		try {
			$statusreport->save(); 
			$statusreport->sendStatusReportSubmissionEmail(); 
			$session->setVar(SUCCESS_MESSAGE, $this->_translate->translate('statusreport_submission_success'));
		} catch (Exception $e){
			$session->setVar(ERROR_MESSAGE, $this->_translate->translate('statusreport_submission_failure').$e->getMessage());
		}
		// redirect to the view page
		$this->_redirect($this->view->baseUrl("statusreport/view/id/".$this->_getParam('id'))); 
	}
}