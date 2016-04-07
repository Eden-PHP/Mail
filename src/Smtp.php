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
 * General available methods for common SMTP functionality
 *
 * @vendor   Eden
 * @package  Mail
 * @author   Christian Blanquera <cblanquera@openovate.com>
 * @author   Airon Paul Dumael <airon.dumael@gmail.com>
 * @standard PSR-2
 */
class Smtp extends Base
{
    /**
     * @const int TIMEOUT Connection timeout
     */
    const TIMEOUT = 30;

    /**
     * @var string $host The SMTP Host
     */
    protected $host = null;
       
    /**
     * @var string|null $port The SMTP port
     */
    protected $port = null;
       
    /**
     * @var bool $ssl Whether to use SSL
     */
    protected $ssl = false;
       
    /**
     * @var bool $tls Whether to use TLS
     */
    protected $tls = false;
       
    /**
     * @var string|null $username The mailbox user name
     */
    protected $username = null;
       
    /**
     * @var string|null $password The mailbox password
     */
    protected $password = null;
       
    /**
     * @var [RESOURCE] $socket The socket connection
     */
    protected $socket = null;
       
    /**
     * @var array $boundary The list of boundaries
     */
    protected $boundary = array();
       
    /**
     * @var array $subject The mail subject
     */
    protected $subject  = null;
       
    /**
     * @var array $body Body content types
     */
    protected $body = array();
       
    /**
     * @var array $to The list of main recipients
     */
    protected $to = array();
       
    /**
     * @var array $cc The list of carbon copies
     */
    protected $cc = array();
       
    /**
     * @var array $bcc The list of BCCs sorry i forgot what this stood for :(
     */
    protected $bcc = array();
       
    /**
     * @var array $attachments The list of attachments
     */
    protected $attachments = array();

    /**
     * @var bool $debugging If true outputs the logs
     */
    private $debugging = false;

    /**
     * Constructor - Store connection information
     *
     * @param *string  $host The SMTP host
     * @param *string  $user The mailbox user name
     * @param *string  $pass The mailbox password
     * @param int|null $port The SMTP port
     * @param bool     $ssl  Whether to use SSL
     * @param bool     $tls  Whether to use TLS
     */
    public function __construct(
        $host,
        $user,
        $pass,
        $port = null,
        $ssl = false,
        $tls = false
    ) {
        Argument::i()
            ->test(1, 'string')
            ->test(2, 'string')
            ->test(3, 'string')
            ->test(4, 'int', 'null')
            ->test(5, 'bool')
            ->test(6, 'bool');

        if (is_null($port)) {
            $port = $ssl ? 465 : 25;
        }

        $this->host         = $host;
        $this->username     = $user;
        $this->password     = $pass;
        $this->port         = $port;
        $this->ssl      = $ssl;
        $this->tls      = $tls;

        $this->boundary[] = md5(time().'1');
        $this->boundary[] = md5(time().'2');
    }

    /**
     * Adds an attachment to the email
     *
     * @param *string $filename The name of the file
     * @param *string $data     The file data
     * @param string  $mime     The mime type
     *
     * @return Eden\Mail\Smtp
     */
    public function addAttachment($filename, $data, $mime = null)
    {
        Argument::i()
            ->test(1, 'string')
            ->test(2, 'string')
            ->test(3, 'string', 'null');

        $this->attachments[] = array($filename, $data, $mime);
        return $this;
    }

    /**
     * Adds an email to the bcc list
     *
     * @param *string $email Email address
     * @param string  $name  Name of person
     *
     * @return Eden\Mail\Smtp
     */
    public function addBCC($email, $name = null)
    {
        Argument::i()
            ->test(1, 'string')
            ->test(2, 'string', 'null');

        $this->bcc[$email] = $name;
        return $this;
    }

    /**
     * Adds an email to the cc list
     *
     * @param *string $email Email address
     * @param string  $name  Name of person
     *
     * @return Eden\Mail\Smtp
     */
    public function addCC($email, $name = null)
    {
        Argument::i()
            ->test(1, 'string')
            ->test(2, 'string', 'null');

        $this->cc[$email] = $name;
        return $this;
    }

    /**
     * Adds an email to the to list
     *
     * @param *string $email Email address
     * @param string  $name  Name of person
     *
     * @return Eden\Mail\Smtp
     */
    public function addTo($email, $name = null)
    {
        Argument::i()
            ->test(1, 'string')
            ->test(2, 'string', 'null');

        $this->to[$email] = $name;
        return $this;
    }

