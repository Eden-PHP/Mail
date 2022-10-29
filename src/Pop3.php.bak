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
 * General available methods for common POP3 functionality
 *
 * @vendor   Eden
 * @package  Mail
 * @author   Christian Blanquera <cblanquera@openovate.com>
 * @author   Airon Paul Dumael <airon.dumael@gmail.com>
 * @standard PSR-2
 */
class Pop3 extends Base
{
    /**
     * @const int TIMEOUT Connection timeout
     */
    const TIMEOUT = 30;

    /**
     * @const string NO_SUBJECT Default subject
     */
    const NO_SUBJECT = '(no subject)';

    /**
     * @var string $host The POP3 Host
     */
    protected $host = null;
       
    /**
     * @var string|null $port The POP3 port
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
     * @var string|null $timestamp Default timestamp
     */
    protected $timestamp = null;
       
    /**
     * @var [RESOURCE] $socket The socket connection
     */
    protected $socket = null;
       
    /**
     * @var bool $loggedin If you are actually logged in
     */
    protected $loggedin = false;
       
    /**
     * @var bool $debugging If true outputs the logs
     */
    private $debugging = false;

    /**
     * Constructor - Store connection information
     *
     * @param *string  $host The POP3 host
     * @param *string  $user The mailbox user name
     * @param *string  $pass The mailbox password
     * @param int|null $port The POP3 port
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
            $port = $ssl ? 995 : 110;
        }

        $this->host = $host;
        $this->username = $user;
        $this->password = $pass;
        $this->port = $port;
        $this->ssl = $ssl;
        $this->tls = $tls;

        $this->connect();
    }

    /**
     * Connects to the server
     *
     * @param bool $test Whether to output the logs
     *
     * @return Eden\Mail\Pop3
     */
    public function connect($test = false)
    {
        Argument::i()->test(1, 'bool');

        if ($this->loggedin) {
            return $this;
        }

        $host = $this->host;

        if ($this->ssl) {
            $host = 'ssl://' . $host;
        }

        $errno  =  0;
        $errstr = '';

        $this->socket = fsockopen($host, $this->port, $errno, $errstr, self::TIMEOUT);

        if (!$this->socket) {
            //throw exception
            Exception::i()
                ->setMessage(Exception::SERVER_ERROR)
                ->addVariable($host.':'.$this->port)
                ->trigger();
        }

        $welcome = $this->receive();

        strtok($welcome, '<');
        $this->timestamp = strtok('>');
        if (!strpos($this->timestamp, '@')) {
            $this->timestamp = null;
        } else {
            $this->timestamp = '<' . $this->timestamp . '>';
        }

        if ($this->tls) {
            $this->call('STLS');
            if (!stream_socket_enable_crypto(
                $this->socket,
                true,
                STREAM_CRYPTO_METHOD_TLS_CLIENT
            )) {
                $this->disconnect();
                //throw exception
                Exception::i()
                    ->setMessage(Exception::TLS_ERROR)
                    ->addVariable($host.':'.$this->port)
                    ->trigger();
            }
        }

        if ($test) {
            $this->disconnect();
            return $this;
        }

        //login
        if ($this->timestamp) {
            try {
                $this->call(
                    'APOP '.$this->username
                    . ' '
                    . md5($this->timestamp . $this->password)
                );
                return;
            } catch (Argument $e) {
                // ignore
            }
        }

        $this->call('USER '.$this->username);
        $this->call('PASS '.$this->password);

        $this->loggedin = true;

        return $this;
    }

    /**
     * Disconnects from the server
     *
     * @return Eden\Mail\Pop3
     */
    public function disconnect()
    {
        if (!$this->socket) {
            return $this;
        }

        try {
            $this->send('QUIT');
        } catch (Argument $e) {
            // ignore error - we're closing the socket anyway
        }

        fclose($this->socket);
        $this->socket = null;

        return $this;
    }

