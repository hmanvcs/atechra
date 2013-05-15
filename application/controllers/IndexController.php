<?php

require_once 'eventdispatcher/sfEventDispatcher.php';
require_once 'eventdispatcher/sfEvent.php';

# event hander functionality
require_once APPLICATION_PATH.'/includes/eventhandlerfunctions.php';

class IndexController extends Zend_Controller_Action  {

	/**
	 * Logger instance
	 * 
	 * @var Zend_Log
	 */
	protected $_logger; 
	/**
	 * Translation instance
	 * 
	 * @var Zend_Translate 
	 */
	protected $_translate; 
	/**
	 * Dispatcher to handle events
	 *
	 * @var sfEventDispatcher
	 */
	protected $_eventdispatcher; 
	
	public function init()    {
        // Initialize logger and translate actions
		$this->_logger = Zend_Registry::get("logger"); 
		$this->_translate = Zend_Registry::get("translate"); 
		// set the redirector to ignore the baseurl for redirections
		$this->_helper->redirector->setPrependBase(false); 
		
		$this->_eventdispatcher = initializeSFEventDispatcher();
		
		// load the application configuration
		loadConfig(); 
    }
    
    /**
     * Application landing page 
     */
    public function indexAction()  {}
    
	/**
     * Action to display the access denied page when a user cannot execute the specified action on a resource    
     */
    public function accessdeniedAction()  {
        // do nothing 
    }
    
   public function createAction() {
    	// debugMessage($this->_getAllParams());	
   		$session = SessionWrapper::getInstance(); 
    	// the name of the class to be instantiated
    	$classname = $this->_getParam("entityname");
    	$new_object = new $classname();
    	
    	// parameters for the response - default do not prepend the baseurl 
    	$response_params = array(); 
    	// add the createdby using the id of the user in session
    	if (isEmptyString($this->_getParam('id'))) {
    		// new entity
    		$this->_setParam('createdby', $session->getVar('userid'));
    		
    		// merge the post data to enable loading of any relationships in process post
    		//  TODO: Verify if this breaks any other functionality
			$new_object->merge(array_remove_empty($this->_getAllParams()), false); 
    	} else {
    		// id is already encoded during update so no need to encode it again 
    		$response_params['id'] = $this->_getParam('id'); 
    		// decode the id field and add it back to the array otherwise it will cause a type error during processPost
    		$this->_setParam('id', decode($this->_getParam('id'))); 
    		// load the details for the current entity from the database 
    		$new_object->populate($this->_getParam('id'));
    		$this->_setParam('lastupdatedby', $session->getVar('userid'));
    	}
    	// populate the object with data from the post and validate the object
    	// to ensure that its wellformed 
    	$new_object->processPost($this->_getAllParams());
    	if ($new_object->hasError()) {
    		// there were errors - add them to the session
    		$this->_logger->info("Validation Error for ".$classname." - ".$new_object->getErrorStackAsString());
    		$session->setVar(FORM_VALUES, $this->_getAllParams());
    		$session->setVar(ERROR_MESSAGE, $new_object->getErrorStackAsString());
    		$response_params['id'] = encode($this->_getParam('id'));  
    		 
    		// return to the create page
    		if (isEmptyString($this->_getParam(URL_FAILURE))) {
    			$this->_helper->redirector->gotoSimple('index', # the action 
	    							    $this->getRequest()->getControllerName(), # the current controller
	    								$this->getRequest()->getModuleName(), # the current module,
	    								$response_params
    	                             );
    	        return false; 
    		} else {
    			$this->_helper->redirector->gotoUrl(decode($this->_getParam(URL_FAILURE)), $response_params); 
    			return false; 
    		}
    	} // end check for whether errors occured during the population of the object instance from the submitted data
    	
    	// save the object to the database
    	try {
    		switch ($this->_getParam('action')) {
				case "" :
				case ACTION_CREATE :
					$new_object->save(); 
					// there are no errors so call the afterSave() hook
					$new_object->afterSave();
					break;
				case ACTION_EDIT:  
					// update the entity 
					$new_object->save(); 
					// there are no errors so call the afterSave() hook
					$new_object->afterUpdate();
					break; 
				case ACTION_DELETE:  
					// update the entity 
					$new_object->delete(); 
					// there are no errors so call the afterSave() hook
					$new_object->afterDelete();
					break;
				case ACTION_APPROVE:  
					// update the entity 
					$new_object->approve(); 
					// there are no errors so call the afterSave() hook
					$new_object->afterApprove();
					break;  
				default :
					break;
    		}
    		
    		// add a success message, if any, to the session for display
    		if (isNotAnEmptyString($this->_getParam(SUCCESS_MESSAGE))) {
    			$session->setVar(SUCCESS_MESSAGE, $this->_translate->translate($this->_getParam(SUCCESS_MESSAGE)));
    		}
    		if (isEmptyString($this->_getParam(URL_SUCCESS))) {
    			// add the id of the new object created which is encoded 
    			$response_params['id'] = encode($new_object->getID()); 
	    		$this->_helper->redirector->gotoSimple('view', # the action 
	    							    $this->getRequest()->getControllerName(), # the current controller
	    								$this->getRequest()->getModuleName(), # the current module,     															
	    	                             $response_params # the parameters for the response
	    	                             );
	    	    return false; 
    		} else {
    			$url = decode($this->_getParam(URL_SUCCESS));
    			// check if the last character is a / then add it
    			if (substr($url, -1) != "/") {
    				 // add the slash
    				 $url.= "/"; 
    			}
    			// add the ID parameter
    			$url.= "id/".encode($new_object->getID()); 
    			$this->_helper->redirector->gotoUrl($url, $response_params); 
    			return false; 
    		}
    	} catch (Exception $e) {
    		$session->setVar(FORM_VALUES, $this->_getAllParams());
    		$session->setVar(ERROR_MESSAGE, $e->getMessage()); 
    		$this->_logger->err("Saving Error ".$e->getMessage());
    		// return to the create page
    		if (isEmptyString($this->_getParam(URL_FAILURE))) {
    			$this->_helper->redirector->gotoSimple('index', # the action 
	    							    $this->getRequest()->getControllerName(), # the current controller
	    								$this->getRequest()->getModuleName(), # the current module, 
	    								$response_params 
    	                             );
    	        return false; 
    		} else {
    			$this->_helper->redirector->gotoUrl(decode($this->_getParam(URL_FAILURE)), $response_params); 
    			return false; 
    		}
    	}
    }

