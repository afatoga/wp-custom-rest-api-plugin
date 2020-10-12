<?php

namespace Afatoga\Services;

/* Functions for converting between notations and short MD5 generation.
 * No license (public domain) but backlink is always welcome :)
 * By Proger_XP. http://proger.i-forge.net/Short_MD5/OMF
 * define('MD5_24_ALPHABET', '0123456789abcdefghijklmnopqrstuvwxyzABCDE');
 */

class HashService
{

    private $MD5_24_ALPHABET;

    public function __construct()
    {
        $this->MD5_24_ALPHABET = '0123456789abcdefghijklmnopqrstuvwxyzABCDE';
    }

    public function MD5_24($str)
    {
        return $this->RawToShortMD5($this->MD5_24_ALPHABET, md5($str, true));
    }

    public function MD5File_24($file)
    {
        return $this->RawToShortMD5($this->MD5_24_ALPHABET, md5_file($file, true));
    }

    public function MD5_RawTo24($str)
    {
        return $this->RawToShortMD5($this->MD5_24_ALPHABET, $str);
    }

    public  function MD5_32to24($hash32)
    {
        return $this->RawToShortMD5($this->MD5_24_ALPHABET, $this->MD5ToRaw($hash32));
    }

    public function MD5_24toRaw($hash24)
    {
        return $this->ShortToRawMD5($this->MD5_24_ALPHABET, $hash24);
    }

    public function MD5_24to32($hash24)
    {
        return $this->RawToMD5($this->ShortToRawMD5($this->MD5_24_ALPHABET, $hash24));
    }

    public function RawToShortMD5($alphabet, $raw)
    {
        $result = '';
        $length = strlen($this->DecToBase($alphabet, 2147483647));

        foreach (str_split($raw, 4) as $dword) {
            $dword = ord($dword[0]) + ord($dword[1]) * 256 + ord($dword[2]) * 65536 + ord($dword[3]) * 16777216;
            $result .= str_pad($this->DecToBase($alphabet, $dword), $length, $alphabet[0], STR_PAD_LEFT);
        }

        return $result;
    }

    public  function DecToBase($alphabet, $dword)
    {
        $rem = (int) fmod($dword, strlen($alphabet));
        if ($dword < strlen($alphabet)) {
            return $alphabet[$rem];
        } else {
            return $this->DecToBase($alphabet, ($dword - $rem) / strlen($alphabet)) . $alphabet[$rem];
        }
    }

    public function ShortToRawMD5($alphabet, $short)
    {
        $result = '';
        $length = strlen($this->DecToBase($alphabet, 2147483647));

        foreach (str_split($short, $length) as $chunk) {
            $dword = $this->BaseToDec($alphabet, $chunk);
            $result .= chr($dword & 0xFF) . chr($dword >> 8 & 0xFF) . chr($dword >> 16 & 0xFF) . chr($dword >> 24);
        }

        return $result;
    }

    public  function BaseToDec($alphabet, $str)
    {
        $result = 0;
        $prod = 1;

        for ($i = strlen($str) - 1; $i >= 0; --$i) {
            $weight = strpos($alphabet, $str[$i]);
            if ($weight === false) {
                throw new \Exception('BaseToDec failed - encountered a character outside of given alphabet.');
            }

            $result += $weight * $prod;
            $prod *= strlen($alphabet);
        }

        return $result;
    }

    public function MD5ToRaw($str)
    {
        return pack('H*', $str);
    }
    public function RawToMD5($raw)
    {
        return bin2hex($raw);
    }
}
