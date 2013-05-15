<?php

require_once 'doctrine/doctrine.compiled.php';

class BaseModelTestCase extends PHPUnit_Framework_TestCase {
	var $conn = null;
	var $config = ""; 
		
	function setUp() {
		$this->conn = Doctrine_Manager::connection();
		$this->config = Zend_Registry::get('config'); 
		
	}
	
	/**
	 * Compares the expected data array with the actual data array from executing the test case. If any of the array values is an array then the comparison is done usign an array_diff
	 *
	 * @param Array $expected The expected data in an array
	 * @param Array $actual The actual data in an array
	 */
	function compareAcutalandExpectedDataArrays($expected, $actual) {
		foreach($expected as $key => $value){
			if (is_array($value)) {
				$this->assertEquals(array(), array_diff($value, $actual[$key])); 
			} else {
				$this->assertEquals($expected[$key], $actual[$key], "field ".$key." expected: ".$expected[$key]." actual ".$actual[$key]);
			}
		}
	}
	
}
?>