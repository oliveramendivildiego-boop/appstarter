<script>
document.addEventListener('DOMContentLoaded', function() {
    var total = document.getElementById('total');
    var monto = document.getElementById('monto_pagar');
    var saldo = document.getElementById('saldo');
    var tipopago = document.getElementById('tipopago');

    function calcularSaldo() {
        var t = parseFloat(total?.value || 0);
        var mVal = (monto?.value || '').trim();
        var esPendiente = (tipopago?.value || '') === '4';
        var m = parseFloat(mVal || 0);
        if (esPendiente && mVal === '') m = 0;
        if (!isNaN(t) && !isNaN(m)) {
            saldo.value = (t - m).toFixed(2);
        } else {
            saldo.value = '';
        }
    }
    function toggleMontoRequerido() {
        var esPendiente = (tipopago?.value || '') === '4';
        if (monto) {
            monto.required = !esPendiente;
            if (esPendiente) {
                monto.value = '';
                monto.disabled = true;
                monto.placeholder = '';
            } else {
                monto.disabled = false;
                monto.placeholder = '';
            }
        }
        var ast = document.querySelector('.monto-req-ast');
        if (ast) ast.style.display = esPendiente ? 'none' : '';
        if (esPendiente && total && saldo) {
            var t = parseFloat(total.value || 0);
            if (!isNaN(t) && t > 0) {
                saldo.value = t.toFixed(2);
            }
        }
        calcularSaldo();
    }
    if (total) {
        total.addEventListener('focus', function() { this.select(); });
        total.addEventListener('click', function() { this.select(); });
    }
    if (total && monto && saldo) {
        total.addEventListener('input', calcularSaldo);
        monto.addEventListener('input', calcularSaldo);
        if (tipopago) {
            tipopago.addEventListener('change', toggleMontoRequerido);
            toggleMontoRequerido();
        }
    }
});
</script>

<style>
.codigo-orden-preview-wrap {
    background: linear-gradient(135deg, #e8f1ff 0%, #f4f9ff 100%);
    border: 2px solid #0d6efd;
    border-radius: 0.5rem;
    box-shadow: 0 0.15rem 0.5rem rgba(13, 110, 253, 0.1);
    margin-bottom: 0.85rem;
    padding: 0.65rem 0.5rem;
    text-align: center;
}
.codigo-orden-preview-label {
    color: #5c6f8a;
    font-size: 0.68rem;
    font-weight: 600;
    letter-spacing: 0.03em;
    margin-bottom: 0.2rem;
    text-transform: uppercase;
}
.codigo-orden-preview-value {
    color: #0a58ca;
    font-size: clamp(1rem, 3vw, 1.25rem);
    font-weight: 700;
    letter-spacing: 0.04em;
    line-height: 1.2;
    word-break: break-word;
}
.codigo-orden-preview-hint {
    color: #6c757d;
    font-size: 0.68rem;
    margin-top: 0.3rem;
}
</style>

<div class="row">
    <div class="col-12">
        <div class="codigo-orden-preview-wrap">
            <div class="codigo-orden-preview-label"><?= !empty($edit_registro) ? 'Código de orden' : 'Código que se asignará' ?></div>
            <div id="codigo_orden_preview" class="codigo-orden-preview-value"><?= esc($codigo_orden ?? '') ?></div>
            <?php if (empty($edit_registro)): ?>
                <div class="codigo-orden-preview-hint">Vista previa; se confirma al guardar.</div>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-12">
        <div class="mb-3">
            <label for="total_reco" class="form-label">Total Recomendado: <span class="text-danger">*</span></label>
            <input type="text" name="total_reco" id="total_reco" class="form-control" readonly value="" required>
            <div class="invalid-feedback" id="total_reco_error"></div>
        </div>
    </div>
    <div class="col-12">
        <div class="mb-3">
            <label for="total" class="form-label">Total: <span class="text-danger">*</span></label>
            <input type="text" name="total" id="total" class="form-control" value="" required>
            <div class="invalid-feedback" id="total_error"></div>
        </div>
    </div>
    <div class="col-12">
        <div class="mb-3">
            <label for="tipopago" class="form-label">Tipo de pago: <span class="text-danger">*</span></label>
            <select name="tipopago" id="tipopago" class="form-select">
                <option value="">-- Seleccione --</option>
                <option value="1">Efectivo</option>
                <option value="2">QR</option>
                <option value="3">Transferencia</option>
                <option value="4">Pendiente</option>
            </select>
            <div class="invalid-feedback" id="tipopago_error"></div>
        </div>
    </div>
    <div class="col-12">
        <div class="mb-3">
            <label for="monto_pagar" class="form-label">Monto a pagar: <span class="text-danger monto-req-ast">*</span></label>
            <input type="text" name="monto_pagar" id="monto_pagar" class="form-control" value="" required placeholder="">
            <div class="invalid-feedback" id="monto_pagar_error"></div>
        </div>
    </div>
    <div class="col-12">
        <div class="mb-3">
            <label for="saldo" class="form-label">Saldo: <span class="text-danger">*</span></label>
            <input type="text" name="saldo" id="saldo" class="form-control" readonly value="" required>
            <div class="invalid-feedback" id="saldo_error"></div>
        </div>
    </div>
    <div class="col-12">
        <div class="mb-3">
            <label for="comentarios" class="form-label">Comentarios:</label>
            <textarea name="comentarios" id="comentarios" class="form-control" rows="3" placeholder="Comentarios..."></textarea>
        </div>
    </div>
    <div class="col-12">
        <button type="button" id="guardar" class="btn btn-primary w-100">Guardar</button>
    </div>
</div>
