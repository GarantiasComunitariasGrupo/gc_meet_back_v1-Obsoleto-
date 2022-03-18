<?php

namespace App\Http\Classes;

class Encrypt
{
    public function encriptar($pass)
    {
        $crypted = Hash::crypt($pass, 'gcmeet-gcg');

        foreach (Hash::$rules as $key => $value) {
            $crypted = str_replace($value, $key, $crypted);
        }

        return $crypted;
    }

    public function desencriptar($codificado)
    {
        foreach (Hash::$rules as $key => $value) {
            $codificado = str_replace($key, $value, $codificado);
        }

        return Hash::decrypt($codificado, 'gcmeet-gcg');
    }
}

class Hash
{
    public static $rules = [
        "GC0MEET" => "!",
        "GC1MEET" => "*",
        "GC2MEET" => "'",
        "GC3MEET" => "(",
        "GC4MEET" => ")",
        "GC5MEET" => ";",
        "GC6MEET" => ":",
        "GC7MEET" => "@",
        "GC8MEET" => "&",
        "GC9MEET" => "=",
        "GC10MEET" => "+",
        "GC11MEET" => "$",
        "GC12MEET" => ",",
        "GC13MEET" => "/",
        "GC14MEET" => "\\",
        "GC15MEET" => "?",
        "GC16MEET" => "%",
        "GC17MEET" => "#",
        "GC18MEET" => "\"",
        "GC19MEET" => "[",
        "GC20MEET" => "]",
        "GC21MEET" => "{",
        "GC22MEET" => "}",
    ];

    /**
     * Codifica una cadena
     *
     * @var string $simple_string Cadena a codificar
     * @var string $encryption_key Contraseña
     */
    public static function crypt(string $simple_string, string $encryption_key)
    {
        // Store the cipher method
        $ciphering = "AES-128-CTR";
        // Use OpenSSl Encryption method
        $iv_length = openssl_cipher_iv_length($ciphering);
        $options = 0;
        // Non-NULL Initialization Vector for encryption
        $encryption_iv = '1234567891011121';

        // Use openssl_encrypt() function to encrypt the data
        return openssl_encrypt($simple_string, $ciphering, $encryption_key, $options, $encryption_iv);
    }

    /**
     * Decodifica una cadena
     *
     * @var string $encrypted_string Cadena a codificada
     * @var string $encryption_key Contraseña
     */
    public static function decrypt(string $encrypted_string, string $encryption_key)
    {
        // Store the cipher method
        $ciphering = "AES-128-CTR";
        // Use OpenSSl Encryption method
        $iv_length = openssl_cipher_iv_length($ciphering);
        $options = 0;
        // Non-NULL Initialization Vector for encryption
        $encryption_iv = '1234567891011121';

        // Use openssl_decrypt() function to decrypt the data
        return openssl_decrypt($encrypted_string, $ciphering, $encryption_key, $options, $encryption_iv);
    }

}