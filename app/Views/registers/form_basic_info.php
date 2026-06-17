<script>
document.addEventListener('DOMContentLoaded', function() {
    var pacienteInput = document.getElementById('paciente');
    var doctorInput = document.getElementById('doctor');
    var pacienteList = document.getElementById('paciente_list');
    var doctorList = document.getElementById('doctor_list');
    var pacienteTimeout, doctorTimeout;

    function showPacienteList(data) {
        if (!pacienteList) return;
        pacienteList.innerHTML = '';
        if (!data || !data.length) {
            pacienteList.style.display = 'none';
            return;
        }
        data.forEach(function(item) {
            var li = document.createElement('div');
            li.className = 'list-group-item list-group-item-action';
            li.style.cursor = 'pointer';
            li.textContent = item.value;
            li.dataset.institucion = item.institucion || '';
            li.dataset.descuento = String(item.descuento || 0);
            li.addEventListener('click', function() {
                pacienteInput.value = item.value;
                document.getElementById('person_id').value = item.data;
                var instInput = document.getElementById('customer_institucion');
                var descInput = document.getElementById('customer_descuento_pct');
                if (instInput) instInput.value = item.institucion || '';
                if (descInput) descInput.value = String(item.descuento || 0);
                if (typeof window.updateInstitutionDiscountInfo === 'function') {
                    window.updateInstitutionDiscountInfo();
                }
                if (typeof window.recalcularTotalesRegistro === 'function') {
                    window.recalcularTotalesRegistro();
                }
                pacienteList.style.display = 'none';
            });
            pacienteList.appendChild(li);
        });
        pacienteList.style.display = 'block';
    }

    function showDoctorList(data) {
        if (!doctorList) return;
        doctorList.innerHTML = '';
        if (!data || !data.length) {
            doctorList.style.display = 'none';
            return;
        }
        data.forEach(function(item) {
            var li = document.createElement('div');
            li.className = 'list-group-item list-group-item-action';
            li.style.cursor = 'pointer';
            li.textContent = item.value;
            li.addEventListener('click', function() {
                doctorInput.value = item.value;
                document.getElementById('doctor_id').value = item.data;
                doctorList.style.display = 'none';
            });
            doctorList.appendChild(li);
        });
        doctorList.style.display = 'block';
    }

    if (pacienteInput) {
        pacienteInput.addEventListener('focus', function() { this.select(); });
        pacienteInput.addEventListener('blur', function() {
            setTimeout(function() { if (pacienteList) pacienteList.style.display = 'none'; }, 200);
        });
        pacienteInput.addEventListener('input', function() {
            document.getElementById('person_id').value = '';
            var instInput = document.getElementById('customer_institucion');
            var descInput = document.getElementById('customer_descuento_pct');
            if (instInput) instInput.value = '';
            if (descInput) descInput.value = '0';
            if (typeof window.updateInstitutionDiscountInfo === 'function') {
                window.updateInstitutionDiscountInfo();
            }
            if (typeof window.recalcularTotalesRegistro === 'function') {
                window.recalcularTotalesRegistro();
            }
            clearTimeout(pacienteTimeout);
            var q = this.value.trim();
            if (q.length < 2) {
                showPacienteList([]);
                return;
            }
            pacienteTimeout = setTimeout(function() {
                fetch('<?= site_url('registers/search_paciente') ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
                    body: 'paciente=' + encodeURIComponent(q)
                })
                .then(function(r) { return r.json(); })
                .then(showPacienteList)
                .catch(function() { showPacienteList([]); });
            }, 300);
        });
    }

    if (doctorInput) {
        doctorInput.addEventListener('focus', function() { this.select(); });
        doctorInput.addEventListener('blur', function() {
            setTimeout(function() { if (doctorList) doctorList.style.display = 'none'; }, 200);
        });
        doctorInput.addEventListener('input', function() {
            document.getElementById('doctor_id').value = '';
            clearTimeout(doctorTimeout);
            var q = this.value.trim();
            if (q.length < 2) {
                showDoctorList([]);
                return;
            }
            doctorTimeout = setTimeout(function() {
                fetch('<?= site_url('registers/search_doctor') ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
                    body: 'doctor=' + encodeURIComponent(q)
                })
                .then(function(r) { return r.json(); })
                .then(showDoctorList)
                .catch(function() { showDoctorList([]); });
            }, 300);
        });
    }

    document.addEventListener('click', function(e) {
        var pc = document.getElementById('paciente_container');
        var dc = document.getElementById('doctor_container');
        if (pacienteList && pc && !pc.contains(e.target)) pacienteList.style.display = 'none';
        if (doctorList && dc && !dc.contains(e.target)) doctorList.style.display = 'none';
    });
});
</script>

