<?php
$activeTab = $activeTab ?? 'sistema';
$validators = $lab_validators ?? [];
$approvers = $lab_approvers ?? [];
$nextApproverIdx = count($approvers);
$labels = [
    'name'        => lang('Config.config_lab_validator_name'),
    'approverNm'  => lang('Config.config_lab_approver_name'),
    'cargo'       => lang('Config.config_lab_approver_cargo'),
    'seal'        => lang('Config.config_lab_seal_image'),
    'sig'         => lang('Config.config_lab_signature_image'),
    'remove'      => lang('Config.config_lab_remove_row'),
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
            <div id="approver-rows" data-next-idx="<?= (int) $nextApproverIdx ?>">
                <?php foreach ($approvers as $idx => $a): ?>
                    <div class="approver-row card mb-3 border">
                        <div class="card-body py-3">
                            <div class="row g-2 align-items-start">
                                <input type="hidden" name="approver_file_slot[]" value="<?= (int) $idx ?>">
                                <input type="hidden" name="approver_id[]" value="<?= esc($a['id']) ?>">
                                <div class="col-md-4">
                                    <label class="form-label small mb-0"><?= lang('Config.config_lab_approver_name') ?></label>
                                    <input type="text" name="approver_name[]" class="form-control" value="<?= esc($a['name']) ?>" maxlength="500" autocomplete="off">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small mb-0"><?= lang('Config.config_lab_approver_cargo') ?></label>
                                    <input type="text" name="approver_cargo[]" class="form-control" value="<?= esc($a['cargo']) ?>" maxlength="255" autocomplete="off">
                                </div>
                                <div class="col-md-4 text-md-end">
                                    <button type="button" class="btn btn-outline-danger btn-sm lab-remove-approver"><?= lang('Config.config_lab_remove_row') ?></button>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small mb-0"><?= lang('Config.config_lab_seal_image') ?></label>
                                    <?php $sp = $a['seal'] ?? ''; ?>
                                    <?php if ($sp !== '' && file_exists(FCPATH . $sp)): ?>
                                        <div class="mb-2"><img src="<?= base_url($sp) ?>?v=<?= time() ?>" alt="" class="border rounded p-1" style="max-height: 100px;"></div>
                                    <?php endif; ?>
                                    <input type="file" name="approver_seal_<?= (int) $idx ?>" class="form-control form-control-sm" accept="image/*" autocomplete="off">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small mb-0"><?= lang('Config.config_lab_signature_image') ?></label>
                                    <?php $gp = $a['signature'] ?? ''; ?>
                                    <?php if ($gp !== '' && file_exists(FCPATH . $gp)): ?>
                                        <div class="mb-2"><img src="<?= base_url($gp) ?>?v=<?= time() ?>" alt="" class="border rounded p-1" style="max-height: 64px;"></div>
                                    <?php endif; ?>
                                    <input type="file" name="approver_signature_<?= (int) $idx ?>" class="form-control form-control-sm" accept="image/*" autocomplete="off">
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

    document.getElementById('btn-add-approver')?.addEventListener('click', function () {
        var wrap = document.getElementById('approver-rows');
        if (!wrap) return;
        var i = parseInt(wrap.getAttribute('data-next-idx') || '0', 10) || 0;
        wrap.setAttribute('data-next-idx', String(i + 1));
        var card = document.createElement('div');
        card.className = 'approver-row card mb-3 border';
        card.innerHTML =
            '<div class="card-body py-3">' +
            '<div class="row g-2 align-items-start">' +
            '<input type="hidden" name="approver_file_slot[]" value="' + i + '">' +
            '<input type="hidden" name="approver_id[]" value="">' +
            '<div class="col-md-4">' +
            '<label class="form-label small mb-0">' + escapeHtml(L.approverNm) + '</label>' +
            '<input type="text" name="approver_name[]" class="form-control" value="" maxlength="500" autocomplete="off">' +
            '</div>' +
            '<div class="col-md-4">' +
            '<label class="form-label small mb-0">' + escapeHtml(L.cargo) + '</label>' +
            '<input type="text" name="approver_cargo[]" class="form-control" value="" maxlength="255" autocomplete="off">' +
            '</div>' +
            '<div class="col-md-4 text-md-end">' +
            '<button type="button" class="btn btn-outline-danger btn-sm lab-remove-approver">' + escapeHtml(L.remove) + '</button>' +
            '</div>' +
            '<div class="col-md-6">' +
            '<label class="form-label small mb-0">' + escapeHtml(L.seal) + '</label>' +
            '<input type="file" name="approver_seal_' + i + '" class="form-control form-control-sm" accept="image/*" autocomplete="off">' +
            '</div>' +
            '<div class="col-md-6">' +
            '<label class="form-label small mb-0">' + escapeHtml(L.sig) + '</label>' +
            '<input type="file" name="approver_signature_' + i + '" class="form-control form-control-sm" accept="image/*" autocomplete="off">' +
            '</div>' +
            '</div></div>';
        wrap.appendChild(card);
    });

    document.getElementById('approver-rows')?.addEventListener('click', function (e) {
        var btn = e.target.closest('.lab-remove-approver');
        if (!btn) return;
        var row = btn.closest('.approver-row');
        if (row) row.remove();
    });

    function escapeHtml(s) {
        if (!s) return '';
        return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }
})();
</script>
