<?php
helper('config');
$doctor_info = $doctor_info ?? new stdClass();
?>
<input type="hidden" name="doctor_id" value="<?= $doctor_info->doctor_id ?? '' ?>" id="doctor_id">

<div class="row">
    <div class="col-md-6 mb-3">
        <?= form_label(lang('Doctors.doctors_name') . ':', 'name', ['class' => 'form-label required']) ?>
        <?= form_input(['name' => 'name', 'id' => 'name', 'class' => 'form-control', 'value' => $doctor_info->name ?? '', 'autocomplete' => 'off']) ?>
    </div>
    <div class="col-md-6 mb-3">
        <?= form_label(lang('Doctors.doctors_phone') . ':', 'phone_number', ['class' => 'form-label']) ?>
        <?= form_input(['name' => 'phone_number', 'id' => 'phone_number', 'class' => 'form-control', 'value' => $doctor_info->phone_number ?? '', 'autocomplete' => 'off']) ?>
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <?= form_label(lang('Doctors.doctors_speciality') . ':', 'speciality', ['class' => 'form-label']) ?>
        <?= form_input(['name' => 'speciality', 'id' => 'speciality', 'class' => 'form-control', 'value' => $doctor_info->speciality ?? '', 'autocomplete' => 'off']) ?>
    </div>
    <div class="col-md-6 mb-3">
        <?= form_label(lang('Doctors.doctors_address') . ':', 'address', ['class' => 'form-label']) ?>
        <?= form_input(['name' => 'address', 'id' => 'address', 'class' => 'form-control', 'value' => $doctor_info->address ?? '', 'autocomplete' => 'off']) ?>
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <?= form_label('Modo de reporte' . ':', 'display_mode', ['class' => 'form-label']) ?>
        <?php
        $modeVal = $doctor_info->display_mode ?? 'clinico';
        $modes = [
            'clinico' => 'Clínico (texto Bajo/Alto) — recomendado (colores)',
            'neutral' => 'Neutral (sin color) — sin colores en resultados',
            'semaforo' => 'Semáforo suave (colores + iconos) — visual rápida',
        ];
        ?>
        <?= form_dropdown('display_mode', $modes, $modeVal, 'id="display_mode" class="form-select"') ?>
        <small class="form-text text-muted">Aplica solo a órdenes que tengan asignado este médico. Tras guardar, recargue el reporte (<code>/registers/viewreport/…</code>) para ver el PDF actualizado.</small>
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label">Vista previa (como en el reporte)</label>
        <div id="display_mode_preview" class="border rounded p-2 bg-white">
            <table class="table table-sm table-bordered mb-0 small doctor-display-mode-preview-table">
                <thead class="table-light">
                    <tr>
                        <th>Análisis</th>
                        <th class="text-center">Resultado</th>
                        <th class="text-center js-preview-col-interpretacion">Interpretación</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Glucosa</td>
                        <td class="text-center js-preview-result-high">180 mg/dL</td>
                        <td class="text-center js-preview-col-interpretacion js-preview-interp-high">Alto</td>
                    </tr>
                    <tr>
                        <td>Hemoglobina</td>
                        <td class="text-center js-preview-result-low">10.2 g/dL</td>
                        <td class="text-center js-preview-col-interpretacion js-preview-interp-low">Bajo</td>
                    </tr>
                    <tr>
                        <td>Colesterol</td>
                        <td class="text-center js-preview-result-normal">180 mg/dL</td>
                        <td class="text-center js-preview-col-interpretacion js-preview-interp-normal">Normal</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
