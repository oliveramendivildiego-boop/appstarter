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
            monto.placeholder = esPendiente ? 'Vacío = pagará después' : '';
        }
        var ast = document.querySelector('.monto-req-ast');
        if (ast) ast.style.display = esPendiente ? 'none' : '';
    }
    if (total && monto && saldo) {
        total.addEventListener('input', calcularSaldo);
        monto.addEventListener('input', calcularSaldo);
        if (tipopago) {
            tipopago.addEventListener('change', function() { toggleMontoRequerido(); calcularSaldo(); });
            toggleMontoRequerido();
        }
    }
});
</script>

<div class="row">
    <div class="col-12">
        <div class="mb-3">
            <label for="total_reco" class="form-label">Total reco: <span class="text-danger">*</span></label>
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
            <label for="monto_pagar" class="form-label">Monto a pagar: <span class="text-danger monto-req-ast">*</span></label>
            <input type="text" name="monto_pagar" id="monto_pagar" class="form-control" value="" required placeholder="">
            <div class="invalid-feedback" id="monto_pagar_error"></div>
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
