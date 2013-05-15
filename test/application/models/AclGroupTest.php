<?php 

require_once 'BaseModelTestCase.php';

class AclGroupTest extends BaseModelTestCase {
	public $empty_attribute_value_list = array(
			"id"=>"",		
			"name" => "",		
			"description" => "",		
			"permissions" => "",
			"createdby"=>""
		);
	
	public  $test_data = array(
			"id"=>"",
			"name" => "group one",		
			"description" => "this are notes",		
			"permissions" => array(
								array("resourceid"=>"3", "create"=>"0", "edit"=>"1", "view"=>"0", "delete"=>"1", "approve"=>"0")
							),
			"createdby"=>"1"
		);
	
	function testValidate(){
		# Validation must pass because there is data
		$group = new AclGroup;
		$group->processPost($this->test_data);
		# Passes because required fields are specified
		$this->assertTrue($group->isValid());
		$this->assertEquals("", $group->getErrorStackAsString());
		
		# check that the lookup name must have a value
		$group = new AclGroup;
		$data_without_name = $this->test_data;
		$data_without_name['name'] = "";
		$group->processPost($data_without_name);
		$this->assertRegExp("/Name/i", $group->getErrorStackAsString());
		
		# check that permissions have been set for the group
		$group = new AclGroup;
		$data_without_permissions = $this->test_data;
		$data_without_permissions['permissions'] = "";
		$group->processPost($data_without_permissions);
		$this->assertRegExp("/Permission/i", $group->getErrorStackAsString());
	}
	
