<?php

namespace App\Controllers\Admin;

use App\Services\PageLayoutService;
use App\Support\HtmlSanitizer;
use App\Support\WhatsApp;
use App\Traits\AdminViewTrait;
use App\Traits\ProcessesImages;
use Illuminate\Database\Capsule\Manager as Capsule;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * UX Builder: ordena/oculta bloques de cada página pública (landing, tienda),
 * monta componentes propios (banners, carrusel, textos…), edita los textos
 * principales y configura el botón flotante de WhatsApp.
 */
class BuilderAdminController
{
    use AdminViewTrait;
    use ProcessesImages;

    /** Textos editables desde el builder, por página: clave => longitud máxima */
    public const TEXT_KEYS = [
        'landing' => [
            'landing_title'    => 150,
            'landing_subtitle' => 200,
        ],
        'shop' => [
            'menu_brand_name'     => 150,
            'menu_brand_subtitle' => 200,
            'menu_header_text'    => 150,
        ],
    ];

    public const WHATSAPP_POSITIONS = ['right', 'left'];

    private const UPLOAD_MAX_BYTES = 5 * 1024 * 1024;
    private const UPLOAD_MIMES = [
        'image/png'  => 'png',
        'image/jpeg' => 'jpg',
        'image/gif'  => 'gif',
        'image/webp' => 'webp',
    ];

    public function index(Request $request, Response $response, array $args = []): Response
    {
        $settings = Capsule::table('settings')->pluck('setting_value', 'setting_key')->toArray();
        $page = $this->resolvePage($args, $settings);
        if ($page === null) {
            return $response->withHeader('Location', url('admin/builder'))->withStatus(302);
        }
        $service = new PageLayoutService($page);

        return $this->render($response, 'admin/builder/index', [
            'settings'   => $settings,
            'page'       => $page,
            'pages'      => $this->availablePages($settings),
            'layout'     => $service->decode($settings[$service->settingKey()] ?? null),
            'blockTypes' => $service->blockTypes(),
        ]);
    }

    public function update(Request $request, Response $response, array $args = []): Response
    {
        $settings = Capsule::table('settings')->pluck('setting_value', 'setting_key')->toArray();
        $page = $this->resolvePage($args, $settings);
        if ($page === null) {
            return $response->withHeader('Location', url('admin/builder'))->withStatus(302);
        }

        $data = (array) $request->getParsedBody();
        $values = [];

        // Layout (JSON serializado por el editor)
        $raw = json_decode((string) ($data['layout'] ?? ''), true);
        if (!is_array($raw)) {
            $_SESSION['flash_error'] = 'No se pudo leer el diseño enviado. No se guardaron cambios.';
            return $this->back($response, $page);
        }
        $service = new PageLayoutService($page);
        $values[$service->settingKey()] = $service->encode($service->sanitize($raw));

        // Textos principales de la página
        foreach (self::TEXT_KEYS[$page] as $key => $max) {
            if (array_key_exists($key, $data)) {
                $values[$key] = mb_substr(trim((string) $data[$key]), 0, $max);
            }
        }

        if ($page === 'landing') {
            // La bio viene del editor rich text: solo HTML de la lista blanca
            if (array_key_exists('landing_bio', $data)) {
                $values['landing_bio'] = HtmlSanitizer::bio((string) $data['landing_bio']);
            }

            // WhatsApp flotante
            $enabled = !empty($data['whatsapp_enabled']);
            $number  = trim((string) ($data['whatsapp_number'] ?? ''));
            if ($enabled && !WhatsApp::isValidNumber($number)) {
                $_SESSION['flash_error'] = 'El número de WhatsApp no es válido. Usá el formato internacional, ej: +54 9 11 1234-5678.';
                return $this->back($response, $page);
            }
            $mode     = (string) ($data['whatsapp_open_mode'] ?? 'auto');
            $position = (string) ($data['whatsapp_position'] ?? 'right');

            $values['whatsapp_enabled']   = $enabled ? '1' : '0';
            $values['whatsapp_number']    = mb_substr($number, 0, 30);
            $values['whatsapp_message']   = mb_substr(trim((string) ($data['whatsapp_message'] ?? '')), 0, 500);
            $values['whatsapp_label']     = mb_substr(trim((string) ($data['whatsapp_label'] ?? '')), 0, 60);
            $values['whatsapp_open_mode'] = in_array($mode, WhatsApp::OPEN_MODES, true) ? $mode : 'auto';
            $values['whatsapp_position']  = in_array($position, self::WHATSAPP_POSITIONS, true) ? $position : 'right';
        }

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
        return $this->back($response, $page);
    }

    /**
     * Sube una imagen para un banner/carrusel (fetch con X-CSRF-Token).
     * Responde JSON: {success:true, url:"/uploads/…"} o {success:false, message}.
     */
    public function upload(Request $request, Response $response): Response
    {
        $file = $request->getUploadedFiles()['image'] ?? null;
        if (!$file || $file->getError() !== UPLOAD_ERR_OK) {
            return $this->json($response, ['success' => false, 'message' => 'No se recibió ninguna imagen (máx. 5 MB).'], 422);
        }
        if (!$this->validateImageSize($file, self::UPLOAD_MAX_BYTES)) {
            return $this->json($response, ['success' => false, 'message' => 'La imagen supera los 5 MB.'], 422);
        }

        // Tipo real del archivo (no el nombre): sin SVG, que puede llevar scripts
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer((string) $file->getStream());
        $ext = self::UPLOAD_MIMES[$mime] ?? null;
        if ($ext === null) {
            return $this->json($response, ['success' => false, 'message' => 'Formato no soportado. Usá JPG, PNG, WEBP o GIF.'], 422);
        }

        $dir = __DIR__ . '/../../../public/uploads';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $filename = 'banner_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        try {
            $file->moveTo($dir . '/' . $filename);
        } catch (\Throwable $e) {
            return $this->json($response, ['success' => false, 'message' => 'No se pudo guardar la imagen.'], 500);
        }
        $this->processAndSaveImage($dir . '/' . $filename, 1920, 1920, 82);

        return $this->json($response, ['success' => true, 'url' => url('uploads/' . $filename)]);
    }

    /** Páginas que se pueden diseñar: la tienda solo si el módulo Catálogo está encendido. */
    private function availablePages(array $settings): array
    {
        $pages = [];
        foreach (PageLayoutService::PAGES as $key => $meta) {
            if ($key === 'shop' && ($settings['module_menu_enabled'] ?? '1') !== '1') {
                continue;
            }
            $pages[$key] = $meta;
        }
        return $pages;
    }

    private function resolvePage(array $args, array $settings): ?string
    {
        $page = (string) ($args['page'] ?? 'landing');
        return isset($this->availablePages($settings)[$page]) ? $page : null;
    }

    private function back(Response $response, string $page): Response
    {
        return $response->withHeader('Location', url($page === 'landing' ? 'admin/builder' : 'admin/builder/' . $page))->withStatus(302);
    }

    private function json(Response $response, array $payload, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }
}