    /**
     * Connects to the server
     *
     * @param int  $timeout The connection timeout
     * @param bool $test    Whether to output the logs
     *
     * @return Eden\Mail\Smtp
     */
    public function connect($timeout = self::TIMEOUT, $test = false)
    {
        Argument::i()
            ->test(1, 'int')
            ->test(2, 'bool');

        $host = $this->host;

        if ($this->ssl) {
            $host = 'ssl://' . $host;
        } else {
            $host = 'tcp://' . $host;
        }

        $errno  =  0;
        $errstr = '';
        $this->socket = @stream_socket_client($host.':'.$this->port, $errno, $errstr, $timeout);

        if (!$this->socket || strlen($errstr) > 0 || $errno > 0) {
            //throw exception
            Exception::i()
                ->setMessage(Exception::SERVER_ERROR)
                ->addVariable($host.':'.$this->port)
                ->trigger();
        }

        $this->receive();

        if (!$this->call('EHLO '.$_SERVER['HTTP_HOST'], 250)
        && !$this->call('HELO '.$_SERVER['HTTP_HOST'], 250)) {
            $this->disconnect();
            //throw exception
            Exception::i()
                ->setMessage(Exception::SERVER_ERROR)
                ->addVariable($host.':'.$this->port)
                ->trigger();
        }

        if ($this->tls && !$this->call('STARTTLS', 220, 250)) {
            if (!stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                $this->disconnect();
                //throw exception
                Exception::i()
                    ->setMessage(Exception::TLS_ERROR)
                    ->addVariable($host.':'.$this->port)
                    ->trigger();
            }

            if (!$this->call('EHLO '.$_SERVER['HTTP_HOST'], 250)
            && !$this->call('HELO '.$_SERVER['HTTP_HOST'], 250)) {
                $this->disconnect();
                //throw exception
                Exception::i()
                    ->setMessage(Exception::SERVER_ERROR)
                    ->addVariable($host.':'.$this->port)
                    ->trigger();
            }
        }

        if ($test) {
            $this->disconnect();
            return $this;
        }

        //login
        if (!$this->call('AUTH LOGIN', 250, 334)) {
            $this->disconnect();
            //throw exception
            Exception::i(Exception::LOGIN_ERROR)->trigger();
        }

        if (!$this->call(base64_encode($this->username), 334)) {
            $this->disconnect();
            //throw exception
            Exception::i()->setMessage(Exception::LOGIN_ERROR);
        }

        if (!$this->call(base64_encode($this->password), 235, 334)) {
            $this->disconnect();
            //throw exception
            Exception::i()->setMessage(Exception::LOGIN_ERROR);
        }

        return $this;
    }

    /**
     * Disconnects from the server
     *
     * @return Eden\Mail\Smtp
     */
    public function disconnect()
    {
        if ($this->socket) {
            $this->push('QUIT');

            fclose($this->socket);

            $this->socket = null;
        }

        return $this;
    }

