<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?= view('partial/breadcrumb_nav', [
    'items' => [
        ['label' => lang('Module.module_registers'), 'url' => site_url('registers/lista')],
        ['label' => 'Pendientes de notificar entrega', 'url' => ''],
    ],
]) ?>

<div class="card shadow-sm">
    <div class="card-header bg-warning text-dark d-flex flex-wrap align-items-center justify-content-between gap-2">
        <h5 class="mb-0"><i class="fa-solid fa-bell me-2"></i><?= ! empty($delivery_scope_selected) ? 'Recepciones para notificar entrega' : 'Pendientes de notificar entrega' ?></h5>
        <span class="badge bg-dark"><?= (int) count($pendientes ?? []) ?> pendiente(s)</span>
    </div>
    <div class="card-body p-0">
        <?php if (empty($pendientes)): ?>
            <p class="text-muted p-4 mb-0">No hay recepciones ni análisis pendientes de notificar entrega.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Código</th>
                            <th>Paciente</th>
                            <th>Registro</th>
                            <?php if (empty($delivery_scope_selected)): ?>
                            <th>Fecha de validación</th>
                            <th>Tiempo transcurrido</th>
                            <?php endif; ?>
                            <th>Estado</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pendientes as $row): ?>
                            <?php
                            $rid = (int) ($row['registro_id'] ?? 0);
                            $codigo = registro_codigo_recepcion_display($row, false);
                            $paciente = trim((string) (($row['first_name'] ?? '') . ' ' . ($row['last_name_fa'] ?? '')));
                            $analisis = trim((string) ($row['analisis_nombre'] ?? ''));
                            if ($analisis === '' && ! empty($delivery_scope_selected)) {
                                $analisis = 'Recepción marcada';
                            }
                            $validatedAt = trim((string) ($row['validated_at'] ?? ''));
                            if ($validatedAt === '' && ! empty($row['ingreso'])) {
                                $validatedAt = trim((string) $row['ingreso']);
                            }
                            $validatedDisplay = $validatedAt !== '' ? lab_dt_short($validatedAt) : '—';
                            $elapsed = delivery_notification_elapsed_label($validatedAt);
                            ?>
                            <tr>
                                <td><a href="<?= site_url('registers/viewreport/' . $rid) ?>"><?= esc($codigo) ?></a></td>
                                <td><?= esc($paciente !== '' ? $paciente : '—') ?></td>
                                <td><?= esc($analisis !== '' ? $analisis : ('#' . (int) ($row['prianacategoria_id'] ?? 0))) ?></td>
                                <?php if (empty($delivery_scope_selected)): ?>
                                <td><?= esc($validatedDisplay) ?></td>
                                <td><?= esc($elapsed) ?></td>
                                <?php endif; ?>
                                <td><span class="badge bg-warning text-dark">Pendiente</span></td>
                                <td class="text-end">
                                    <div class="d-flex flex-wrap gap-1 justify-content-end">
                                        <a href="<?= site_url('registers/viewreport/' . $rid) ?>" class="btn btn-sm btn-outline-primary">Ver reporte</a>
                                        <button type="button"
                                                class="btn btn-sm text-white btn-notificar-entrega"
                                                style="background-color:#FF7218;border-color:#FF7218;"
                                                data-id="<?= $rid ?>"
                                                title="Confirmar que informó la entrega de resultados al médico o paciente">
                                            <i class="fa-solid fa-bell me-1"></i> Notificar
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
document.querySelectorAll('.btn-notificar-entrega').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var id = this.getAttribute('data-id');
        if (!id) return;
        var msg = '¿Confirma que ya informó la entrega de estos resultados al médico o paciente?';
        var ejecutar = function() {
            var csrfName = window.CI_CSRF_TOKEN_NAME || 'csrf_test_name';
            var csrfVal = window.CI_CSRF_TOKEN || '';
            var body = csrfName + '=' + encodeURIComponent(csrfVal);
            btn.disabled = true;
            fetch('<?= site_url('registers/notifyDelivery') ?>/' + encodeURIComponent(id), {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
                body: body
            })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                if (res && res.csrf_token) {
                    window.CI_CSRF_TOKEN = res.csrf_token;
                    var meta = document.querySelector('meta[name="csrf-token"]');
                    if (meta) meta.setAttribute('content', res.csrf_token);
                }
                if (res && res.success) {
                    if (typeof showToast === 'function') {
                        showToast(res.message || 'Entrega notificada.', 'success');
                    }
                    location.reload();
                    return;
                }
                if (typeof uiAlert === 'function') {
                    uiAlert((res && res.message) ? res.message : 'No se pudo registrar la notificación.', 'Error');
                }
            })
            .catch(function() {
                if (typeof uiAlert === 'function') {
                    uiAlert('Error de conexión al registrar la notificación.', 'Error');
                }
            })
            .finally(function() { btn.disabled = false; });
        };
        if (typeof uiConfirm === 'function') {
            uiConfirm(msg, 'Confirmar notificación').then(function(ok) {
                if (ok) ejecutar();
            });
        } else if (window.confirm(msg)) {
            ejecutar();
        }
    });
});
</script>
<?= $this->endSection() ?>
