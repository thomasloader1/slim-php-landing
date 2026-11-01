<?php

namespace App\Support;

/**
 * Divide un script SQL en sentencias, respetando comillas ('...', "...", `...`),
 * comentarios (-- , #, /* *\/) y escapes. Necesario porque los seeds contienen
 * textos con ';' que un explode(';') rompería.
 */
final class SqlSplitter
{
    /** @return string[] sentencias sin el ';' final, sin comentarios sueltos */
    public static function split(string $sql): array
    {
        $statements = [];
        $buf = '';
        $len = strlen($sql);
        $quote = null;

        for ($i = 0; $i < $len; $i++) {
            $c = $sql[$i];
            $next = $i + 1 < $len ? $sql[$i + 1] : '';

            if ($quote !== null) {
                $buf .= $c;
                if ($c === '\\' && $quote !== '`') {
                    $buf .= $next;
                    $i++;
                } elseif ($c === $quote) {
                    if ($next === $quote) { // comilla duplicada = escape
                        $buf .= $next;
                        $i++;
                    } else {
                        $quote = null;
                    }
                }
                continue;
            }

            // Comentarios de línea: "-- " o "#"
            if (($c === '-' && $next === '-' && ($i + 2 >= $len || ctype_space($sql[$i + 2]))) || $c === '#') {
                $eol = strpos($sql, "\n", $i);
                $i = $eol === false ? $len : $eol;
                $buf .= "\n";
                continue;
            }
            // Comentario de bloque
            if ($c === '/' && $next === '*') {
                $end = strpos($sql, '*/', $i + 2);
                $i = $end === false ? $len : $end + 1;
                $buf .= ' ';
                continue;
            }

            if ($c === "'" || $c === '"' || $c === '`') {
                $quote = $c;
                $buf .= $c;
                continue;
            }

            if ($c === ';') {
                self::push($statements, $buf);
                $buf = '';
                continue;
            }

            $buf .= $c;
        }
        self::push($statements, $buf);

        return $statements;
    }

    private static function push(array &$statements, string $buf): void
    {
        $stmt = trim($buf);
        if ($stmt !== '') {
            $statements[] = $stmt;
        }
    }
}
