<?php //-->
/*
 * This file is part of the Mail package of the Eden PHP Library.
 * (c) 2013-2014 Openovate Labs
 *
 * Copyright and license information can be found at LICENSE
 * distributed with this package.
 */

namespace Eden\Mail;

use Eden\System\File;

/**
 * General available methods for common SMTP functionality
 *
 * @vendor Eden
 * @package Mail
 * @author Christian Blanquera cblanquera@openovate.com
 * @author Airon Paul Dumael airon.dumael@gmail.com
 * @author Andre Marcelo-Tanner atanner@openovate.com
 */
class Mail extends Base
{
    const TIMEOUT = 30;

    protected $subject  = null;
    protected $from  = array();
    protected $body = array();

    protected $to = array();
    protected $cc = array();
    protected $bcc = array();
    protected $attachments = array();

    private $debugging = false;

    /**
     * Constructor - Store connection information
     *
     * @param string
     * @param string
     * @param string
     * @param int|null
     * @param bool
     * @param bool
     */
    public function __construct() {
        $this->boundary[] = md5(time().'1');
        $this->boundary[] = md5(time().'2');
    }

    /**
     * Adds an attachment to the email
     *
     * @param string filename
     * @param string data
     * @param string mime
     * @return this
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
     * @param string email
     * @param string name
     * @return this
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
     * @param string email
     * @param string name
     * @return this
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
     * @param string email
     * @param string name
     * @return this
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
     * Reply to an existing email
     *
     * @param string message id
     * @param string topic
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
     * @return Eden\Mail\Mail
     */
    public function reset()
    {
        $this->subject      = null;
        $this->body     = array();
        $this->to           = array();
        $this->cc           = array();
        $this->bcc      = array();
        $this->attachments = array();

        return $this;
    }

    /**
     * Sends an email
     *
     * @param array custom headers
     * @return array headers
     */
    public function send(array $headers = array())
    {
        $headersArray = $this->getHeaders($headers);
        $headers = '';
        foreach ($headersArray as $key => $val) {
            // headers to ignore
            if (in_array(strtolower($key), array('to', 'subject', 'mime-version'))) { continue; }
            
            $headers .= $key . ': ' . $val . "\n";
        }
        $headers .= 'MIME-Version: 1.0';

        $to = $headersArray['To'];
        $subject = $this->subject;
        $bodyArray = $this->getBody();
        $body = implode("\n", $bodyArray);

        $this->debug('To: ' . $to);
        $this->debug('Subject: ' . $subject);
        $this->debug('Body: ' . $body);
        $this->debug('Headers: ' . $headers);

        $res = mail(
            $to, 
            $subject, 
            $body,
            $headers
        );

        $headersArray['result'] = $res;

        return $headersArray;
    }

    /**
     * Sets body
     *
     * @param string body
     * @param bool is this an html body?
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
     * @param string subject
     * @return Eden\Mail\Smtp
     */
    public function setSubject($subject)
    {
        Argument::i()->test(1, 'string');
        $this->subject = $subject;
        return $this;
    }

    /**
     * Adds an email to the bcc list
     *
     * @param string email
     * @param string name
     * @return this
     */
    public function setFrom($email, $name = null)
    {
        Argument::i()
            ->test(1, 'string')
            ->test(2, 'string', 'null');

        // reset $this->from
        $this->from = array();

        $this->from[$email] = $name;
        return $this;
    }

    /**
     * Adds the attachment string body
     * for plain text emails
     *
     * @param array
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
     * @param array
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

        $headers = array(
            'Date'          => $timestamp,
            'Subject'       => $subject,
            'To'            => implode(', ', $to));

        if (!empty($this->from)) {
            list($email, $name) = each($this->from);
            $headers['From'] = trim($name.' <'.$email.'>');

            //also set reply-to
            $headers['Reply-To'] = $headers['From'];
        }
        
        if (!empty($cc)) {
            $headers['Cc'] = implode(', ', $cc);
        }

        if (!empty($bcc)) {
            $headers['Bcc'] = implode(', ', $bcc);
        }

        $headers['Thread-Topic'] = $this->subject;

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
     * Debugging
     *
     * @param string
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
     * @param string
     * @param int line length
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
