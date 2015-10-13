<?php //-->
/**
 * This file is part of the Eden PHP Library.
 * (c) 2014-2016 Openovate Labs
 *
 * Copyright and license information can be found at LICENSE.txt
 * distributed with this package.
 */
class EdenMailIndexTest extends PHPUnit_Framework_TestCase
{
    public function testPop3()
    {
        $class = eden('mail')->pop3('pop.gmail.com', '[EMAIL-ADDRESS]', '[PASSWORD]', 995, true);
        $this->assertInstanceOf('Eden\\Mail\\Pop3', $class);
    }

    public function testImap()
    {
        $class = eden('mail')->imap('imap.gmail.com', '[EMAIL-ADDRESS]', '[PASSWORD]', 993, true);
        $this->assertInstanceOf('Eden\\Mail\\Imap', $class);
    }

    public function testSmtp()
    {
        $class = eden('mail')->smtp('smtp.gmail.com', '[EMAIL-ADDRESS]', '[PASSWORD]', 465, true);
        $this->assertInstanceOf('Eden\\Mail\\Smtp', $class);
    }
}