    /**
     * Reply to an existing email
     *
     * @param string $message The mail id to reply to
     * @param string $topic   The topic ID
     * @param array  $headers Custom headers
     *
     * @return array headers
     */
    public function reply($messageId, $topic = null, array $headers = array())
    {
        Argument::i()
            ->test(1, 'string')
            ->test(2, 'string', 'null');

        //if no socket
        if (!$this->socket) {
            //then connect
            $this->connect();
        }

        //add from
        if (!$this->call('MAIL FROM:<' . $this->username . '>', 250, 251)) {
            $this->disconnect();
            //throw exception
            Exception::i()
                ->setMessage(Exception::SMTP_ADD_EMAIL)
                ->addVariable($this->username)
                ->trigger();
        }

        //add to
        foreach ($this->to as $email => $name) {
            if (!$this->call('RCPT TO:<' . $email . '>', 250, 251)) {
                $this->disconnect();
                //throw exception
                Exception::i()
                    ->setMessage(Exception::SMTP_ADD_EMAIL)
                    ->addVariable($email)
                    ->trigger();
            }
        }

        //add cc
        foreach ($this->cc as $email => $name) {
            if (!$this->call('RCPT TO:<' . $email . '>', 250, 251)) {
                $this->disconnect();
                //throw exception
                Exception::i()
                    ->setMessage(Exception::SMTP_ADD_EMAIL)
                    ->addVariable($email)
                    ->trigger();
            }
        }

        //add bcc
        foreach ($this->bcc as $email => $name) {
            if (!$this->call('RCPT TO:<' . $email . '>', 250, 251)) {
                $this->disconnect();
                //throw exception
                Exception::i()
                    ->setMessage(Exception::SMTP_ADD_EMAIL)
                    ->addVariable($email)
                    ->trigger();
            }
        }

        //start compose
        if (!$this->call('DATA', 354)) {
            $this->disconnect();
            //throw exception
            Exception::i(Exception::SMTP_DATA)->trigger();
        }

        $headers    = $this->getHeaders($headers);
        $body       = $this->getBody();

        $headers['In-Reply-To'] = $messageId;

        if ($topic) {
            $headers['Thread-Topic'] = $topic;
        }

        //send header data
        foreach ($headers as $name => $value) {
            var_dump($name.': '.$value);
            $this->push($name.': '.$value);
        }

        //send body data
        foreach ($body as $line) {
            if (strpos($line, '.') === 0) {
                // Escape lines prefixed with a '.'
                $line = '.' . $line;
            }

            $this->push($line);
        }

        //tell server this is the end
        if (!$this->call("\r\n.\r\n", 250)) {
            $this->disconnect();
            //throw exception
            Exception::i(Exception::SMTP_DATA)->trigger();
        }

        //reset (some reason without this, this class spazzes out)
        $this->push('RSET');

        return $headers;
    }

    /**
     * Resets the class
     *
     * @return Eden\Mail\Smtp
     */
    public function reset()
    {
        $this->subject      = null;
        $this->body     = array();
        $this->to           = array();
        $this->cc           = array();
        $this->bcc      = array();
        $this->attachments = array();

        $this->disconnect();

        return $this;
    }

    /**
     * Sends an email
     *
     * @param array  $headers Custom headers
     *
     * @return array headers
     */
    public function send(array $headers = array())
    {
        //if no socket
        if (!$this->socket) {
            //then connect
            $this->connect();
        }

        $headers    = $this->getHeaders($headers);
        $body       = $this->getBody();

        //add from
        if (!$this->call('MAIL FROM:<' . $this->username . '>', 250, 251)) {
            $this->disconnect();
            //throw exception
            Exception::i()
                ->setMessage(Exception::SMTP_ADD_EMAIL)
                ->addVariable($this->username)
                ->trigger();
        }

        //add to
        foreach ($this->to as $email => $name) {
            if (!$this->call('RCPT TO:<' . $email . '>', 250, 251)) {
                $this->disconnect();
                //throw exception
                Exception::i()
                    ->setMessage(Exception::SMTP_ADD_EMAIL)
                    ->addVariable($email)
                    ->trigger();
            }
        }

        //add cc
        foreach ($this->cc as $email => $name) {
            if (!$this->call('RCPT TO:<' . $email . '>', 250, 251)) {
                $this->disconnect();
                //throw exception
                Exception::i()
                    ->setMessage(Exception::SMTP_ADD_EMAIL)
                    ->addVariable($email)
                    ->trigger();
            }
        }

        //add bcc
        foreach ($this->bcc as $email => $name) {
            if (!$this->call('RCPT TO:<' . $email . '>', 250, 251)) {
                $this->disconnect();
                //throw exception
                Exception::i()
                    ->setMessage(Exception::SMTP_ADD_EMAIL)
                    ->addVariable($email)
                    ->trigger();
            }
        }

        //start compose
        if (!$this->call('DATA', 354)) {
            $this->disconnect();
            //throw exception
            Exception::i(Exception::SMTP_DATA)->trigger();
        }

        //send header data
        foreach ($headers as $name => $value) {
            $this->push($name.': '.$value);
        }

        //send body data
        foreach ($body as $line) {
            if (strpos($line, '.') === 0) {
                // Escape lines prefixed with a '.'
                $line = '.' . $line;
            }

            $this->push($line);
        }

        //tell server this is the end
        if (!$this->call(".", 250)) {
            $this->disconnect();
            //throw exception
            Exception::i(Exception::SMTP_DATA)->trigger();
        }

        //reset (some reason without this, this class spazzes out)
        $this->push('RSET');

        return $headers;
    }

