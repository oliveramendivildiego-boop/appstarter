<?php

namespace App\Services;

use App\Models\AppConfigModel;

/**
 * Registra qué reportes analíticos ya abrió cada usuario (persistido en app_config).
 */
class ReportsAnalyticsSeenService
{
    public const CONFIG_KEY = 'reports_analytics_seen_json';

    /** @var list<string> Claves estables de los reportes analíticos nuevos */
    public const REPORT_KEYS = [
        'tendencia_paciente',
        'valores_criticos',
        'pruebas_mas_solicitadas',
        'comparativo_mensual',
        'tiempo_entrega',
        'productividad_usuarios',
        'notificaciones_entrega',
        'resultados_corregidos',
        'pendientes_validacion',
        'consumo_insumos',
        'proyeccion_insumos',
    ];

    protected AppConfigModel $appConfig;

    public function __construct(?AppConfigModel $appConfig = null)
    {
        $this->appConfig = $appConfig ?? model(AppConfigModel::class);
    }

    /** @return list<string> */
    public function getSeenKeys(int $personId): array
    {
        if ($personId <= 0) {
            return [];
        }

        $map = $this->loadMap();

        return array_values(array_filter(
            $map[(string) $personId] ?? [],
            static fn (string $key): bool => in_array($key, self::REPORT_KEYS, true)
        ));
    }

    public function isNew(int $personId, string $reportKey): bool
    {
        if ($personId <= 0 || ! in_array($reportKey, self::REPORT_KEYS, true)) {
            return false;
        }

        return ! in_array($reportKey, $this->getSeenKeys($personId), true);
    }

    public function markSeen(int $personId, string $reportKey): void
    {
        if ($personId <= 0 || ! in_array($reportKey, self::REPORT_KEYS, true)) {
            return;
        }

        $map = $this->loadMap();
        $uid = (string) $personId;
        $seen = $map[$uid] ?? [];

        if (in_array($reportKey, $seen, true)) {
            return;
        }

        $seen[] = $reportKey;
        sort($seen);
        $map[$uid] = $seen;

        $this->appConfig->saveValue(
            self::CONFIG_KEY,
            json_encode($map, JSON_UNESCAPED_UNICODE)
        );
    }

    /** @return array<string, list<string>> */
    private function loadMap(): array
    {
        $raw = trim($this->appConfig->getValue(self::CONFIG_KEY));
        if ($raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (! is_array($decoded)) {
            return [];
        }

        $map = [];
        foreach ($decoded as $personId => $keys) {
            if (! is_array($keys)) {
                continue;
            }
            $personKey = trim((string) $personId);
            if ($personKey === '') {
                continue;
            }
            $map[$personKey] = array_values(array_unique(array_filter(array_map(
                static fn ($k): string => trim((string) $k),
                $keys
            ))));
        }

        return $map;
    }
}
