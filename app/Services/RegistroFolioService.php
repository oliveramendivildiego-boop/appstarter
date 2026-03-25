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

        $seqKey = $this->sequenceKey($format, $at);
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

            $candidate = $this->applyFormat($format, $at, $i);
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
     * Clave de secuencia según la granularidad de fecha usada en la plantilla.
     */
    public function sequenceKey(string $format, \DateTimeInterface $at): string
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

    public function applyFormat(string $format, \DateTimeInterface $at, int $incremento): string
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

        $s = str_replace('%i', (string) $incremento, $s);
        $s = str_replace("\x00PERCT\x00", '%', $s);

        return $s;
    }
}
