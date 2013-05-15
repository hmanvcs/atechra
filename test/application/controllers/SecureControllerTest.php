<?php
/**
 * IndexController test case.
 */
class SecureControllerTest extends BaseControllerTest    {
	
	function setUp() {
		 parent::setUp();
		 // force a login 
		 $this->request->setMethod('POST')
              ->setPost(array(
                  'email' => 'elawrence@devmail.infomacorp.com',
                  'password' => 'password'
              ));
        $this->dispatch('/user/checklogin');
        //$this->assertHeaderRegex('Location', '/dashboard/i');
        // $this->assertRedirectTo('dashboard/index', "The login redirects to ".$this->_response->getBody());
	}
	 
}

