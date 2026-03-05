<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?= view('partial/breadcrumb_nav', ['items' => [
    ['label' => lang('Module.module_home'), 'url' => site_url('home')],
    ['label' => 'Historial por paciente', 'url' => null],
]]) ?>

<div class="card shadow-sm">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="fa-solid fa-folder-open me-2"></i>Expediente - Historial por paciente</h5>
    </div>
    <div class="card-body">
        <p class="text-muted">Busque un paciente para ver su historial de estudios, resultados y antecedentes.</p>
        <form action="<?= site_url('expediente/view/0') ?>" method="get" id="expediente_search_form">
            <div class="row g-3">
                <div class="col-md-8">
                    <label for="paciente_search" class="form-label">Buscar paciente</label>
                    <div class="position-relative">
                        <input type="text" id="paciente_search" name="q" class="form-control" placeholder="Escriba nombre o apellido (mín. 2 caracteres)..." autocomplete="off">
                        <div class="invalid-feedback d-block" id="paciente_error"></div>
                        <input type="hidden" id="person_id" name="person_id" value="">
                        <div id="suggestions_box" class="list-group position-absolute w-100 mt-1 shadow suggestions-box-expediente"></div>
                    </div>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary" id="btn_buscar">
                        <i class="fa-solid fa-search me-1"></i> Ver historial
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var form = document.getElementById('expediente_search_form');
    var input = document.getElementById('paciente_search');
    var personInput = document.getElementById('person_id');
    var suggestionsBox = document.getElementById('suggestions_box');
    var debounceTimer;

    form.addEventListener('submit', function(e) {
        var errEl = document.getElementById('paciente_error');
        if (!personInput.value || personInput.value === '0') {
            e.preventDefault();
            input.classList.add('is-invalid');
            if (errEl) errEl.textContent = 'Seleccione un paciente de la lista de sugerencias.';
            input.focus();
            return;
        }
        input.classList.remove('is-invalid');
        if (errEl) errEl.textContent = '';
        form.action = '<?= site_url('expediente/view/') ?>' + personInput.value;
    });

    input.addEventListener('input', function() {
        var q = this.value.trim();
        clearTimeout(debounceTimer);
        personInput.value = '';
        suggestionsBox.style.display = 'none';
        if (q.length < 2) return;

        debounceTimer = setTimeout(function() {
            var body = 'q=' + encodeURIComponent(q);
            if (typeof window.CI_CSRF_TOKEN_NAME !== 'undefined' && window.CI_CSRF_TOKEN) {
                body = (window.CI_CSRF_TOKEN_NAME || 'csrf_test_name') + '=' + encodeURIComponent(window.CI_CSRF_TOKEN) + '&' + body;
            }
            fetch('<?= site_url('expediente/search') ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
                body: body
            })
            .then(function(r) {
                if (!r.ok) throw new Error('Error ' + r.status);
                return r.json();
            })
            .then(function(data) {
                suggestionsBox.innerHTML = '';
                if (!Array.isArray(data) || data.length === 0) {
                    suggestionsBox.innerHTML = '<div class="list-group-item text-muted">No se encontraron pacientes</div>';
                } else {
                    data.forEach(function(item) {
                        var a = document.createElement('a');
                        a.href = '#';
                        a.className = 'list-group-item list-group-item-action';
                        a.textContent = item.value;
                        a.addEventListener('click', function(e) {
                            e.preventDefault();
                            input.value = item.value;
                            personInput.value = item.data;
                            suggestionsBox.style.display = 'none';
                            input.classList.remove('is-invalid');
                            var errEl = document.getElementById('paciente_error');
                            if (errEl) errEl.textContent = '';
                        });
                        suggestionsBox.appendChild(a);
                    });
                }
                suggestionsBox.style.display = 'block';
            })
            .catch(function() {
                suggestionsBox.innerHTML = '<div class="list-group-item text-danger">Error al buscar. Recargue la página e intente de nuevo.</div>';
                suggestionsBox.style.display = 'block';
            });
        }, 300);
    });

    input.addEventListener('blur', function() {
        setTimeout(function() { suggestionsBox.style.display = 'none'; }, 200);
    });
});
</script>
<?= $this->endSection() ?>