<input type="hidden" name="doctor_id" id="doctor_id" value="">
<input type="hidden" name="person_id" id="person_id" value="">
<input type="hidden" name="customer_institucion" id="customer_institucion" value="">
<input type="hidden" name="customer_descuento_pct" id="customer_descuento_pct" value="0">

<div class="row">
    <div class="col-12 col-md-6" id="paciente_container">
        <div class="mb-3 position-relative">
            <label for="paciente" class="form-label">Paciente: <span class="text-danger">*</span></label>
            <input type="text" name="paciente" id="paciente" class="form-control" value="" placeholder="Escriba para buscar..." autocomplete="off">
            <div id="paciente_list" class="list-group position-absolute top-100 start-0 w-100 mt-1 shadow" style="display:none; max-height:200px; overflow-y:auto; z-index:1050;"></div>
            <div class="invalid-feedback" id="paciente_error"></div>
            <div id="institucion_descuento_info" class="small text-muted mt-2"></div>
        </div>
    </div>
    <div class="col-12 col-md-6" id="doctor_container">
        <div class="mb-3 position-relative">
            <label for="doctor" class="form-label">Doctor: <span class="text-muted">(opcional)</span></label>
            <input type="text" name="doctor" id="doctor" class="form-control" value="" placeholder="Escriba para buscar..." autocomplete="off">
            <div id="doctor_list" class="list-group position-absolute top-100 start-0 w-100 mt-1 shadow" style="display:none; max-height:200px; overflow-y:auto; z-index:1050;"></div>
            <div class="invalid-feedback" id="doctor_error"></div>
        </div>
    </div>
    <div class="col-12 col-md-6" id="prioridad_container">
        <div class="mb-3">
            <label class="form-label">Procesamiento:</label>
            <select id="prioridad" name="prioridad" class="form-select">
                <option value="0" selected>Rutina</option>
                <option value="1">Urgente</option>
                <option value="2">Derivación</option>
            </select>
        </div>
    </div>
    <div class="col-12 col-md-6">
        <div class="mb-3">
            <label for="diagnostico_presuntivo" class="form-label">Diagnóstico presuntivo:</label>
            <input type="text" id="diagnostico_presuntivo" class="form-control" maxlength="255" placeholder="Ej: diabetes en estudio, anemia, infección urinaria">
        </div>
    </div>
    <div class="col-12 col-md-6" id="prueba_search_container">
        <div class="mb-3 position-relative">
            <label for="search_prueba_input" class="form-label">Buscar prueba:</label>
            <input type="text" name="search_prueba" id="search_prueba_input" class="form-control" placeholder="Escriba para buscar..." autocomplete="off">
            <div id="prueba_list" class="list-group position-absolute top-100 start-0 w-100 mt-1 shadow" style="display:none; max-height:200px; overflow-y:auto; z-index:1050;"></div>
        </div>
    </div>
    <?php if (!empty($perfiles)): ?>
    <div class="col-12 col-md-6">
        <div class="mb-3">
            <label class="form-label">Perfil rápido:</label>
            <select id="perfil_rapido" class="form-select">
                <option value="">-- Seleccionar perfil --</option>
                <?php foreach ($perfiles as $p): ?>
                <option value="<?= esc($p['pruebas'] ?? '') ?>"><?= esc($p['nombre'] ?? '') ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <?php endif; ?>
</div>
