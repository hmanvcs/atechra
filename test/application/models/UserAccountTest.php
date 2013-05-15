<?php 

class UserAccountTest extends BaseModelTestCase  {
	public $empty_attribute_value_list = array(
			"firstname" => "",
			"lastname" => "",
			"othername" => "",
			"email" => "",
			"phonenumber" => "",
			"phonenumber2" => "",
			"password" => "",
			"notes" => "",
			"securityquestion" => "",
			"securityanswer" => "",
			"changepassword" => "",
			"activationkey" => "",
			"isactive" => "",
			"nextpasswordchangedate" => "",
			"lastlogindate" => "",
			"loginretries" => "",
			"createdby" => "",
			"usergroups_groupid" => array()
		);
	
	public $test_data = array(
			"id" => "",			
			"firstname" => "John",
			"lastname" => "Doe",
			"othername" => "",
			"email" => "johndoe@devmail.infomacorp.com",
			"phonenumber" => "0782698451",
			"phonenumber2" => "",
			"password" => "5baa61e4c9b93f3f0682250b6cf8331b7ee68fd8",
			"notes" => "xxx",
			"securityquestion" => "",
			"securityanswer" => "",
			"changepassword" => "Y",
			"activationkey" => "dfsfds",
			"isactive" => "Y",
			"nextpasswordchangedate" => "",
			"lastlogindate" => "",
			"loginretries" => "",
			"createdby" => "",
			"usergroups_groupid" => array("1", "3"),
		);
		
	public $update_data = array(
		);
	
  function testValidate(){				
		# check that the firstname must have a value
		$useraccount = new UserAccount();
		$data_without_firstname = $this->test_data;		
		$data_without_firstname['firstname'] = "";
		$useraccount->processPost($data_without_firstname);
		$this->assertRegExp("/First Name/i", $useraccount->getErrorStackAsString());
		
		# check that the lastname must have a value
		$useraccount = new UserAccount();
		$data_without_lastname = $this->test_data;
		$data_without_lastname['lastname'] = "";
		$useraccount->processPost($data_without_lastname);
		$this->assertRegExp("/last name/i", $useraccount->getErrorStackAsString());
		
		# check that the email must have a value
		 $useraccount = new UserAccount();
		$data_without_email = $this->test_data;
		$data_without_email['email'] = "";
		$useraccount->processPost($data_without_email);
		$this->assertRegExp("/email/i", $useraccount->getErrorStackAsString());
		
		# check that atleast one group has been selected
		$useraccount = new UserAccount();
		$data_without_group = $this->test_data;
		$data_without_group['usergroups_groupid'] = array();
		$useraccount->processPost($data_without_group);		
		$this->assertRegExp("/Group/i", $useraccount->getErrorStackAsString());
		
		# check that the email is unique 
		$useraccount = new UserAccount();
		$data_with_duplicate_email = $this->test_data;
		$data_with_duplicate_email['email'] = "elawrence@devmail.infomacorp.com";
		$useraccount->processPost($data_with_duplicate_email);		
		$this->assertRegExp("/already exists/i", $useraccount->getErrorStackAsString());
	}
	
	function testSave() {
		// cleanup the user account table
		$this->conn->exec("DELETE FROM useraccount WHERE email = 'johndoe@devmail.infomacorp.com'");
		$useraccount = new UserAccount();
		
		$data = array(
			"firstname" => "John",
			"lastname" => "Doe",
			"othername" => "",
			"email" => "johndoe@devmail.infomacorp.com",
			"phonenumber" => "0782698451",
			"phonenumber2" => "",
			"password" => "5baa61e4c9b93f3f0682250b6cf8331b7ee68fd8",
			"notes" => "xxx",
			"securityquestion" => "",
			"securityanswer" => "",
			"changepassword" => "Y",
			"activationkey" => "",
			"isactive" => "Y",
			"nextpasswordchangedate" => date('Y-m-d', strtotime(" + ".$this->config->password->daystoexpire." days")),
			"lastlogindate" => "",
			"loginretries" => "",
			"createdby" => "1",
			"usergroups_groupid" => array("2")
		
		);
		
		$useraccount->processPost($data);		
		# check that there are no errors in the object. Fail if error in process post
		$this->assertEquals("", $useraccount->getErrorStackAsString());
		
		$useraccount->save();
		
		# check that there are no errors and that the ID is a number
		$this->assertEquals("", $useraccount->getErrorStackAsString());
		
		# load the saved user account data
		$query = "SELECT u.*, GROUP_CONCAT(g.id) as usergroups  FROM useraccount u, aclusergroup acg, aclgroup g  WHERE u.id = '".$useraccount->getID()."' AND acg.userid = u.id AND acg.groupid = g.id GROUP BY u.id";
		$saved_useraccount = $this->conn->fetchRow($query);
		
		$saved_useraccount['usergroups_groupid'] = explode("," ,$saved_useraccount['usergroups']);
		# set the password to the hashed value
		$data['password'] = sha1($data['password']);
		$this->compareAcutalandExpectedDataArrays($data, $saved_useraccount);
				
		# cleanup
		$this->conn->exec("DELETE FROM useraccount WHERE id = '".$useraccount->getID()."' OR email = 'johndoe@devmail.infomacorp.com'");		
	}
	
