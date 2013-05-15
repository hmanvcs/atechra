<?php 

class CorporateFile extends BaseEntity {
	
    public function setTableDefinition() {
		#add the table definitions from the parent table
		parent::setTableDefinition();
		$this->setTableName('corporatefile');
        
		$this->hasColumn('folderid', 'integer', null, array('notnull' => true, 'notblank' => true));
        $this->hasColumn('filename', 'string', 50, array('notnull' => true, 'notblank' => true));
		$this->hasColumn('title', 'string', 255);
        $this->hasColumn('description', 'string', 500);
        $this->hasColumn('extension', 'string', 4);
        $this->hasColumn('filesize', 'integer', null);
        $this->hasColumn('type', 'integer', null);
        $this->hasColumn('accessflag', 'integer', null, array('default' => '0'));
        $this->hasColumn('allowedlist', 'string', 500);
        $this->hasColumn('deniedlist', 'string', 500);
    }
	/**
    * Contructor method for custom functionality - add the fields to be marked as dates
    */
	public function construct() {
		parent::construct();
       
        // set the custom error messages
        $this->addCustomErrorMessages(array(
       									"filename.notblank" => $this->translate->_("corporatefile_filename_error"),
       									"folderid.notblank" => $this->translate->_("corporatefile_folderid_error"),
        								"filename.length" => sprintf($this->translate->_("corporatefile_filename_length_error"), 50)
       								));
    }
    
    function setUp() {
    	parent::setUp();
    	# relationship to link a document to a matter
    	# Link to the matter
		$this->hasOne('Folder as folder',
						 array(
								'local' => 'folderid',
								'foreign' => 'id'
							)
					); 
    }
	/*
	 * Custom validation
	 */
	function validate() {
		parent::validate();
		
		$conn = Doctrine_Manager::connection();
		
		# Unique file name in same folder
		$unique_query = "SELECT id FROM corporatefile WHERE filename = '".$this->getFileName()."' AND folderid = '".$this->getFolderID()."' AND id <> '".$this->getID()."' ";
		# debugMessage($unique_query);
		$result = $conn->fetchOne($unique_query);
		if(!isEmptyString($result)){ 
			$this->getErrorStack()->add("file.unique",  sprintf($this->translate->_("corporatefile_filename_unique_error_withname"), $this->getFileName()));
		}
	}
	/*
	 * Pre process model data
	 */
	function processPost($formvalues){
		# force setting of default none string column values. enum, int and date 	
		if(isArrayKeyAnEmptyString('type', $formvalues)){
			unset($formvalues['type']); 
		}
		if(isArrayKeyAnEmptyString('accessflag', $formvalues)){
			unset($formvalues['accessflag']); 
		}
		if(!isArrayKeyAnEmptyString('deniedlistids', $formvalues)) {
			$ids = $formvalues['deniedlistids']; 
			// debugMessage($ids);
			$alluserids = getUserIDArray();
			// debugMessage($alluserids);
			$array_contrast = array_diff($alluserids, $ids);
			// debugMessage($array_contrast);
			$typelist = ''; 
			if(count($ids) > 0){
				$typelist = createHTMLCommaListFromArray($ids, ",");
			}
			$formvalues['deniedlist'] = $typelist; 
			# remove the usergroups_groupid array, it will be ignored, but to be on the safe side
			unset($formvalues['deniedlistids']);
			
			$allowedlist = createHTMLCommaListFromArray($array_contrast, ",");
			$formvalues['allowedlist'] = $allowedlist;
			
		} else {
			unset($formvalues['deniedlistids']); 
		}
		// debugMessage($formvalues); exit();
		parent::processPost($formvalues);
	}
	/**
	 * Return multiple files
	 */
	function populateMultiple($idslist) {
		// debugMessage($idslist);
		$q = Doctrine_Query::create()->from("CorporateFile")->where("id IN($idslist)");
		return $q->execute();
	}
	function grantList(){
		$list = '';
		$user_array = array();
		$users = new UserAccount();
		$userlist = $users->populateAll();
		// debugMessage($userlist->toArray());
		foreach ($userlist as $auser){
			if($auser->canViewFilesAndFolders() && !in_array($auser->getID(), $this->getDeniedIDs())){
				$user_array[$auser->getID()] = $auser->getName();
			}
		}
		
		asort($user_array);
		// debugMessage($user_array);
		$list = implode(', ', $user_array);
		return $list;
	}
	# list of allowed userids
	function getAllowedIDs(){
		$dataarray = array();
		if(!isEmptyString($this->getAllowedList())){
			$list_array = explode(',', $this->getAllowedList());
			if(is_array($list_array)){
				$dataarray = $list_array;
			}
		}
		return $dataarray;
	}
	# list of denied userids
	function getDeniedIDs(){
		$dataarray = array();
		if(!isEmptyString($this->getDeniedList())){
			$list_array = explode(',', $this->getDeniedList());
			if(is_array($list_array)){
				$dataarray = $list_array;
			}
		}
		return $dataarray;
	}
	# format the denied users from the comma list
	function getAllowedLabel(){
		$label = '--';
		if(!isEmptyString($this->getAllowedList())){
			$lookup_array = getAllSystemUsers();
			$list_array = explode(',', $this->getAllowedList());
			$list_text_array = array();
			if(count($list_array) > 0){
				foreach ($list_array as $value){
					$list_text_array[$value] = $lookup_array[$value];
				}
				asort($list_text_array);
			}
			$label = createHTMLCommaListFromArray($list_text_array, ", ");
		}
		return $label;
	}
	# format the denied users from the comma list
	function getDeniedLabel(){
		$label = '--';
		if(!isEmptyString($this->getDeniedList())){
			$lookup_array = getAllSystemUsers();
			$list_array = explode(',', $this->getDeniedList());
			// debugMessage($lookup_array);
			// debugMessage($list_array);
			$list_text_array = array();
			if(count($list_array) > 0){
				foreach ($list_array as $value){
					$list_text_array[$value] = $lookup_array[$value];
				}
				asort($list_text_array);
			}
			$label = createHTMLCommaListFromArray($list_text_array, ", ");
		}
		return $label;
	}
	function deniedList(){
		$list = '';
		$user_array = array();
		
		$current_list = $this->getDeniedList();
		$current_list_array = array();
		if(!isEmptyString($current_list)){
			$current_list_array = explode(',', $current_list);
		}
		// debugMessage($current_list_array);
		$new_list_array = $current_list_array;
		
		$users = new UserAccount();
		$userlist = $users->populateAll();
		// debugMessage($userlist->toArray());
		foreach ($userlist as $auser){
			if(!$auser->canViewFilesAndFolders()){
				$user_array[$auser->getID()] = $auser->getName();
				if(!in_array($auser->getID(), $current_list_array)){
					$new_list_array[] = $auser->getID();
				}
			}
			
		}
		// debugMessage($new_list_array);
		# check if any user denied is not on list
		$array_contrast = array_diff($new_list_array, $current_list_array);
		# update folder deny list
		if(count($array_contrast) > 0){
			$this->setDeniedList(implode(',', $new_list_array));
			$this->save();
		}
		
		asort($user_array);
		// debugMessage($user_array);
		$list = implode(', ', $user_array);
		return $this->getDeniedLabel();
	}
	# determine if user is allowed to view
	function isAllowedToView(){
		$allow = false;
		if(!isEmptyString($this->getAllowedList())){
			if(in_array($this->getCreatedBy(), $this->getAllowedIDs())){
				$allow = true;
			}
		} else {
			if($this->getCreator()->canViewFilesAndFolders()){
				$allow = true;
			}
		}
		
		return $allow;
	}
}
?>