<?php

namespace App\Traits;

/**
 * Trait que agrega un scope `active()` para filtrar registros activos.
 * Asume que la tabla tiene una columna `active` (tinyint/boolean).
 */
trait HasActiveScope
{
    public function scopeActive($query)
    {
        return $query->where('active', 1);
    }
}
