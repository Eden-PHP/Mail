<?php //-->
/**
 * This file is part of the Eden PHP Library.
 * (c) 2014-2016 Openovate Labs
 *
 * Copyright and license information can be found at LICENSE.txt
 * distributed with this package.
 */

namespace Eden\Mail;

/**
 * Mail Factory Class
 *
 * @vendor   Eden
 * @package  Mail
 * @author   Christian Blanquera <cblanquera@openovate.com>
 * @standard PSR-2
 */
class Index extends Base
{
    /**
     * @const int INSTANCE Flag that designates singleton when using ::i()
     */
    const INSTANCE = 1;
    
    /**
     * Returns Mail IMAP
     *
     * @param *string  $host The IMAP host
     * @param *string  $user The mailbox user name
     * @param *string  $pass The mailbox password
     * @param int|null $port The IMAP port
     * @param bool     $ssl  Whether to use SSL
     * @param bool     $tls  Whether to use TLS
     *
     * @return Eden\Mail\Imap
     */
    public function imap($host, $user, $pass, $port = null, $ssl = false, $tls = false)
    {
        Argument::i()
            ->test(1, 'string')
            ->test(2, 'string')
            ->test(3, 'string')
            ->test(4, 'int', 'null')
            ->test(5, 'bool')
            ->test(6, 'bool');
            
        return Imap::i($host, $user, $pass, $port, $ssl, $tls);
    }
    
    /**
     * Returns Mail POP3
     *
     * @param *string  $host The POP3 host
     * @param *string  $user The mailbox user name
     * @param *string  $pass The mailbox password
     * @param int|null $port The POP3 port
     * @param bool     $ssl  Whether to use SSL
     * @param bool     $tls  Whether to use TLS
     *
     * @return Eden\Mail\Pop3
     */
    public function pop3($host, $user, $pass, $port = null, $ssl = false, $tls = false)
    {
        Argument::i()
            ->test(1, 'string')
            ->test(2, 'string')
            ->test(3, 'string')
            ->test(4, 'int', 'null')
            ->test(5, 'bool')
            ->test(6, 'bool');
        
        return Pop3::i($host, $user, $pass, $port, $ssl, $tls);
    }
    
    /**
     * Returns Mail SMTP
     *
     * @param *string  $host The SMTP host
     * @param *string  $user The mailbox user name
     * @param *string  $pass The mailbox password
     * @param int|null $port The SMTP port
     * @param bool     $ssl  Whether to use SSL
     * @param bool     $tls  Whether to use TLS
     *
     * @return Eden\Mail\Smtp
     */
    public function smtp($host, $user, $pass, $port = null, $ssl = false, $tls = false)
    {
        Argument::i()
            ->test(1, 'string')
            ->test(2, 'string')
            ->test(3, 'string')
            ->test(4, 'int', 'null')
            ->test(5, 'bool')
            ->test(6, 'bool');
            
        return Smtp::i($host, $user, $pass, $port, $ssl, $tls);
    }
}
