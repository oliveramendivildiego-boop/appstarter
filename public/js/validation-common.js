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