    /**
     * Returns a list of emails given the range
     *
     * @param number $start Pagination start
     * @param number $range Pagination range
     *
     * @return array
     */
    public function getEmails($start = 0, $range = 10)
    {
        Argument::i()
            ->test(1, 'int')
            ->test(2, 'int');

        $total = $this->getEmailTotal();

        if ($total == 0) {
            return array();
        }

        if (!is_array($start)) {
            $range = $range > 0 ? $range : 1;
            $start = $start >= 0 ? $start : 0;
            $max = $total - $start;

            if ($max < 1) {
                $max = $total;
            }

            $min = $max - $range + 1;

            if ($min < 1) {
                $min = 1;
            }

            $set = $min . ':' . $max;

            if ($min == $max) {
                $set = $min;
            }
        }

        $emails = array();
        for ($i = $min; $i <= $max; $i++) {
            $emails[] = $this->getEmailFormat($this->call('RETR '.$i, true));
        }

        return $emails;
    }

    /**
     * Returns the total number of emails in a mailbox
     *
     * @return number
     */
    public function getEmailTotal()
    {
        @list($messages, $octets) = explode(' ', $this->call('STAT'));
        $messages = is_numeric($messages) ? $messages : 0;

        return $messages;
    }

    /**
     * Remove an email from a mailbox
     *
     * @param *number $msgno The mail UID to remove
     *
     * @return Eden\Mail\Pop3
     */
    public function remove($msgno)
    {
        Argument::i()->test(1, 'int', 'string');

        $this->call("DELE $msgno");

        if (!$this->loggedin || !$this->socket) {
            return false;
        }

        if (!is_array($msgno)) {
            $msgno = array($msgno);
        }

        foreach ($msgno as $number) {
            $this->call('DELE '.$number);
        }

        return $this;
    }

    /**
     * Send it out and return the response
     *
     * @param *string $command   The raw POP3 command
     * @param bool    $multiline Whether to expect a multiline response
     *
     * @return string|false
     */
    protected function call($command, $multiline = false)
    {
        if (!$this->send($command)) {
            return false;
        }

        return $this->receive($multiline);
    }

    /**
     * Returns the response when all of it is received
     *
     * @param bool $multiline Whether to expect a multiline response
     *
     * @return string
     */
    protected function receive($multiline = false)
    {
        $result = @fgets($this->socket);
        $status = $result = trim($result);
        $message = '';

        if (strpos($result, ' ')) {
            list($status, $message) = explode(' ', $result, 2);
        }

        if ($status != '+OK') {
            return false;
        }

        if ($multiline) {
            $message = '';
            $line = fgets($this->socket);
            while ($line && rtrim($line, "\r\n") != '.') {
                if ($line[0] == '.') {
                    $line = substr($line, 1);
                }
                $this->debug('Receiving: '.$line);
                $message .= $line;
                $line = fgets($this->socket);
            };
        }

        return $message;
    }

    /**
     * Sends out the command
     *
     * @param *string $command The raw POP3 command
     *
     * @return bool
     */
    protected function send($command)
    {
        $this->debug('Sending: '.$command);

        return fputs($this->socket, $command . "\r\n");
    }

