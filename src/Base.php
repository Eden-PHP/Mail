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
 * Base Class
 *
 * @vendor   Eden
 * @package  Mail
 * @author   Christian Blanquera <cblanquera@openovate.com>
 * @standard PSR-2
 */
class Base extends \Eden\Core\Base
{
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
        $headers->subject = trim($subject[1]);
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
