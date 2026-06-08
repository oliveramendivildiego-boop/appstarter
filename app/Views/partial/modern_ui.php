<!-- Capa UI moderna (Fase 1–2): aditiva, no altera flujos existentes -->
<link rel="manifest" href="<?= base_url('manifest.webmanifest') ?>" />
<link rel="stylesheet" href="<?= base_url('css/modern-ui.css') ?>" />

<div id="lab-command-palette" class="lab-cmd-palette" hidden aria-hidden="true">
    <div class="lab-cmd-backdrop"></div>
    <div class="lab-cmd-dialog" role="dialog" aria-modal="true" aria-label="Paleta de comandos">
        <div class="lab-cmd-input-wrap">
            <i class="fa-solid fa-magnifying-glass text-muted"></i>
            <input type="text" id="lab-cmd-input" class="lab-cmd-input" placeholder="Buscar módulos, acciones…" autocomplete="off" aria-autocomplete="list" aria-controls="lab-cmd-results" />
            <kbd>Ctrl+K</kbd>
        </div>
        <ul id="lab-cmd-results" class="lab-cmd-results" role="listbox"></ul>
        <div class="lab-cmd-hint">↑↓ navegar · Enter abrir · Esc cerrar</div>
    </div>
</div>

<script src="<?= base_url('js/modern/command-palette.js') ?>" defer></script>
<script src="<?= base_url('js/modern/data-grid-enhance.js') ?>" defer></script>
<script src="<?= base_url('js/modern/offline-pwa.js') ?>" defer></script>
