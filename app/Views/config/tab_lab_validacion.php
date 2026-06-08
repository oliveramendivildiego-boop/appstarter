<?php
$activeTab = $activeTab ?? 'sistema';
$validators = $lab_validators ?? [];
$approvers = $lab_approvers ?? [];
$labels = [
    'name'        => lang('Config.config_lab_validator_name'),
    'approverNm'  => lang('Config.config_lab_approver_name'),
    'cargo'       => lang('Config.config_lab_approver_cargo'),
    'matricula'   => lang('Config.config_lab_approver_matricula'),
    'seal'        => lang('Config.config_lab_seal_image'),
    'sig'         => lang('Config.config_lab_signature_image'),
    'remove'      => lang('Config.config_lab_remove_row'),
    'removeSeal'  => lang('Config.config_lab_remove_seal'),
    'removeSig'   => lang('Config.config_lab_remove_signature'),
];
?>
<div class="tab-pane fade <?= $activeTab === 'lab_validacion' ? 'show active' : '' ?>" id="tab-lab-validacion" role="tabpanel">
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fa-solid fa-user-check me-2"></i><?= lang('Config.config_lab_validation_section') ?></h5>
        </div>
        <div class="card-body">
            <p class="text-muted small"><?= lang('Config.config_lab_validation_tab_intro') ?></p>

            <?= form_open_multipart(site_url('config/saveLabValidation'), ['id' => 'lab_validation_form']) ?>
            <?= csrf_field() ?>

            <h6 class="border-bottom pb-2"><?= lang('Config.config_lab_validators_heading') ?></h6>
            <p class="small text-muted mb-3"><?= lang('Config.config_lab_validators_intro') ?></p>
            <div id="validator-rows" class="mb-3">
                <?php foreach ($validators as $v): ?>
                    <div class="validator-row row g-2 align-items-end mb-2">
                        <input type="hidden" name="validator_id[]" value="<?= esc($v['id']) ?>">
                        <div class="col-md-10">
                            <label class="form-label small mb-0"><?= lang('Config.config_lab_validator_name') ?></label>
                            <input type="text" name="validator_name[]" class="form-control" value="<?= esc($v['name']) ?>" maxlength="500" autocomplete="off">
                        </div>
                        <div class="col-md-2">
                            <button type="button" class="btn btn-outline-danger btn-sm w-100 lab-remove-validator"><?= lang('Config.config_lab_remove_row') ?></button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <button type="button" class="btn btn-outline-primary btn-sm mb-4" id="btn-add-validator">
                <i class="fa-solid fa-plus me-1"></i><?= lang('Config.config_lab_add_validator') ?>
            </button>

            <h6 class="border-bottom pb-2"><?= lang('Config.config_lab_approvers_heading') ?></h6>
            <p class="small text-muted mb-3"><?= lang('Config.config_lab_approvers_intro') ?></p>
            <div id="approver-rows">
                <?php foreach ($approvers as $a): ?>
                    <?php $aid = preg_replace('/[^a-f0-9]/i', '', (string) ($a['id'] ?? '')); ?>
                    <div class="approver-row card mb-3 border" data-approver-id="<?= esc($aid) ?>">
                        <div class="card-body py-3">
                            <div class="row g-2 align-items-start">
                                <input type="hidden" name="approver_id[]" value="<?= esc($a['id']) ?>">
                                <div class="col-md-4">
                                    <label class="form-label small mb-0"><?= lang('Config.config_lab_approver_name') ?></label>
                                    <input type="text" name="approver_name[]" class="form-control" value="<?= esc($a['name']) ?>" maxlength="500" autocomplete="off">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small mb-0"><?= lang('Config.config_lab_approver_cargo') ?></label>
                                    <input type="text" name="approver_cargo[]" class="form-control" value="<?= esc($a['cargo']) ?>" maxlength="255" autocomplete="off">
                                    <label class="form-label small mb-0 mt-2"><?= lang('Config.config_lab_approver_matricula') ?></label>
                                    <input type="text" name="approver_matricula[]" class="form-control" value="<?= esc($a['matricula'] ?? '') ?>" maxlength="255" autocomplete="off">
                                </div>
                                <div class="col-md-4 text-md-end align-self-start">
                                    <button type="button" class="btn btn-outline-danger btn-sm lab-remove-approver"><?= lang('Config.config_lab_remove_row') ?></button>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small mb-0"><?= lang('Config.config_lab_seal_image') ?></label>
                                    <?php
                                    $sp = trim((string) ($a['seal'] ?? ''));
                                    $spFs = $sp !== '' ? (FCPATH . str_replace('/', DIRECTORY_SEPARATOR, $sp)) : '';
                                    ?>
                                    <div class="mb-2 lab-seal-preview">
                                    <?php if ($sp !== '' && is_file($spFs)): ?>
                                        <div class="d-flex align-items-start gap-2">
                                            <img src="<?= base_url($sp) ?>?v=<?= time() ?>" alt="" class="border rounded p-1 lab-seal-preview-img" style="max-height: 100px;">
                                            <button type="button" class="btn btn-outline-danger btn-sm lab-remove-seal" title="<?= esc(lang('Config.config_lab_remove_seal')) ?>">
                                                <i class="fa-solid fa-trash-can me-1"></i><?= lang('Config.config_lab_remove_seal') ?>
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                    </div>
                                    <input type="file" name="approver_seal_<?= esc($aid) ?>" class="form-control form-control-sm lab-seal-input" accept="image/jpeg,image/png,image/gif,image/webp,.jpg,.jpeg,.png,.gif,.webp" autocomplete="off">
                                    <div class="small text-muted lab-seal-upload-status mt-1">Al elegir imagen se guarda automáticamente.</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small mb-0"><?= lang('Config.config_lab_signature_image') ?></label>
                                    <?php
                                    $gp = trim((string) ($a['signature'] ?? ''));
                                    $gpFs = $gp !== '' ? (FCPATH . str_replace('/', DIRECTORY_SEPARATOR, $gp)) : '';
                                    ?>
                                    <div class="mb-2 lab-sig-preview">
                                    <?php if ($gp !== '' && is_file($gpFs)): ?>
                                        <div class="d-flex align-items-start gap-2">
                                            <img src="<?= base_url($gp) ?>?v=<?= time() ?>" alt="" class="border rounded p-1 lab-sig-preview-img" style="max-height: 64px;">
                                            <button type="button" class="btn btn-outline-danger btn-sm lab-remove-signature" title="<?= esc(lang('Config.config_lab_remove_signature')) ?>">
                                                <i class="fa-solid fa-trash-can me-1"></i><?= lang('Config.config_lab_remove_signature') ?>
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                    </div>
                                    <input type="file" name="approver_signature_<?= esc($aid) ?>" class="form-control form-control-sm lab-sig-input" accept="image/jpeg,image/png,image/gif,image/webp,.jpg,.jpeg,.png,.gif,.webp" autocomplete="off">
                                    <div class="small text-muted lab-sig-upload-status mt-1">Al elegir imagen se guarda automáticamente.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <button type="button" class="btn btn-outline-primary btn-sm mb-4" id="btn-add-approver">
                <i class="fa-solid fa-plus me-1"></i><?= lang('Config.config_lab_add_approver') ?>
            </button>

            <div>
                <button type="submit" class="btn btn-primary"><?= lang('Config.config_save_btn') ?></button>
            </div>
            <?= form_close() ?>
        </div>
    </div>