<style>
.doctor-display-mode-preview-table .report-interpretacion-alto { color: #dc3545 !important; font-weight: 700; }
.doctor-display-mode-preview-table .report-interpretacion-bajo { color: #0d6efd !important; font-weight: 700; }
.doctor-display-mode-preview-table .report-interpretacion-icon { display: inline-block; font-weight: 700; font-size: 0.9em; margin-left: 0.25rem; vertical-align: baseline; }
.doctor-display-mode-preview-table .report-interpretacion-icon-alto { color: #dc3545; }
.doctor-display-mode-preview-table .report-interpretacion-icon-bajo { color: #0d6efd; }
.doctor-display-mode-preview-table .report-interpretacion-icon-normal { color: #212529; }
</style>

<div class="row mt-2">
    <div class="col-md-6 mb-3">
        <?= form_label('Mostrar interpretación en reportes' . ':', 'interpretacion_enabled', ['class' => 'form-label']) ?>
        <div class="form-check mt-2">
            <?= form_checkbox(['name' => 'interpretacion_enabled', 'id' => 'interpretacion_enabled', 'class' => 'form-check-input', 'value' => '1', 'checked' => (($doctor_info->interpretacion_enabled ?? 1) == 1)]) ?>
            <?= form_label('Mostrar columna Interpretación para este doctor', 'interpretacion_enabled', ['class' => 'form-check-label']) ?>
        </div>
        <small class="form-text text-muted">Si está desactivado, la columna Interpretación no se mostrará en los reportes para este doctor.</small>
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <?= form_label(lang('Doctors.doctors_gender') . ':', 'gender', ['class' => 'form-label required']) ?>
        <?= form_dropdown('gender', genero_dropdown_options(), $doctor_info->gender ?? '', 'id="gender" class="form-select"') ?>
    </div>
    <div class="col-md-6 mb-3">
        <div class="form-check mt-4">
            <?= form_checkbox(['name' => 'has_commission', 'id' => 'has_commission', 'class' => 'form-check-input', 'value' => '1', 'checked' => ($doctor_info->has_commission ?? 0) == 1]) ?>
            <?= form_label('¿Este doctor usa comisiones?', 'has_commission', ['class' => 'form-check-label']) ?>
        </div>
    </div>
</div>

<div class="row" id="commission_field" style="<?= ($doctor_info->has_commission ?? 0) == 1 ? '' : 'display:none;' ?>">
    <div class="col-md-6 mb-3">
        <?= form_label('Comisión (%)', 'commission_percent', ['class' => 'form-label']) ?>
        <div class="input-group">
            <?= form_input(['name' => 'commission_percent', 'id' => 'commission_percent', 'class' => 'form-control', 'value' => $doctor_info->commission_percent ?? 0.00, 'step' => '0.01', 'min' => '0', 'max' => '100', 'type' => 'number', 'placeholder' => '0.00']) ?>
            <span class="input-group-text">%</span>
        </div>
        <small class="form-text text-muted">Porcentaje de comisión que recibirá el doctor por cada prueba realizada.</small>
    </div>
    <div class="col-md-6 mb-3">
        <div class="form-check mt-4">
            <?= form_checkbox(['name' => 'hide_commission_details', 'id' => 'hide_commission_details', 'class' => 'form-check-input', 'value' => '1', 'checked' => ($doctor_info->hide_commission_details ?? 0) == 1]) ?>
            <?= form_label('Ocultar detalle de comisiones en el portal', 'hide_commission_details', ['class' => 'form-check-label']) ?>
        </div>
        <small class="form-text text-muted">El doctor solo verá el monto pagado, sin registros, pacientes ni porcentaje.</small>
    </div>
</div>

<hr class="my-4">
<h6 class="mb-3"><?= lang('Doctors.doctors_login_access') ?? 'Acceso al portal' ?></h6>
<p class="text-muted small mb-3">Opcional. Si se configuran usuario y contraseña, el doctor podrá ingresar al portal y ver solo el historial de sus pacientes.</p>
<div class="row">
    <div class="col-md-4 mb-3">
        <?= form_label('Usuario', 'username', ['class' => 'form-label']) ?>
        <?= form_input(['name' => 'username', 'id' => 'username', 'class' => 'form-control', 'value' => $doctor_info->username ?? '', 'placeholder' => 'Ej: dr.garcia', 'autocomplete' => 'off', 'data-lpignore' => 'true', 'data-1p-ignore' => 'true']) ?>
    </div>
    <div class="col-md-4 mb-3">
        <?= form_label('Contraseña', 'password', ['class' => 'form-label']) ?>
        <?= form_password(['name' => 'password', 'id' => 'password', 'class' => 'form-control', 'value' => '', 'placeholder' => (isset($doctor_info->username) && $doctor_info->username) ? 'Dejar vacío para no cambiar' : '', 'autocomplete' => 'new-password', 'data-lpignore' => 'true', 'data-1p-ignore' => 'true']) ?>
    </div>
    <div class="col-md-4 mb-3">
        <?= form_label('Correo', 'email', ['class' => 'form-label']) ?>
        <?= form_input(['name' => 'email', 'id' => 'email', 'type' => 'email', 'class' => 'form-control', 'value' => $doctor_info->email ?? '', 'placeholder' => 'Para login con correo', 'autocomplete' => 'off', 'data-lpignore' => 'true', 'data-1p-ignore' => 'true']) ?>
    </div>
</div>

<div class="row">
    <div class="col-md-12 mb-3">
        <?= form_label(lang('Common.common_comments') . ':', 'comments', ['class' => 'form-label']) ?>
        <?= form_textarea(['name' => 'comments', 'id' => 'comments', 'value' => $doctor_info->comments ?? '', 'rows' => '5', 'class' => 'form-control']) ?>
    </div>
</div>







