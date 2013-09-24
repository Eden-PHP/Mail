<?php //-->
/*
 * This file is part of the Mail package of the Eden PHP Library.
 * (c) 2013-2014 Openovate Labs
 *
 * Copyright and license information can be found at LICENSE
 * distributed with this package.
 */

class Eden_Mail_Tests_Mail_Pop3Test extends \PHPUnit_Framework_TestCase 
{
	public $pop; 
	public function setUp() {
		date_default_timezone_set('GMT');
		$this->pop = eden('mail')->pop3(
			'[YOUR EMAIL]', 
			'cblanquera@openovate.com', 
			'[YOUR PASSWORD]', 995, true);
	}	
	
	public function tearDown() {
		$this->pop->disconnect();
	}
	
	public function testGetEmails() {
		$emails = $this->pop->getEmails();
		
		$this->assertTrue(is_array($emails));
		
		if(count($emails) > 0) {
			$this->assertTrue(isset($emails[0]['subject']));
		}
	}
	
	public function testGetEmailTotal()
	{
		$total = $this->pop->getEmailTotal();
		$this->assertTrue(is_numeric($total));
	}
}