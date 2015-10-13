<?php //-->
/**
 * This file is part of the Eden PHP Library.
 * (c) 2014-2016 Openovate Labs
 *
 * Copyright and license information can be found at LICENSE.txt
 * distributed with this package.
 */
class EdenMailSmtpTest extends PHPUnit_Framework_TestCase
{
    public $smtp;

    public function setUp()
    {
        date_default_timezone_set('GMT');
        $this->smtp = eden('mail')->smtp(
            'smtp.gmail.com',
            '[EMAIL-ADDRESS]',
            '[PASSWORD]',
            465,
            true
        );
    }

    public function tearDown()
    {
        $this->smtp->disconnect();
    }

    public function testAddAttachment()
    {
        $smtp = $this->smtp->addAttachment('test.txt', 'hi', 'text/plain');
        $this->assertInstanceOf('Eden\\Mail\\Smtp', $smtp);
    }

    public function testAddBCC()
    {
        $smtp = $this->smtp->addBcc('airon.dumael@gmail.com', 'airon');
        $this->assertInstanceOf('Eden\\Mail\\Smtp', $smtp);
    }

    public function testAddCC()
    {
        $smtp = $this->smtp->addCc('airon.dumael@gmail.com', 'airon');
        $this->assertInstanceOf('Eden\\Mail\\Smtp', $smtp);
    }

    public function testAddTo()
    {
        $smtp = $this->smtp->addTo('airon.dumael@gmail.com', 'airon');
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
        // $test = $this->smtp
        //  ->setSubject('[SUBJECT/TOPIC])
        //  ->setBody('[BODY]')
        //  ->addTo('[RECIEVER-EMAIL]')
        //  ->reply('[MESSAGE-ID]');
    }

    public function testSend()
    {
        // $this->smtp
        //  ->setSubject('[SUBJECT/TOPIC]')
        //  ->setBody('[BODY]')
        //  ->addTo('[RECIEVER-EMAIL]')
        //  ->send();
    }
}
