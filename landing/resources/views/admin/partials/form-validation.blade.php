{{--
    Validación de formularios del admin (todas las pantallas).
    Usa las reglas que ya declara cada campo (required, type, min/max, maxlength, pattern):
      - marca con * los campos obligatorios;
      - al guardar, si algo falla, muestra el error debajo de cada campo, un resumen en la
        barra de guardado y lleva al primero; no deja que el botón quede en "Guardando...";
      - el error se limpia al corregir el campo.
    Excluir un formulario: <form data-no-validate>. Mensaje propio: data-error="..." en el campo.
    Reglas extra (atributos en el campo):
      data-max-mb="5"                 tamaño máximo de archivo
      data-min-field="plan_start"     no menor que otro campo (fechas/números); mensaje: data-error-min
      data-required-if="campo"        obligatorio si el checkbox "campo" está marcado
      data-required-if="campo=valor"  obligatorio si "campo" vale "valor" (radio/select)
      data-not-zero                   número distinto de 0
--}}
<style>
    .fv-invalid { border-color: rgb(239 68 68 / .7) !important; box-shadow: 0 0 0 3px rgb(239 68 68 / .15) !important; }
    .fv-req { color: rgb(248 113 113); margin-left: .15rem; }
</style>
<script>
(function () {
    'use strict';

    var FIELDS = 'input:not([type=hidden]):not([type=submit]):not([type=button]), select, textarea';

    function isAdminPostForm(f) {
        return f instanceof HTMLFormElement && !f.hasAttribute('data-no-validate')
            && (f.getAttribute('method') || 'get').toLowerCase() === 'post' && !!f.closest('main');
    }

    /** Label visible del campo: <label for>, label que lo envuelve, o el label anterior del mismo bloque. */
    function labelFor(field) {
        if (field.id) {
            var byFor = document.querySelector('label[for="' + CSS.escape(field.id) + '"]');
            if (byFor) return byFor;
        }
        var wrap = field.closest('label');
        if (wrap) return wrap;
        var node = field.parentElement;
        for (var i = 0; node && i < 3; i++, node = node.parentElement) {
            var lbl = node.querySelector(':scope > label');
            if (lbl && !lbl.contains(field) && !lbl.querySelector('input,select,textarea')) return lbl;
        }
        return null;
    }

    function fieldName(field) {
        var lbl = labelFor(field);
        var text = lbl ? lbl.textContent : (field.getAttribute('aria-label') || field.placeholder || field.name || 'Campo');
        return text.replace(/\*/g, '').replace(/\(.*?\)/g, '').replace(/\s+/g, ' ').trim();
    }

    function other(field, name) {
        return field.form ? field.form.elements[name] : null;
    }

    /** Reglas que no existen como atributo HTML: se traducen a setCustomValidity / required. */
    function applyRules(field) {
        var d = field.dataset, msg = '';
        if (d.requiredIf) {
            var parts = d.requiredIf.split('='), ctrl = other(field, parts[0]), on = false;
            if (ctrl) {
                if (parts.length > 1) on = (ctrl.value === parts[1]);              // RadioNodeList o select
                else on = ctrl.type === 'checkbox' ? ctrl.checked : !!ctrl.value;
            }
            field.required = on;
        }
        if (d.maxMb && field.type === 'file' && field.files && field.files[0]
            && field.files[0].size > parseFloat(d.maxMb) * 1024 * 1024) {
            msg = 'El archivo pesa ' + (field.files[0].size / 1048576).toFixed(1) + ' MB; el máximo es ' + d.maxMb + ' MB.';
        }
        if (!msg && d.minField && field.value) {
            var ref = other(field, d.minField);
            if (ref && ref.value) {
                field.min = ref.value;
                var bad = field.type === 'number' ? parseFloat(field.value) < parseFloat(ref.value) : field.value < ref.value;
                if (bad) msg = d.errorMin || 'No puede ser menor que el valor de referencia.';
            }
        }
        if (!msg && field.hasAttribute('data-not-zero') && field.value !== '' && parseFloat(field.value) === 0) {
            msg = 'Tiene que ser distinto de 0.';
        }
        field.setCustomValidity(msg);
    }

    function message(field) {
        var v = field.validity;
        if (v.customError) return field.validationMessage;
        if (field.dataset.error) return field.dataset.error;
        if (v.valueMissing) {
            if (field.type === 'checkbox') return 'Tenés que marcar esta opción.';
            if (field.type === 'file') return 'Elegí un archivo.';
            if (field.tagName === 'SELECT') return 'Elegí una opción.';
            return 'Este campo es obligatorio.';
        }
        if (v.typeMismatch) {
            if (field.type === 'email') return 'Ingresá un email válido (ej: nombre@dominio.com).';
            if (field.type === 'url') return 'Ingresá una URL completa, que empiece con https://';
            return 'El formato no es válido.';
        }
        if (v.tooShort) return 'Mínimo ' + field.minLength + ' caracteres (tiene ' + field.value.length + ').';
        if (v.tooLong) return 'Máximo ' + field.maxLength + ' caracteres.';
        if (v.rangeUnderflow) return 'El valor mínimo es ' + field.min + '.';
        if (v.rangeOverflow) return 'El valor máximo es ' + field.max + '.';
        if (v.badInput) return 'Ingresá un número.';
        if (v.stepMismatch) return 'Ingresá un número válido' + (field.step === '1' ? ' (sin decimales).' : '.');
        if (v.patternMismatch) return field.title || 'El formato no es válido.';
        return field.validationMessage || 'Revisá este campo.';
    }

    function errorEl(field) {
        var key = (field.id || field.name || '') + '';
        if (!field.dataset.fvId) field.dataset.fvId = 'fv-err-' + key.replace(/[^\w-]/g, '_') + '-' + Math.random().toString(36).slice(2, 7);
        var el = document.getElementById(field.dataset.fvId);
        if (!el) {
            el = document.createElement('p');
            el.id = field.dataset.fvId;
            el.className = 'fv-error text-xs text-red-400 mt-1.5 flex items-center gap-1.5';
            el.setAttribute('role', 'alert');
            // Debajo del bloque visual del campo (input con botón/ícono al lado → después del contenedor)
            var anchor = field;
            var parent = field.parentElement;
            if (field.type === 'checkbox' || field.type === 'radio') {
                anchor = field.closest('label') || anchor;
            } else if (parent && /\b(flex|relative)\b/.test(parent.className) && !parent.querySelector('label')) {
                anchor = parent;
            }
            anchor.insertAdjacentElement('afterend', el);
        }
        return el;
    }

    function visualTarget(field) {
        return field.type === 'checkbox' || field.type === 'radio' ? (field.closest('label') || field) : field;
    }

    function setError(field, msg) {
        visualTarget(field).classList.add('fv-invalid');
        field.setAttribute('aria-invalid', 'true');
        var el = errorEl(field);
        el.innerHTML = '<i class="fa-solid fa-circle-exclamation"></i><span></span>';
        el.querySelector('span').textContent = msg;
        field.setAttribute('aria-describedby', el.id);
    }

    function clearError(field) {
        visualTarget(field).classList.remove('fv-invalid');
        field.removeAttribute('aria-invalid');
        if (field.dataset.fvId) {
            var el = document.getElementById(field.dataset.fvId);
            if (el) el.remove();
            field.removeAttribute('aria-describedby');
        }
    }

    /** Resumen junto al botón de guardar (barra sticky) o arriba del formulario. */
    function summary(form, invalid) {
        var box = form.querySelector('.fv-summary');
        if (!invalid.length) { if (box) box.remove(); return; }
        if (!box) {
            box = document.createElement('div');
            box.className = 'fv-summary mr-auto self-center text-sm text-red-300 flex items-center gap-2 min-w-0';
            box.setAttribute('role', 'alert');
            var submit = form.querySelector('[type=submit]');
            var bar = form.querySelector('.sticky.bottom-0') || (submit && submit.parentElement);
            if (bar && bar !== form) bar.insertBefore(box, bar.firstChild); else form.insertBefore(box, form.firstChild);
        }
        var names = invalid.slice(0, 3).map(fieldName).filter(Boolean);
        var more = invalid.length > 3 ? ' y ' + (invalid.length - 3) + ' más' : '';
        box.innerHTML = '<i class="fa-solid fa-triangle-exclamation text-red-400 shrink-0"></i><span class="truncate"></span>';
        box.querySelector('span').textContent = (invalid.length === 1 ? 'Revisá: ' : 'Revisá ' + invalid.length + ' campos: ') + names.join(', ') + more;
    }

    /** Abre los <details> que contengan el campo para poder mostrarlo. */
    function reveal(field) {
        for (var node = field.parentElement; node && node !== document.body; node = node.parentElement) {
            if (node.tagName === 'DETAILS') node.open = true;
        }
    }

    function validate(form) {
        var invalid = [];
        form.querySelectorAll(FIELDS).forEach(function (f) {
            if (f.disabled || f.closest('[data-no-validate]')) return;
            applyRules(f);
            if (f.checkValidity()) { clearError(f); return; }
            invalid.push(f);
            setError(f, message(f));
        });
        summary(form, invalid);
        if (invalid.length) {
            var first = invalid[0];
            reveal(first);
            if (first.getClientRects().length > 0) {
                first.scrollIntoView({ behavior: 'smooth', block: 'center' });
                setTimeout(function () { try { first.focus({ preventScroll: true }); } catch (e) {} }, 250);
            }
        }
        return invalid.length === 0;
    }

    function init() {
        Array.prototype.filter.call(document.querySelectorAll('main form'), isAdminPostForm).forEach(function (form) {
            form.setAttribute('novalidate', ''); // mensajes propios en lugar de los globos del navegador

            // Asterisco en los obligatorios
            form.querySelectorAll('[required]').forEach(function (f) {
                if (f.type === 'hidden' || f.type === 'checkbox' || f.type === 'radio') return;
                var lbl = labelFor(f);
                if (lbl && !/\*/.test(lbl.textContent) && !lbl.querySelector('.fv-req')) {
                    var star = document.createElement('span');
                    star.className = 'fv-req';
                    star.setAttribute('aria-hidden', 'true');
                    star.textContent = '*';
                    lbl.appendChild(star);
                }
            });

            // Limpiar o actualizar el error al corregir
            var onChange = function (e) {
                var f = e.target;
                if (!f.matches || !f.matches(FIELDS)) return;
                // Un cambio puede afectar a campos dependientes (data-required-if / data-min-field)
                form.querySelectorAll('[data-required-if],[data-min-field]').forEach(function (dep) {
                    applyRules(dep);
                    if (dep !== f && dep.hasAttribute('aria-invalid') && dep.checkValidity()) clearError(dep);
                });
                if (!f.hasAttribute('aria-invalid')) { applyRules(f); return; }
                applyRules(f);
                if (f.checkValidity()) clearError(f); else setError(f, message(f));
                summary(form, Array.prototype.slice.call(form.querySelectorAll('[aria-invalid="true"]')));
            };
            form.addEventListener('input', onChange);
            form.addEventListener('change', onChange);
        });
    }

    // Captura en document: corre antes que los listeners del form (ej. el botón "Guardando...")
    document.addEventListener('submit', function (e) {
        if (!isAdminPostForm(e.target)) return;
        if (!validate(e.target)) {
            e.preventDefault();
            e.stopImmediatePropagation();
        }
    }, true);

    window.adminValidateForm = validate; // para formularios que se envían por JS

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();
})();
</script>
