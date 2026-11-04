<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Capsule\Manager as Capsule;

class AuthService
{
    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    private const MAX_ATTEMPTS = 5;
    private const LOCK_SECONDS = 900; // 15 minutos

    public function login(string $email, string $password, string $ip = '', string $context = 'master'): bool
    {
        // Rate limit por IP + email; clave hasheada para entrar en setting_key (80)
        $key = 'auth_attempts:' . sha1($ip . '|' . strtolower($email));
        [$attempts, $lockedUntil] = $this->readAttempts($key);

        if ($lockedUntil > time()) {
            log_error("[AuthService] Login bloqueado para {$ip}", 'security');
            return false;
        }

        $user = User::where('email', $email)->where('active', 1)->first();

        if ($user && password_verify($password, $user->password_hash)) {
            Capsule::table('settings')->where('setting_key', $key)->delete();

            session_regenerate_id(true);
            unset($_SESSION['csrf_token']); // token CSRF nuevo para la sesión autenticada
            $_SESSION['user_id'] = $user->id;
            $_SESSION['user_name'] = $user->name;
            $_SESSION['user_role'] = $user->role;
            // Ata la sesión al contexto (master o site:<id>) donde se autenticó
            $_SESSION['context'] = $context;
            unset($_SESSION['auth_source'], $_SESSION['impersonated_by']);
            return true;
        }

        // Credenciales del root de la master en un sitio hijo: valida contra la DB
        // master (MASTER_DB_*) sin necesidad de usuario en la DB del sitio.
        if (str_starts_with($context, 'site:') && ($masterUser = $this->masterRootUser($email)) !== null
            && password_verify($password, (string) $masterUser->password_hash)) {
            Capsule::table('settings')->where('setting_key', $key)->delete();

            session_regenerate_id(true);
            unset($_SESSION['csrf_token']);
            $_SESSION['user_id'] = (int) $masterUser->id;
            $_SESSION['user_name'] = (string) $masterUser->name;
            $_SESSION['user_role'] = (string) $masterUser->role;
            $_SESSION['context'] = $context;
            // La sesión se valida contra la conexión master (ver AuthMiddleware)
            $_SESSION['auth_source'] = 'master';
            $_SESSION['impersonated_by'] = 'master';
            log_error("[AuthService][AUDIT] Login master '{$email}' en {$context} desde {$ip}");
            return true;
        }

        $attempts++;
        $lockedUntil = $attempts >= self::MAX_ATTEMPTS ? time() + self::LOCK_SECONDS : 0;
        Capsule::table('settings')->updateOrInsert(
            ['setting_key' => $key],
            ['setting_value' => json_encode(['attempts' => $attempts, 'locked_until' => $lockedUntil])]
        );
        log_error("[AuthService] Login fallido desde {$ip} ({$attempts}/" . self::MAX_ATTEMPTS . ")", 'warning');

        return false;
    }

    /** ¿Esta instancia conoce la DB master (MASTER_DB_* en el .env)? */
    public static function masterConfigured(): bool
    {
        $raw = getenv('MASTER_DB_NAME');
        if ($raw === false) {
            $raw = $_ENV['MASTER_DB_NAME'] ?? '';
        }
        return trim((string) $raw) !== '';
    }

    /**
     * Usuario con el que el master entra a un sitio hijo (login con credenciales):
     * el root de la master que coincida con el email. Requiere MASTER_DB_* en el .env.
     */
    public function masterRootUser(string $email): ?object
    {
        if (!self::masterConfigured()) {
            return null;
        }
        try {
            $user = (clone $this->masterUsers())->where('email', $email)
                ->where('role', AccessService::ROOT)->where('active', 1)->first();
            if (!$user && !$this->masterHasRoot()) {
                // Legacy: la master todavía no migró a roles (no existe ningún root)
                $user = (clone $this->masterUsers())->where('email', $email)
                    ->where('role', 'admin')->where('active', 1)->first();
            }
            return $user ?: null;
        } catch (\Throwable $e) {
            log_error('[AuthService] master fallback: ' . $e->getMessage(), null, [], $e);
            return null;
        }
    }

    /**
     * Root de la master para el SSO (no depende de MASTER_DB_*: si la conexión
     * 'master' está aliasada a la propia DB, esta instancia ES la master).
     */
    public function masterRoot(): ?object
    {
        try {
            $user = (clone $this->masterUsers())->where('role', AccessService::ROOT)
                ->where('active', 1)->orderBy('id')->first();
            if (!$user && !$this->masterHasRoot()) {
                $user = (clone $this->masterUsers())->where('role', 'admin')
                    ->where('active', 1)->orderBy('id')->first();
            }
            return $user ?: null;
        } catch (\Throwable $e) {
            log_error('[AuthService] master root: ' . $e->getMessage(), null, [], $e);
            return null;
        }
    }

    private function masterUsers(): \Illuminate\Database\Query\Builder
    {
        return Capsule::connection('master')->table('users');
    }

    private function masterHasRoot(): bool
    {
        return (clone $this->masterUsers())->where('role', AccessService::ROOT)->exists();
    }

    /** @return array{0:int,1:int} [intentos, bloqueado_hasta] */
    private function readAttempts(string $key): array
    {
        $raw = Capsule::table('settings')->where('setting_key', $key)->value('setting_value');
        $data = $raw ? json_decode($raw, true) : null;
        if (!is_array($data)) {
            return [0, 0];
        }
        $lockedUntil = (int) ($data['locked_until'] ?? 0);
        // Bloqueo vencido: se reinicia el contador
        if ($lockedUntil && $lockedUntil <= time()) {
            return [0, 0];
        }
        return [(int) ($data['attempts'] ?? 0), $lockedUntil];
    }

    /**
     * Inicia sesión directamente como $user (SSO del superadmin). Sin chequeo de clave:
     * el llamador ya validó un token de un solo uso.
     */
    public function loginAs(User $user, string $context, ?string $impersonatedBy = null): void
    {
        session_regenerate_id(true);
        unset($_SESSION['csrf_token']); // token CSRF nuevo para la sesión autenticada
        $_SESSION['user_id'] = $user->id;
        $_SESSION['user_name'] = $user->name;
        $_SESSION['user_role'] = $user->role;
        $_SESSION['context'] = $context;
        if ($impersonatedBy !== null) {
            $_SESSION['impersonated_by'] = $impersonatedBy;
        } else {
            unset($_SESSION['impersonated_by'], $_SESSION['auth_source']);
        }
    }

    public function logout(): void
    {
        session_unset();
        session_destroy();
    }

    public function isAuthenticated(): bool
    {
        return isset($_SESSION['user_id']);
    }

    /** Contexto (master | site:<id>) en el que se inició la sesión. */
    public function sessionContext(): ?string
    {
        return $_SESSION['context'] ?? null;
    }

    public function user(): ?User
    {
        if (!$this->isAuthenticated()) {
            return null;
        }
        return User::find($_SESSION['user_id']);
    }
}
