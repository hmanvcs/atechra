<?php

class DashboardController extends SecureController  {
	
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

}