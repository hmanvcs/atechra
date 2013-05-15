<?php

class TaskController extends SecureController {
	function getResourceForACL() {
		return "Task";
	}
	
	public function getActionforACL() {
        $action = strtolower($this->getRequest()->getActionName());
		if($action == "completed" || $action == "completedsearch") {
			return ACTION_LIST; 
		}
		
        return parent::getActionforACL(); 
	}
	function commentAction(){
		
	}
	
	function deleteAction(){		
		// disable rendering of the view and layout so that we can just echo the AJAX output 
	    $this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender(TRUE);
		
		$session = SessionWrapper::getInstance(); 
		
		// the task id and project being deleted
		$id = $this->_getParam("id");
		
		// populate task to be deleted
		$task = new Task();
		$task->populate($id);
			
		if($task->delete()){
			$session->setVar(SUCCESS_MESSAGE, "Task was successfully deleted");
		} else {
			$this->_setParam(URL_FAILURE, "An error occured in deleting your task");
		}
		$this->_setParam(URL_FAILURE, encode($this->view->baseUrl('task/view/id/'.$this->_getParam('id'))));
		parent::deleteAction();
	}
	
	function completedAction(){
		$listcount = new LookupType();
    	$listcount->setName("LIST_ITEM_COUNT_OPTIONS");
    	$values = $listcount->getOptionValues(); 
    	asort($values, SORT_NUMERIC); 
    	$dropdown = new Zend_Form_Element_Select('itemcountperpage',
							array(
								'multiOptions' => $values, 
								'view' => new Zend_View(),
								'decorators' => array('ViewHelper')
							)
						);
		if (isEmptyString($this->getRequest()->itemcountperpage)) {
			$dropdown->setValue(10);
		} else {
			$dropdown->setValue($this->getRequest()->itemcountperpage);
		}  
	    $this->view->listcountdropdown = $this->_translate->translate("list_itemcount_dropdown").$dropdown->render();
	}
	
	function completedsearchAction(){
		$this->_helper->redirector->gotoSimple("completed", $this->getRequest()->getControllerName(), 
    											$this->getRequest()->getModuleName(),
    											array_remove_empty(array_merge_maintain_keys($this->_getAllParams(), $this->getRequest()->getQuery())));
	}
}

