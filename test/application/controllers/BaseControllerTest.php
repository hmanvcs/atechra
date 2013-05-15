<?php
/**
 * IndexController test case.
 */
class BaseControllerTest extends Zend_Test_PHPUnit_ControllerTestCase   {
	
	function setUp() {
		 $this->getFrontController()->setControllerDirectory(APPLICATION_PATH . '/controllers');
	}
	/**
	 * Dummy test method which always passws for the controller class 
	 */
	 public function testDummy() {
		$this->assertModule($this->getRequest()->getModuleName());
	}
	
	function tearDown() {
		 $this->resetRequest()
             ->resetResponse(); 
	}
}

