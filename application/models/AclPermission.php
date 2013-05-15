<?php

/**
 * Access permissions for a group on a resource
 */
class AclPermission extends BaseEntity   {
	
	public function setTableDefinition() {
		parent::setTableDefinition();
        $this->setTableName('aclpermission');
        $this->hasColumn('groupid', 'integer', null, array('notnull' => true, 'notblank' => true));
        $this->hasColumn('resourceid', 'integer', null, array('notnull' => true, 'notblank' => true));
        $this->hasColumn('create', 'enum', null, array('values' => array(0 => '0', 1 => '1'), 'default' => '0'));
        $this->hasColumn('edit', 'enum', null, array('values' => array(0 => '0', 1 => '1'), 'default' => '0'));
        $this->hasColumn('approve', 'enum', null, array('values' => array(0 => '0', 1 => '1'), 'default' => '0'));
        $this->hasColumn('view', 'enum', null, array('values' => array(0 => '0', 1 => '1'), 'default' => '0'));
		$this->hasColumn('list', 'enum', null, array('values' => array(0 => '0', 1 => '1'), 'default' => '0'));
        $this->hasColumn('delete', 'enum', null, array('values' => array(0 => '0', 1 => '1'), 'default' => '0'));
        $this->hasColumn('export', 'enum', null, array('values' => array(0 => '0', 1 => '1'), 'default' => '0'));
        $this->hasColumn('acclist', 'enum', null, array('values' => array(0 => '0', 1 => '1'), 'default' => '0'));
    }
	/**
	 * Contructor method for custom functionality - add the fields to be marked as dates
	 */
	public function construct() {
		parent::construct();
		
		// set the custom error messages
       	$this->addCustomErrorMessages(array(
       									"groupid.unique" => $this->translate->_("permission_groupid_unique_error"),
       									"resourceid.unique" => $this->translate->_("permission_resourceid_unique_error"),
       									"groupid.notblank" => $this->translate->_("permission_groupid_error"),
       									"resourceid.notblank" => $this->translate->_("permission_resourceid_error")
       								)); 
		
	}
	
    function setUp() {
    	parent::setUp();
    	// foreign key for the group
    	$this->hasOne('AclGroup as group', array(
							'local' => 'groupid',
							'foreign' => 'id')
					);
		$this->hasOne('AclResource as resource', array(
							'local' => 'resourceid',
							'foreign' => 'id')
					);
    }
    /**
     * Return the permission for the specified action
     *
     * @param String $action The action for which the permission is required
     * 
     * @return 1 if the action can be executed on the resource, and 0 if the action cannot be executed on the resource
     */
    function checkPermissionForAction($action) {
    	return $this->_get($action); 
    }
	/**
	 * Return the checked status for a checkbox signifying whether an action is allowed or denied on a resource.
	 *
	 * @param String $action The action to be executed on the resource
	 * 
	 * @return String the checked attribute value for the checkbox
	 */
	function getCheckedStatus($action) {
		return getCheckedAttribute($this->checkPermissionForAction($action));
	}
}
?>