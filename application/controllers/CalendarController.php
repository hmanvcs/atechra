<?php
class CalendarController extends SecureController   {
	
	/**
	 * Get the name of the resource being accessed 
	 *
	 * @return String 
	 */
	function getActionforACL() {
		return ACTION_VIEW;
	}

	/**
     * Redirect list searches to maintain the urls as per zend format 
     */
    public function searchAction() {
    	$this->_helper->redirector->gotoSimple(null, $this->getRequest()->getControllerName(), 
    											$this->getRequest()->getModuleName(),
    											array_remove_empty(array_merge_maintain_keys($this->_getAllParams(), $this->getRequest()->getQuery())));
    }
}

