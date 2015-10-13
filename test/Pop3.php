<?php //-->
/**
 * This file is part of the Eden PHP Library.
 * (c) 2014-2016 Openovate Labs
 *
 * Copyright and license information can be found at LICENSE.txt
 * distributed with this package.
 */
class EdenMailPop3Test extends PHPUnit_Framework_TestCase
{
    public $pop;
    public function setUp()
    {
        date_default_timezone_set('GMT');
        $this->pop = eden('mail')->pop3(
            'pop.gmail.com',
            '[EMAIL-ADDRESS]',
            '[PASSWORD]',
            995,
            true
        );
    }

    public function tearDown()
    {
        $this->pop->disconnect();
    }

    public function testGetEmails()
    {
        $emails = $this->pop->getEmails(0, 10);

        $this->assertTrue(is_array($emails));

        if (count($emails) > 0) {
            $this->assertTrue(isset($emails[0]['subject']));
        }
    }

    public function testGetEmailTotal()
    {
        $total = $this->pop->getEmailTotal();

        $this->assertTrue(is_numeric($total));
    }
}
