<?php

namespace Eden\Mail;

/** http://php.net/manual/pt_BR/function.imap-utf7-decode.php#116677 */
class ImapUtf7
{
    private static $imap_base64 =
        'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+,';

    private static function encodeB64Imap($s)
    {
        $a   = 0;
        $al  = 0;
        $res = '';
        $n   = strlen($s);

        for ($i = 0; $i < $n; $i++) {
            $a = ($a << 8) | ord($s[$i]);
            $al += 8;

            for (; $al >= 6; $al -= 6) {
                $res .= self::$imap_base64[($a >> ($al - 6)) & 0x3F];
            }
        }

        if ($al > 0) {
            $res .= self::$imap_base64[($a << (6 - $al)) & 0x3F];
        }

        return $res;
    }

    private static function encodeUtf8Char($w)
    {
        if ($w & 0x80000000) {
            return '';
        }

        if ($w & 0xFC000000) {
            $n = 5;
        } elseif ($w & 0xFFE00000) {
            $n = 4;
        } elseif ($w & 0xFFFF0000) {
            $n = 3;
        } elseif ($w & 0xFFFFF800) {
            $n = 2;
        } elseif ($w & 0xFFFFFF80) {
            $n = 1;
        } else {
            return chr($w);
        }

        $res = chr(((255 << (7 - $n)) | ($w >> ($n * 6))) & 255);

        while (--$n >= 0) {
            $res .= chr((($w >> ($n * 6)) & 0x3F) | 0x80);
        }

        return $res;
    }

    private static function decodeB64Imap($s)
    {
        $a   = 0;
        $al  = 0;
        $res = '';
        $n   = strlen($s);

        for ($i = 0; $i < $n; $i++) {
            $k = strpos(self::$imap_base64, $s[$i]);
            if ($k === false) {
                continue;
            }
            $a = ($a << 6) | $k;
            $al += 6;

            if ($al >= 8) {
                $res .= chr(($a >> ($al - 8)) & 255);
                $al -= 8;
            }
        }

        $r2 = '';
        $n  = strlen($res);

        for ($i = 0; $i < $n; $i++) {
            $c = ord($res[$i]);
            $i++;

            if ($i < $n) {
                $c = ($c << 8) | ord($res[$i]);
            }

            $r2 .= self::encodeUtf8Char($c);
        }

        return $r2;
    }

    public static function encode($s)
    {
        $n   = strlen($s);
        $err = 0;
        $buf = '';
        $res = '';

        for ($i = 0; $i < $n;) {
            $x = ord($s[$i++]);

            if (($x & 0x80) == 0x00) {
                $r = $x;
                $w = 0;
            } elseif (($x & 0xE0) == 0xC0) {
                $w = 1;
                $r = $x & 0x1F;
            } elseif (($x & 0xF0) == 0xE0) {
                $w = 2;
                $r = $x & 0x0F;
            } elseif (($x & 0xF8) == 0xF0) {
                $w = 3;
                $r = $x & 0x07;
            } elseif (($x & 0xFC) == 0xF8) {
                $w = 4;
                $r = $x & 0x03;
            } elseif (($x & 0xFE) == 0xFC) {
                $w = 5;
                $r = $x & 0x01;
            } elseif (($x & 0xC0) == 0x80) {
                $w = 0;
                $r = -1;
                $err++;
            } else {
                $w = 0;
                $r = -2;
                $err++;
            }

            for ($k = 0; $k < $w && $i < $n; $k++) {
                $x = ord($s[$i++]);
                if ($x & 0xE0 != 0x80) {
                    $err++;
                }
                $r = ($r << 6) | ($x & 0x3F);
            }

            if ($r < 0x20 || $r > 0x7E) {
                $buf .= chr(($r >> 8) & 0xFF);
                $buf .= chr($r & 0xFF);
            } else {
                if (strlen($buf)) {
                    $res .= '&' . self::encodeB64Imap($buf) . '-';
                    $buf = '';
                }
                if ($r == 0x26) {
                    $res .= '&-';
                } else {
                    $res .= chr($r);
                }
            }
        }

        if (strlen($buf)) {
            $res .= '&' . self::encodeB64Imap($buf) . '-';
        }

        return $res;
    }

    public static function decode($s)
    {
        $res = '';
        $n   = strlen($s);
        $h   = 0;

        while ($h < $n) {
            $t = strpos($s, '&', $h);

            if ($t === false) {
                $t = $n;
            }

            $res .= substr($s, $h, $t - $h);
            $h = $t + 1;

            if ($h >= $n) {
                break;
            }

            $t = strpos($s, '-', $h);

            if ($t === false) {
                $t = $n;
            }

            $k = $t - $h;

            if ($k == 0) {
                $res .= '&';
            } else {
                $res .= self::decodeB64Imap(substr($s, $h, $k));
            }

            $h = $t + 1;
        }

        return $res;
    }
}
