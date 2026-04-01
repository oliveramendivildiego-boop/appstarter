/**
 * Módulo Config - paleta de colores del tema (pestaña Apariencia).
 * Paleta Bootstrap 5.3: https://getbootstrap.com/docs/5.3/customize/color/
 */
document.addEventListener('DOMContentLoaded', function () {
    function hexMatch(a, b) {
        var x = String(a || '').replace(/^#/, '').toLowerCase();
        var y = String(b || '').replace(/^#/, '').toLowerCase();
        return x === y;
    }

    function bindPalettePair(paletteSelect, colorInput, hexInput) {
        if (!colorInput || !hexInput) {
            return;
        }
        if (paletteSelect) {
            paletteSelect.addEventListener('change', function () {
                var val = this.value;
                if (val) {
                    colorInput.value = val;
                    hexInput.value = val;
                }
            });
        }
        colorInput.addEventListener('input', function () {
            var val = this.value;
            hexInput.value = val;
            if (paletteSelect) {
                var found = false;
                for (var i = 0; i < paletteSelect.options.length; i++) {
                    if (paletteSelect.options[i].value && hexMatch(paletteSelect.options[i].value, val)) {
                        paletteSelect.value = paletteSelect.options[i].value;
                        found = true;
                        break;
                    }
                }
                if (!found) {
                    paletteSelect.value = '';
                }
            }
        });
    }

    bindPalettePair(
        document.getElementById('theme_palette_select'),
        document.getElementById('theme_color'),
        document.getElementById('theme_color_hex')
    );
    bindPalettePair(
        document.getElementById('theme_gradient_palette_select'),
        document.getElementById('theme_gradient_end'),
        document.getElementById('theme_gradient_end_hex')
    );
});
