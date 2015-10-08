<?php //-->
/*
 * This file is part of the Mail package of the Eden PHP Library.
 * (c) 2013-2014 Openovate Labs
 *
 * Copyright and license information can be found at LICENSE
 * distributed with this package.
 */

namespace Eden\Mail;

/**
 * The base class for any class handling exceptions. Exceptions
 * allow an application to custom handle errors that would
 * normally let the system handle. This exception allows you to
 * specify error levels and error types. Also using this exception
 * outputs a trace (can be turned off) that shows where the problem
 * started to where the program stopped.
 *
 * @vendor Eden
 * @package mail
 * @author Christian Blanquera cblanquera@openovate.com
 */
class Exception extends \Eden\Core\Exception
{
    const SERVER_ERROR      = 'Problem connecting to %s. Check server, port or ssl settings for your email server.';
    const LOGIN_ERROR       = 'Your email provider has rejected your login information. Verify your email and/or password is correct.';
    const TLS_ERROR             = 'Problem connecting to %s with TLS on.';
    const SMTP_ADD_EMAIL    = 'Adding %s to email failed.';
    const SMTP_DATA         = 'Server did not allow data to be added.';
}
