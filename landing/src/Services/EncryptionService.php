<?php

namespace App\Services;

class EncryptionService
{
    private const CIPHER = 'aes-256-cbc';

    /**
     * Encripta un string con AES-256-CBC.
     * Retorna base64(iv + ciphertext).
     */
    public function encrypt(string $plain, string $key): string
    {
        $key = hash('sha256', $key, true);
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length(self::CIPHER));
        $encrypted = openssl_encrypt($plain, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv);

        return base64_encode($iv . $encrypted);
    }

    /**
     * Desencripta un string previamente encriptado con encrypt().
     */
    public function decrypt(string $cipher, string $key): string
    {
        $key = hash('sha256', $key, true);
        $data = base64_decode($cipher);
        $ivLength = openssl_cipher_iv_length(self::CIPHER);
        $iv = substr($data, 0, $ivLength);
        $encrypted = substr($data, $ivLength);

        $decrypted = openssl_decrypt($encrypted, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv);

        if ($decrypted === false) {
            throw new \RuntimeException('No se pudo desencriptar el valor. Verifique la clave de encriptación.');
        }

        return $decrypted;
    }
}
