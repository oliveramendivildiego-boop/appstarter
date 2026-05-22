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

        $tz = date_default_timezone_get() ?: 'UTC';
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

                if (!$row) {
                    $db->table('registro_folio_secuencia')->insert(['seq_key' => $seqKey, 'last_num' => 1]);
                    $i = 1;
                } else {
                    $i = (int) $row['last_num'] + 1;
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

    public function applyFormat(string $format, \DateTimeInterface $at, int $incremento, ?int $padWidth = null): string
    {
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

        $width = $padWidth ?? $this->counterPadWidth();
        $iStr = $width > 0
            ? str_pad((string) max(0, $incremento), $width, '0', STR_PAD_LEFT)
            : (string) $incremento;
        $s = str_replace('%i', $iStr, $s);
        $s = str_replace("\x00PERCT\x00", '%', $s);

        return $s;
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
}
