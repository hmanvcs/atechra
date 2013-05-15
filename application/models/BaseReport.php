<?php

class BaseReport extends BaseEntity{
	/**
	 * 
	 * Whether or not the report has been approved
	 * 
	 * @return bool
	 */
	function isSaved() {
		return ($this->getStatus() == 0); 
	}
	/**
	 * 
	 * Whether or not the report has been approved
	 * 
	 * @return bool
	 */
	function isApproved() {
		return ($this->getStatus() == 2); 
	}
	/**
	 * Whether or not the report has been rejected 
	 * 
	 * @return bool 
	 */
	function isRejected() {
		return ($this->getStatus() == 3); 
	}
	/**
	 * 
	 * Whether or not to allow approval, which is only for reports with Submitted status
	 * 
	 * @return bool
	 */
	
	function allowApprove() {
		return ($this->getStatus() == 1); 
	}
	/**
	 * 
	 * Whether or not to allow approval, which is only for reports with Submitted status
	 * 
	 * @return bool
	 */
	
	function allowReject() {
		return ($this->getStatus() == 1); 
	}
	/**
	 * 
	 * Whether or not to allow editing, which is only for reports with Submitted and rejected status values
	 * 
	 * @return bool
	 */
	
	function allowUpdate() {
		return ($this->getStatus() == 0 || $this->getStatus() == 3); 
	}
	/**
	 * Return the text description of the numeric status value 
	 * 
	 * @Return String 
	 */
	function getStatusDescription() {
		return LookupType::getLookupValueDescription("TIMESHEET_STATUSREPORT_STATUS", $this->getStatus()); 
	}
	/**
	 * 
	 * Whether or not to allow editing, which is only for reports with Submitted and rejected status values
	 * 
	 * @return bool
	 */
	
	function allowDelete() {
		return ($this->getStatus() == 0); 
	}
}
?>
