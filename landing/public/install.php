<?php
/**
 * Wizard de Instalación – template-2mez-landing
 *
 * Página PHP standalone: no depende del bootstrap de Slim.
 * Funciona antes de que exista el archivo .env o la base de datos.
 */

declare(strict_types=1);

// ─── Autoload mínimo para clases Install/ ────────────────────────
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Install/InstallLock.php';
require_once __DIR__ . '/../src/Install/DbInstaller.php';
require_once __DIR__ . '/../src/Install/ModuleRegistry.php';

use App\Install\InstallLock;
use App\Install\DbInstaller;
use App\Install\ModuleRegistry;

session_start();

$lock      = new InstallLock();
$installer = new DbInstaller();
$registry  = new ModuleRegistry();

// ─── Si ya está instalado, redirigir ─────────────────────────────
if ($lock->isInstalled() && ($_GET['force'] ?? '') !== '1') {
    header('Location: /');
    exit;
}

// ─── Estado y CSRF ───────────────────────────────────────────────
if (empty($_SESSION['install_csrf'])) {
    $_SESSION['install_csrf'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION['install_csrf'];

$step    = (int) ($_SESSION['install_step'] ?? 1);
$errors  = [];
$success = [];
$log     = [];

// ─── Procesamiento de POST ────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Verificar CSRF
    if (($_POST['_csrf'] ?? '') !== $csrf) {
        $errors[] = 'Token de seguridad inválido. Recarga la página.';
    } else {

        $action = $_POST['action'] ?? '';

        // ── PASO 1 → 2: Iniciar ──────────────────────────────────
        if ($action === 'start') {
            $_SESSION['install_step'] = 2;
            $step = 2;
        }

        // ── PASO 2 → 3: Guardar config y probar conexión ─────────
        elseif ($action === 'test_db') {
            $config = [
                'DB_HOST'       => trim($_POST['DB_HOST'] ?? 'localhost'),
                'DB_PORT'       => trim($_POST['DB_PORT'] ?? '3306'),
                'DB_NAME'       => trim($_POST['DB_NAME'] ?? ''),
                'DB_USER'       => trim($_POST['DB_USER'] ?? ''),
                'DB_PASS'       => $_POST['DB_PASS'] ?? '',
                'APP_ENV'       => trim($_POST['APP_ENV'] ?? 'production'),
                'APP_BASE_PATH' => trim($_POST['APP_BASE_PATH'] ?? ''),
            ];

            if (empty($config['DB_NAME'])) {
                $errors[] = 'El nombre de la base de datos es obligatorio.';
                $step = 2;
            } elseif (empty($config['DB_USER'])) {
                $errors[] = 'El usuario de la base de datos es obligatorio.';
                $step = 2;
            } elseif (!$installer->testConnection($config)) {
                $errors[] = 'No se pudo conectar a la base de datos. Verificá los datos.';
                $step = 2;
            } else {
                $_SESSION['install_config'] = $config;
                $_SESSION['install_step']   = 3;
                $step = 3;
                $success[] = 'Conexión exitosa a la base de datos.';
            }
        }

        // ── PASO 3 → 4: Confirmar y pasar a selección de módulos ─
        elseif ($action === 'confirm_db') {
            if (empty($_SESSION['install_config'])) {
                $_SESSION['install_step'] = 2;
                $step = 2;
            } else {
                $_SESSION['install_step'] = 4;
                $step = 4;
            }
        }

        // ── PASO 4 → 5: Ejecutar instalación ─────────────────────
        elseif ($action === 'install') {
            $config  = $_SESSION['install_config'] ?? [];
            $modules = $registry->discover();

            // Módulos seleccionados (los requeridos siempre)
            $selected = $_POST['modules'] ?? [];
            foreach ($modules as $id => $module) {
                if ($module['required']) {
                    $selected[] = $id;
                }
            }
            $selected = array_unique($selected);

            try {
                // 1. Escribir .env
                $installer->writeEnv($config);
                $log[] = '✓ Archivo .env generado correctamente.';

                // 2. Conectar
                $pdo = $installer->connect($config);
                $log[] = '✓ Conexión a base de datos establecida.';

                // 3. Core schema + seed
                $dbInitDir = __DIR__ . '/../../db/init';
                foreach (['01_schema.sql', '02_seed.sql'] as $sqlFile) {
                    $filePath = $dbInitDir . '/' . $sqlFile;
                    $fileLog  = $installer->runSqlFile($pdo, $filePath);
                    $log[]    = '── ' . $sqlFile . ':';
                    $log      = array_merge($log, $fileLog);
                }

                // 4. SQL de módulos seleccionados (excluyendo landing_base que ya está en init/)
                foreach ($selected as $moduleId) {
                    if (!isset($modules[$moduleId]) || $moduleId === 'landing_base') {
                        continue;
                    }
                    $module  = $modules[$moduleId];
                    $fileLog = $installer->runSqlFile($pdo, $module['sqlPath']);
                    $log[]   = '── Módulo ' . $module['name'] . ':';
                    $log     = array_merge($log, $fileLog);
                }

                // 5. Lock
                $lock->lock();
                $log[] = '✓ Instalación completada y bloqueada.';

                $_SESSION['install_step'] = 5;
                $_SESSION['install_log']  = $log;
                $step = 5;

            } catch (\Exception $e) {
                $errors[] = 'Error durante la instalación: ' . $e->getMessage();
                $step = 4;
            }
        }

        // ── PASO 5: Ya instalado ──────────────────────────────────
        elseif ($action === 'finish') {
            session_destroy();
            header('Location: /admin/login');
            exit;
        }
    }
}

// Recuperar log del paso 5
if ($step === 5 && empty($log)) {
    $log = $_SESSION['install_log'] ?? [];
}

$modules = $registry->discover();

// ─── Helper: etiqueta del paso ───────────────────────────────────
$stepLabels = [
    1 => 'Bienvenida',
    2 => 'Base de Datos',
    3 => 'Confirmar',
    4 => 'Módulos',
    5 => 'Completado',
];

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalación – Landing App</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', system-ui, sans-serif; }
        .step-active  { background: #3b82f6; color: #fff; }
        .step-done    { background: #22c55e; color: #fff; }
        .step-pending { background: #1e293b; color: #64748b; }
        .log-ok  { color: #86efac; }
        .log-err { color: #fca5a5; }
        .log-sep { color: #94a3b8; }
    </style>
</head>
<body class="min-h-screen bg-slate-950 text-white flex flex-col items-center justify-start py-16 px-4">

    <!-- Header -->
    <div class="w-full max-w-2xl mb-10 text-center">
        <div class="text-4xl font-black tracking-tight mb-1 text-white">
            <i class="fa-solid fa-rocket text-blue-400 mr-2"></i>Instalador
        </div>
        <p class="text-slate-400 text-sm">Landing App – Asistente de configuración inicial</p>
    </div>

    <!-- Progress Bar -->
    <div class="w-full max-w-2xl mb-8">
        <div class="flex items-center justify-between">
            <?php foreach ($stepLabels as $n => $label): ?>
                <div class="flex flex-col items-center gap-1 flex-1">
                    <div class="w-9 h-9 rounded-full flex items-center justify-center text-sm font-bold transition-all
                        <?= $n < $step ? 'step-done' : ($n === $step ? 'step-active' : 'step-pending') ?>">
                        <?= $n < $step ? '<i class="fa-solid fa-check text-xs"></i>' : $n ?>
                    </div>
                    <span class="text-[8px] sm:text-[10px] <?= $n === $step ? 'text-blue-400 font-semibold' : 'text-slate-500' ?> whitespace-nowrap">
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

        <!-- Mensajes de error / éxito -->
        <?php if (!empty($errors)): ?>
            <div class="bg-red-900/30 border-b border-red-800 px-8 py-4">
                <?php foreach ($errors as $e): ?>
                    <p class="text-red-300 text-sm"><i class="fa-solid fa-triangle-exclamation mr-2"></i><?= htmlspecialchars($e) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($success)): ?>
            <div class="bg-green-900/30 border-b border-green-800 px-8 py-4">
                <?php foreach ($success as $s): ?>
                    <p class="text-green-300 text-sm"><i class="fa-solid fa-circle-check mr-2"></i><?= htmlspecialchars($s) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- ── PASO 1: Bienvenida ──────────────────────────────── -->
        <?php if ($step === 1): ?>
        <div class="p-10 text-center">
            <div class="w-20 h-20 bg-blue-500/10 rounded-full flex items-center justify-center mx-auto mb-6">
                <i class="fa-solid fa-rocket text-blue-400 text-3xl"></i>
            </div>
            <h2 class="text-2xl font-bold mb-3">¡Bienvenido al instalador!</h2>
            <p class="text-slate-400 mb-2 max-w-md mx-auto">
                Este asistente te guiará para configurar la base de datos, generar el archivo <code class="text-blue-300 bg-slate-800 px-1 rounded">.env</code> y dejar la app lista para usar.
            </p>
            <p class="text-slate-500 text-sm mb-8">Antes de comenzar, asegúrate de tener creada la base de datos MySQL y las credenciales de acceso a mano.</p>
            <form method="POST">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
                <input type="hidden" name="action" value="start">
                <button type="submit" class="bg-blue-600 hover:bg-blue-500 text-white font-bold px-10 py-4 rounded-2xl transition-all shadow-xl shadow-blue-500/20 active:scale-95">
                    Comenzar instalación <i class="fa-solid fa-arrow-right ml-2"></i>
                </button>
            </form>
        </div>

        <!-- ── PASO 2: Configurar BD ──────────────────────────────── -->
        <?php elseif ($step === 2): ?>
        <div class="p-8">
            <h2 class="text-xl font-bold mb-1">Configuración de Base de Datos</h2>
            <p class="text-slate-400 text-sm mb-6">Ingresá los datos de conexión a MySQL. La base de datos debe existir previamente.</p>
            <form method="POST" class="space-y-5">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
                <input type="hidden" name="action" value="test_db">

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-slate-400 mb-1">Host de MySQL</label>
                        <input type="text" name="DB_HOST" value="<?= htmlspecialchars($_POST['DB_HOST'] ?? 'localhost') ?>"
                               class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 outline-none focus:border-blue-500/50 transition-all text-white"
                               placeholder="localhost">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-400 mb-1">Puerto</label>
                        <input type="text" name="DB_PORT" value="<?= htmlspecialchars($_POST['DB_PORT'] ?? '3306') ?>"
                               class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 outline-none focus:border-blue-500/50 transition-all text-white"
                               placeholder="3306">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-400 mb-1">Nombre de la Base de Datos</label>
                    <input type="text" name="DB_NAME" value="<?= htmlspecialchars($_POST['DB_NAME'] ?? '') ?>"
                           class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 outline-none focus:border-blue-500/50 transition-all text-white"
                           placeholder="mi_landing_db" required>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-400 mb-1">Usuario</label>
                        <input type="text" name="DB_USER" value="<?= htmlspecialchars($_POST['DB_USER'] ?? '') ?>"
                               class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 outline-none focus:border-blue-500/50 transition-all text-white"
                               placeholder="root" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-400 mb-1">Contraseña</label>
                        <input type="password" name="DB_PASS"
                               class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 outline-none focus:border-blue-500/50 transition-all text-white"
                               placeholder="••••••••">
                    </div>
                </div>

                <hr class="border-slate-800">
                <h3 class="text-sm font-semibold text-slate-300 -mb-2">Configuración de la App</h3>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-400 mb-1">Entorno</label>
                        <select name="APP_ENV" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 outline-none focus:border-blue-500/50 transition-all text-white">
                            <option value="production" <?= ($_POST['APP_ENV'] ?? 'production') === 'production' ? 'selected' : '' ?>>production</option>
                            <option value="development" <?= ($_POST['APP_ENV'] ?? '') === 'development' ? 'selected' : '' ?>>development</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-400 mb-1">Base Path <span class="text-slate-600 font-normal">(opcional)</span></label>
                        <input type="text" name="APP_BASE_PATH" value="<?= htmlspecialchars($_POST['APP_BASE_PATH'] ?? '') ?>"
                               class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 outline-none focus:border-blue-500/50 transition-all text-white"
                               placeholder="/landing  (vacío si está en raíz)">
                    </div>
                </div>

                <div class="flex justify-end pt-2">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-500 text-white font-bold px-8 py-3 rounded-2xl transition-all active:scale-95">
                        Probar conexión <i class="fa-solid fa-plug ml-2"></i>
                    </button>
                </div>
            </form>
        </div>

        <!-- ── PASO 3: Confirmar conexión ──────────────────────────── -->
        <?php elseif ($step === 3): ?>
        <?php $cfg = $_SESSION['install_config'] ?? []; ?>
        <div class="p-8">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-10 h-10 bg-green-500/10 rounded-full flex items-center justify-center">
                    <i class="fa-solid fa-circle-check text-green-400"></i>
                </div>
                <div>
                    <h2 class="text-xl font-bold">Conexión verificada</h2>
                    <p class="text-slate-400 text-sm">Los datos ingresados son correctos.</p>
                </div>
            </div>

            <div class="bg-slate-950 rounded-2xl border border-slate-800 p-5 mb-6 space-y-2 font-mono text-sm">
                <div class="flex justify-between"><span class="text-slate-500">Host:</span><span class="text-white"><?= htmlspecialchars($cfg['DB_HOST'] ?? '') ?>:<?= htmlspecialchars($cfg['DB_PORT'] ?? '') ?></span></div>
                <div class="flex justify-between"><span class="text-slate-500">Base de datos:</span><span class="text-blue-300"><?= htmlspecialchars($cfg['DB_NAME'] ?? '') ?></span></div>
                <div class="flex justify-between"><span class="text-slate-500">Usuario:</span><span class="text-white"><?= htmlspecialchars($cfg['DB_USER'] ?? '') ?></span></div>
                <div class="flex justify-between"><span class="text-slate-500">Contraseña:</span><span class="text-slate-400">••••••••</span></div>
                <div class="flex justify-between"><span class="text-slate-500">Entorno:</span><span class="text-amber-300"><?= htmlspecialchars($cfg['APP_ENV'] ?? '') ?></span></div>
                <?php if (!empty($cfg['APP_BASE_PATH'])): ?>
                <div class="flex justify-between"><span class="text-slate-500">Base path:</span><span class="text-white"><?= htmlspecialchars($cfg['APP_BASE_PATH']) ?></span></div>
                <?php endif; ?>
            </div>

            <div class="flex gap-3 justify-between">
                <form method="POST">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
                    <input type="hidden" name="action" value="start">
                    <button type="submit" class="border border-slate-700 hover:border-slate-500 text-slate-400 hover:text-white px-6 py-3 rounded-2xl transition-all text-sm">
                        <i class="fa-solid fa-arrow-left mr-2"></i> Volver
                    </button>
                </form>
                <form method="POST">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
                    <input type="hidden" name="action" value="confirm_db">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-500 text-white font-bold px-8 py-3 rounded-2xl transition-all active:scale-95">
                        Continuar <i class="fa-solid fa-arrow-right ml-2"></i>
                    </button>
                </form>
            </div>
        </div>

        <!-- ── PASO 4: Seleccionar módulos ──────────────────────────── -->
        <?php elseif ($step === 4): ?>
        <div class="p-8">
            <h2 class="text-xl font-bold mb-1">Seleccionar Módulos</h2>
            <p class="text-slate-400 text-sm mb-6">Elegí qué módulos instalar. Los marcados como requeridos no se pueden desactivar.</p>

            <form method="POST">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
                <input type="hidden" name="action" value="install">

                <div class="space-y-3 mb-6">
                    <?php foreach ($modules as $module): ?>
                    <label class="flex items-start gap-4 bg-slate-950 border <?= $module['required'] ? 'border-blue-800/60' : 'border-slate-800' ?> rounded-2xl p-4 cursor-pointer <?= $module['required'] ? '' : 'hover:border-slate-600' ?> transition-all">
                        <input type="checkbox"
                               name="modules[]"
                               value="<?= htmlspecialchars($module['id']) ?>"
                               <?= $module['required'] ? 'checked disabled' : 'checked' ?>
                               class="mt-1 w-4 h-4 cursor-pointer flex-shrink-0">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="font-semibold text-white"><?= htmlspecialchars($module['name']) ?></span>
                                <span class="text-xs text-slate-500">v<?= htmlspecialchars($module['version']) ?></span>
                                <?php if ($module['required']): ?>
                                    <span class="text-[10px] bg-blue-600/20 text-blue-400 border border-blue-600/30 px-2 py-0.5 rounded-full font-semibold uppercase tracking-wide">Requerido</span>
                                <?php endif; ?>
                            </div>
                            <p class="text-slate-400 text-sm mt-0.5"><?= htmlspecialchars($module['description']) ?></p>
                        </div>
                    </label>
                    <?php endforeach; ?>
                </div>

                <div class="bg-amber-900/20 border border-amber-800/40 rounded-xl px-5 py-4 mb-6 text-sm text-amber-300">
                    <i class="fa-solid fa-triangle-exclamation mr-2"></i>
                    Esta acción creará las tablas en la base de datos y generará el archivo <code class="bg-slate-800 px-1 rounded">.env</code>. Si ya existen tablas, se mantendrán (INSERT IGNORE / ON DUPLICATE KEY).
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="bg-green-600 hover:bg-green-500 text-white font-bold px-10 py-4 rounded-2xl transition-all shadow-xl shadow-green-500/20 active:scale-95">
                        <i class="fa-solid fa-play mr-2"></i> Instalar ahora
                    </button>
                </div>
            </form>
        </div>

        <!-- ── PASO 5: Completado ──────────────────────────────────── -->
        <?php elseif ($step === 5): ?>
        <div class="p-8">
            <div class="text-center mb-8">
                <div class="w-20 h-20 bg-green-500/10 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fa-solid fa-circle-check text-green-400 text-4xl"></i>
                </div>
                <h2 class="text-2xl font-bold text-green-300">¡Instalación completada!</h2>
                <p class="text-slate-400 mt-2">La app está lista para usar. El instalador quedará bloqueado.</p>
            </div>

            <!-- Log de instalación -->
            <?php if (!empty($log)): ?>
            <div class="bg-slate-950 border border-slate-800 rounded-2xl p-5 mb-8 font-mono text-xs max-h-64 overflow-y-auto space-y-1">
                <?php foreach ($log as $line): ?>
                    <div class="<?= str_starts_with($line, '✓') ? 'log-ok' : (str_starts_with($line, '✗') ? 'log-err' : 'log-sep') ?>">
                        <?= htmlspecialchars($line) ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <div class="flex flex-col sm:flex-row gap-3 justify-center">
                <a href="../admin/login" class="bg-blue-600 hover:bg-blue-500 text-white font-bold px-8 py-4 rounded-2xl transition-all text-center active:scale-95">
                    <i class="fa-solid fa-lock mr-2"></i> Ir al panel de admin
                </a>
                <a href="../" class="border border-slate-700 hover:border-slate-500 text-slate-300 hover:text-white font-semibold px-8 py-4 rounded-2xl transition-all text-center">
                    <i class="fa-solid fa-eye mr-2"></i> Ver la landing
                </a>
            </div>
        </div>
        <?php endif; ?>

    </div>

    <p class="text-slate-700 text-xs mt-8">template-2mez-landing installer</p>
</body>
</html>
