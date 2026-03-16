<?php
/**
 * Wizard de Actualización – template-2mez-landing
 *
 * Detecta el commit/versión actual del código, compara con la versión
 * instalada en la base de datos y aplica las migraciones SQL pendientes
 * que se encuentran en db/updates/.
 *
 * Convención de archivos SQL:
 *   YYYYMMDD_HHMM_{version}_{descripcion}.sql
 *   Ej: 20260316_1100_1_1_0_seo_settings.sql
 *
 * Flujo:
 *   Step 1 → Login (admin)
 *   Step 2 → Estado (versión instalada vs. código + migraciones pendientes)
 *   Step 3 → Aplicar migraciones + log
 *   Step 4 → Completado
 *
 * Seguridad:
 *   - Solo accesible si la app está instalada (.installed existe)
 *   - Requiere autenticación con cuenta admin activa
 *   - CSRF token en cada formulario
 */

declare(strict_types=1);

// ─── Autoload ────────────────────────────────────────────────────
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Install/InstallLock.php';
require_once __DIR__ . '/../src/Install/DbInstaller.php';
require_once __DIR__ . '/../src/Install/UpdateManager.php';

use App\Install\InstallLock;
use App\Install\DbInstaller;
use App\Install\UpdateManager;

session_start();

// ─── Gate: debe estar instalado ─────────────────────────────────
$lock = new InstallLock();
if (!$lock->isInstalled()) {
    header('Location: install.php');
    exit;
}

// ─── CSRF ────────────────────────────────────────────────────────
if (empty($_SESSION['update_csrf'])) {
    $_SESSION['update_csrf'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION['update_csrf'];

// ─── Estado de la sesión ─────────────────────────────────────────
$step        = (int) ($_SESSION['update_step'] ?? 1);
$errors      = [];
$success     = [];
$log         = [];
$updateStats = [];

$installer = new DbInstaller();

// ─── Conectar a la DB (necesario desde step 2+) ─────────────────
$pdo     = null;
$manager = null;

function connectFromEnv(DbInstaller $installer): ?\PDO
{
    $envFile = __DIR__ . '/../.env';
    if (!file_exists($envFile)) {
        return null;
    }

    $config = [];
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }
        [$k, $v] = explode('=', $line, 2);
        $config[trim($k)] = trim($v, '"\'');
    }

    try {
        return $installer->connect($config);
    } catch (\PDOException) {
        return null;
    }
}

// ─── Procesamiento POST ──────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (($_POST['_csrf'] ?? '') !== $csrf) {
        $errors[] = 'Token de seguridad inválido. Recargá la página.';
    } else {

        $action = $_POST['action'] ?? '';

        // ── PASO 1 → 2: Login ────────────────────────────────────
        if ($action === 'login') {
            $email    = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';

            if (empty($email) || empty($password)) {
                $errors[] = 'Email y contraseña son obligatorios.';
            } else {
                $pdo = connectFromEnv($installer);

                if (!$pdo) {
                    $errors[] = 'No se pudo conectar a la base de datos. Verificá que el archivo .env esté configurado.';
                } else {
                    try {
                        $stmt = $pdo->prepare(
                            "SELECT id, password_hash, role, active FROM users WHERE email = ? LIMIT 1"
                        );
                        $stmt->execute([$email]);
                        $user = $stmt->fetch(\PDO::FETCH_ASSOC);

                        if (!$user || !password_verify($password, $user['password_hash'])) {
                            $errors[] = 'Credenciales incorrectas.';
                        } elseif (!$user['active']) {
                            $errors[] = 'Tu cuenta está desactivada.';
                        } elseif ($user['role'] !== 'admin') {
                            $errors[] = 'Se requiere una cuenta con rol admin para ejecutar actualizaciones.';
                        } else {
                            $_SESSION['update_step']       = 2;
                            $_SESSION['update_auth_email'] = $email;
                            $step = 2;
                            $success[] = 'Sesión iniciada correctamente.';
                        }
                    } catch (\PDOException $e) {
                        $errors[] = 'Error de base de datos: ' . $e->getMessage();
                    }
                }
            }
        }

        // ── PASO 2 → 3: Aplicar migraciones ─────────────────────
        elseif ($action === 'apply' && !empty($_SESSION['update_auth_email'])) {
            $pdo = connectFromEnv($installer);

            if (!$pdo) {
                $errors[] = 'No se pudo conectar a la base de datos.';
            } else {
                $manager = new UpdateManager($pdo);

                try {
                    $results = $manager->applyAll($installer);

                    $_SESSION['update_step']    = 3;
                    $_SESSION['update_results'] = $results;
                    $_SESSION['update_version'] = $manager->getCodeVersion();
                    $step = 3;

                } catch (\Exception $e) {
                    $errors[] = 'Error durante la actualización: ' . $e->getMessage();
                }
            }
        }

        // ── PASO 3 → Fin: Reset de sesión ────────────────────────
        elseif ($action === 'finish') {
            session_destroy();
            header('Location: /admin/settings');
            exit;
        }

        // ── Logout / Reiniciar ───────────────────────────────────
        elseif ($action === 'logout') {
            session_destroy();
            header('Location: update.php');
            exit;
        }
    }
}

