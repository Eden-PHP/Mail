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
 * Exception Class
 *
 * @vendor   Eden
 * @package  Mail
 * @author   Christian Blanquera <cblanquera@openovate.com>
 * @standard PSR-2
 */
class Exception extends \Eden\Core\Exception
{
    /**
     * @const string SERVER_ERROR Error template
     */
    const SERVER_ERROR = 'Problem connecting to %s. Check server, port or ssl settings for your email server.';

    /**
     * @const string LOGIN_ERROR Error template
     */
    const LOGIN_ERROR = 'Your email provider has rejected your login information. Verify your email and/or password is correct.';

    /**
     * @const string TLS_ERROR Error template
     */
    const TLS_ERROR = 'Problem connecting to %s with TLS on.';

    /**
     * @const string SMTP_ADD_EMAIL Error template
     */
    const SMTP_ADD_EMAIL = 'Adding %s to email failed.';

    /**
     * @const string SMTP_DATA Error template
     */
    const SMTP_DATA = 'Server did not allow data to be added.';
}