    /**
     * Debugging
     *
     * @param *string $string The string to output
     *
     * @return Eden\Mail\Imap
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
     * Secret Sauce - Transform an email string
     * response to array key value format
     *
     * @param *string $email The actual email
     * @param array   $flags Any mail flags
     *
     * @return array
     */
    private function getEmailFormat($email, array $flags = array())
    {
        //if email is an array
        if (is_array($email)) {
            //make it into a string
            $email = implode("\n", $email);
        }

        //split the head and the body
        $parts = preg_split("/\n\s*\n/", $email, 2);

        $head = $parts[0];
        $body = null;
        if (isset($parts[1]) && trim($parts[1]) != ')') {
            $body = $parts[1];
        }

        $lines = explode("\n", $head);
        $head = array();
        foreach ($lines as $line) {
            if (trim($line) && preg_match("/^\s+/", $line)) {
                $head[count($head)-1] .= ' '.trim($line);
                continue;
            }

            $head[] = trim($line);
        }

        $head = implode("\n", $head);

        $recipientsTo = $recipientsCc = $recipientsBcc = $sender = array();

        //get the headers
        $headers1   = imap_rfc822_parse_headers($head);
        $headers2   = $this->getHeaders($head);

        //set the from
        $sender['name'] = null;
        if (isset($headers1->from[0]->personal)) {
            $sender['name'] = $headers1->from[0]->personal;
            //if the name is iso or utf encoded
            if (preg_match("/^\=\?[a-zA-Z]+\-[0-9]+.*\?/", strtolower($sender['name']))) {
                //decode the subject
                $sender['name'] = str_replace('_', ' ', mb_decode_mimeheader($sender['name']));
            }
        }

        $sender['email'] = $headers1->from[0]->mailbox . '@' . $headers1->from[0]->host;

        //set the to
        if (isset($headers1->to)) {
            foreach ($headers1->to as $to) {
                if (!isset($to->mailbox, $to->host)) {
                    continue;
                }

                $recipient = array('name'=>null);
                if (isset($to->personal)) {
                    $recipient['name'] = $to->personal;
                    //if the name is iso or utf encoded
                    if (preg_match("/^\=\?[a-zA-Z]+\-[0-9]+.*\?/", strtolower($recipient['name']))) {
                        //decode the subject
                        $recipient['name'] = str_replace('_', ' ', mb_decode_mimeheader($recipient['name']));
                    }
                }

                $recipient['email'] = $to->mailbox . '@' . $to->host;

                $recipientsTo[] = $recipient;
            }
        }

        //set the cc
        if (isset($headers1->cc)) {
            foreach ($headers1->cc as $cc) {
                $recipient = array('name'=>null);
                if (isset($cc->personal)) {
                    $recipient['name'] = $cc->personal;

                    //if the name is iso or utf encoded
                    if (preg_match("/^\=\?[a-zA-Z]+\-[0-9]+.*\?/", strtolower($recipient['name']))) {
                        //decode the subject
                        $recipient['name'] = str_replace('_', ' ', mb_decode_mimeheader($recipient['name']));
                    }
                }

                $recipient['email'] = $cc->mailbox . '@' . $cc->host;

                $recipientsCc[] = $recipient;
            }
        }

        //set the bcc
        if (isset($headers1->bcc)) {
            foreach ($headers1->bcc as $bcc) {
                $recipient = array('name'=>null);
                if (isset($bcc->personal)) {
                    $recipient['name'] = $bcc->personal;
                    //if the name is iso or utf encoded
                    if (preg_match("/^\=\?[a-zA-Z]+\-[0-9]+.*\?/", strtolower($recipient['name']))) {
                        //decode the subject
                        $recipient['name'] = str_replace('_', ' ', mb_decode_mimeheader($recipient['name']));
                    }
                }

                $recipient['email'] = $bcc->mailbox . '@' . $bcc->host;

                $recipientsBcc[] = $recipient;
            }
        }

        //if subject is not set
        if (!isset($headers1->subject) || strlen(trim($headers1->subject)) === 0) {
            //set subject
            $headers1->subject = self::NO_SUBJECT;
        }

        //trim the subject
        $headers1->subject = str_replace(array('<', '>'), '', trim($headers1->subject));

        //if the subject is iso or utf encoded
        if (preg_match("/^\=\?[a-zA-Z]+\-[0-9]+.*\?/", strtolower($headers1->subject))) {
            //decode the subject
            $headers1->subject = str_replace('_', ' ', mb_decode_mimeheader($headers1->subject));
        }

        //set thread details
        $topic  = isset($headers2['thread-topic']) ? $headers2['thread-topic'] : $headers1->subject;
        $parent = isset($headers2['in-reply-to']) ? str_replace('"', '', $headers2['in-reply-to']) : null;

        //set date
        $date = isset($headers1->date) ? strtotime($headers1->date) : null;

        //set message id
        if (isset($headers2['message-id'])) {
            $messageId = str_replace('"', '', $headers2['message-id']);
        } else {
            $messageId = '<eden-no-id-'.md5(uniqid()).'>';
        }

        $attachment = isset($headers2['content-type'])
            && strpos($headers2['content-type'], 'multipart/mixed') === 0;

        $format = array(
            'id'            => $messageId,
            'parent'        => $parent,
            'topic'         => $topic,
            'mailbox'       => 'INBOX',
            'date'          => $date,
            'subject'       => str_replace('’', '\'', $headers1->subject),
            'from'          => $sender,
            'flags'         => $flags,
            'to'            => $recipientsTo,
            'cc'            => $recipientsCc,
            'bcc'           => $recipientsBcc,
            'attachment'    => $attachment);

        if (trim($body) && $body != ')') {
            //get the body parts
            $parts = $this->getParts($email);

            //if there are no parts
            if (empty($parts)) {
                //just make the body as a single part
                $parts = array('text/plain' => $body);
            }

            //set body to the body parts
            $body = $parts;

            //look for attachments
            $attachment = array();
            //if there is an attachment in the body
            if (isset($body['attachment'])) {
                //take it out
                $attachment = $body['attachment'];
                unset($body['attachment']);
            }

            $format['body']         = $body;
            $format['attachment']   = $attachment;
        }

        return $format;
    }

