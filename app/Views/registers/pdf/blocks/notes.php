<?php $notaResultado = trim((string)($register_info->comentario_resultado ?? '')); ?>
<?php if ($notaResultado !== ''): ?>
    <div class="group-title" style="margin-top: 10px;">NOTAS</div>
    <table class="results">
        <tbody>
            <tr>
                <td style="white-space: pre-wrap;"><?= esc($notaResultado) ?></td>
            </tr>
        </tbody>
    </table>
<?php endif; ?>