// ─── Datos de estado para el paso actual ─────────────────────────
if ($step >= 2 && empty($errors) && !$pdo) {
    $pdo = connectFromEnv($installer);
}

if ($pdo && !$manager) {
    $manager = new UpdateManager($pdo);
}

// Recuperar resultados guardados en step 3
if ($step === 3) {
    $results  = $_SESSION['update_results'] ?? [];
    $newVersion = $_SESSION['update_version'] ?? '';
}

// ─── Helpers ─────────────────────────────────────────────────────
$stepLabels = [
    1 => 'Login',
    2 => 'Estado',
    3 => 'Resultado',
];

function countErrors(array $log): int
{
    return count(array_filter($log, fn($l) => str_starts_with($l, '✗')));
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Actualización del Sistema</title>
    <meta name="robots" content="noindex, nofollow">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body        { font-family: 'Inter', system-ui, sans-serif; }
        .step-active  { background: #3b82f6; color: #fff; }
        .step-done    { background: #22c55e; color: #fff; }
        .step-pending { background: #1e293b; color: #64748b; }
        .log-ok  { color: #86efac; }
        .log-err { color: #fca5a5; }
        .log-sep { color: #94a3b8; }
        .log-warn{ color: #fde68a; }
    </style>
</head>
<body class="min-h-screen bg-slate-950 text-white flex flex-col items-center justify-start py-16 px-4">

    <!-- Header -->
    <div class="w-full max-w-2xl mb-10 text-center">
        <div class="text-4xl font-black tracking-tight mb-1 text-white">
            <i class="fa-solid fa-code-branch text-amber-400 mr-2"></i>Sistema de Actualización
        </div>
        <p class="text-slate-400 text-sm">Landing App – Aplicar migraciones del nuevo commit</p>
    </div>

    <!-- Progress Steps -->
    <div class="w-full max-w-2xl mb-8">
        <div class="flex items-center justify-between">
            <?php foreach ($stepLabels as $n => $label): ?>
                <div class="flex flex-col items-center gap-1 flex-1">
                    <div class="w-9 h-9 rounded-full flex items-center justify-center text-sm font-bold transition-all
                        <?= $n < $step ? 'step-done' : ($n === $step ? 'step-active' : 'step-pending') ?>">
                        <?= $n < $step ? '<i class="fa-solid fa-check text-xs"></i>' : $n ?>
                    </div>
                    <span class="text-[9px] sm:text-[11px] <?= $n === $step ? 'text-blue-400 font-semibold' : 'text-slate-500' ?> whitespace-nowrap">
                        <?= htmlspecialchars($label) ?>
                    </span>
                </div>
                <?php if ($n < count($stepLabels)): ?>
                    <div class="flex-1 h-px <?= $n < $step ? 'bg-green-500' : 'bg-slate-700' ?> -mt-5 mx-1"></div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Card -->
    <div class="w-full max-w-2xl bg-slate-900 border border-slate-800 rounded-3xl shadow-2xl overflow-hidden">

        <!-- Mensajes -->
        <?php if (!empty($errors)): ?>
            <div class="bg-red-900/30 border-b border-red-800 px-8 py-4">
                <?php foreach ($errors as $e): ?>
                    <p class="text-red-300 text-sm"><i class="fa-solid fa-triangle-exclamation mr-2"></i><?= htmlspecialchars($e) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success) && $step < 3): ?>
            <div class="bg-green-900/30 border-b border-green-800 px-8 py-4">
                <?php foreach ($success as $s): ?>
                    <p class="text-green-300 text-sm"><i class="fa-solid fa-circle-check mr-2"></i><?= htmlspecialchars($s) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>


        <?php /* ══════════════════════════════════════════════════
            PASO 1 – LOGIN
        ══════════════════════════════════════════════════ */ ?>
        <?php if ($step === 1): ?>
        <div class="p-10">
            <div class="flex items-center gap-4 mb-8">
                <div class="w-12 h-12 bg-amber-500/10 rounded-2xl flex items-center justify-center shrink-0">
                    <i class="fa-solid fa-lock-open text-amber-400 text-xl"></i>
                </div>
                <div>
                    <h2 class="text-xl font-bold">Autenticación requerida</h2>
                    <p class="text-slate-400 text-sm">Solo cuentas con rol <span class="text-amber-400 font-medium">admin</span> pueden ejecutar actualizaciones.</p>
                </div>
            </div>

            <form method="POST" class="space-y-5">
                <input type="hidden" name="_csrf"   value="<?= htmlspecialchars($csrf) ?>">
                <input type="hidden" name="action"  value="login">

                <div>
                    <label class="block text-sm font-medium text-slate-400 mb-2">Email de administrador</label>
                    <input type="email" name="email" required autofocus
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                           class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 outline-none focus:border-amber-500/50 transition-all text-white"
                           placeholder="admin@mi-landing.com">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-400 mb-2">Contraseña</label>
                    <input type="password" name="password" required
                           class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 outline-none focus:border-amber-500/50 transition-all text-white"
                           placeholder="••••••••">
                </div>

                <div class="flex justify-between items-center pt-2">
                    <a href="/admin/login" class="text-sm text-slate-500 hover:text-slate-300 transition-colors">
                        <i class="fa-solid fa-arrow-left mr-1"></i> Ir al admin
                    </a>
                    <button type="submit"
                            class="bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold px-8 py-3 rounded-2xl transition-all active:scale-95 shadow-xl shadow-amber-500/20">
                        Entrar <i class="fa-solid fa-arrow-right ml-2"></i>
                    </button>
                </div>
            </form>
        </div>


        <?php /* ══════════════════════════════════════════════════
            PASO 2 – ESTADO + CONFIRMAR
        ══════════════════════════════════════════════════ */ ?>
        <?php elseif ($step === 2 && $manager): ?>
        <?php
            $codeVersion      = $manager->getCodeVersion();
            $installedVersion = $manager->getInstalledVersion();
            $pending          = $manager->getPendingMigrations();
            $applied          = $manager->getAppliedMigrations();
            $isUpToDate       = empty($pending);
        ?>
        <div class="p-8">
            <div class="flex items-center gap-4 mb-8">
                <div class="w-12 h-12 <?= $isUpToDate ? 'bg-green-500/10' : 'bg-amber-500/10' ?> rounded-2xl flex items-center justify-center shrink-0">
                    <i class="fa-solid fa-<?= $isUpToDate ? 'circle-check text-green-400' : 'code-branch text-amber-400' ?> text-xl"></i>
                </div>
                <div>
                    <h2 class="text-xl font-bold"><?= $isUpToDate ? 'El sistema está actualizado' : 'Actualización disponible' ?></h2>
                    <p class="text-slate-400 text-sm">
                        <?= $isUpToDate
                            ? 'No hay migraciones pendientes.'
                            : count($pending) . ' migración(es) pendiente(s) de aplicar.' ?>
                    </p>
                </div>
            </div>

            <!-- Versiones -->
            <div class="grid grid-cols-2 gap-4 mb-6">
                <div class="bg-slate-950 border border-slate-800 rounded-2xl p-5 text-center">
                    <p class="text-xs text-slate-500 uppercase tracking-widest mb-2">Versión del código</p>
                    <p class="font-mono font-bold text-lg text-amber-400"><?= htmlspecialchars($codeVersion) ?></p>
                    <p class="text-[10px] text-slate-600 mt-1">git / VERSION</p>
                </div>
                <div class="bg-slate-950 border border-slate-800 rounded-2xl p-5 text-center">
                    <p class="text-xs text-slate-500 uppercase tracking-widest mb-2">Versión instalada</p>
                    <p class="font-mono font-bold text-lg <?= $installedVersion === 'ninguna' ? 'text-slate-500' : 'text-blue-400' ?>"><?= htmlspecialchars($installedVersion) ?></p>
                    <p class="text-[10px] text-slate-600 mt-1">base de datos</p>
                </div>
            </div>

            <?php if (!$isUpToDate): ?>
            <!-- Lista de migraciones pendientes -->
            <div class="mb-6">
                <p class="text-sm font-semibold text-slate-300 mb-3">Migraciones a aplicar:</p>
                <div class="space-y-2">
                    <?php foreach ($pending as $file): ?>
                    <div class="flex items-center gap-3 bg-slate-950 border border-slate-800 rounded-xl px-4 py-3">
                        <i class="fa-solid fa-file-code text-amber-400 text-sm shrink-0"></i>
                        <div class="flex-1 min-w-0">
                            <p class="font-mono text-sm text-white truncate"><?= htmlspecialchars(basename($file)) ?></p>
                            <?php
                                // Leer primera línea de comentario descriptivo
                                $firstLines = array_slice(file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [], 0, 5);
                                $desc = '';
                                foreach ($firstLines as $line) {
                                    if (str_starts_with(ltrim($line), '--') && str_contains($line, 'Descripción')) {
                                        $desc = trim(ltrim($line, '- '));
                                        break;
                                    }
                                }
                                if ($desc):
                            ?>
                            <p class="text-xs text-slate-500 mt-0.5"><?= htmlspecialchars($desc) ?></p>
                            <?php endif; ?>
                        </div>
                        <span class="text-[10px] bg-amber-500/10 text-amber-400 border border-amber-500/20 px-2 py-0.5 rounded-full font-semibold uppercase shrink-0">Pendiente</span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($applied)): ?>
            <!-- Migraciones ya aplicadas (colapsable) -->
            <details class="mb-6 group">
                <summary class="cursor-pointer text-sm text-slate-500 hover:text-slate-300 transition-colors flex items-center gap-2">
                    <i class="fa-solid fa-chevron-right text-xs group-open:rotate-90 transition-transform"></i>
                    Ver <?= count($applied) ?> migración(es) ya aplicada(s)
                </summary>
                <div class="mt-3 space-y-1">
                    <?php foreach ($applied as $name): ?>
                    <div class="flex items-center gap-3 px-4 py-2 rounded-xl opacity-50">
                        <i class="fa-solid fa-circle-check text-green-500 text-xs shrink-0"></i>
                        <p class="font-mono text-xs text-slate-400 truncate"><?= htmlspecialchars($name) ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
            </details>
            <?php endif; ?>

            <div class="flex justify-between items-center pt-2">
                <form method="POST">
                    <input type="hidden" name="_csrf"   value="<?= htmlspecialchars($csrf) ?>">
                    <input type="hidden" name="action"  value="logout">
                    <button type="submit" class="border border-slate-700 hover:border-slate-500 text-slate-400 hover:text-white px-5 py-2.5 rounded-2xl transition-all text-sm">
                        <i class="fa-solid fa-right-from-bracket mr-2"></i>Salir
                    </button>
                </form>

                <?php if ($isUpToDate): ?>
                    <a href="/admin/settings"
                       class="bg-slate-800 hover:bg-slate-700 text-white font-semibold px-8 py-3 rounded-2xl transition-all active:scale-95">
                        <i class="fa-solid fa-house mr-2"></i> Ir al admin
                    </a>
                <?php else: ?>
                    <form method="POST" onsubmit="return confirm('¿Aplicar las <?= count($pending) ?> migración(es) pendiente(s)? Esta acción modificará la base de datos.');">
                        <input type="hidden" name="_csrf"   value="<?= htmlspecialchars($csrf) ?>">
                        <input type="hidden" name="action"  value="apply">
                        <button type="submit"
                                class="bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold px-8 py-3 rounded-2xl transition-all active:scale-95 shadow-xl shadow-amber-500/20">
                            <i class="fa-solid fa-play mr-2"></i>Aplicar <?= count($pending) ?> migración(es)
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>


        <?php /* ══════════════════════════════════════════════════
            PASO 3 – RESULTADO
        ══════════════════════════════════════════════════ */ ?>
        <?php elseif ($step === 3): ?>
        <?php
            $results    = $_SESSION['update_results'] ?? [];
            $newVersion = $_SESSION['update_version'] ?? '';
            $totalErr   = 0;
            foreach ($results as $r) {
                $totalErr += countErrors($r['log']);
            }
        ?>
        <div class="p-8">
            <div class="text-center mb-8">
                <div class="w-20 h-20 <?= $totalErr > 0 ? 'bg-yellow-500/10' : 'bg-green-500/10' ?> rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fa-solid fa-<?= $totalErr > 0 ? 'triangle-exclamation text-yellow-400' : 'circle-check text-green-400' ?> text-4xl"></i>
                </div>
                <?php if ($totalErr > 0): ?>
                    <h2 class="text-2xl font-bold text-yellow-300">Actualización con advertencias</h2>
                    <p class="text-slate-400 mt-2"><?= $totalErr ?> statement(s) fallaron. Revisá el log y la base de datos.</p>
                <?php else: ?>
                    <h2 class="text-2xl font-bold text-green-300">¡Actualización completada!</h2>
                    <p class="text-slate-400 mt-2">
                        <?= count($results) ?> migración(es) aplicada(s) correctamente.
                        Versión instalada: <span class="font-mono text-amber-400"><?= htmlspecialchars($newVersion) ?></span>
                    </p>
                <?php endif; ?>
            </div>

            <!-- Log detallado -->
            <?php foreach ($results as $r): ?>
            <div class="mb-5">
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2 flex items-center gap-2">
                    <i class="fa-solid fa-file-code text-amber-400"></i>
                    <?= htmlspecialchars(basename($r['file'])) ?>
                    <?php $errs = countErrors($r['log']); ?>
                    <?php if ($errs > 0): ?>
                        <span class="text-[10px] bg-red-500/10 text-red-400 border border-red-500/20 px-2 py-0.5 rounded-full"><?= $errs ?> error(s)</span>
                    <?php else: ?>
                        <span class="text-[10px] bg-green-500/10 text-green-400 border border-green-500/20 px-2 py-0.5 rounded-full">OK</span>
                    <?php endif; ?>
                </p>
                <div class="bg-slate-950 border border-slate-800 rounded-2xl p-4 font-mono text-xs max-h-48 overflow-y-auto space-y-1">
                    <?php if (empty($r['log'])): ?>
                        <p class="log-sep">— Sin statements ejecutados —</p>
                    <?php else: ?>
                        <?php foreach ($r['log'] as $line): ?>
                            <?php
                                $cls = 'log-sep';
                                if (str_starts_with($line, '✓')) $cls = 'log-ok';
                                elseif (str_starts_with($line, '✗')) $cls = 'log-err';
                                elseif (str_starts_with($line, '⚠')) $cls = 'log-warn';
                            ?>
                            <div class="<?= $cls ?>"><?= htmlspecialchars($line) ?></div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>

            <?php if (empty($results)): ?>
            <div class="bg-slate-950 border border-slate-800 rounded-2xl p-8 text-center mb-6">
                <i class="fa-solid fa-circle-check text-green-400 text-3xl mb-3 block"></i>
                <p class="text-slate-400">No había migraciones pendientes. El sistema ya estaba actualizado.</p>
            </div>
            <?php endif; ?>

            <div class="flex flex-col sm:flex-row gap-3 justify-center mt-6">
                <form method="POST">
                    <input type="hidden" name="_csrf"   value="<?= htmlspecialchars($csrf) ?>">
                    <input type="hidden" name="action"  value="finish">
                    <button type="submit"
                            class="w-full sm:w-auto bg-blue-600 hover:bg-blue-500 text-white font-bold px-8 py-4 rounded-2xl transition-all text-center active:scale-95">
                        <i class="fa-solid fa-gauge mr-2"></i>Ir al panel de admin
                    </button>
                </form>
                <a href="/"
                   class="border border-slate-700 hover:border-slate-500 text-slate-300 hover:text-white font-semibold px-8 py-4 rounded-2xl transition-all text-center">
                    <i class="fa-solid fa-eye mr-2"></i>Ver la landing
                </a>
            </div>
        </div>

        <?php else: ?>
        <!-- Estado inesperado: reiniciar -->
        <div class="p-8 text-center">
            <p class="text-slate-400 mb-4">Estado de sesión inválido.</p>
            <form method="POST">
                <input type="hidden" name="_csrf"  value="<?= htmlspecialchars($csrf) ?>">
                <input type="hidden" name="action" value="logout">
                <button type="submit" class="bg-slate-800 hover:bg-slate-700 text-white px-6 py-3 rounded-2xl transition-all">
                    Reiniciar
                </button>
            </form>
        </div>
        <?php endif; ?>

    </div>

    <p class="text-slate-700 text-xs mt-8">template-2mez-landing updater</p>
</body>
</html>
