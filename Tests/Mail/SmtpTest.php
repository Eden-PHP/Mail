<?php //-->
/*
 * This file is part of the Mail package of the Eden PHP Library.
 * (c) 2013-2014 Openovate Labs
 *
 * Copyright and license information can be found at LICENSE
 * distributed with this package.
 */

class Eden_Mail_Tests_Mail_SmtpTest extends \PHPUnit_Framework_TestCase {
	public $smtp;
	public function setUp() {
		date_default_timezone_set('GMT');
		$this->smtp = eden('mail')->smtp(
			'pop.gmail.com', 
			'[YOUR EMAIL]', 
			'[YOUR PASSWORD]', 465, true);
	}	
	
	public function tearDown() {
		$this->smtp->disconnect();
	}
	
    public function testAddAttachment()
	{
		$smtp = $this->smtp->addAttachment('test.txt', 'hi', 'text/plain');
		$this->assertInstanceOf('Eden\\Mail\\Smtp', $smtp);
	}
	
	public function testAddBCC()
	{
		$smtp = $this->smtp->addBcc('cblanquera@openovate.com', 'Chris');
		$this->assertInstanceOf('Eden\\Mail\\Smtp', $smtp);
	}
	
	public function testAddCC()
	{
		$smtp = $this->smtp->addCc('cblanquera@openovate.com', 'Chris');
		$this->assertInstanceOf('Eden\\Mail\\Smtp', $smtp);
	}
	
	public function testAddTo()
	{
		$smtp = $this->smtp->addTo('cblanquera@openovate.com', 'Chris');
		$this->assertInstanceOf('Eden\\Mail\\Smtp', $smtp);
	}
	
	public function testSetBody()
	{
		$smtp = $this->smtp->setBody('hi');
		$this->assertInstanceOf('Eden\\Mail\\Smtp', $smtp);
		
		$smtp = $this->smtp->setBody('hi', true);
		$this->assertInstanceOf('Eden\\Mail\\Smtp', $smtp);
	}
	
	public function testSetSubject()
	{
		$smtp = $this->smtp->setSubject('hi');
		$this->assertInstanceOf('Eden\\Mail\\Smtp', $smtp);
	}
	
	public function testReply()
	{
		//$this->smtp
		//	->setSubject('Unit Test')
		//	->setBody('Unit Test')
		//	->addTo('cblanquera@gmail.com')
		//	->reply('someid');
	}
	
	public function testSend()
	{
		//$this->smtp
		//	->setSubject('Unit Test')
		//	->setBody('Unit Test')
		//	->addTo('cblanquera@gmail.com')
		//	->send();
	}
}