<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Suscripción vencida<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="row justify-content-center">
    <div class="col-lg-8 col-xl-7">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4 p-md-5 text-center">
                <div class="text-danger mb-3"><i class="fa-solid fa-circle-exclamation fa-3x"></i></div>
                <h1 class="h3 fw-bold mb-3">Acceso suspendido</h1>
                <p class="text-muted mb-4">
                    El período de su último pago de suscripción ya venció. No puede utilizar el sistema hasta que el laboratorio principal registre un nuevo pago con vigencia actualizada.
                </p>
                <div class="d-flex flex-wrap justify-content-center gap-2">
                    <a href="<?= site_url('tenant-subscription') ?>" class="btn btn-primary">
                        <i class="fa-solid fa-file-invoice me-1"></i> Ver comprobantes y vigencia
                    </a>
                    <a href="<?= site_url('home/logout') ?>" class="btn btn-outline-secondary">Cerrar sesión</a>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
