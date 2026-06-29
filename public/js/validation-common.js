/**
 * Configuración común de jQuery Validate para mostrar errores debajo del input (estilo Bootstrap).
 * Uso: $.extend(true, {}, window.VALIDATE_COMMON_OPTIONS, { rules: {...}, messages: {...} })
 */
window.VALIDATE_COMMON_OPTIONS = {
    errorClass: "invalid-feedback",
    errorElement: "div",
    highlight: function(el) { $(el).addClass("is-invalid"); },
    unhighlight: function(el) { $(el).removeClass("is-invalid"); },
    errorPlacement: function(error, element) {
        error.addClass("invalid-feedback d-block");
        element.after(error);
    }
};

// Fuerza mensajes por defecto de jQuery Validate en español.
if (typeof jQuery !== 'undefined' && jQuery.validator && jQuery.validator.messages) {
    jQuery.extend(jQuery.validator.messages, {
        required: "Este campo es obligatorio.",
        remote: "Corrija este campo.",
        email: "Ingrese un correo electrónico válido.",
        url: "Ingrese una URL válida.",
        date: "Ingrese una fecha válida.",
        dateISO: "Ingrese una fecha válida (ISO).",
        number: "Ingrese un número válido.",
        digits: "Ingrese solo dígitos.",
        creditcard: "Ingrese un número de tarjeta válido.",
        equalTo: "Ingrese el mismo valor nuevamente.",
        maxlength: jQuery.validator.format("No ingrese más de {0} caracteres."),
        minlength: jQuery.validator.format("Ingrese al menos {0} caracteres."),
        rangelength: jQuery.validator.format("Ingrese un valor entre {0} y {1} caracteres."),
        range: jQuery.validator.format("Ingrese un valor entre {0} y {1}."),
        max: jQuery.validator.format("Ingrese un valor menor o igual a {0}."),
        min: jQuery.validator.format("Ingrese un valor mayor o igual a {0}.")
    });

    // Mensaje cuando falta definir validación personalizada
    if (jQuery.validator.prototype) {
        var origDefaultMessage = jQuery.validator.prototype.defaultMessage;
        jQuery.validator.prototype.defaultMessage = function (method, element) {
            var msg = origDefaultMessage.call(this, method, element);
            if (msg && msg.indexOf('Warning: No message defined') !== -1) {
                return 'Complete correctamente el campo «' + (element.name || element.id || '') + '».';
            }
            return msg;
        };
    }
}

/**
 * Muestra errores de servidor en los campos del formulario (cuando se redirige con with('errors', {...}))
 * Debe llamarse en DOMContentLoaded si window.CI_VALIDATION_ERRORS existe.
 */
function showServerValidationErrors(formSelector) {
    var err = typeof window.CI_VALIDATION_ERRORS !== 'undefined' ? window.CI_VALIDATION_ERRORS : null;
    if (!err || typeof err !== 'object') return;
    var form = typeof formSelector === 'string' ? document.querySelector(formSelector) : formSelector;
    if (!form) return;
    for (var name in err) {
        if (!err.hasOwnProperty(name)) continue;
        var inp = form.querySelector('[name="' + name + '"]');
        if (inp) {
            inp.classList.add('is-invalid');
            var div = document.createElement('div');
            div.className = 'invalid-feedback d-block';
            div.textContent = err[name];
            inp.parentNode.insertBefore(div, inp.nextSibling);
        }
    }
}