    /**
     * Returns email reponse headers
     * array key value format
     *
     * @param *string $rawData The data to parse
     *
     * @return array
     */
    private function getHeaders($rawData)
    {
        if (is_string($rawData)) {
            $rawData = explode("\n", $rawData);
        }

        $key = null;
        $headers = array();
        foreach ($rawData as $line) {
            $line = trim($line);
            if (preg_match("/^([a-zA-Z0-9-]+):/i", $line, $matches)) {
                $key = strtolower($matches[1]);
                if (isset($headers[$key])) {
                    if (!is_array($headers[$key])) {
                        $headers[$key] = array($headers[$key]);
                    }

                    $headers[$key][] = trim(str_replace($matches[0], '', $line));
                    continue;
                }

                $headers[$key] = trim(str_replace($matches[0], '', $line));
                continue;
            }

            if (!is_null($key) && isset($headers[$key])) {
                if (is_array($headers[$key])) {
                    $headers[$key][count($headers[$key])-1] .= ' '.$line;
                    continue;
                }

                $headers[$key] .= ' '.$line;
            }
        }

        return $headers;
    }

    /**
     * Splits out body parts
     * ie. plain, HTML, attachment
     *
     * @param string $content The content to parse
     * @param array  $parts   The existing parts
     *
     * @return array
     */
    private function getParts($content, array $parts = array())
    {
        //separate the head and the body
        list($head, $body) = preg_split("/\n\s*\n/", $content, 2);
        //get the headers
        $head = $this->getHeaders($head);
        //if content type is not set
        if (!isset($head['content-type'])) {
            return $parts;
        }

        //split the content type
        if (is_array($head['content-type'])) {
            $type = array($head['content-type'][1]);
            if (strpos($type[0], ';') !== false) {
                $type = explode(';', $type[0], 2);
            }
        } else {
            $type = explode(';', $head['content-type'], 2);
        }

        //see if there are any extra stuff
        $extra = array();
        if (count($type) == 2) {
            $extra = explode('; ', str_replace(array('"', "'"), '', trim($type[1])));
        }

        //the content type is the first part of this
        $type = trim($type[0]);


        //foreach extra
        foreach ($extra as $i => $attr) {
            //transform the extra array to a key value pair
            $attr = explode('=', $attr, 2);
            if (count($attr) > 1) {
                list($key, $value) = $attr;
                $extra[strtolower($key)] = $value;
            }
            unset($extra[$i]);
        }

        //if a boundary is set
        if (isset($extra['boundary'])) {
            //split the body into sections
            $sections = explode('--'.str_replace(array('"', "'"), '', $extra['boundary']), $body);
            //we only want what's in the middle of these sections
            array_pop($sections);
            array_shift($sections);

            //foreach section
            foreach ($sections as $section) {
                //get the parts of that
                $parts = $this->getParts($section, $parts);
            }
        } else {
            //if name is set, it's an attachment
            //if encoding is set
            if (isset($head['content-transfer-encoding'])) {
                if (is_array($head['content-transfer-encoding']) === true) {
                    $transferEncoding = $head['content-transfer-encoding'][1];
                } else {
                    $transferEncoding = $head['content-transfer-encoding'];
                }
                //the goal here is to make everytihg utf-8 standard
                switch (strtolower($transferEncoding)) {
                    case 'binary':
                        $body = imap_binary($body);
                        break;
                    case 'base64':
                        $body = base64_decode($body);
                        break;
                    case 'quoted-printable':
                        $body = quoted_printable_decode($body);
                        break;
                    case '7bit':
                        $body = mb_convert_encoding($body, 'UTF-8', 'ISO-2022-JP');
                        break;
                    default:
                        $body = str_replace(array("\n", ' '), '', $body);
                        break;
                }
            }

            if (isset($extra['name'])) {
                //add to parts
                $parts['attachment'][$extra['name']][$type] = $body;
            } else {
                //it's just a regular body
                //add to parts
                $parts[$type] = $body;
            }
        }
        return $parts;
    }
}
