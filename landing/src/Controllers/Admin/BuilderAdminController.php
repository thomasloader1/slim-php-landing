<?php

namespace App\Controllers\Admin;

use App\Services\PageLayoutService;
use App\Support\HtmlSanitizer;
use App\Support\WhatsApp;
use App\Traits\AdminViewTrait;
use Illuminate\Database\Capsule\Manager as Capsule;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * UX Builder: ordena/oculta bloques de la landing, monta componentes propios,
 * edita los textos principales y configura el botón flotante de WhatsApp.
 */
class BuilderAdminController
{
    use AdminViewTrait;

    /** Textos editables desde el builder: clave => longitud máxima */
    public const TEXT_KEYS = [
        'landing_title'    => 150,
        'landing_subtitle' => 200,
    ];

    public const WHATSAPP_POSITIONS = ['right', 'left'];

    public function index(Request $request, Response $response): Response
    {
        $settings = Capsule::table('settings')->pluck('setting_value', 'setting_key')->toArray();
        $layout = (new PageLayoutService())->decode($settings[PageLayoutService::SETTING_KEY] ?? null);

        return $this->render($response, 'admin/builder/index', [
            'settings'   => $settings,
            'layout'     => $layout,
            'blockTypes' => PageLayoutService::BLOCK_TYPES,
        ]);
    }

    public function update(Request $request, Response $response): Response
    {
        $data = (array) $request->getParsedBody();
        $values = [];

        // Layout (JSON serializado por el editor)
        $raw = json_decode((string) ($data['layout'] ?? ''), true);
        if (!is_array($raw)) {
            $_SESSION['flash_error'] = 'No se pudo leer el diseño enviado. No se guardaron cambios.';
            return $this->back($response);
        }
        $service = new PageLayoutService();
        $values[PageLayoutService::SETTING_KEY] = $service->encode($service->sanitize($raw));

        // Textos principales
        foreach (self::TEXT_KEYS as $key => $max) {
            if (array_key_exists($key, $data)) {
                $values[$key] = mb_substr(trim((string) $data[$key]), 0, $max);
            }
        }
        // La bio viene del editor rich text: solo HTML de la lista blanca
        if (array_key_exists('landing_bio', $data)) {
            $values['landing_bio'] = HtmlSanitizer::bio((string) $data['landing_bio']);
        }

        // WhatsApp flotante
        $enabled = !empty($data['whatsapp_enabled']);
        $number  = trim((string) ($data['whatsapp_number'] ?? ''));
        if ($enabled && !WhatsApp::isValidNumber($number)) {
            $_SESSION['flash_error'] = 'El número de WhatsApp no es válido. Usá el formato internacional, ej: +54 9 11 1234-5678.';
            return $this->back($response);
        }
        $mode     = (string) ($data['whatsapp_open_mode'] ?? 'auto');
        $position = (string) ($data['whatsapp_position'] ?? 'right');

        $values['whatsapp_enabled']   = $enabled ? '1' : '0';
        $values['whatsapp_number']    = mb_substr($number, 0, 30);
        $values['whatsapp_message']   = mb_substr(trim((string) ($data['whatsapp_message'] ?? '')), 0, 500);
        $values['whatsapp_label']     = mb_substr(trim((string) ($data['whatsapp_label'] ?? '')), 0, 60);
        $values['whatsapp_open_mode'] = in_array($mode, WhatsApp::OPEN_MODES, true) ? $mode : 'auto';
        $values['whatsapp_position']  = in_array($position, self::WHATSAPP_POSITIONS, true) ? $position : 'right';

        $now = date('Y-m-d H:i:s');
        Capsule::connection()->transaction(function () use ($values, $now) {
            foreach ($values as $key => $value) {
                Capsule::table('settings')->updateOrInsert(
                    ['setting_key' => $key],
                    ['setting_value' => $value, 'updated_at' => $now]
                );
            }
        });

        $_SESSION['flash_success'] = 'Diseño guardado.';
        return $this->back($response);
    }

    private function back(Response $response): Response
    {
        return $response->withHeader('Location', url('admin/builder'))->withStatus(302);
    }
}
