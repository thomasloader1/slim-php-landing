<?php

namespace App\Support;

/**
 * Sanitiza el HTML "rich text" de la biografía (editor Quill).
 *
 * Lista blanca: p, br, strong/b, em/i, u, ul, ol, li y a[href].
 * Todo lo demás se descarta (se conserva su texto); los atributos se eliminan
 * salvo href en <a>, que solo admite http(s), mailto y tel.
 *
 * Texto plano sin etiquetas (bios viejas o cargadas como texto) se escapa y
 * se respetan los saltos de línea.
 */
final class HtmlSanitizer
{
    private const ALLOWED = ['p', 'br', 'strong', 'b', 'em', 'i', 'u', 'ul', 'ol', 'li', 'a'];

    /** Etiquetas cuyo contenido tampoco se conserva. */
    private const DROP_WITH_CONTENT = ['script', 'style', 'iframe', 'object', 'embed', 'template', 'svg', 'math', 'noscript'];

    public static function bio(?string $html): string
    {
        $html = trim((string) $html);
        if ($html === '') {
            return '';
        }
        if (!preg_match('/<[a-z!\/]/i', $html)) {
            return nl2br(htmlspecialchars($html, ENT_QUOTES | ENT_HTML5, 'UTF-8'), false);
        }

        $doc = new \DOMDocument();
        $prev = libxml_use_internal_errors(true);
        $doc->loadHTML(
            '<?xml encoding="UTF-8"><div id="__root">' . $html . '</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET
        );
        libxml_clear_errors();
        libxml_use_internal_errors($prev);

        $root = $doc->getElementById('__root');
        if (!$root) {
            return '';
        }
        self::cleanChildren($root);

        $out = '';
        foreach (iterator_to_array($root->childNodes) as $child) {
            $out .= $doc->saveHTML($child);
        }
        $out = trim($out);

        // Quill deja "<p><br></p>" cuando el editor queda vacío
        $textOnly = trim(html_entity_decode(strip_tags($out), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        return $textOnly === '' ? '' : $out;
    }

    private static function cleanChildren(\DOMNode $node): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child instanceof \DOMText) {
                continue;
            }
            if (!$child instanceof \DOMElement) {
                $node->removeChild($child); // comentarios, PIs, CDATA
                continue;
            }

            $tag = strtolower($child->tagName);
            if (in_array($tag, self::DROP_WITH_CONTENT, true)) {
                $node->removeChild($child);
                continue;
            }

            self::cleanChildren($child);

            if (!in_array($tag, self::ALLOWED, true)) {
                // Desenvolver: conservar el contenido ya limpio, quitar la etiqueta
                while ($child->firstChild) {
                    $node->insertBefore($child->firstChild, $child);
                }
                $node->removeChild($child);
                continue;
            }

            $href = $tag === 'a' ? trim($child->getAttribute('href')) : '';
            foreach (iterator_to_array($child->attributes) as $attr) {
                $child->removeAttribute($attr->nodeName);
            }
            if ($tag === 'a') {
                if (preg_match('#^(https?://|mailto:|tel:)#i', $href)) {
                    $child->setAttribute('href', $href);
                    $child->setAttribute('target', '_blank');
                    $child->setAttribute('rel', 'noopener noreferrer nofollow');
                } else {
                    // Link inseguro o vacío: queda solo el texto
                    while ($child->firstChild) {
                        $node->insertBefore($child->firstChild, $child);
                    }
                    $node->removeChild($child);
                }
            }
        }
    }
}