    /**
     * Sets body
     *
     * @param *string $body The raw body
     * @param bool    $html Is this an html body?
     *
     * @return Eden\Mail\Smtp
     */
    public function setBody($body, $html = false)
    {
        Argument::i()
            ->test(1, 'string')
            ->test(2, 'bool');

        if ($html) {
            $this->body['text/html'] = $body;
            $body = strip_tags($body);
        }

        $this->body['text/plain'] = $body;

        return $this;
    }

    /**
     * Sets subject
     *
     * @param string $subject The title of this message
     *
     * @return Eden\Mail\Smtp
     */
    public function setSubject($subject)
    {
        Argument::i()->test(1, 'string');
        $this->subject = $subject;
        return $this;
    }

    /**
     * Adds the attachment string body
     * for plain text emails
     *
     * @param *array $body The body types
     *
     * @return array
     */
    protected function addAttachmentBody(array $body)
    {
        foreach ($this->attachments as $attachment) {
            list($name, $data, $mime) = $attachment;
            $mime   = $mime ? $mime : File::i($name)->getMime();
            $data   = base64_encode($data);
            $count  = ceil(strlen($data) / 998);

            $body[] = '--'.$this->boundary[1];
            $body[] = 'Content-type: '.$mime.'; name="'.$name.'"';
            $body[] = 'Content-disposition: attachment; filename="'.$name.'"';
            $body[] = 'Content-transfer-encoding: base64';
            $body[] = null;

            for ($i = 0; $i < $count; $i++) {
                $body[] = substr($data, ($i * 998), 998);
            }

            $body[] = null;
            $body[] = null;
        }

        $body[] = '--'.$this->boundary[1].'--';

        return $body;
    }

    /**
     * Send it out and return the response
     *
     * @param *string  $command The raw SMTP command
     * @param int|null $code    The command code
     *
     * @return string|false
     */
    protected function call($command, $code = null)
    {
        if (!$this->push($command)) {
            return false;
        }

        $receive = $this->receive();

        $args = func_get_args();
        if (count($args) > 1) {
            for ($i = 1; $i < count($args); $i++) {
                if (strpos($receive, (string)$args[$i]) === 0) {
                    return true;
                }
            }

            return false;
        }

        return $receive;
    }

    /**
     * Adds the attachment string body
     * for HTML formatted emails
     *
     * @return array
     */
    protected function getAlternativeAttachmentBody()
    {
        $alternative    = $this->getAlternativeBody();

        $body = array();
        $body[] = 'Content-Type: multipart/mixed; boundary="'.$this->boundary[1].'"';
        $body[] = null;
        $body[] = '--'.$this->boundary[1];

        foreach ($alternative as $line) {
            $body[] = $line;
        }

        return $this->addAttachmentBody($body);
    }

    /**
     * Adds the string body
     * for HTML formatted emails
     *
     * @return array
     */
    protected function getAlternativeBody()
    {
        $plain  = $this->getPlainBody();
        $html   = $this->getHtmlBody();

        $body   = array();
        $body[] = 'Content-Type: multipart/alternative; boundary="'.$this->boundary[0].'"';
        $body[] = null;
        $body[] = '--'.$this->boundary[0];

        foreach ($plain as $line) {
            $body[] = $line;
        }

        $body[] = '--'.$this->boundary[0];

        foreach ($html as $line) {
            $body[] = $line;
        }

        $body[] = '--'.$this->boundary[0].'--';
        $body[] = null;
        $body[] = null;

        return $body;
    }

    /**
     * Returns the body
     *
     * @return array
     */
    protected function getBody()
    {
        $type = 'Plain';
        if (count($this->body) > 1) {
            $type = 'Alternative';
        } else if (isset($this->body['text/html'])) {
            $type = 'Html';
        }

        $method = 'get%sBody';
        if (!empty($this->attachments)) {
            $method = 'get%sAttachmentBody';
        }

        $method = sprintf($method, $type);

        return $this->$method();
    }

