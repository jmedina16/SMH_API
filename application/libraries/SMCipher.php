<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class SMCipher {

    private $securekey;
    private $iv_size;

    function __construct() {
        $fp = fopen("/etc/smcryp/.smsk", "r");
        fscanf($fp, "%s", $this->securekey);
        fclose($fp);
        $this->iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
        $this->securekey = mhash(MHASH_MD5, $this->securekey);
    }

    function encrypt($input) {
        $iv = mcrypt_create_iv($this->iv_size, MCRYPT_RAND);
        $encrypt_word = base64_encode($iv . mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $this->securekey, $input, MCRYPT_MODE_CBC, $iv));

        if (strpos($encrypt_word, '+') !== false) {
            return $this->encrypt($input);
        } else {
            return $encrypt_word;
        }
    }

    function decrypt($input) {
        $input = base64_decode($input);
        $iv = substr($input, 0, $this->iv_size);
        $cipher = substr($input, $this->iv_size);
        return trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $this->securekey, $cipher, MCRYPT_MODE_CBC, $iv));
    }

}