	function testSave(){
		$group = new AclGroup;
		$data_with_group = array(			
			"name" => "group two for saving",		
			"description" => "this are notes",		
			"permissions" => array(
								array("resourceid"=>"2", "create"=>"0", "edit"=>"1", "view"=>"0", "delete"=>"1", "approve"=>"0"),
								array("resourceid"=>"4", "create"=>"0", "edit"=>"1", "view"=>"0", "delete"=>"1", "approve"=>"0")
							),
			"createdby"=>"1"
		);
				
		$group->processPost($data_with_group);		
		# check that there are no errors in the object. Fail if error in process post
		$this->assertEquals("", $group->getErrorStackAsString(), "Error validating new group ".$group->getErrorStackAsString());
		
		$group->save();
		
		# check that there are no errors and that the ID is a number
		$this->assertFalse($group->hasError());
		$this->assertTrue(is_numeric($group->getID()));
		$this->assertEquals("", $group->getErrorStackAsString());
		
		# load the data from the database
		$query = "SELECT * FROM aclgroup WHERE id = '".$group->getID()."'";
		$saved_group = $this->conn->fetchRow($query);
		
		# load the permissions from the permissions table for the cuurent id
		$permissionsquery = "SELECT aclpermission.resourceid, aclpermission.`create`, aclpermission.edit, aclpermission.approve, aclpermission.view, aclpermission.`delete` FROM aclpermission WHERE groupid = '".$group->getID()."'";
		$saved_group['permissions'] = $this->conn->fetchAll($permissionsquery);
		
		# compare the data
		$this->compareAcutalandExpectedDataArrays($data_with_group, $saved_group);		

		# cleanup
		$this->conn->exec("DELETE FROM aclgroup WHERE id = '".$group->getID()."'");
		$this->conn->exec("DELETE FROM aclpermission WHERE groupid = '".$group->getID()."'");
	}	
	/*
	function testUpdate() {
		# cleanup
		$this->conn->exec("DELETE FROM aclgroup WHERE id = '25'");
		$this->conn->exec("DELETE FROM aclpermission WHERE groupid = '25'");
		
		# records for updating
		$this->conn->exec("INSERT INTO aclgroup (id, name, description) VALUES ('25','group three','notes for the group')");
		$this->conn->exec("INSERT INTO aclpermission (id, groupid, resourceid) VALUES ('1000','25','2')");
		
		$group = new AclGroup;
		$group = $group->populate(25);
		
		# Enter new information for the fields
		$new_data = array(
			"name" => "group four",
			"description" => "this is group four",
			"permissions" => array(
								md5(1000) => array("id"=>"1000", "resourceid"=>"2", "create"=>"1", "edit"=>"1", "view"=>"1", "delete"=>"1", "archive"=>"1", "approve"=>"0"),
								md5(1005) => array("resourceid"=>"4", "create"=>"1", "edit"=>"1", "view"=>"1", "delete"=>"1", "archive"=>"1", "approve"=>"0"),
							),			
		);
		
		$group->processPost($new_data);		
		# verify that there are no validation errors
		$this->assertFalse($group->hasError());
		$this->assertEquals("", $group->getErrorStackAsString());
		
		$group->save();	
		
		# check that there are no errors  
		$this->assertFalse($group->hasError());
		$this->assertEquals("", $group->getErrorStackAsString());
		
		# load the data from the database
		$query = "SELECT * FROM aclgroup WHERE id = '".$group->getID()."'";
		$saved_group = $this->conn->fetchRow($query);
		
		# load the permissions from the permissions table for the cuurent id
		$permissionsquery = "SELECT aclpermission.resourceid, aclpermission.`create`, aclpermission.edit, aclpermission.approve, aclpermission.view, aclpermission.`delete` FROM aclpermission WHERE groupid = '".$group->getID()."'";
		$saved_group['permissions'] = $this->conn->fetchAll($permissionsquery);
		
		# compare the data
		$this->compareAcutalandExpectedDataArrays($new_data, $saved_group);
		
		# cleanup
		$this->conn->exec("DELETE FROM aclgroup WHERE id = '25'");
		$this->conn->exec("DELETE FROM aclpermission WHERE groupid = '25'");
	}

	function testDuplicateSave() {
		# cleanup
		$this->conn->exec("DELETE FROM aclgroup WHERE id = '19'");
		$this->conn->exec("DELETE FROM aclpermission WHERE groupid = '19'");
		
		# records for updating
		$this->conn->exec("INSERT INTO aclgroup (id, name, description) VALUES ('19','group four','notes for the group')");
		$this->conn->exec("INSERT INTO `aclpermission` (`groupid`,`resourceid`,`create`,`edit`,`approve`,`view`,`delete`) VALUES ('19','2','0','1','1','1','0')");

		$permissions_query = "SELECT aclpermission.resourceid, aclpermission.`create`, aclpermission.edit, aclpermission.approve, aclpermission.view, aclpermission.`delete` FROM aclpermission WHERE groupid = '19'"; 
		$permissions_before_duplicate_save = $this->conn->fetchAll($permissions_query); 
		
		# Enter new information for the fields having unique field(name) with a duplicate
		$duplicate_data = array(								
			"name" => "group four"		
		);
		
		# Attempt to save duplicate data
		$group = new AclGroup;
		$group->processPost($duplicate_data);
		# check that there are errors in the object. Must fail because there are errors
		$this->assertTrue($group->hasError());
		$this->assertRegExp("/Name/i", $group->getErrorStackAsString());
		
		$group->clearErrorStack();
		try {
			$group->save();
			$this->assertTrue(false, "A duplicate record has been saved");
		} catch (Exception $e) {
			$this->assertTrue(true, "Save failed because of a duplicate record ".$group->getErrorStackAsString());
		}
		
		# check that there are errors due to duplicate email
		$this->assertTrue($group->hasError());
		$this->assertFalse(is_numeric($group->getID()));
		$this->assertRegExp("/already exists/i", $group->getErrorStackAsString());
		
		# load the permissions from the permissions table for the current id
		$permissions_after_duplicate_save = $this->conn->fetchAll($permissions_query); 
		$this->assertEquals(array(), array_diff($permissions_before_duplicate_save, $permissions_after_duplicate_save));		
		
		# cleanup
		$this->conn->exec("DELETE FROM aclgroup WHERE id = '19'");
		$this->conn->exec("DELETE FROM aclpermission WHERE groupid = '19'");
	}
	
	
	function testDuplicateUpdate() {
		# cleanup	
		$this->conn->exec("DELETE FROM aclgroup WHERE id IN('50', '51')");
		$this->conn->exec("DELETE FROM aclpermission WHERE groupid IN('50', '51')");
		
		# insert two records and attempt to update one violating a unique index of the other		
		$this->conn->exec("INSERT INTO aclgroup (id, name, description) VALUES ('50','old group','notes for the group'), ('51','new group','notes for the group')");
		$this->conn->exec("INSERT INTO aclpermission (id, groupid, resourceid) VALUES ('500','50','1'), ('501','51','1') ");
		
		$group_before_update_50 = $this->conn->fetchAll("SELECT * FROM aclgroup WHERE id ='50'");
		$group_before_update_50['permissions'] = $this->conn->fetchAll("SELECT * FROM aclgroup WHERE id ='50'");
		$group_before_update_51 = $this->conn->fetchAll("SELECT * FROM aclgroup WHERE id ='51'");
		$group_before_update_51['permissions'] = $this->conn->fetchAll("SELECT * FROM aclgroup WHERE id ='51'");
		
		$group = new AclGroup;
		$group = Doctrine::getTable($group->getTableName())->find(50);
		
		#new data to update having a duplicate name to updated
		$duplicate_update_data = array(	
			"name" => "new group"
		);
		
		$group->processPost($duplicate_update_data);
		# verify that there are errors for duplicate email
		$this->assertTrue($group->hasError());
		$this->assertRegExp("/Name/i", $group->getErrorStackAsString());
		
		try {
			$group->save();
			$this->assertTrue(false, "A duplicate record has been updated");
		} catch (Exception $e) {
			$this->assertTrue(true, "Update failed because of a duplicate record ".$group->getErrorStackAsString());
		}
		# verify that there are validation errors
		$this->assertTrue($group->hasError());
		$this->assertRegExp("/already exists/i", $group->getErrorStackAsString());
		
		# load the data that failed to update from the database
		$group_after_failed_update_50 = $this->conn->fetchAll("SELECT * FROM aclgroup WHERE id ='50'");
		$group_after_failed_update_50['permissions'] = $this->conn->fetchAll("SELECT * FROM aclgroup WHERE id ='50'"); 
		$group_after_failed_update_51 = $this->conn->fetchAll("SELECT * FROM aclgroup WHERE id ='51'");
		$group_after_failed_update_51['permissions'] = $this->conn->fetchAll("SELECT * FROM aclgroup WHERE id ='51'");
		
		$this->compareAcutalandExpectedDataArrays($group_before_update_50, $group_after_failed_update_50);
		$this->compareAcutalandExpectedDataArrays($group_before_update_51, $group_after_failed_update_51);
		
		# cleanup	
		$this->conn->exec("DELETE FROM aclgroup WHERE id IN('50', '51')");
		$this->conn->exec("DELETE FROM aclpermission WHERE groupid IN('50', '51')");
	}*/
}
?>