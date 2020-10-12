<?php

//namespace Afatoga\Services;

class EncryptService
{
    // public function encrypt_decrypt($action, $string) 
    // {
    //     $output = false;
    //     $encrypt_method = "AES-256-GCM";
    //     $secret_key = 'xxxxxxxxxxxxxxxxxxxxxxxx';
    //     $secret_iv = 'xxxxxxxxxxxxxxxxxxxxxxxxx';
    //     // hash
    //     $key = hash('sha256', $secret_key);    
    //     // iv - encrypt method AES-256-CBC expects 16 bytes 
    //     $iv = substr(hash('sha256', $secret_iv), 0, 16);
    //     if ( $action == 'encrypt' ) {
    //         $output = openssl_encrypt($string, $encrypt_method, $key, 0, $iv);
    //         $output = base64_encode($output);
    //     } else if( $action == 'decrypt' ) {
    //         $output = openssl_decrypt(base64_decode($string), $encrypt_method, $key, 0, $iv);
    //     }
    //     return $output;
    // }

    public function encrypt(string $message): string
    {
        $cipher = "AES-128-GCM";
        if (in_array($cipher, openssl_get_cipher_methods())) {
            $ivlen = openssl_cipher_iv_length($cipher);
            $iv = openssl_random_pseudo_bytes($ivlen);
            //$ciphertext = openssl_encrypt($message, $cipher, $key, $options = 0, $iv, $key);
            $ciphertext = openssl_encrypt($message, $cipher, ENCRYPTFCE_SECRET_KEY, $options = 0, $iv);
            //store $cipher, $iv, and $tag for decryption later
            return $ciphertext
        }
    }

    public function decrypt($ciphertext): string
    {   
        $cipher = "AES-128-GCM";
        $original_plaintext = openssl_decrypt($ciphertext, $cipher, ENCRYPTFCE_SECRET_KEY, $options = 0, $iv);
        return $original_plaintext;
    }
}
