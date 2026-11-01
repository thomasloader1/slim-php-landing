<?php

namespace App\Services;

class EncryptionService
{
    private const CIPHER = 'aes-256-cbc';
    private const KEY_PREFIX_V1 = 'v1:';
    private const KEY_PREFIX_V2 = 'v2:';

    private string $keyV1;
    private ?string $keyV2;
    private string $activeKeyVersion;

    public function __construct(
        ?string $keyV1 = null,
        ?string $keyV2 = null,
        ?string $activeKeyVersion = null
    ) {
        $this->keyV1 = $keyV1 ?? getenv('TENANT_ENCRYPTION_KEY') ?: ($_ENV['TENANT_ENCRYPTION_KEY'] ?? '');
        $this->keyV2 = $keyV2 ?? getenv('TENANT_ENCRYPTION_KEY_V2') ?: ($_ENV['TENANT_ENCRYPTION_KEY_V2'] ?? '');
        // Un valor explícito gana sobre el entorno (antes el entorno lo pisaba y la
        // rotación v1->v2 volvía a cifrar con v1).
        $this->activeKeyVersion = $activeKeyVersion
            ?? (getenv('TENANT_ENCRYPTION_KEY_ACTIVE') ?: ($_ENV['TENANT_ENCRYPTION_KEY_ACTIVE'] ?? 'v1'));

        // Clave ausente => hasKey() false (el middleware responde 503); clave débil => error
        if (!empty($this->keyV1)) {
            $this->validateKeyStrength($this->keyV1, 'TENANT_ENCRYPTION_KEY');
        }
        if (!empty($this->keyV2)) {
            $this->validateKeyStrength($this->keyV2, 'TENANT_ENCRYPTION_KEY_V2');
        }
    }

    /**
     * Encripta un string con AES-256-CBC usando la clave activa.
     * Retorna base64(iv + ciphertext) prefixeado con la versión de la clave (ej. v1:...).
     */
    public function encrypt(string $plain): string
    {
        $key = $this->getActiveKey();
        $keyHash = hash('sha256', $key, true);
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length(self::CIPHER));
        $encrypted = openssl_encrypt($plain, self::CIPHER, $keyHash, OPENSSL_RAW_DATA, $iv);

        $prefix = $this->activeKeyVersion === 'v2' ? self::KEY_PREFIX_V2 : self::KEY_PREFIX_V1;
        return $prefix . base64_encode($iv . $encrypted);
    }

    /**
     * Desencripta un string previamente encriptado con encrypt().
     * Detecta automáticamente la versión de la clave por el prefijo (v1: o v2:).
     * Retrocompatibilidad: si no tiene prefijo, usa la clave v1.
     */
    public function decrypt(string $cipher): string
    {
        $keyVersion = $this->detectKeyVersion($cipher);
        $key = $keyVersion === 'v2' ? $this->keyV2 : $this->keyV1;

        if (empty($key)) {
            throw new \RuntimeException('Clave de encriptación no configurada para la versión: ' . $keyVersion);
        }

        $keyHash = hash('sha256', $key, true);

        // Remover prefijo si existe
        if (str_starts_with($cipher, self::KEY_PREFIX_V1)) {
            $cipher = substr($cipher, strlen(self::KEY_PREFIX_V1));
        } elseif (str_starts_with($cipher, self::KEY_PREFIX_V2)) {
            $cipher = substr($cipher, strlen(self::KEY_PREFIX_V2));
        }

        $data = base64_decode($cipher);
        $ivLength = openssl_cipher_iv_length(self::CIPHER);
        $iv = substr($data, 0, $ivLength);
        $encrypted = substr($data, $ivLength);

        $decrypted = openssl_decrypt($encrypted, self::CIPHER, $keyHash, OPENSSL_RAW_DATA, $iv);

        if ($decrypted === false) {
            throw new \RuntimeException('No se pudo desencriptar el valor. Verifique la clave de encriptación.');
        }

        return $decrypted;
    }
    private function validateKeyStrength(string $key, string $envName): void
    {
        if ($key === '') {
            throw new \RuntimeException("Variable de entorno {$envName} no está configurada o está vacía. Se requiere una clave para el servicio de cifrado.");
        }

        $decoded = base64_decode($key, true);
        $effectiveLength = ($decoded !== false) ? strlen($decoded) : strlen($key);

        if ($effectiveLength < 32) {
            throw new \RuntimeException("Clave {$envName} es demasiado corta o carece de entropía suficiente. Se requieren al menos 32 bytes (o base64 de 32 bytes).");
        }

        if (strlen($key) < 16) {
            throw new \RuntimeException("Clave {$envName} no cumple con la fortaleza mínima requerida (< 16 caracteres).");
        }
    }

    /**
     * Obtiene la clave activa para encriptación.
     */
    private function getActiveKey(): string
    {
        $key = $this->activeKeyVersion === 'v2' ? $this->keyV2 : $this->keyV1;
        if (empty($key)) {
            throw new \RuntimeException('Clave de encriptación activa no configurada (TENANT_ENCRYPTION_KEY' . ($this->activeKeyVersion === 'v2' ? '_V2' : '') . ')');
        }
        return $key;
    }

    /**
     * Detecta la versión de la clave por el prefijo del valor cifrado.
     */
    private function detectKeyVersion(string $cipher): string
    {
        if (str_starts_with($cipher, self::KEY_PREFIX_V2)) {
            return 'v2';
        }
        // Por defecto v1 (incluye valores sin prefijo para retrocompatibilidad)
        return 'v1';
    }

    /**
     * Verifica si una clave de encriptación está configurada.
     */
    public function hasKey(string $version = 'v1'): bool
    {
        return $version === 'v2' ? !empty($this->keyV2) : !empty($this->keyV1);
    }
}