    /**
     * Returns the header information
     *
     * @param array $customHeaders Custom headers to include
     *
     * @return array
     */
    protected function getHeaders(array $customHeaders = array())
    {
        $timestamp = $this->getTimestamp();

        $subject = trim($this->subject);
        $subject = str_replace(array("\n", "\r"), '', $subject);

        $to = $cc = $bcc = array();
        foreach ($this->to as $email => $name) {
            $to[] = trim($name.' <'.$email.'>');
        }

        foreach ($this->cc as $email => $name) {
            $cc[] = trim($name.' <'.$email.'>');
        }

        foreach ($this->bcc as $email => $name) {
            $bcc[] = trim($name.' <'.$email.'>');
        }

        list($account, $suffix) = explode('@', $this->username);

        $headers = array(
            'Date'          => $timestamp,
            'Subject'       => $subject,
            'From'          => '<'.$this->username.'>',
            'To'            => implode(', ', $to));

        if (!empty($cc)) {
            $headers['Cc'] = implode(', ', $cc);
        }

        if (!empty($bcc)) {
            $headers['Bcc'] = implode(', ', $bcc);
        }

        $headers['Message-ID']  = '<'.md5(uniqid(time())).'.eden@'.$suffix.'>';

        $headers['Thread-Topic'] = $this->subject;

        $headers['Reply-To'] = '<'.$this->username.'>';

        foreach ($customHeaders as $key => $value) {
            $headers[$key] = $value;
        }

        return $headers;
    }

    /**
     * Returns the HTML + Attachment version body
     *
     * @return array
     */
    protected function getHtmlAttachmentBody()
    {
        $html   = $this->getHtmlBody();

        $body = array();
        $body[] = 'Content-Type: multipart/mixed; boundary="'.$this->boundary[1].'"';
        $body[] = null;
        $body[] = '--'.$this->boundary[1];

        foreach ($html as $line) {
            $body[] = $line;
        }

        return $this->addAttachmentBody($body);
    }

    /**
     * Returns the HTML version body
     *
     * @return array
     */
    protected function getHtmlBody()
    {
        $charset    = $this->isUtf8($this->body['text/html']) ? 'utf-8' : 'US-ASCII';
        $html       = str_replace("\r", '', trim($this->body['text/html']));

        $encoded = explode("\n", $this->quotedPrintableEncode($html));
        $body   = array();
        $body[] = 'Content-Type: text/html; charset='.$charset;
        $body[] = 'Content-Transfer-Encoding: quoted-printable'."\n";

        foreach ($encoded as $line) {
            $body[] = $line;
        }

        $body[] = null;
        $body[] = null;

        return $body;
    }

    /**
     * Returns the Plain + Attachment version body
     *
     * @return array
     */
    protected function getPlainAttachmentBody()
    {
        $plain  = $this->getPlainBody();

        $body = array();
        $body[] = 'Content-Type: multipart/mixed; boundary="'.$this->boundary[1].'"';
        $body[] = null;
        $body[] = '--'.$this->boundary[1];

        foreach ($plain as $line) {
            $body[] = $line;
        }

        return $this->addAttachmentBody($body);
    }

    /**
     * Returns the Plain version body
     *
     * @return array
     */
    protected function getPlainBody()
    {
        $charset    = $this->isUtf8($this->body['text/plain']) ? 'utf-8' : 'US-ASCII';
        $plane      = str_replace("\r", '', trim($this->body['text/plain']));
        $count      = ceil(strlen($plane) / 998);

        $body = array();
        $body[] = 'Content-Type: text/plain; charset='.$charset;
        $body[] = 'Content-Transfer-Encoding: 7bit';
        $body[] = null;

        for ($i = 0; $i < $count; $i++) {
            $body[] = substr($plane, ($i * 998), 998);
        }

        $body[] = null;
        $body[] = null;

        return $body;
    }

    /**
     * Returns the response when all of it is received
     *
     * @return string
     */
    protected function receive()
    {
        $data = '';
        $now = time();

        while ($str = fgets($this->socket, 1024)) {
            $data .= $str;

            if (substr($str, 3, 1) == ' ' || time() > ($now + self::TIMEOUT)) {
                break;
            }
        }

        $this->debug('Receiving: '. $data);

        return $data;
    }

    /**
     * Sends out the command
     *
     * @param *string $command The raw SMTP command
     *
     * @return bool
     */
    protected function push($command)
    {
        $this->debug('Sending: '.$command);

        return fwrite($this->socket, $command . "\r\n");
    }

    /**
     * Debugging
     *
     * @param *string $string The string to output
     *
     * @return Eden\Mail\Smtp
     */
    private function debug($string)
    {
        if ($this->debugging) {
            $string = htmlspecialchars($string);


            echo '<pre>'.$string.'</pre>'."\n";
        }

        return $this;
    }

