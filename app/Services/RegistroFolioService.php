<?php

namespace App\Services;

use App\Models\AppConfigModel;
use Config\Database;

/**
 * Genera el número de orden visible (folio) según plantilla en configuración.
 * El registro_id interno sigue siendo autonumérico; numero_orden es el identificador personalizado.
 */
class RegistroFolioService
{
    protected AppConfigModel $appConfigModel;

    public function __construct(?AppConfigModel $appConfigModel = null)
    {
        $this->appConfigModel = $appConfigModel ?? model(AppConfigModel::class);
    }

    /**
     * Obtiene el siguiente folio único o null si no hay plantilla configurada.
     */
    public function generateNextFolio(?\DateTimeInterface $at = null): ?string
    {
        $format = trim((string) $this->appConfigModel->getValue('registro_folio_format'));
        if ($format === '' || strpos($format, '%i') === false) {
            return null;
        }

        $tz = RegisterService::reportDisplayTimezone();
        if ($at === null) {
            $at = new \DateTimeImmutable('now', new \DateTimeZone($tz));
        } elseif ($at instanceof \DateTime) {
            $at = \DateTimeImmutable::createFromMutable($at)->setTimezone(new \DateTimeZone($tz));
        } else {
            $at = $at->setTimezone(new \DateTimeZone($tz));
        }

        $seqKey = $this->sequenceKey($format, $at, $this->counterResetMode());
        $db = Database::connect();

        $maxAttempts = 15;
        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            $db->transBegin();
            try {
                $row = $db->query(
                    'SELECT last_num FROM ' . $db->prefixTable('registro_folio_secuencia') . ' WHERE seq_key = ? FOR UPDATE',
                    [$seqKey]
                )->getRowArray();

                $seqLast = $row ? (int) $row['last_num'] : 0;
                $dbMax = $this->maxIncrementFromExisting($db, $format, $at, $seqKey);
                $i = max($seqLast, $dbMax) + 1;

                if (!$row) {
                    $db->table('registro_folio_secuencia')->insert(['seq_key' => $seqKey, 'last_num' => $i]);
                } else {
                    $db->table('registro_folio_secuencia')->where('seq_key', $seqKey)->update(['last_num' => $i]);
                }
            } catch (\Throwable $e) {
                $db->transRollback();
                throw $e;
            }
            $db->transCommit();

            $candidate = $this->applyFormat($format, $at, $i, $this->counterPadWidth());
            if ($candidate === '' || strlen($candidate) > 64) {
                return null;
            }

            $exists = $db->table('registro')->where('numero_orden', $candidate)->countAllResults();
            if ($exists === 0) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * Clave de secuencia según reinicio configurado o, en modo automático, según la plantilla.
     */
    public function sequenceKey(string $format, \DateTimeInterface $at, ?string $resetMode = null): string
    {
        $mode = $resetMode ?? $this->counterResetMode();
        if ($mode !== 'auto') {
            return match ($mode) {
                'day'    => $at->format('Y-m-d'),
                'month'  => $at->format('Y-m'),
                'year'   => $at->format('Y'),
                'global' => 'global',
                default  => $this->sequenceKeyFromFormat($format, $at),
            };
        }

        return $this->sequenceKeyFromFormat($format, $at);
    }

    /**
     * Reinicio automático: día si la plantilla incluye día; si no, mes; si no, año; si no, global.
     */
    protected function sequenceKeyFromFormat(string $format, \DateTimeInterface $at): string
    {
        $hasDay = str_contains($format, '%dd') || str_contains($format, '%d');
        $hasMonth = str_contains($format, '%mm') || str_contains($format, '%m');
        $hasYear = str_contains($format, '%yyyy') || str_contains($format, '%yy');

        if ($hasDay) {
            return $at->format('Y-m-d');
        }
        if ($hasMonth) {
            return $at->format('Y-m');
        }
        if ($hasYear) {
            return $at->format('Y');
        }

        return 'global';
    }

    /**
     * Parte fija del folio (sin el contador %i) para la fecha indicada.
     */
    public function folioStaticPart(string $format, \DateTimeInterface $at): string
    {
        $s = str_replace('%%', "\x00PERCT\x00", $format);
        $s = str_replace('%i', '', $s);

        $repl = [
            '%yyyy' => $at->format('Y'),
            '%yy'   => $at->format('y'),
            '%mm'   => $at->format('m'),
            '%dd'   => $at->format('d'),
            '%m'    => (string) (int) $at->format('n'),
            '%d'    => (string) (int) $at->format('j'),
        ];
        foreach ($repl as $tok => $val) {
            $s = str_replace($tok, $val, $s);
        }

        return str_replace("\x00PERCT\x00", '%', $s);
    }

    public function applyFormat(string $format, \DateTimeInterface $at, int $incremento, ?int $padWidth = null): string
    {
        $width = $padWidth ?? $this->counterPadWidth();
        $iStr = $width > 0
            ? str_pad((string) max(0, $incremento), $width, '0', STR_PAD_LEFT)
            : (string) $incremento;

        $s = str_replace('%%', "\x00PERCT\x00", $format);
        $repl = [
            '%yyyy' => $at->format('Y'),
            '%yy'   => $at->format('y'),
            '%mm'   => $at->format('m'),
            '%dd'   => $at->format('d'),
            '%m'    => (string) (int) $at->format('n'),
            '%d'    => (string) (int) $at->format('j'),
        ];
        foreach ($repl as $tok => $val) {
            $s = str_replace($tok, $val, $s);
        }
        $s = str_replace('%i', $iStr, $s);

        return str_replace("\x00PERCT\x00", '%', $s);
    }

    /**
     * Mayor %i ya usado en numero_orden para el mismo prefijo y periodo de reinicio.
     */
    protected function maxIncrementFromExisting($db, string $format, \DateTimeInterface $at, string $seqKey): int
    {
        $prefix = $this->folioStaticPart($format, $at);
        if ($prefix === '') {
            return 0;
        }

        $builder = $db->table('registro')
            ->select('numero_orden')
            ->like('numero_orden', $prefix, 'after');
        $this->applyIngresoFilterForSeqKey($builder, $seqKey, $at);

        $max = 0;
        $prefixLen = strlen($prefix);
        foreach ($builder->get()->getResultArray() as $row) {
            $num = trim((string) ($row['numero_orden'] ?? ''));
            if ($num === '' || ! str_starts_with($num, $prefix)) {
                continue;
            }
            $suffix = substr($num, $prefixLen);
            if ($suffix !== '' && ctype_digit($suffix)) {
                $max = max($max, (int) $suffix);
            }
        }

        return $max;
    }

    /**
     * Limita la búsqueda al periodo de reinicio cuando el prefijo no incluye toda la fecha.
     */
    protected function applyIngresoFilterForSeqKey($builder, string $seqKey, \DateTimeInterface $at): void
    {
        if ($seqKey === 'global') {
            return;
        }

        $tz = $at->getTimezone();
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $seqKey)) {
            $start = \DateTimeImmutable::createFromFormat('Y-m-d', $seqKey, $tz)->setTime(0, 0, 0);
            $end = $start->modify('+1 day');
        } elseif (preg_match('/^\d{4}-\d{2}$/', $seqKey)) {
            $start = \DateTimeImmutable::createFromFormat('Y-m-d', $seqKey . '-01', $tz)->setTime(0, 0, 0);
            $end = $start->modify('+1 month');
        } elseif (preg_match('/^\d{4}$/', $seqKey)) {
            $start = \DateTimeImmutable::createFromFormat('Y-m-d', $seqKey . '-01-01', $tz)->setTime(0, 0, 0);
            $end = $start->modify('+1 year');
        } else {
            return;
        }