</div>

<script>
(function () {
    var L = <?= json_encode($labels, JSON_UNESCAPED_UNICODE) ?>;
    var sealUploadUrl = <?= json_encode(site_url('config/uploadLabApproverSeal')) ?>;
    var sigUploadUrl = <?= json_encode(site_url('config/uploadLabApproverSignature')) ?>;
    var sealDeleteUrl = <?= json_encode(site_url('config/deleteLabApproverSeal')) ?>;
    var sigDeleteUrl = <?= json_encode(site_url('config/deleteLabApproverSignature')) ?>;

    document.getElementById('btn-add-validator')?.addEventListener('click', function () {
        var wrap = document.getElementById('validator-rows');
        if (!wrap) return;
        var row = document.createElement('div');
        row.className = 'validator-row row g-2 align-items-end mb-2';
        row.innerHTML =
            '<input type="hidden" name="validator_id[]" value="">' +
            '<div class="col-md-10">' +
            '<label class="form-label small mb-0">' + escapeHtml(L.name) + '</label>' +
            '<input type="text" name="validator_name[]" class="form-control" value="" maxlength="500" autocomplete="off">' +
            '</div>' +
            '<div class="col-md-2">' +
            '<button type="button" class="btn btn-outline-danger btn-sm w-100 lab-remove-validator">' + escapeHtml(L.remove) + '</button>' +
            '</div>';
        wrap.appendChild(row);
    });

    document.getElementById('validator-rows')?.addEventListener('click', function (e) {
        var btn = e.target.closest('.lab-remove-validator');
        if (!btn) return;
        var row = btn.closest('.validator-row');
        if (row) row.remove();
    });

    function newApproverHexId() {
        var s = '';
        for (var i = 0; i < 16; i++) {
            s += Math.floor(Math.random() * 16).toString(16);
        }
        return s;
    }

    function uploadLabApproverImage(input, kind, uploadUrl) {
        var card = input.closest('.approver-row');
        if (!card) return;
        var approverId = card.getAttribute('data-approver-id') || '';
        var file = input.files && input.files[0];
        var statusEl = card.querySelector(kind === 'seal' ? '.lab-seal-upload-status' : '.lab-sig-upload-status');
        if (!file) return;
        if (!approverId) {
            if (statusEl) {
                statusEl.textContent = 'Error: falta identificador del responsable. Recargue la página.';
                statusEl.className = 'small text-danger ' + (kind === 'seal' ? 'lab-seal-upload-status' : 'lab-sig-upload-status') + ' mt-1';
            }
            return;
        }
        if (statusEl) {
            statusEl.textContent = 'Guardando imagen…';
            statusEl.className = 'small text-muted ' + (kind === 'seal' ? 'lab-seal-upload-status' : 'lab-sig-upload-status') + ' mt-1';
        }
        var fd = new FormData();
        fd.append('approver_id', approverId);
        fd.append('approver_name', card.querySelector('[name="approver_name[]"]')?.value || '');
        fd.append('approver_cargo', card.querySelector('[name="approver_cargo[]"]')?.value || '');
        fd.append('approver_matricula', card.querySelector('[name="approver_matricula[]"]')?.value || '');
        fd.append(kind, file);
        var csrfName = window.CI_CSRF_TOKEN_NAME || 'csrf_test_name';
        fd.append(csrfName, window.CI_CSRF_TOKEN || '');
        fetch(uploadUrl, {
            method: 'POST',
            body: fd,
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.csrf_token) window.CI_CSRF_TOKEN = data.csrf_token;
                if (data.success && data.url) {
                    var wrap = card.querySelector(kind === 'seal' ? '.lab-seal-preview' : '.lab-sig-preview');
                    if (wrap) {
                        wrap.innerHTML = buildLabImagePreviewHtml(kind, data.url);
                    }
                    if (statusEl) {
                        statusEl.textContent = data.message || 'Imagen guardada.';
                        statusEl.className = 'small text-success ' + (kind === 'seal' ? 'lab-seal-upload-status' : 'lab-sig-upload-status') + ' mt-1';
                    }
                    input.value = '';
                    return;
                }
                if (statusEl) {
                    statusEl.textContent = data.message || 'No se pudo guardar la imagen.';
                    statusEl.className = 'small text-danger ' + (kind === 'seal' ? 'lab-seal-upload-status' : 'lab-sig-upload-status') + ' mt-1';
                }
            })
            .catch(function () {
                if (statusEl) {
                    statusEl.textContent = 'Error de conexión al guardar la imagen.';
                    statusEl.className = 'small text-danger ' + (kind === 'seal' ? 'lab-seal-upload-status' : 'lab-sig-upload-status') + ' mt-1';
                }
            });
    }

    function bindLabImagePreview(input, previewWrap, imgClass, kind, uploadUrl) {
        if (!input) return;
        input.addEventListener('change', function () {
            var file = input.files && input.files[0];
            if (!file) return;
            if (!/^image\//i.test(file.type || '')) return;
            uploadLabApproverImage(input, kind, uploadUrl);
        });
    }

    function buildLabImagePreviewHtml(kind, url) {
        var imgClass = kind === 'seal' ? 'lab-seal-preview-img' : 'lab-sig-preview-img';
        var maxH = kind === 'seal' ? '100' : '64';
        var btnClass = kind === 'seal' ? 'lab-remove-seal' : 'lab-remove-signature';
        var btnLabel = kind === 'seal' ? L.removeSeal : L.removeSig;
        return '<div class="d-flex align-items-start gap-2">' +
            '<img src="' + url + '" class="border rounded p-1 ' + imgClass + '" style="max-height:' + maxH + 'px" alt="">' +
            '<button type="button" class="btn btn-outline-danger btn-sm ' + btnClass + '" title="' + escapeHtml(btnLabel) + '">' +
            '<i class="fa-solid fa-trash-can me-1"></i>' + escapeHtml(btnLabel) +
            '</button></div>';
    }

    function deleteLabApproverImage(card, kind, deleteUrl) {
        var approverId = card.getAttribute('data-approver-id') || '';
        var statusEl = card.querySelector(kind === 'seal' ? '.lab-seal-upload-status' : '.lab-sig-upload-status');
        var btnLabel = kind === 'seal' ? L.removeSeal : L.removeSig;
        if (!approverId) {
            if (statusEl) {
                statusEl.textContent = 'Error: falta identificador del responsable. Recargue la página.';
                statusEl.className = 'small text-danger ' + (kind === 'seal' ? 'lab-seal-upload-status' : 'lab-sig-upload-status') + ' mt-1';
            }
            return;
        }
        if (!window.confirm('¿Eliminar ' + btnLabel.toLowerCase() + '?')) {
            return;
        }
        if (statusEl) {
            statusEl.textContent = 'Eliminando imagen…';
            statusEl.className = 'small text-muted ' + (kind === 'seal' ? 'lab-seal-upload-status' : 'lab-sig-upload-status') + ' mt-1';
        }
        var fd = new FormData();
        fd.append('approver_id', approverId);
        var csrfName = window.CI_CSRF_TOKEN_NAME || 'csrf_test_name';
        fd.append(csrfName, window.CI_CSRF_TOKEN || '');
        fetch(deleteUrl, {
            method: 'POST',
            body: fd,
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.csrf_token) window.CI_CSRF_TOKEN = data.csrf_token;
                if (data.success) {
                    var wrap = card.querySelector(kind === 'seal' ? '.lab-seal-preview' : '.lab-sig-preview');
                    if (wrap) wrap.innerHTML = '';
                    if (statusEl) {
                        statusEl.textContent = data.message || 'Imagen eliminada.';
                        statusEl.className = 'small text-success ' + (kind === 'seal' ? 'lab-seal-upload-status' : 'lab-sig-upload-status') + ' mt-1';
                    }
                    return;
                }
                if (statusEl) {
                    statusEl.textContent = data.message || 'No se pudo eliminar la imagen.';
                    statusEl.className = 'small text-danger ' + (kind === 'seal' ? 'lab-seal-upload-status' : 'lab-sig-upload-status') + ' mt-1';
                }
            })
            .catch(function () {
                if (statusEl) {
                    statusEl.textContent = 'Error de conexión al eliminar la imagen.';
                    statusEl.className = 'small text-danger ' + (kind === 'seal' ? 'lab-seal-upload-status' : 'lab-sig-upload-status') + ' mt-1';
                }
            });
    }

    document.querySelectorAll('.lab-seal-input').forEach(function (el) {
        bindLabImagePreview(el, el.closest('.col-md-6')?.querySelector('.lab-seal-preview'), 'lab-seal-preview-img', 'seal', sealUploadUrl);
    });
    document.querySelectorAll('.lab-sig-input').forEach(function (el) {
        bindLabImagePreview(el, el.closest('.col-md-6')?.querySelector('.lab-sig-preview'), 'lab-sig-preview-img', 'signature', sigUploadUrl);
    });

    document.getElementById('btn-add-approver')?.addEventListener('click', function () {
        var wrap = document.getElementById('approver-rows');
        if (!wrap) return;
        var newId = newApproverHexId();
        var card = document.createElement('div');
        card.className = 'approver-row card mb-3 border';
        card.setAttribute('data-approver-id', newId);
        card.innerHTML =
            '<div class="card-body py-3">' +
            '<div class="row g-2 align-items-start">' +
            '<input type="hidden" name="approver_id[]" value="' + escapeHtml(newId) + '">' +
            '<div class="col-md-4">' +
            '<label class="form-label small mb-0">' + escapeHtml(L.approverNm) + '</label>' +
            '<input type="text" name="approver_name[]" class="form-control" value="" maxlength="500" autocomplete="off">' +
            '</div>' +
            '<div class="col-md-4">' +
            '<label class="form-label small mb-0">' + escapeHtml(L.cargo) + '</label>' +
            '<input type="text" name="approver_cargo[]" class="form-control" value="" maxlength="255" autocomplete="off">' +
            '<label class="form-label small mb-0 mt-2">' + escapeHtml(L.matricula) + '</label>' +
            '<input type="text" name="approver_matricula[]" class="form-control" value="" maxlength="255" autocomplete="off">' +
            '</div>' +
            '<div class="col-md-4 text-md-end align-self-start">' +
            '<button type="button" class="btn btn-outline-danger btn-sm lab-remove-approver">' + escapeHtml(L.remove) + '</button>' +
            '</div>' +
            '<div class="col-md-6">' +
            '<label class="form-label small mb-0">' + escapeHtml(L.seal) + '</label>' +
            '<div class="mb-2 lab-seal-preview"></div>' +
            '<input type="file" name="approver_seal_' + escapeHtml(newId) + '" class="form-control form-control-sm lab-seal-input" accept="image/jpeg,image/png,image/gif,image/webp,.jpg,.jpeg,.png,.gif,.webp" autocomplete="off">' +
            '<div class="small text-muted lab-seal-upload-status mt-1">Al elegir imagen se guarda automáticamente.</div>' +
            '</div>' +
            '<div class="col-md-6">' +
            '<label class="form-label small mb-0">' + escapeHtml(L.sig) + '</label>' +
            '<div class="mb-2 lab-sig-preview"></div>' +
            '<input type="file" name="approver_signature_' + escapeHtml(newId) + '" class="form-control form-control-sm lab-sig-input" accept="image/jpeg,image/png,image/gif,image/webp,.jpg,.jpeg,.png,.gif,.webp" autocomplete="off">' +
            '<div class="small text-muted lab-sig-upload-status mt-1">Al elegir imagen se guarda automáticamente.</div>' +
            '</div>' +
            '</div></div>';
        wrap.appendChild(card);
        bindLabImagePreview(card.querySelector('.lab-seal-input'), card.querySelector('.lab-seal-preview'), 'lab-seal-preview-img', 'seal', sealUploadUrl);
        bindLabImagePreview(card.querySelector('.lab-sig-input'), card.querySelector('.lab-sig-preview'), 'lab-sig-preview-img', 'signature', sigUploadUrl);
    });

    document.getElementById('approver-rows')?.addEventListener('click', function (e) {
        var btnApprover = e.target.closest('.lab-remove-approver');
        if (btnApprover) {
            var row = btnApprover.closest('.approver-row');
            if (row) row.remove();
            return;
        }
        var btnSeal = e.target.closest('.lab-remove-seal');
        if (btnSeal) {
            var cardSeal = btnSeal.closest('.approver-row');
            if (cardSeal) deleteLabApproverImage(cardSeal, 'seal', sealDeleteUrl);
            return;
        }
        var btnSig = e.target.closest('.lab-remove-signature');
        if (btnSig) {
            var cardSig = btnSig.closest('.approver-row');
            if (cardSig) deleteLabApproverImage(cardSig, 'signature', sigDeleteUrl);
        }
    });

    function escapeHtml(s) {
        if (!s) return '';
        return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }
})();
</script>
