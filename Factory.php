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
 * Mail Factory Class
 *
 * @vendor Eden
 * @package Mail
 * @author Christian Blanquera cblanquera@openovate.com
 */
class Factory extends Base
{
    const INSTANCE = 1;
	
	/**
	 * Returns Mail IMAP
	 *
	 * @param string
	 * @param string
	 * @param string
	 * @param int|null
	 * @param bool
	 * @param bool
	 * @return Eden\Mail\Imap
	 */
	public function imap($host, $user, $pass, $port = NULL, $ssl = false, $tls = false) 
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
	 * @param string
	 * @param string
	 * @param string
	 * @param int|null
	 * @param bool
	 * @param bool
	 * @return Eden\Mail\Pop3
	 */
	public function pop3($host, $user, $pass, $port = NULL, $ssl = false, $tls = false) 
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
	 * @param string
	 * @param string
	 * @param string
	 * @param int|null
	 * @param bool
	 * @param bool
	 * @return Eden_Mail_Smtp
	 */
	public function smtp($host, $user, $pass, $port = NULL, $ssl = false, $tls = false) 
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

	/**
	 * Returns PHP Mailer
	 *
	 * @return Eden_Mail_Mail
	 */
	public function mail() 
	{		
		return Mail::i();
	}
}