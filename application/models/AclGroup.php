<?php
/**
 * A collection of a set of permissions resources
 * 
 */
class AclGroup extends BaseEntity implements Zend_Acl_Role_Interface {
	
	public function setTableDefinition() {
		parent::setTableDefinition();
		$this->setTableName('aclgroup');
		$this->hasColumn('name', 'string', 50, array('unique' => true, 'notnull' => true, 'notblank' => true, 'unique' => true));
		$this->hasColumn('description', 'string', 255, array('notnull' => true, 'notblank' => true));
	}
	/**
	 * Contructor method for custom functionality - add the fields to be marked as dates
	 */
	public function construct() {
		parent::construct();
		
		// set the custom error messages
       	$this->addCustomErrorMessages(array(
       									"name.unique" => "The Group&nbsp;<span id='uniquegroupname'></span>&nbsp;already exists.",
       									"name.notblank" => "Please enter the Group Name",
       									"description.notblank" => "Please enter the Description"
       								)); 
	}
	/**
	 * Setup the permissions for the ACLGroup class
	 */
	public function setUp() {
		parent::setUp();
		// the permissions for the group
		$this->hasMany('AclPermission as permissions', 
						array('local' => 'id', 
								'foreign' => 'groupid',
						));
		$this->hasMany('UserGroup as usergroups',
							array('local' => 'id',
									'foreign' => 'groupid',
                                    )
						);
	}
	
	function validate() {
		# check that the permissions are specfied
		if (($this->getPermissions()->count() == 0)) {
			$this->getErrorStack()->add('permissions', 'Please select at least one set of permissions for the group');
		}
	}
	
	/**
	 * @see Zend_Acl_Role_Interface::getRoleId()
	 *
	 * @return string The ID of the role
	 */
	public function getRoleId() {
		return $this->getID();
	}
	
	/**
	 * Get the AclPermission instance for the specified resource. 
	 * If the permission does not exist, then a new empty ACLPermission instance is returned
	 *
	 * @param Integer $resourceid The ID of the resource
	 * @return AclPermission instance for the resource
	 */
	function getPermissionForResource($resourceid) {
		$thepermissions = $this->getPermissions();
		foreach ($thepermissions as $aperm ) {
			// check if we are dealing with the specified resource
			if ($aperm->getResourceID() == $resourceid) {
				return $aperm; 
			}
		}
		return new AclPermission(); 
	}
	/**
	 * Clean out the data received from the screen by:
	 * - remove empty/blank groupid  - the groupid is not required and therefore is an empty value is maintained will cause an out of range exception 
	 *
	 * @param Array $post_array
	 */
	function processPost($post_array) {
		// check if the groupid is blank then remove it 
		if (array_key_exists('permissions', $post_array)) {
			if (is_array($post_array['permissions'])) { 
				foreach($post_array['permissions'] as $key => $value) {
					if(array_key_exists('groupid', $value)) {
						if(isEmptyString($value['groupid'])) {
							unset($post_array['permissions'][$key]['groupid']); 
						}
					}
					if(isArrayKeyAnEmptyString('create', $value)){
						$post_array['permissions'][$key]['create'] = "0";
					}
					if(isArrayKeyAnEmptyString('edit', $value)){
						$post_array['permissions'][$key]['edit'] = "0";
					}
					if(isArrayKeyAnEmptyString('view', $value)){
						$post_array['permissions'][$key]['view'] = "0";
					}
					if(isArrayKeyAnEmptyString('list', $value)){
						$post_array['permissions'][$key]['list'] = "0";
					}
					if(isArrayKeyAnEmptyString('delete', $value)){
						$post_array['permissions'][$key]['delete'] = "0";
					}
					if(isArrayKeyAnEmptyString('approve', $value)){
						$post_array['permissions'][$key]['approve'] = "0";
					}
					if(isArrayKeyAnEmptyString('export', $value)){
						$post_array['permissions'][$key]['export'] = "0";
					}	
				} // end loop through permissions to unset empty groupids 
			} 
		} // end check if permissions exist 
		// now process the data
		// debugMessage($post_array);
		parent::processPost($post_array); 
	}
}
?>