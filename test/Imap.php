<?php //-->
/**
 * This file is part of the Eden PHP Library.
 * (c) 2014-2016 Openovate Labs
 *
 * Copyright and license information can be found at LICENSE.txt
 * distributed with this package.
 */
class EdenMailImapTest extends PHPUnit_Framework_TestCase
{
    public $imap;

    public function setUp()
    {
        date_default_timezone_set('GMT');
        $this->imap = eden('mail')->imap(
            'imap.gmail.com',
            '[EMAIL-ADDRESS]',
            '[PASSWORD]',
            993,
            true
        );
    }

    public function tearDown()
    {
        $this->imap->disconnect();
    }

    public function testGetEmails()
    {
        // $emails = $this->imap
        // ->setActiveMailbox('INBOX')
        // ->getEmails(0, 1);

        // $this->assertTrue(is_array($emails));

        // if (count($emails) > 0) {
        //     $this->assertTrue(isset($emails[0]['subject']));
        // }
    }

    public function testGetEmailTotal()
    {
        // $total = $this->imap
        // ->setActiveMailbox('INBOX')
        // ->getEmailTotal();

        // $this->assertTrue(is_numeric($total));
    }

    public function testGetActiveMailbox()
    {
        // $mailbox = $this->imap
        // ->setActiveMailbox('INBOX')
        // ->getActiveMailbox();

        // $this->assertEquals('INBOX', $mailbox);
    }

    public function testGetNextUid()
    {
        // $uid = $this->imap
        // ->setActiveMailbox('INBOX')
        // ->getNextUid();

        // $this->assertTrue(is_numeric($uid));
    }

    public function testGetMailboxes()
    {
        // $mailboxes = $this->imap
        // ->getMailboxes('INBOX');

        // $this->assertTrue(count($mailboxes) > 0);
    }

    public function testGetUniqueEmails()
    {
    }

    public function testMove()
    {
    }

    public function testRemove()
    {
    }

    public function testSearch()
    {
    }

    public function testSearchTotal()
    {
    }
}
