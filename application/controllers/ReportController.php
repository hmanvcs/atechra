<?php
class ReportController extends SecureController   {
	
	/**
	 * Get the name of the resource being accessed 
	 *
	 * @return String 
	 */
	function getActionforACL() {
		return ACTION_VIEW;
	}
	
	function getResourceForACL() {
		$resource = strtolower($this->getRequest()->getActionName()); 
		
		if ($resource == "weeklystatusreport") {
			return "Weekly Status Report";
		}
		
		if ($resource == "monthlytimesheetreport") {
			return "Monthly Timesheet Report";
		}
		
		if ($resource == "vacationtimereport") {
			return "Vacation Time Report";
		}
		return parent::getResourceForACL(); 
	}
	
	function weeklystatusreportAction(){}
	
	function vacationtimereportAction(){}

	function monthlytimesheetreportAction(){
		if(isNotAnEmptyString($this->_getParam(PAGE_CONTENTS_ONLY))){
			// disable rendering of the view and layout so that we can just echo the AJAX output
			$this->_helper->layout->disableLayout();
			$this->_helper->viewRenderer->setNoRender(true);
			
	        // required for IE, otherwise Content-disposition is ignored
			if(ini_get('zlib.output_compression')) {
				ini_set('zlib.output_compression', 'Off');
			}
			
			$response = $this->getResponse();
			
			# This line will stream the file to the user rather than spray it across the screen
			$response->setHeader("Content-type", "application/vnd.ms-excel");
			
			# replace excelfile.xls with whatever you want the filename to default to
			$response->setHeader("Content-Disposition", "attachment;filename=".time().rand(1, 10).".xls");
			$response->setHeader("Expires", 0);
			$response->setHeader("Cache-Control", "private");
			session_cache_limiter("public");
		}
	}
	
}

