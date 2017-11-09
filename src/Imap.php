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
 * General available methods for common IMAP functionality
 *
 * @vendor   Eden
 * @package  Mail
 * @author   Christian Blanquera <cblanquera@openovate.com>
 * @author   Airon Paul Dumael <airon.dumael@gmail.com>
 * @standard PSR-2
 */
class Imap extends Base
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
     * @var string $host The IMAP Host
     */
    protected $host = null;
       
    /**
     * @var string|null $port The IMAP port
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
     * @var int $tag The tag number
     */
    protected $tag = 0;
       
    /**
     * @var int $total The total main in mailbox
     */
    protected $total = 0;
       
    /**
     * @var int $next for pagination
     */
    protected $next = 0;
       
    /**
     * @var string|null $buffer Mail body
     */
    protected $buffer = null;
       
    /**
     * @var [RESOURCE] $socket The socket connection
     */
    protected $socket = null;
       
    /**
     * @var string|null $mailbox The mailbox name
     */
    protected $mailbox = null;
       
    /**
     * @var array $mailboxes The list of mailboxes
     */
    protected $mailboxes = array();
       
    /**
     * @var bool $debugging If true outputs the logs
     */
    private $debugging = false;

    /**
     * Constructor - Store connection information
     *
     * @param *string  $host The IMAP host
     * @param *string  $user The mailbox user name
     * @param *string  $pass The mailbox password
     * @param int|null $port The IMAP port
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
            $port = $ssl ? 993 : 143;
        }

        $this->host = $host;
        $this->username = $user;
        $this->password = $pass;
        $this->port = $port;
        $this->ssl = $ssl;
        $this->tls = $tls;
    }

    /**
     * Connects to the server
     *
     * @param int  $timeout The connection timeout
     * @param bool $test    Whether to output the logs
     *
     * @return Eden\Mail\Imap
     */
    public function connect($timeout = self::TIMEOUT, $test = false)
    {
        Argument::i()->test(1, 'int')->test(2, 'bool');

        if ($this->socket) {
            return $this;
        }

        $host = $this->host;

        if ($this->ssl) {
            $host = 'ssl://' . $host;
        }

        $errno  =  0;
        $errstr = '';

        $this->socket = @fsockopen($host, $this->port, $errno, $errstr, $timeout);

        if (!$this->socket) {
            //throw exception
            Exception::i()
                ->setMessage(Exception::SERVER_ERROR)
                ->addVariable($host.':'.$this->port)
                ->trigger();
        }

        if (strpos($this->getLine(), '* OK') === false) {
            $this->disconnect();
            //throw exception
            Exception::i()
                ->setMessage(Exception::SERVER_ERROR)
                ->addVariable($host.':'.$this->port)
                ->trigger();
        }

        if ($this->tls) {
            $this->send('STARTTLS');
            if (!stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                $this->disconnect();
                //throw exception
                Exception::i()
                ->setMessage(Exception::TLS_ERROR)
                ->addVariable($host.':'.$this->port)
                ->trigger();
            }
        }

        if ($test) {
            fclose($this->socket);

            $this->socket = null;
            return $this;
        }

        //login
        $result = $this->call('LOGIN', $this->escape($this->username, $this->password));

        if (!is_array($result) || strpos(implode(' ', $result), 'OK') === false) {
            $this->disconnect();
            //throw exception
            Exception::i(Exception::LOGIN_ERROR)->trigger();
        }

        return $this;
    }

    /**
     * Disconnects from the server
     *
     * @return Eden\Mail\Imap
     */
    public function disconnect()
    {
        if ($this->socket) {
            $this->send('LOGOUT');

            fclose($this->socket);

            $this->socket = null;
        }

        return $this;
    }

    /**
     * Returns the active mailbox
     *
     * @return string
     */
    public function getActiveMailbox()
    {
        return $this->mailbox;
    }

    /**
     * Returns a list of emails given the range
     *
     * @param number $start Pagination start
     * @param number $range Pagination range
     * @param bool   $body  add body to threads
     *
     * @return array
     */
    public function getEmails($start = 0, $range = 10, $body = false)
    {
        Argument::i()
            ->test(1, 'int', 'array')
            ->test(2, 'int');

        //if not connected
        if (!$this->socket) {
            //then connect
            $this->connect();
        }

        //if the total in this mailbox is 0
        //it means they probably didn't select a mailbox
        //or the mailbox selected is empty
        if ($this->total == 0) {
            //we might as well return an empty array
            return array();
        }

        //if start is an array
        if (is_array($start)) {
            //it is a set of numbers
            $set = implode(',', $start);
            //just ignore the range parameter
        } else {
            //start is a number
            //range must be grater than 0
            $range = $range > 0 ? $range : 1;
            //start must be a positive number
            $start = $start >= 0 ? $start : 0;

            //calculate max (ex. 300 - 4 = 296)
            $max = $this->total - $start;

            //if max is less than 1
            if ($max < 1) {
                //set max to total (ex. 300)
                $max = $this->total;
            }

            //calculate min (ex. 296 - 15 + 1 = 282)
            $min = $max - $range + 1;

            //if min less than 1
            if ($min < 1) {
                //set it to 1
                $min = 1;
            }

            //now add min and max to set (ex. 282:296 or 1 - 300)
            $set = $min . ':' . $max;

            //if min equal max
            if ($min == $max) {
                //we should only get one number
                $set = $min;
            }
        }

        $items = array('UID', 'FLAGS', 'BODY[HEADER]');

        if ($body) {
            $items  = array('UID', 'FLAGS', 'BODY[]');
        }

        //now lets call this
        $emails = $this->getEmailResponse('FETCH', array($set, $this->getList($items)));

        //this will be in ascending order
        //we actually want to reverse this
        $emails = array_reverse($emails);

        return $emails;
    }

    /**
     * Returns the total number of emails in a mailbox
     *
     * @return number
     */
    public function getEmailTotal()
    {
        return $this->total;
    }

    /**
     * Returns the total number of emails in a mailbox
     *
     * @return number
     */
    public function getNextUid()
    {
        return $this->next;
    }

    /**
     * Returns a list of mailboxes
     *
     * @return array
     */
    public function getMailboxes()
    {
        if (!$this->socket) {
            $this->connect();
        }

        $response = $this->call('LIST', $this->escape('', '*'));

        $mailboxes = array();
        foreach ($response as $line) {
            if (strpos($line, 'Noselect') !== false || strpos($line, 'LIST') == false) {
                continue;
            }

            $line = explode('"', $line);

            if (strpos(trim($line[0]), '*') !== 0) {
                continue;
            }

            $mailboxes[] = $line[count($line)-2];
        }

        return $mailboxes;
    }

    /**
     * Returns a list of emails given a uid or set of uids
     *
     * @param *number|array $uid  A list of uid/s
     * @param bool          $body Whether to also include the body
     *
     * @return array
     */
    public function getUniqueEmails($uid, $body = false)
    {
        Argument::i()
            ->test(1, 'int', 'string', 'array')
            ->test(2, 'bool');

        //if not connected
        if (!$this->socket) {
            //then connect
            $this->connect();
        }

        //if the total in this mailbox is 0
        //it means they probably didn't select a mailbox
        //or the mailbox selected is empty
        if ($this->total == 0) {
            //we might as well return an empty array
            return array();
        }

        //if uid is an array
        if (is_array($uid)) {
            $uid = implode(',', $uid);
        }

        //lets call it
        $items = array('UID', 'FLAGS', 'BODY[HEADER]');

        if ($body) {
            $items = array('UID', 'FLAGS', 'BODY[]');
        }

        $first = is_numeric($uid) ? true : false;

        return $this->getEmailResponse('UID FETCH', array($uid, $this->getList($items)), $first);
    }

    /**
     * Moves an email to another mailbox
     *
     * @param *number $uid     The mail unique ID
     * @param *string $mailbox The mailbox destination
     *
     * @return Eden\Mail\Imap
     */
    public function move($uid, $mailbox)
    {
        Argument::i()->test(1, 'int', 'string')->test(2, 'string');

        if (!$this->socket) {
            $this->connect();
        }

        $this->call('UID COPY '.$uid.' '.$mailbox);

        return $this->remove($uid);
    }

    /**
     * Remove an email from a mailbox
     *
     * @param *number $uid The mail UID to remove
     *
     * @return Eden\Mail\Imap
     */
    public function remove($uid)
    {
        Argument::i()->test(1, 'int', 'string');

        if (!$this->socket) {
            $this->connect();
        }

        $this->call('UID STORE '.$uid.' FLAGS.SILENT \Deleted');

        return $this;
    }
    
    /**
     * Remove an email from a mailbox
     *
     * @return Eden\Mail\Imap
     */
    public function expunge()
    {
        
        $this->call('expunge');
                    
        return $this;
    }

    /**
     * Searches a mailbox for specific emails
     *
     * @param *array $filter Search filters
     * @param number $start  Results start
     * @param number $range  Results range
     * @param bool   $or     Is this an OR search ?
     * @param bool   $body   Whether to include the body
     *
     * @return array
     */
    public function search(
        array $filter,
        $start = 0,
        $range = 10,
        $or = false,
        $body = false
    ) {
        Argument::i()
            ->test(2, 'int')
            ->test(3, 'int')
            ->test(4, 'bool')
            ->test(5, 'bool');

        if (!$this->socket) {
            $this->connect();
        }

        //build a search criteria
        $search = $not = array();
        foreach ($filter as $where) {
            if (is_string($where)) {
                $search[] = $where;
                continue;
            }

            if ($where[0] == 'NOT') {
                $not = $where[1];
                continue;
            }

            $item = $where[0].' "'.$where[1].'"';
            if (isset($where[2])) {
                $item .= ' "'.$where[2].'"';
            }

            $search[] = $item;
        }

        //if this is an or search
        if ($or && count($search) > 1) {
            //item1
            //OR (item1) (item2)
            //OR (item1) (OR (item2) (item3))
            //OR (item1) (OR (item2) (OR (item3) (item4)))
            $query = null;
            while ($item = array_pop($search)) {
                if (is_null($query)) {
                    $query = $item;
                } else if (strpos($query, 'OR') !== 0) {
                    $query = 'OR ('.$query.') ('.$item.')';
                } else {
                    $query = 'OR ('.$item.') ('.$query.')';
                }
            }

            $search = $query;
        } else {
            //this is an and search
            $search = implode(' ', $search);
        }

        //do the search
        $response = $this->call('UID SEARCH '.$search);

        //get the result
        $result = array_pop($response);
        //if we got some results
        if (strpos($result, 'OK') !== false) {
            //parse out the uids
            $uids = explode(' ', $response[0]);
            array_shift($uids);
            array_shift($uids);

            foreach ($uids as $i => $uid) {
                if (in_array($uid, $not)) {
                    unset($uids[$i]);
                }
            }

            if (empty($uids)) {
                return array();
            }

            $uids = array_reverse($uids);

            //pagination
            $count = 0;
            foreach ($uids as $i => $id) {
                if ($i < $start) {
                    unset($uids[$i]);
                    continue;
                }

                $count ++;

                if ($range != 0 && $count > $range) {
                    unset($uids[$i]);
                    continue;
                }
            }

            //return the email details for this
            return $this->getUniqueEmails($uids, $body);
        }

        //it's not okay just return an empty set
        return array();
    }

    /**
     * Returns the total amount of emails
     *
     * @param *array $filter Search filters
     * @param bool   $or     Is this an OR search ?
     *
     * @return number
     */
    public function searchTotal(array $filter, $or = false)
    {
        Argument::i()->test(2, 'bool');

        if (!$this->socket) {
            $this->connect();
        }

        //build a search criteria
        $search = array();
        foreach ($filter as $where) {
            $item = $where[0].' "'.$where[1].'"';
            if (isset($where[2])) {
                $item .= ' "'.$where[2].'"';
            }

            $search[] = $item;
        }

        //if this is an or search
        if ($or) {
            $search = 'OR ('.implode(') (', $search).')';
        } else {
            //this is an and search
            $search = implode(' ', $search);
        }

        $response = $this->call('UID SEARCH '.$search);

        //get the result
        $result = array_pop($response);

        //if we got some results
        if (strpos($result, 'OK') !== false) {
            //parse out the uids
            $uids = explode(' ', $response[0]);
            array_shift($uids);
            array_shift($uids);

            return count($uids);
        }

        //it's not okay just return 0
        return 0;
    }

    /**
     * IMAP requires setting an active mailbox
     * before getting a list of mails
     *
     * @param string $mailbox Name of mailbox
     *
     * @return false|Eden\Mail\Imap
     */
    public function setActiveMailbox($mailbox)
    {
        Argument::i()->test(1, 'string');

        if (!$this->socket) {
            $this->connect();
        }

        $response = $this->call('SELECT', $this->escape($mailbox));
        $result = array_pop($response);

        foreach ($response as $line) {
            if (strpos($line, 'EXISTS') !== false) {
                list($star, $this->total, $type) = explode(' ', $line, 3);
            } else if (strpos($line, 'UIDNEXT') !== false) {
                list($star, $ok, $next, $this->next, $type) = explode(' ', $line, 5);
                $this->next = substr($this->next, 0, -1);
            }

            if ($this->total && $this->next) {
                break;
            }
        }

        if (strpos($result, 'OK') !== false) {
            $this->mailbox = $mailbox;

            return $this;
        }

        return false;
    }

    /**
     * Send it out and return the response
     *
     * @param *string $command   The raw IMAP command
     * @param array   $parameter Parameters to include
     *
     * @return string|false
     */
    protected function call($command, $parameters = array())
    {
        if (!$this->send($command, $parameters)) {
            return false;
        }

        return $this->receive($this->tag);
    }

    /**
     * Returns the response one line at a time
     *
     * @return string
     */
    protected function getLine()
    {
        $line = fgets($this->socket);

        if ($line === false) {
            $this->disconnect();
        }

        $this->debug('Receiving: '.$line);

        return $line;
    }

    /**
     * Returns the response when all of it is received
     *
     * @param string $sentTag The custom tag to look for
     *
     * @return string
     */
    protected function receive($sentTag)
    {
        $this->buffer = array();

        $start = time();

        while (time() < ($start + self::TIMEOUT)) {
            list($receivedTag, $line) = explode(' ', $this->getLine(), 2);
            $this->buffer[] = trim($receivedTag . ' ' . $line);
            if ($receivedTag == 'TAG'.$sentTag) {
                return $this->buffer;
            }
        }

        return null;
    }

    /**
     * Sends out the command
     *
     * @param *string $command   The raw IMAP command
     * @param array   $parameter Parameters to include
     *
     * @return bool
     */
    protected function send($command, $parameters = array())
    {
        $this->tag ++;

        $line = 'TAG' . $this->tag . ' ' . $command;

        if (!is_array($parameters)) {
            $parameters = array($parameters);
        }

        foreach ($parameters as $parameter) {
            if (is_array($parameter)) {
                if (fputs($this->socket, $line . ' ' . $parameter[0] . "\r\n") === false) {
                    return false;
                }

                if (strpos($this->getLine(), '+ ') === false) {
                    return false;
                }

                $line = $parameter[1];
            } else {
                $line .= ' ' . $parameter;
            }
        }

        $this->debug('Sending: '.$line);

        return fputs($this->socket, $line . "\r\n");
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
     * Escaping works differently with IMAP
     * compared to how PHP escapes code
     *
     * @param *string $string the string to escape
     *
     * @return string
     */
    private function escape($string)
    {
        if (func_num_args() < 2) {
            if (strpos($string, "\n") !== false) {
                return array('{' . strlen($string) . '}', $string);
            } else {
                return '"' . str_replace(array('\\', '"'), array('\\\\', '\\"'), $string) . '"';
            }
        }

        $result = array();
        foreach (func_get_args() as $string) {
            $result[] = $this->escape($string);
        }

        return $result;
    }

    /**
     * Secret Sauce - Transform an email string
     * response to array key value format
     *
     * @param *string      $email    The actual email
     * @param string|null  $uniqueId The mail UID
     * @param array        $flags    Any mail flags
     *
     * @return array
     */
    private function getEmailFormat($email, $uniqueId = null, array $flags = array())
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
            'mailbox'       => $this->mailbox,
            'uid'           => $uniqueId,
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
     * Splits emails into arrays
     *
     * @param *string $command    The IMAP command
     * @param array   $parameters Any extra parameters
     * @param bool    $first      Whether the return should just be the first
     *
     * @return array
     */
    private function getEmailResponse($command, $parameters = array(), $first = false)
    {
        //send out the command
        if (!$this->send($command, $parameters)) {
            return false;
        }

        $messageId  = $uniqueId = $count = 0;
        $emails     = $email = array();
        $start      = time();

        //while there is no hang
        while (time() < ($start + self::TIMEOUT)) {
            //get a response line
            $line = str_replace("\n", '', $this->getLine());

            //if the line starts with a fetch
            //it means it's the end of getting an email
            if (strpos($line, 'FETCH') !== false && strpos($line, 'TAG'.$this->tag) === false) {
                //if there is email data
                if (!empty($email)) {
                    //create the email format and add it to emails
                    $emails[$uniqueId] = $this->getEmailFormat($email, $uniqueId, $flags);

                    //if all we want is the first one
                    if ($first) {
                        //just return this
                        return $emails[$uniqueId];
                    }

                    //make email data empty again
                    $email = array();
                }

                //if just okay
                if (strpos($line, 'OK') !== false) {
                    //then skip the rest
                    continue;
                }

                //if it's not just ok
                //it will contain the message id and the unique id and flags
                $flags = array();
                if (strpos($line, '\Answered') !== false) {
                    $flags[] = 'answered';
                }

                if (strpos($line, '\Flagged') !== false) {
                    $flags[] = 'flagged';
                }

                if (strpos($line, '\Deleted') !== false) {
                    $flags[] = 'deleted';
                }

                if (strpos($line, '\Seen') !== false) {
                    $flags[] = 'seen';
                }

                if (strpos($line, '\Draft') !== false) {
                    $flags[] = 'draft';
                }

                $findUid = explode(' ', $line);
                foreach ($findUid as $i => $uid) {
                    if (is_numeric($uid)) {
                        $uniqueId = $uid;
                    }
                    if (strpos(strtolower($uid), 'uid') !== false) {
                        $uniqueId = $findUid[$i+1];
                        break;
                    }
                }

                //skip the rest
                continue;
            }

            //if there is a tag it means we are at the end
            if (strpos($line, 'TAG'.$this->tag) !== false) {
                //if email details are not empty and the last line is just a )
                if (!empty($email) && strpos(trim($email[count($email) -1]), ')') === 0) {
                    //take it out because that is not part of the details
                    array_pop($email);
                }

                //if there is email data
                if (!empty($email)) {
                    //create the email format and add it to emails
                    $emails[$uniqueId] = $this->getEmailFormat($email, $uniqueId, $flags);

                    //if all we want is the first one
                    if ($first) {
                        //just return this
                        return $emails[$uniqueId];
                    }
                }

                //break out of this loop
                break;
            }

            //so at this point we are getting raw data
            //capture this data in email details
            $email[] = $line;
        }

        return $emails;
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
     * Returns stringified list
     * considering arrays inside of arrays
     *
     * @param array $array The list to transform
     *
     * @return string
     */
    private function getList($array)
    {
        $list = array();
        foreach ($array as $key => $value) {
            $list[] = !is_array($value) ? $value : $this->getList($v);
        }

        return '(' . implode(' ', $list) . ')';
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
        //front()->output($head);
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
                $extra[$key] = $value;
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
                //the goal here is to make everytihg utf-8 standard
                if (is_array($head['content-transfer-encoding'])) {
                    $head['content-transfer-encoding'] = array_pop($head['content-transfer-encoding']);
                }

                switch (strtolower($head['content-transfer-encoding'])) {
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

// if IMAP PHP is not installed we still need these functions
if (!function_exists('imap_rfc822_parse_headers')) {
    function imap_rfc822_parse_headers_decode($from)
    {
        if (preg_match('#\<([^\>]*)#', html_entity_decode($from))) {
            preg_match('#([^<]*)\<([^\>]*)\>#', html_entity_decode($from), $From);
            $from = array(
                'personal'  => trim($From[1]),
                'email'     => trim($From[2]));
        } else {
            $from = array(
                'personal'  => '',
                'email'     => trim($from));
        }

        preg_match('#([^\@]*)@(.*)#', $from['email'], $from);

        if (empty($from[1])) {
            $from[1] = '';
        }

        if (empty($from[2])) {
            $from[2] = '';
        }

        $__from = array(
            'mailbox'   => trim($from[1]),
            'host'      => trim($from[2]));

        return (object) array_merge($from, $__from);
    }

    function imap_rfc822_parse_headers($header)
    {
        $header = htmlentities($header);
        $headers = new \stdClass();
        $tos = $ccs = $bccs = array();
        $headers->to = $headers->cc = $headers->bcc = array();

        preg_match('#Message\-(ID|id|Id)\:([^\n]*)#', $header, $ID);
        $headers->ID = trim($ID[2]);
        unset($ID);

        preg_match('#\nTo\:([^\n]*)#', $header, $to);
        if (isset($to[1])) {
            $tos = array(trim($to[1]));
            if (strpos($to[1], ',') !== false) {
                explode(',', trim($to[1]));
            }
        }

        $headers->from = array(new \stdClass());
        preg_match('#\nFrom\:([^\n]*)#', $header, $from);
        $headers->from[0] = imap_rfc822_parse_headers_decode(trim($from[1]));

        preg_match('#\nCc\:([^\n]*)#', $header, $cc);
        if (isset($cc[1])) {
            $ccs = array(trim($cc[1]));
            if (strpos($cc[1], ',') !== false) {
                explode(',', trim($cc[1]));
            }
        }

        preg_match('#\nBcc\:([^\n]*)#', $header, $bcc);
        if (isset($bcc[1])) {
            $bccs = array(trim($bcc[1]));
            if (strpos($bcc[1], ',') !== false) {
                explode(',', trim($bcc[1]));
            }
        }

        preg_match('#\nSubject\:([^\n]*)#', $header, $subject);
        $headers->subject = 'no subject';
        if (isset($subject[1])) {
            $headers->subject = trim($subject[1]);
        }
        unset($subject);

        preg_match('#\nDate\:([^\n]*)#', $header, $date);
        $date = substr(trim($date[0]), 6);

        $date = preg_replace('/\(.*\)/', '', $date);

        $headers->date = trim($date);
        unset($date);

        foreach ($ccs as $k => $cc) {
            $headers->cc[$k] = imap_rfc822_parse_headers_decode(trim($cc));
        }

        foreach ($bccs as $k => $bcc) {
            $headers->bcc[$k] = imap_rfc822_parse_headers_decode(trim($bcc));
        }

        foreach ($tos as $k => $to) {
            $headers->to[$k] = imap_rfc822_parse_headers_decode(trim($to));
        }

        return $headers;
    }
}
