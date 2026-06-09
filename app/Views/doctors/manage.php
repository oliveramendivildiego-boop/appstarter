<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
$bcRight = form_open(site_url('doctors/search'), ['id' => 'search_form', 'class' => 'd-inline']) .
    '<input type="text" name="search" id="search" class="form-control form-control-sm d-inline-block search-input-inline" />' .
    '</form>' .
    '<div class="dropdown d-inline-block">' .
    '<button type="button" class="btn btn-outline-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" id="doctorsTransformNamesBtn">' .
    '<i class="fa-solid fa-font me-1"></i> Formato de nombres</button>' .
    '<ul class="dropdown-menu dropdown-menu-end doctors-transform-menu">' .
    '<li><h6 class="dropdown-header">Aplicar a todos los doctores</h6></li>' .
    '<li><button type="button" class="dropdown-item doctors-transform-action" data-mode="uppercase">' .
    '<i class="fa-solid fa-text-height me-2 text-muted"></i> Todo en MAYÚSCULAS</button></li>' .
    '<li><button type="button" class="dropdown-item doctors-transform-action" data-mode="title">' .
    '<i class="fa-solid fa-heading me-2 text-muted"></i> Primera letra de cada palabra mayúscula</button></li>' .
    '</ul></div>' .
    anchor('doctors/view', lang('Doctors.doctors_new'), ['class' => 'btn btn-primary btn-sm']) .
    anchor('doctors/delete', lang('Common.common_delete'), ['id' => 'delete', 'class' => 'btn btn-primary btn-sm']);
?>
<?= view('partial/breadcrumb_nav', [
    'items' => [['label' => lang('Module.module_doctors'), 'url' => site_url('doctors')]],
    'right' => $bcRight,
]) ?>
<div id="doctors-transform-status" class="small text-muted mb-2 d-none"></div>
<div id="table_holder"><?= $manage_table ?? '' ?></div>
<div id="feedback_bar"></div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
$(document).ready(function() {
    enable_select_all();
    enable_row_selection();
    enable_search('<?= site_url('doctors/suggest') ?>', '<?= lang('Doctors.doctors_confirm_search') ?>');
    enable_delete('<?= lang('Doctors.doctors_confirm_delete') ?>', '<?= lang('Doctors.doctors_none_selected') ?>');

    (function() {
        var transformStatus = document.getElementById('doctors-transform-status');
        var transformActions = document.querySelectorAll('.doctors-transform-action');
        if (!transformActions.length) {
            return;
        }

        var modeLabels = {
            uppercase: 'convertir nombre y especialidad de todos los doctores a MAYÚSCULAS',
            title: 'poner la primera letra de cada palabra en mayúscula en nombre y especialidad de todos los doctores'
        };
        var transformBusy = false;
        var transformToggle = document.getElementById('doctorsTransformNamesBtn');

        function setTransformBusy(busy) {
            transformBusy = !!busy;
            if (transformToggle) {
                transformToggle.disabled = transformBusy;
                transformToggle.classList.toggle('disabled', transformBusy);
                transformToggle.setAttribute('aria-busy', transformBusy ? 'true' : 'false');
            }
        }

        function parseJsonResponse(response) {
            return response.text().then(function(text) {
                var data = null;
                if (text) {
                    try {
                        data = JSON.parse(text);
                    } catch (error) {
                        throw new Error('Respuesta inválida del servidor');
                    }
                }
                return { ok: response.ok, data: data };
            });
        }

        function getCsrfData() {
            return {
                name: window.CI_CSRF_TOKEN_NAME || 'csrf_test_name',
                value: window.CI_CSRF_TOKEN || ''
            };
        }

        function applyCsrfData(data) {
            if (!data || !data.csrf_token || !data.csrf_name) {
                return;
            }
            window.CI_CSRF_TOKEN = data.csrf_token;
            window.CI_CSRF_TOKEN_NAME = data.csrf_name;
        }

        function setTransformStatus(message, className, show) {
            if (!transformStatus) {
                return;
            }
            transformStatus.className = 'small mb-2 ' + (className || 'text-muted') + (show ? '' : ' d-none');
            transformStatus.textContent = message || '';
        }

        function runTransform(mode) {
            if (transformBusy) {
                return;
            }
            var label = modeLabels[mode] || 'transformar los nombres';
            var confirmMessage = '¿Confirma ' + label + '? Esta acción modifica la base de datos.';
            var proceed = function() {
                setTransformStatus('Aplicando formato...', 'text-muted', true);
                setTransformBusy(true);

                var csrf = getCsrfData();
                var fd = new FormData();
                fd.append('mode', mode);
                if (csrf.value) {
                    fd.append(csrf.name, csrf.value);
                }

                var willReload = false;
                fetch('<?= site_url('doctors/transformnames') ?>', {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrf.value || ''
                    },
                    body: fd
                })
                    .then(parseJsonResponse)
                    .then(function(result) {
                        applyCsrfData(result.data);
                        if (!result.ok || !result.data || !result.data.success) {
                            throw new Error((result.data && result.data.message) || 'No se pudo aplicar el formato');
                        }
                        setTransformStatus(result.data.message, 'text-success', true);
                        willReload = true;
                        window.setTimeout(function() {
                            window.location.reload();
                        }, 900);
                    })
                    .catch(function(error) {
                        setTransformStatus(error.message, 'text-danger', true);
                    })
                    .finally(function() {
                        if (!willReload) {
                            setTransformBusy(false);
                        } else {
                            window.setTimeout(function() {
                                setTransformBusy(false);
                            }, 4000);
                        }
                    });
            };

            if (typeof uiConfirm === 'function') {
                uiConfirm(confirmMessage, 'Confirmar formato').then(function(ok) {
                    if (ok) {
                        proceed();
                    }
                });
                return;
            }
            if (window.confirm(confirmMessage)) {
                proceed();
            }
        }

        transformActions.forEach(function(button) {
            button.addEventListener('click', function() {
                var mode = button.getAttribute('data-mode') || '';
                if (!mode) {
                    return;
                }
                runTransform(mode);
            });
        });
    })();
});
</script>
<?= $this->endSection() ?>