        $builder->where('ingreso >=', $start->format('Y-m-d H:i:s'))
            ->where('ingreso <', $end->format('Y-m-d H:i:s'));
    }

    /** @return 'auto'|'day'|'month'|'year'|'global' */
    public function counterResetMode(): string
    {
        $v = strtolower(trim((string) $this->appConfigModel->getValue('registro_folio_counter_reset')));
        if (in_array($v, ['day', 'month', 'year', 'global'], true)) {
            return $v;
        }

        return 'auto';
    }

    /** Ancho mínimo del contador %i (0 = sin ceros a la izquierda). */
    public function counterPadWidth(): int
    {
        $w = (int) $this->appConfigModel->getValue('registro_folio_counter_pad');
        if ($w < 0) {
            return 0;
        }
        if ($w > 6) {
            return 6;
        }

        return $w;
    }

    /**
     * Código de recepción visible: folio guardado o inferido por formato y fecha de ingreso.
     * Nunca devuelve el registro_id interno.
     */
    public function codigoRecepcionDisplay(int $registroId, ?string $numeroOrden, ?string $ingreso, bool $persistIfMissing = true): string
    {
        if ($registroId < 1) {
            return '';
        }

        $existing = trim((string) ($numeroOrden ?? ''));
        if ($existing !== '') {
            return $existing;
        }

        $inferred = $this->inferFolioFromIngreso($registroId, $ingreso);
        if ($inferred === null || $inferred === '') {
            return '';
        }

        if ($persistIfMissing) {
            $this->persistNumeroOrdenIfVacant($registroId, $inferred);
        }

        return $inferred;
    }

    /**
     * Calcula el folio que correspondería según la posición cronológica en el periodo de reinicio.
     */
    public function inferFolioFromIngreso(int $registroId, ?string $ingreso): ?string
    {
        if ($registroId < 1) {
            return null;
        }

        $format = trim((string) $this->appConfigModel->getValue('registro_folio_format'));
        if ($format === '' || ! str_contains($format, '%i')) {
            return null;
        }

        $ingreso = trim((string) $ingreso);
        if ($ingreso === '') {
            return null;
        }

        try {
            $at = new \DateTimeImmutable($ingreso, new \DateTimeZone(RegisterService::reportDisplayTimezone()));
        } catch (\Throwable) {
            return null;
        }

        $db = Database::connect();
        $builder = $db->table('registro')
            ->select('registro_id')
            ->orderBy('ingreso', 'ASC')
            ->orderBy('registro_id', 'ASC');
        $this->applyIngresoFilterForSeqKey($builder, $this->sequenceKey($format, $at, $this->counterResetMode()), $at);

        $position = 0;
        foreach ($builder->get()->getResultArray() as $idx => $row) {
            if ((int) ($row['registro_id'] ?? 0) === $registroId) {
                $position = $idx + 1;
                break;
            }
        }
        if ($position < 1) {
            return null;
        }

        $folio = $this->applyFormat($format, $at, $position, $this->counterPadWidth());

        return $folio !== '' ? $folio : null;
    }

    protected function persistNumeroOrdenIfVacant(int $registroId, string $folio): void
    {
        $folio = trim($folio);
        if ($folio === '') {
            return;
        }

        $db = Database::connect();
        $taken = (int) $db->table('registro')
            ->where('numero_orden', $folio)
            ->where('registro_id !=', $registroId)
            ->countAllResults();
        if ($taken > 0) {
            return;
        }

        $db->table('registro')
            ->where('registro_id', $registroId)
            ->groupStart()
            ->where('numero_orden IS NULL', null, false)
            ->orWhere('numero_orden', '')
            ->groupEnd()
            ->update(['numero_orden' => $folio]);
    }
}