    /**
     * Returns timestamp, formatted to what SMTP expects
     *
     * @return string
     */
    private function getTimestamp()
    {
        $zone = date('Z');
        $sign = ($zone < 0) ? '-' : '+';
        $zone = abs($zone);
        $zone = (int)($zone / 3600) * 100 + ($zone % 3600) / 60;
        return sprintf("%s %s%04d", date('D, j M Y H:i:s'), $sign, $zone);
    }

    /**
     * Returns true if there's UTF encodeing
     *
     * @param *string The string to test
     *
     * @return bool
     */
    private function isUtf8($string)
    {
        $regex = array(
            '[\xC2-\xDF][\x80-\xBF]',
            '\xE0[\xA0-\xBF][\x80-\xBF]',
            '[\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}',
            '\xED[\x80-\x9F][\x80-\xBF]',
            '\xF0[\x90-\xBF][\x80-\xBF]{2}',
            '[\xF1-\xF3][\x80-\xBF]{3}',
            '\xF4[\x80-\x8F][\x80-\xBF]{2}');

        $count = ceil(strlen($string) / 5000);
        for ($i = 0; $i < $count; $i++) {
            if (preg_match('%(?:'. implode('|', $regex).')+%xs', substr($string, ($i * 5000), 5000))) {
                return false;
            }
        }

        return true;
    }

    /**
     * Returns a printable encode version of the body
     *
     * @param *string $input    The string to encode
     * @param int     $line_max line length
     *
     * @return string
     */
    private function quotedPrintableEncode($input, $line_max = 250)
    {
        $hex = array('0','1','2','3','4','5','6','7',
                              '8','9','A','B','C','D','E','F');
        $lines = preg_split("/(?:\r\n|\r|\n)/", $input);
        $linebreak = "=0D=0A=\r\n";
        /* the linebreak also counts as characters in the mime_qp_long_line
        * rule of spam-assassin */
        $line_max = $line_max - strlen($linebreak);
        $escape = "=";
        $output = "";
        $cur_conv_line = "";
        $length = 0;
        $whitespace_pos = 0;
        $addtl_chars = 0;

        // iterate lines
        for ($j = 0; $j < count($lines); $j++) {
            $line = $lines[$j];
            $linlen = strlen($line);

            // iterate chars
            for ($i = 0; $i < $linlen; $i++) {
                $c = substr($line, $i, 1);
                $dec = ord($c);

                $length++;

                if ($dec == 32) {
                    // space occurring at end of line, need to encode
                    if (($i == ($linlen - 1))) {
                        $c = "=20";
                        $length += 2;
                    }

                    $addtl_chars = 0;
                    $whitespace_pos = $i;
                } else if (($dec == 61) || ($dec < 32 ) || ($dec > 126)) {
                      $h2 = floor($dec/16);
                      $h1 = floor($dec%16);
                      $c = $escape . $hex["$h2"] . $hex["$h1"];
                      $length += 2;
                      $addtl_chars += 2;
                }

                // length for wordwrap exceeded, get a newline into the text
                if ($length >= $line_max) {
                    $cur_conv_line .= $c;

                    // read only up to the whitespace for the current line
                    $whitesp_diff = $i - $whitespace_pos + $addtl_chars;

                    //the text after the whitespace will have to be read
                    // again ( + any additional characters that came into
                    // existence as a result of the encoding process after the whitespace)
                    //
                    // Also, do not start at 0, if there was *no* whitespace in
                    // the whole line
                    if (($i + $addtl_chars) > $whitesp_diff) {
                        $output .= substr($cur_conv_line, 0, (strlen($cur_conv_line) -
                                $whitesp_diff)) . $linebreak;
                        $i =  $i - $whitesp_diff + $addtl_chars;
                    } else {
                        $output .= $cur_conv_line . $linebreak;
                    }

                    $cur_conv_line = "";
                    $length = 0;
                    $whitespace_pos = 0;
                } else {
                    // length for wordwrap not reached, continue reading
                    $cur_conv_line .= $c;
                }
            } // end of for

            $length = 0;
            $whitespace_pos = 0;
            $output .= $cur_conv_line;
            $cur_conv_line = "";

            if ($j<=count($lines)-1) {
                $output .= $linebreak;
            }
        } // end for

        return trim($output);
    }
}