	function testUpdate(){		
		// delete
		$this->conn->exec("DELETE FROM useraccount WHERE id = '100' OR email = 'andrew.stuart@devmail.infomacorp.com'");
		
		// insert new row in to user account
		$this->conn->exec("INSERT INTO useraccount (id, firstname, lastname, email, nextpasswordchangedate, createdby, datecreated) 
		VALUES (100, 'Andrew', 'Stuart', 'andrew.stuart@devmail.infomacorp.com', '2011-04-01', '2', NOW())"); 	
		
		// insert new row in to aclusergroup 
		$this->conn->exec("INSERT INTO aclusergroup (userid, groupid) VALUES ('100', '2')"); 		
		
		$useraccount = new UserAccount();
		$useraccount->populate(100);
		
		$data = array(
			"id" => "100",
			"firstname" => "Jeffrey",
			"lastname" => "Smith",			
			"email" => "jsmith@devmail.infomacorp.com",
			"usergroups_groupid" => array("1")
		); 
	
		# add the new data
		$useraccount->processPost($data);		
		# check that there are no errors in the object. Fail if error in process post
		$this->assertEquals("", $useraccount->getErrorStackAsString());
		
		# update
		$useraccount->save();		
		# check that there are no errors in the object. Fail if error in process post
		$this->assertEquals("", $useraccount->getErrorStackAsString());		
				
		# load the saved user account data
		$query = "SELECT u.*, GROUP_CONCAT(g.id) as usergroups  FROM useraccount u, aclusergroup acg, aclgroup g  WHERE u.id = '".$useraccount->getID()."' AND acg.userid = u.id AND acg.groupid = g.id GROUP BY u.id";
		$saved_useraccount = $this->conn->fetchRow($query);
		 
		$saved_useraccount['usergroups_groupid'] = explode("," ,$saved_useraccount['usergroups']);
		// set the password to the hashed value
		$this->compareAcutalandExpectedDataArrays($data, $saved_useraccount);
		
		// delete
		$this->conn->exec("DELETE FROM useraccount WHERE id = '100' OR  email = 'andrew.stuart@devmail.infomacorp.com'");
	}
	
	function testDuplicateSave() {
		// delete
		$this->conn->exec("DELETE FROM useraccount WHERE id = '100' OR email = 'andrew.stuart@devmail.infomacorp.com'");
		
		// insert new row in to user account
		$this->conn->exec("INSERT INTO useraccount (id, firstname, lastname, email, nextpasswordchangedate, createdby, datecreated) 
		VALUES (100, 'Andrew', 'Stuart', 'andrew.stuart@devmail.infomacorp.com', '2011-04-01', '2', NOW())"); 	
		
		// insert new row in to aclusergroup 
		$this->conn->exec("INSERT INTO aclusergroup (userid, groupid) VALUES ('100', '2')"); 		
		
		# Enter new information for the fields having unique field(username or email) with a duplicate
		$duplicate_data = array(								
			"firstname" => "beyonce",
			"lastname" => "knowles",
			"email" => "andrew.stuart@devmail.infomacorp.com",
			"password" => "b4656hgf",
			"phonenumber" => "(238) 658-9632", 
			"usergroups_groupid" => array("2")
		);
		
		# Attempt to save duplicate data
		$useraccount = new UserAccount();
		$useraccount->processPost($duplicate_data);
		# check that there are errors in the object. Must fail because there are errors
		//$this->assertTrue($useraccount->hasError());
		$this->assertRegExp("/email/i", $useraccount->getErrorStackAsString());
		
		try {
			$useraccount->save();
			$this->assertTrue(false, "A record with a duplicate email address");
		} catch (Exception $e) {
			$this->assertTrue(true, "Save failed because of a duplicate record ".$useraccount->getErrorStackAsString());
		}
		
		// delete
		$this->conn->exec("DELETE FROM useraccount WHERE id = '100' OR email = 'andrew.stuart@devmail.infomacorp.com'");
	}
	
	function testDuplicateUpdate(){
		// delete
		$this->conn->exec("DELETE FROM useraccount WHERE id IN('100','200') OR email IN ('jreeves@devmail.infomacorp.com', 'krogers@devmail.infomacorp.com')");	
		
		// insert new row in to user account
		$this->conn->exec("INSERT INTO useraccount (id, firstname, lastname, email, password, 
		isactive, createdby, datecreated) VALUES (100, 'Jim', 'Reeves', 'jreeves@devmail.infomacorp.com', SHA1('555555'), 'Y', 1, NOW()),
		(200, 'Ken', 'Rogers', 'krogers@devmail.infomacorp.com', SHA1('666666'), 'N', 1, NOW())"); 	
		
		// insert new row in to aclusergroup 
		$this->conn->exec("INSERT INTO aclusergroup (userid, groupid) VALUES ('100', '2'), ('200', '2')");
				
		$useraccount_before_update_100 = $this->conn->fetchRow("SELECT * FROM useraccount WHERE id ='100'");
		$useraccount_before_update_200 = $this->conn->fetchRow("SELECT * FROM useraccount WHERE id ='200'");
		
		$useraccount = new UserAccount();
		$useraccount->populate(100);	
		
		#new data to update having a duplicate email to updated
		$duplicate_update_data = array(	
			"email" => "krogers@devmail.infomacorp.com",
		);
		
		$useraccount->processPost($duplicate_update_data);
		# verify that there are errors for duplicate email
		$this->assertRegExp("/email/i", $useraccount->getErrorStackAsString());
		
		try {
			$useraccount->save();
			$this->assertTrue(false, "A duplicate record has been saved");
		} catch (Exception $e) {
			$this->assertTrue(true, "Save failed because of a duplicate record ".$useraccount->getErrorStackAsString());
		}
		
		# load the data that failed to update from the database
		$useraccount_after_failed_update_100 = $this->conn->fetchRow("SELECT *FROM useraccount WHERE id ='100'");
		$useraccount_after_failed_update_200 = $this->conn->fetchRow("SELECT *FROM useraccount WHERE id ='200'");
		
		$this->compareAcutalandExpectedDataArrays($useraccount_before_update_100, $useraccount_after_failed_update_100);
		$this->compareAcutalandExpectedDataArrays($useraccount_before_update_200, $useraccount_after_failed_update_200);
		
		// delete
		$this->conn->exec("DELETE FROM useraccount WHERE id IN('100','200') OR email IN ('jreeves@devmail.infomacorp.com', 'krogers@devmail.infomacorp.com')");		
	}
	
	function testUpdateWithoutChangingPassword() {
		
		// delete
		$this->conn->exec("DELETE FROM useraccount WHERE id = '100' OR email = 'dwilliams@devmail.infomacorp.com'");
		
		// the password
		$password_value = sha1('dwilliams'); 
		// insert new row 
		$this->conn->exec("INSERT INTO useraccount (id, firstname, lastname, email, password, 
		isactive, datecreated, createdby) VALUES (100, 'Andrew', 'Stuart',
		'dwilliams@devmail.infomacorp.com',  '".$password_value."', 'Y', NOW(), '1' )"); 	
		
		$saved_password_after_update = $this->conn->fetchRow("SELECT * FROM useraccount WHERE id = '100'");
		// insert new row in to aclusergroup 
		$this->conn->exec("INSERT INTO aclusergroup (userid, groupid) VALUES ('100', '2'), ('100', '1')");
				
		# populate using the id that has been created
		$useraccount = new UserAccount();
		$useraccount->populate(100);
		
		$no_password_data = array(
			"firstname" => "Antwane",
			"lastname" => "Fisher",
			"email" => "afisher@devmail.infomacorp.com",
			"phonenumber" => "123456",
			"usergroups_groupid" => array("2","1")
		);

		// set the password to an empty string
		$useraccount->processPost($no_password_data);
		
		# check that there are no errors in the object. Fail if error in process post
		$this->assertEquals("", $useraccount->getErrorStackAsString());
		# update using the class
		$useraccount->save();
		$saved_password_after_update = $this->conn->fetchRow("SELECT * FROM useraccount WHERE id = '100'");
		
		# check that there are no errors in the object. Fail if error in process post
		$this->assertEquals("", $useraccount->getErrorStackAsString());
		
		# get the data from the table and compare it
		# load the data from the database
		$saved_password_after_update = $this->conn->fetchRow("SELECT * FROM useraccount WHERE id = '100'");  
		
		# compare the data
		$this->assertEquals($password_value, $saved_password_after_update['password']);
		
		// delete
		$this->conn->exec("DELETE FROM useraccount WHERE id = '100' OR email = 'dwilliams@devmail.infomacorp.com'");
	}
	
	function testResetPassword() {
		// delete
		$this->conn->exec("DELETE FROM useraccount WHERE id ='100' OR email='3456yh@devmail.infomacorp.com'");
		
		// insert new row 
		$this->conn->exec("INSERT INTO useraccount (id, firstname, lastname, email,  password, 
		isactive, datecreated, createdby, phonenumber) VALUES ('100', 'Andrew', 'Stuart',
		'3456yh@devmail.infomacorp.com',   SHA1('sandrew123'), 'Y', NOW(), '1' , '(906) 442-9056')"); 	
		
		// insert new row in to aclusergroup 
		$this->conn->exec("INSERT INTO aclusergroup (userid, groupid) VALUES ('100', '2'), ('100', '1')");
		
		$saved_password_before_update = $this->conn->fetchRow("SELECT password FROM useraccount WHERE id = '100'"); 
		//$this->dump($saved_password_before_update["password"]);		
		
		# populate using the id that has been created 
		$useraccount = new UserAccount();
		$useraccount->populate(100);
		
		$newpassword = "dianaking123"; 
		
		$useraccount->resetPassword($newpassword);
		# check that there are no errors in the object. Fail if error in process post
		$this->assertEquals("", $useraccount->getErrorStackAsString());
		
		$saved_password_after_update = $this->conn->fetchRow("SELECT password FROM useraccount WHERE id = '100'");
		//$this->dump($saved_password_after_update);
		# compare the data		
		$this->assertEquals(sha1($newpassword), $saved_password_after_update['password']);		
		
		// delete
		$this->conn->exec("DELETE FROM useraccount WHERE id ='100' OR email='3456yh@devmail.infomacorp.com'");
	}
	
	function testchangePasswordWithInvalidOldPassword() {
		// delete
		$this->conn->exec("DELETE FROM useraccount WHERE id = '71' OR email = 'involdp@devmail.infomacorp.com'");
		
		// insert new row 
		$this->conn->exec("INSERT INTO useraccount (id, firstname, lastname, email,  password, 
		isactive, datecreated, createdby) VALUES ('71', 'Andrew', 'Stuart',
		'involdp@devmail.infomacorp.com',   SHA1('kazibwe'), 'Y', NOW(), '1' )"); 	
		
		// insert new row in to aclusergroup 
		$this->conn->exec("INSERT INTO aclusergroup (userid, groupid) VALUES ('71', '2'), ('71', '1')");
		
		$saved_password_before_update = $this->conn->fetchRow("SELECT password FROM useraccount WHERE id = '71'"); 
		# debugMessage("before is ".$saved_password_before_update["password"]);
		
		# populate using the id that has been created 
		$useraccount = new UserAccount();
		$useraccount->populate(71);
		
		$invalidoldpasswordarray = "nakazibwe";
		$newpassword = "newstring";
		$useraccount->changePassword($invalidoldpasswordarray, $newpassword);
		
		# check that there are errors in the object. Fail if error in process post
		$this->assertTrue($useraccount->hasError());
		$this->assertRegExp("/Invalid Current Password/i", $useraccount->getErrorStackAsString());
		
		$saved_password_after_update = $this->conn->fetchRow("SELECT password FROM useraccount WHERE id = '71'");
		// debugMessage("after is ".$saved_password_after_update);
		# compare the data		
		$this->assertEquals($saved_password_before_update["password"], $saved_password_after_update['password']);
		// delete
		$this->conn->exec("DELETE FROM useraccount WHERE id = '71' OR email = 'involdp@devmail.infomacorp.com'");
	}
	
	function testchangePasswordWithValidOldPassword() {
		// delete
		$this->conn->exec("DELETE FROM useraccount WHERE id = '72' OR email = 'psander@devmail.infomacorp.com'");
		
		// insert new row 
		$this->conn->exec("INSERT INTO useraccount (id, firstname, lastname, email,  password, 
		isactive, datecreated, createdby) VALUES ('72', 'Paul', 'Sander',
		'psander@devmail.infomacorp.com',  SHA1('kazibwe'), 'Y', NOW(), '1' )"); 	
		
		// insert new row in to aclusergroup 
		$this->conn->exec("INSERT INTO aclusergroup (userid, groupid) VALUES ('72', '2'), ('72', '1')");
		
		$saved_password_before_update = $this->conn->fetchRow("SELECT password FROM useraccount WHERE id = '72'"); 
		//$this->dump($saved_password_before_update["password"]);
		
		# populate using the id that has been created 
		$useraccount = new UserAccount();
		$useraccount->populate(72);		
		
		$validoldpassword = "kazibwe";
		$newpassword = "newstring";
		$useraccount->changePassword($validoldpassword, $newpassword);
		
		# check that there are no errors in the object. Fail if error in process post
		$this->assertFalse($useraccount->hasError());
		$this->assertEquals("", $useraccount->getErrorStackAsString());
		
		$saved_password_after_update = $this->conn->fetchRow("SELECT password FROM useraccount WHERE id = '72'");
		# compare the data		
		$this->assertEquals(SHA1($newpassword), $saved_password_after_update["password"]);
		
		// delete
		$this->conn->exec("DELETE FROM useraccount WHERE id = '72' OR email = 'psander@devmail.infomacorp.com'");
	}
	
}