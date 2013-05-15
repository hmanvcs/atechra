<?php
class AppconfigController extends SecureController   {

	/**
	 * @see SecureController::getResourceForACL()
	 * 
	 * Return the Application Settings since we need to make the url more friendly 
	 *
	 * @return String
	 */
	function getResourceForACL() {
		return "Application Settings"; 
	}
    
}