    public function editAction() {
    	$this->_setParam("action", ACTION_EDIT); 
    	$this->createAction();
    }
    
    public function deleteAction() {
    	$this->_setParam("action", ACTION_DELETE); 
    	$this->createAction();
    }
    
	public function approveAction() {
    	$this->_setParam("action", ACTION_APPROVE); 
    	$this->createAction();
    }
    
	public function rejectAction() {
    	$this->_setParam("action", ACTION_APPROVE); 
    	$this->createAction();
    }
    
    public function listAction() {
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
    /**
     * Redirect list searches to maintain the urls as per zend format 
     */
    public function listsearchAction() {
    	$this->_helper->redirector->gotoSimple(ACTION_LIST, $this->getRequest()->getControllerName(), 
    											$this->getRequest()->getModuleName(),
    											array_remove_empty(array_merge_maintain_keys($this->_getAllParams(), $this->getRequest()->getQuery())));
    }
    public function viewAction() {
    	
    }
    
    public function returntolistAction(){
    	$this->_helper->redirector->gotoSimple(ACTION_LIST, $this->getRequest()->getControllerName(), 
    											$this->getRequest()->getModuleName(), 
    											array_remove_empty(array_merge_maintain_keys($this->_getAllParams(), $this->getRequest()->getQuery())));    
    }
public function newAction(){
    	$this->_helper->redirector->gotoSimple(ACTION_INDEX, $this->getRequest()->getControllerName(), 
    											$this->getRequest()->getModuleName(), 
    											array_remove_empty(array_merge_maintain_keys($this->_getAllParams(), $this->getRequest()->getQuery())));  
    }	
    
    function overviewAction() {
    	
    }
	
    public function exportAction() {
    	
    }
	/**
     * Notify all listeners of the event, through the event dispatcher instance for the class. This is just a convenience method to
     * avoid accessing the event dispatcher directly
     *
     * @param sfEvent $event The event that has occured
     */
    function notify($event) {
    	$this->_eventdispatcher->notify($event); 
    }
    
    function selectchainAction() {
	    $select_type = $this->_getParam(SELECT_CHAIN_TYPE); 
		
		switch ($select_type) { 		
			default: 
				# get all the villages in a parishes			
				echo '';			
				break;	
		}
		
		// disable rendering of the view and layout so that we can just echo the AJAX output 
	    $this->_helper->layout->disableLayout();
	    $this->_helper->viewRenderer->setNoRender(TRUE);
	}

	/**
     * Action to download details into MS Excel
    */
    public function exceldownloadAction()  {
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
		
		$session = SessionWrapper::getInstance();
		
		# the coluumns that have numbers, these have to be formatted differently from the rest of the
		# columns
		$number_column_array = explode(",", $this->_getParam(EXPORT_NUMBER_COLUMN_LIST));
		
		$xml = new ExcelXML();
		// the number of columns to ignore in the query, these are usually ids
		$xml->setStartingColumn(trim($this->_getParam(EXPORT_IGNORE_COLUMN_COUNT)));
		echo $xml->generateXMLFromQuery($session->getVar(CURRENT_RESULTS_QUERY));
    }
	/**
     * Action to download details into MS Excel
    */
    public function printerfriendlyAction()  {
    
    }
    /**
     * Clear user specific cache items on expiry of the session or logout of the user
     *
     */
    public function clearUserCache() {
    	$session = SessionWrapper::getInstance(); 
    	
    	// clear the acl instance for the user
        $aclkey = "acl".$session->getVar('userid'); 
        $cache = Zend_Registry::get('cache');
        $cache->remove($aclkey); 
    }
    /**
     * Clear the user session and any cache files 
     *
     */
    function clearSession() {
    	// clear user specific cache
    	$this->clearUserCache();
    	
        // clear the session
        $session = SessionWrapper::getInstance(); 
        $session->clearSession();
    }
    
    /**
     * Pre-processing for all actions
     *
     * - Disable the layout when displaying printer friendly pages 
     *
     */
    function preDispatch(){
    	// disable rendering of the layout so that we can just echo the AJAX output
    	if(!isEmptyString($this->_getParam(PAGE_CONTENTS_ONLY))) { 
    		$this->_helper->layout->disableLayout();
    	}
    } 
}