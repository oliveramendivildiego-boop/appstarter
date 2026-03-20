<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?= view('partial/breadcrumb_nav', [
    'items' => [['label' => lang('Module.module_' . $controller_name), 'url' => site_url($controller_name)]],
    'right' => form_open(site_url($controller_name . '/search'), ['id' => 'search_form', 'class' => 'd-inline']) .
        '<input type="text" name="search" id="search" class="form-control form-control-sm d-inline-block search-input-inline" />' .
        '</form>' .
        anchor($controller_name . '/view', lang(\ucfirst($controller_name) . '.' . $controller_name . '_new'), ['class' => 'btn btn-primary btn-sm']) .
        anchor($controller_name . '/delete', lang('Common.common_delete'), ['id' => 'delete', 'class' => 'btn btn-primary btn-sm']),
]) ?>
<div id="table_holder"><?= $manage_table ?? '' ?></div>
<div id="feedback_bar"></div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
$(document).ready(function() {
    enable_select_all();
    enable_row_selection();
    enable_search('<?= site_url($controller_name . '/suggest') ?>', '<?= lang('Common.common_confirm_search') ?>');
    enable_delete('<?= lang(\ucfirst($controller_name) . '.' . $controller_name . '_confirm_delete') ?>', '<?= lang(\ucfirst($controller_name) . '.' . $controller_name . '_none_selected') ?>');
    
    // Toggle estado de empleado (solo si es empleados)
    <?php if ($controller_name === 'employees'): ?>
    document.querySelectorAll('.btn-toggle-employee-status').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            var personId = this.getAttribute('data-person-id');
            var isEnabled = this.getAttribute('data-enabled') === '1';
            var newStatus = !isEnabled;
            
            var btn = this;
            var originalHtml = btn.innerHTML;
            // Spinner más visible con mensaje
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin" style="animation: spin 1s linear infinite;"></i>';
            btn.disabled = true;
            btn.style.opacity = '1';
            
            var csrfInput = document.querySelector('input[name="csrf_test_name"]') || document.querySelector('input[name*="csrf"]');
            var csrfName = (csrfInput && csrfInput.name) ? csrfInput.name : (typeof window.CI_CSRF_TOKEN_NAME !== 'undefined' ? window.CI_CSRF_TOKEN_NAME : 'csrf_test_name');
            var csrfVal = (csrfInput && csrfInput.value) ? csrfInput.value : (typeof window.CI_CSRF_TOKEN !== 'undefined' ? window.CI_CSRF_TOKEN : '');
            
            var fd = new FormData();
            fd.append('person_id', personId);
            fd.append('enable', newStatus ? '1' : '0');
            if (csrfVal) fd.append(csrfName, csrfVal);
            
            var fetchHeaders = { 'X-Requested-With': 'XMLHttpRequest' };
            if (csrfVal) fetchHeaders['X-CSRF-TOKEN'] = csrfVal;
            
            console.log('Enviando toggle:', { personId: personId, newStatus: newStatus });
            
            fetch('<?= site_url($controller_name . '/toggleStatus') ?>', {
                method: 'POST',
                body: fd,
                headers: fetchHeaders
            }).then(function(r) { return r.json(); }).then(function(response) {
                console.log('Toggle response completa:', response);
                console.log('logoutCurrentUser:', response.logoutCurrentUser, 'tipo:', typeof response.logoutCurrentUser);
                
                // Si el usuario actual fue deshabilitado, redirigir INMEDIATAMENTE
                if (response.logoutCurrentUser === true || response.logoutCurrentUser === 'true') {
                    console.log('🔴 Usuario deshabilitado - CERRANDO SESIÓN AHORA');
                    // No cambiar botón, mantener spinner visible
                    if (typeof showToast === 'function') {
                        showToast('Tu sesión ha sido cerrada. Redirigiendo...', 'success');
                    }
                    // Redirigir SIN delay
                    setTimeout(function() {
                        console.log('Redirigiendo a login');
                        window.location.href = '<?= site_url('login') ?>';
                    }, 300);
                    return; // No hacer nada más
                }
                
                // Si llegamos aquí, el cambio fue en otro usuario
                btn.disabled = false;
                if (response.success) {
                    var statusIcon = newStatus ? 'fa-toggle-on text-success' : 'fa-toggle-off text-danger';
                    var statusTitle = newStatus ? 'Desactivar empleado' : 'Activar empleado';
                    btn.innerHTML = '<i class="fa-solid ' + statusIcon + '"></i>';
                    btn.setAttribute('data-enabled', newStatus ? '1' : '0');
                    btn.setAttribute('title', statusTitle);
                    btn.style.opacity = '1';
                    if (typeof showToast === 'function') {
                        showToast(response.message || 'Empleado actualizado', 'success');
                    }
                } else {
                    btn.innerHTML = originalHtml;
                    if (typeof showToast === 'function') {
                        showToast(response.message || 'Error al cambiar el estado', 'error');
                    }
                }
            }).catch(function(err) {
                console.error('❌ Error en toggleStatus:', err);
                btn.innerHTML = originalHtml;
                btn.disabled = false;
                btn.style.opacity = '1';
                if (typeof showToast === 'function') {
                    showToast('Error al conectar con el servidor', 'error');
                }
            });
        });
    });
    <?php endif; ?>
});
</script>
<?= $this->endSection() ?>
