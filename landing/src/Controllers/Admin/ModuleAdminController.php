<?php

namespace App\Controllers\Admin;

use App\Services\AccessService;
use App\Support\Permissions;
use App\Traits\AdminViewTrait;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * /admin/modules (solo root): qué secciones del panel existen en este sitio.
 *
 * Un interruptor por sección del sidebar (sale de Permissions::CATALOG['module']).
 * Apagada: desaparece y se bloquea para todos los usuarios; el root la sigue
 * viendo marcada "Off" para poder configurarla antes de habilitarla.
 * Además, los bloques fijos de la landing (logo, título, bio).
 */
class ModuleAdminController
{
    use AdminViewTrait;

    /** Qué hace cada sección y qué pasa al apagarla (flag => texto). */
    public const DESCRIPTIONS = [
        'module_builder_enabled'      => 'Orden de bloques de la landing, componentes y botón flotante de WhatsApp.',
        'module_links_enabled'        => 'Enlaces estilo Linktree. Apagado también los oculta en la landing.',
        'module_locations_enabled'    => 'Sucursales con mapa o botón. Apagado también las oculta en la landing.',
        'module_menu_enabled'         => 'Catálogo: productos, categorías, importación Excel y configuración. Apagado, /shop responde 404.',
        'module_orders_enabled'       => 'Carrito y pedidos por WhatsApp con control de stock. Apagado, /shop queda solo como catálogo.',
        'module_users_enabled'        => 'Alta, edición y baja de usuarios del panel.',
        'module_settings_enabled'     => 'Contenido principal, apariencia y colores de la landing.',
        'module_seo_enabled'          => 'Meta tags, Open Graph, Schema.org y preguntas frecuentes.',
        'module_favicon_enabled'      => 'Íconos del sitio para navegador y dispositivos.',
        'module_site_manager_enabled' => 'Gestión de N landings: plazos, vencimientos de dominio, suspensión automática.',
    ];

    /** Bloques fijos de la landing (no son secciones del panel). */
    public const LANDING_BLOCKS = [
        'module_avatar_enabled' => ['label' => 'Logo / Avatar',      'icon' => 'fa-image',      'desc' => 'Logo o avatar arriba de la landing.', 'requires' => null],
        'module_title_enabled'  => ['label' => 'Título y Subtítulo', 'icon' => 'fa-heading',    'desc' => 'Título y subtítulo de la landing.', 'requires' => null],
        'module_bio_enabled'    => ['label' => 'Biografía',          'icon' => 'fa-align-left', 'desc' => 'Sección de biografía / descripción.', 'requires' => null],
        'landing_footer_enabled' => ['label' => 'Footer',            'icon' => 'fa-copyright',  'desc' => 'Línea de crédito al pie de la landing. El texto se edita abajo.', 'requires' => null],
    ];

    /** Footer de la landing: campo => [default, largo máximo]. Solo el root los edita (esta pantalla). */
    public const FOOTER_FIELDS = [
        'landing_footer_text'       => ['Desarrollado por', 120],
        'landing_footer_link_label' => ['gomepab.com.ar', 80],
        'landing_footer_url'        => ['https://gomepab.com.ar', 300],
    ];

    /**
     * Interruptores agrupados como el sidebar:
     * [groupKey => ['label','icon','modules' => [flag => [label, icon, desc, requires]]]]
     */
    public static function groups(): array
    {
        $groups = [];
        foreach (Permissions::CATALOG as $section) {
            $flag = $section['module'] ?? null;
            if ($flag === null) {
                continue;
            }
            if ($section['scope'] === 'master' && !AccessService::isMasterContext()) {
                continue; // el gestor de sitios solo existe en la master
            }
            $group = $section['group'];
            $groups[$group] ??= Permissions::GROUPS[$group] + ['modules' => []];
            $groups[$group]['modules'][$flag] = [
                'label'    => $section['label'] === 'Productos' ? 'Catálogo (Productos, Categorías, Configuración)' : $section['label'],
                'icon'     => $section['icon'],
                'desc'     => self::DESCRIPTIONS[$flag] ?? '',
                'requires' => $section['requires'] ?? null,
            ];
        }
        $ordered = [];
        foreach (array_keys(Permissions::GROUPS) as $g) {
            if (isset($groups[$g])) {
                $ordered[$g] = $groups[$g];
            }
        }
        $ordered['landing'] = ['label' => 'Bloques de la landing', 'icon' => 'fa-mobile-screen', 'modules' => self::LANDING_BLOCKS];
        return $ordered;
    }

    /** @return string[] todos los flags que guarda este formulario */
    public static function moduleKeys(): array
    {
        $keys = [];
        foreach (self::groups() as $group) {
            array_push($keys, ...array_keys($group['modules']));
        }
        return $keys;
    }

    public function index(Request $request, Response $response): Response
    {
        $settings = Capsule::table('settings')->pluck('setting_value', 'setting_key')->toArray();
        foreach (Permissions::MODULES_OFF_BY_DEFAULT as $flag) {
            $settings[$flag] ??= '0';
        }
        foreach (self::FOOTER_FIELDS as $key => [$default]) {
            $settings[$key] ??= $default;
        }
        return $this->render($response, 'admin/modules', ['settings' => $settings, 'groups' => self::groups()]);
    }

    public function update(Request $request, Response $response): Response
    {
        $data = (array) $request->getParsedBody();

        foreach (self::moduleKeys() as $key) {
            Capsule::table('settings')->updateOrInsert(
                ['setting_key' => $key],
                ['setting_value' => ($data[$key] ?? '0') === '1' ? '1' : '0', 'updated_at' => date('Y-m-d H:i:s')]
            );
        }
        AccessService::reset();

        // Footer: texto libre acotado; el link solo http(s) o vacío
        $footerError = null;
        foreach (self::FOOTER_FIELDS as $key => [$default, $max]) {
            if (!array_key_exists($key, $data)) {
                continue;
            }
            $value = mb_substr(trim((string) $data[$key]), 0, $max);
            if ($key === 'landing_footer_url' && $value !== ''
                && (!preg_match('#^https?://#i', $value) || filter_var($value, FILTER_VALIDATE_URL) === false)) {
                $footerError = 'El link del footer tiene que ser una URL completa (https://...). No se cambió.';
                continue;
            }
            Capsule::table('settings')->updateOrInsert(
                ['setting_key' => $key],
                ['setting_value' => $value, 'updated_at' => date('Y-m-d H:i:s')]
            );
        }

        if ($footerError) {
            $_SESSION['flash_error'] = 'Módulos guardados. ' . $footerError;
            return $response->withHeader('Location', url('admin/modules'))->withStatus(302);
        }
        $_SESSION['flash_success'] = 'Módulos guardados.';
        return $response->withHeader('Location', url('admin/modules'))->withStatus(302);
    }
}
