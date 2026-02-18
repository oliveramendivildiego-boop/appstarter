<?= view('partial/header', ['allowed_modules' => $allowed_modules ?? [], 'user_info' => $user_info ?? null, 'current_module' => 'employees']) ?>
<?php if (!empty($extra_head_links)): foreach ($extra_head_links as $link): ?>
    <?= $link ?>
<?php endforeach; endif; ?>
<script type="text/javascript">
$(document).ready(function() {
    flatpickr("#birthday", { dateFormat: "Y-m-d", maxDate: "today", locale: "es" });
    var isEdit = <?= !empty($employee_info->person_id) && (int)$employee_info->person_id > 0 ? 'true' : 'false' ?>;
    $("#employee_form").validate({
        rules: {
            first_name: { required: true, minlength: 2 },
            last_name_fa: { required: true, minlength: 2 },
            username: { required: true, minlength: 3 },
            password: isEdit ? {} : { required: true, minlength: 4 }
        },
        messages: {
            first_name: { required: "Ingrese el nombre", minlength: "Mínimo 2 caracteres" },
            last_name_fa: { required: "Ingrese el apellido", minlength: "Mínimo 2 caracteres" },
            username: { required: "Ingrese el usuario", minlength: "Mínimo 3 caracteres" },
            password: { required: "La contraseña es obligatoria", minlength: "Mínimo 4 caracteres" }
        },
        errorClass: "is-invalid text-danger small",
        validClass: "is-valid",
        errorElement: "div"
    });
});
</script>
<?= view('partial/breadcrumb_nav', ['items' => [
    ['label' => lang('Module.module_employees'), 'url' => site_url('employees')],
    ['label' => (!empty($employee_info->person_id) && (int)$employee_info->person_id > 0 ? lang('Employees.employees_update') : lang('Employees.employees_new')), 'url' => null],
]]) ?>

<div class="card shadow-sm">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="fa-solid fa-user-tie me-2"></i><?= lang('Employees.employees_basic_information') ?></h5>
    </div>
    <div class="card-body">
        <?php $pid = $employee_info->person_id ?? ''; $saveId = ($pid === '' || $pid === null) ? '-1' : (int) $pid; ?>
        <?= form_open(site_url('employees/save/' . $saveId), ['id' => 'employee_form', 'data-async' => '1']) ?>
        <ul id="error_message_box"></ul>

        <?= view('people/form_basic_info', ['person_info' => $employee_info]) ?>

        <hr class="my-4">
        <h6 class="mb-3"><?= lang('Employees.employees_username') ?></h6>
        <?php if (!empty($roles)): ?>
        <div class="row mb-2">
            <div class="col-md-6">
                <label class="form-label">Rol</label>
                <select name="rol_id" class="form-select">
                    <option value="">-- Sin rol --</option>
                    <?php foreach ($roles as $rol): ?>
                    <option value="<?= (int)($rol['rol_id'] ?? 0) ?>" <?= ((int)($employee_info->rol_id ?? 0) === (int)($rol['rol_id'] ?? 0)) ? 'selected' : '' ?>><?= esc($rol['nombre'] ?? '') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <?php endif; ?>
        <div class="row">
            <div class="col-md-6 mb-3">
                <?= form_label(lang('Employees.employees_username') . ' <span class="text-danger">*</span>', 'username', ['class' => 'form-label']) ?>
                <?= form_input(['name' => 'username', 'id' => 'username', 'class' => 'form-control', 'value' => $employee_info->username ?? '', 'autocomplete' => 'username']) ?>
            </div>
            <div class="col-md-6 mb-3">
                <?= form_label(lang('Employees.employees_password') . ' ' . (!empty($employee_info->person_id) && (int)$employee_info->person_id > 0 ? '(opcional)' : '') . ' <span class="text-danger">*</span>', 'password', ['class' => 'form-label']) ?>
                <?= form_password(['name' => 'password', 'id' => 'password', 'class' => 'form-control', 'autocomplete' => 'new-password', 'value' => '']) ?>
                <?php if (!empty($employee_info->person_id) && (int)$employee_info->person_id > 0): ?>
                <small class="text-muted"><?= lang('Employees.employees_password_help') ?></small>
                <?php endif; ?>
            </div>
        </div>

        <hr class="my-4">
        <h6 class="mb-3"><?= lang('Employees.employees_permissions') ?></h6>
        <?php
        $rolActual = null;
        if (!empty($roles) && !empty($employee_info->rol_id)) {
            foreach ($roles as $r) {
                if ((int)($r['rol_id'] ?? 0) === (int)($employee_info->rol_id ?? 0)) {
                    $rolActual = $r['nombre'] ?? '';
                    break;
                }
            }
        }
        ?>
        <?php if ($rolActual): ?>
        <p class="text-muted small mb-2">Rol asignado: <strong><?= esc($rolActual) ?></strong></p>
        <?php endif; ?>
        <p class="text-muted small">Marque los módulos a los que este empleado tendrá acceso.</p>
        <div class="row">
            <?php foreach ($all_modules ?? [] as $mod):
                $modId = $mod->module_id ?? '';
                $label = $module_labels[$modId] ?? '';
                if ($label === '') {
                    $label = lang('Module.' . ($mod->name_lang_key ?? 'module_' . $modId));
                    if ((strpos($label, 'module_') === 0) || $label === '') {
                        $label = ucwords(str_replace('_', ' ', $modId));
                    }
                }
            ?>
            <div class="col-md-4 col-lg-3 mb-2">
                <div class="form-check">
                    <?= form_checkbox('permissions[]', $modId, isset($user_permissions[$modId]), 'id="perm_' . esc($modId) . '" class="form-check-input"') ?>
                    <label class="form-check-label" for="perm_<?= esc($modId) ?>"><?= esc($label) ?></label>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="mt-4">
            <?= form_submit(['name' => 'submit', 'id' => 'submit', 'value' => lang('Common.common_submit'), 'class' => 'btn btn-primary']) ?>
            <a href="<?= site_url('employees') ?>" class="btn btn-secondary">Cancelar</a>
        </div>
        <?= form_close() ?>
    </div>
</div>

<?= view('partial/footer') ?>
