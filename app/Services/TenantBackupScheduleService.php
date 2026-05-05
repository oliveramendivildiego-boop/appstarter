<?php

namespace App\Services;

use App\Models\AppConfigModel;
use DateTimeImmutable;
use DateTimeZone;

/**
 * Programación de respaldos automáticos de tenants (config en app_config + comando spark).
 */
class TenantBackupScheduleService
{
    /** @var string */
    public static $keyEnabled = 'tenant_backup_schedule_enabled';

    /** @var string */
    public static $keyFrequency = 'tenant_backup_schedule_frequency';

    /** @var string */
    public static $keyTime = 'tenant_backup_schedule_time';

    /** @var string */
    public static $keyWeekday = 'tenant_backup_schedule_weekday';

    /** @var string */
    public static $keyMonthday = 'tenant_backup_schedule_monthday';

    /** @var string */
    public static $keyKeep = 'tenant_backup_schedule_keep';

    /** @var string */
    public static $keyLastRun = 'tenant_backup_schedule_last_run';

    /** @var string */
    public static $freqDaily = 'daily';

    /** @var string */
    public static $freqWeekly = 'weekly';

    /** @var string */
    public static $freqMonthly = 'monthly';

    /** @var AppConfigModel */
    private $appConfig;

    public function __construct(?AppConfigModel $appConfig = null)
    {
        $this->appConfig = $appConfig ?? model(AppConfigModel::class);
    }

    /**
     * Valores para el formulario de configuración.
     *
     * @return array<string, string|int>
     */
    public function getFormState(): array
    {
        $enabled = $this->appConfig->getValue(self::$keyEnabled);
        $freq    = strtolower(trim($this->appConfig->getValue(self::$keyFrequency)));
        if (! in_array($freq, [self::$freqDaily, self::$freqWeekly, self::$freqMonthly], true)) {
            $freq = self::$freqDaily;
        }
        $time = trim($this->appConfig->getValue(self::$keyTime));
        if ($time === '' || ! preg_match('/^\d{1,2}:\d{2}$/', $time)) {
            $time = '02:30';
        }
        $parts = explode(':', $time);
        $time  = sprintf('%02d:%02d', max(0, min(23, (int) ($parts[0] ?? 0))), max(0, min(59, (int) ($parts[1] ?? 0))));

        $wd = (int) $this->appConfig->getValue(self::$keyWeekday);
        $wd = max(0, min(6, $wd));

        $md = (int) $this->appConfig->getValue(self::$keyMonthday);
        $md = max(1, min(28, $md !== 0 ? $md : 1));

        $keep = (int) $this->appConfig->getValue(self::$keyKeep);
        $keep = $keep > 0 ? max(1, min(100, $keep)) : 14;

        return [
            self::$keyEnabled   => ($enabled === '1') ? '1' : '0',
            self::$keyFrequency => $freq,
            self::$keyTime      => $time,
            self::$keyWeekday   => $wd,
            self::$keyMonthday  => $md,
            self::$keyKeep      => $keep,
            self::$keyLastRun  => trim($this->appConfig->getValue(self::$keyLastRun)),
        ];
    }

    /**
     * @param array<string, mixed> $post
     *
     * @return array{success: bool, message: string, data?: array<string, string>}
     */
    public function saveFromPost(array $post): array
    {
        $enabled     = ! empty($post['tenant_backup_schedule_enabled']) ? '1' : '0';
        $prevEnabled = $this->appConfig->getValue(self::$keyEnabled);

        $freq = strtolower(trim((string) ($post['tenant_backup_schedule_frequency'] ?? '')));
        if (! in_array($freq, [self::$freqDaily, self::$freqWeekly, self::$freqMonthly], true)) {
            return ['success' => false, 'message' => 'Frecuencia no válida.'];
        }

        $timeRaw = trim((string) ($post['tenant_backup_schedule_time'] ?? ''));
        if ($timeRaw === '' || ! preg_match('/^\d{1,2}:\d{2}$/', $timeRaw)) {
            return ['success' => false, 'message' => 'Hora no válida (use HH:MM).'];
        }
        $tp = explode(':', $timeRaw);
        $h  = max(0, min(23, (int) ($tp[0] ?? 0)));
        $m  = max(0, min(59, (int) ($tp[1] ?? 0)));
        $time = sprintf('%02d:%02d', $h, $m);

        $wd = (int) ($post['tenant_backup_schedule_weekday'] ?? 1);
        $wd = max(0, min(6, $wd));

        $md = (int) ($post['tenant_backup_schedule_monthday'] ?? 1);
        $md = max(1, min(28, $md));

        $keep = (int) ($post['tenant_backup_schedule_keep'] ?? 14);
        $keep = max(1, min(100, $keep));

        $batch = [
            self::$keyEnabled   => $enabled,
            self::$keyFrequency => $freq,
            self::$keyTime      => $time,
            self::$keyWeekday   => (string) $wd,
            self::$keyMonthday  => (string) $md,
            self::$keyKeep      => (string) $keep,
        ];

        if (! $this->appConfig->batchSave($batch)) {
            return ['success' => false, 'message' => 'No se pudo guardar la programación.'];
        }

        // Evita una corrida inmediata por huecos viejos (p. ej. semanal: domingo con last_run vacío).
        if ($enabled === '1') {
            $lr = trim($this->appConfig->getValue(self::$keyLastRun));
            if ($prevEnabled !== '1' || $lr === '') {
                $this->markLastRunNow();
            }
        }

        return ['success' => true, 'message' => 'Programación de respaldos guardada.', 'data' => $batch];
    }

    public function markLastRunNow(): void
    {
        $tz   = $this->resolveTimezone();
        $now  = new DateTimeImmutable('now', $tz);
        $this->appConfig->saveValue(self::$keyLastRun, $now->format('Y-m-d H:i:s'));
    }

    public function isAutomationEnabled(): bool
    {
        return $this->appConfig->getValue(self::$keyEnabled) === '1';
    }

    public function getKeepCount(): int
    {
        $k = (int) $this->appConfig->getValue(self::$keyKeep);

        return $k > 0 ? max(1, min(100, $k)) : 14;
    }

    /**
     * Zona horaria efectiva para la programación (misma que isDue / markLastRunNow).
     */
    public function getResolvedTimezoneIdentifier(): string
    {
        return $this->resolveTimezone()->getName();
    }

    /**
     * ¿Debe ejecutarse el respaldo en este momento? (invocado por spark cada pocos minutos).
     *
     * Solo es "due" si ya pasó el instante programado del período actual (día / semana / mes)
     * en la zona del laboratorio y no se registró una ejecución posterior a ese instante.
     */
    public function isDue(?DateTimeImmutable $now = null): bool
    {
        if (! $this->isAutomationEnabled()) {
            return false;
        }

        $tz  = $this->resolveTimezone();
        $now = $now ?? new DateTimeImmutable('now', $tz);

        $state = $this->getFormState();
        $hmi   = $this->parseHourMinute((string) $state[self::$keyTime]);
        if ($hmi === null) {
            return false;
        }
        [$hour, $minute] = $hmi;

        $freq = (string) $state[self::$keyFrequency];
        $slot = match ($freq) {
            self::$freqWeekly  => $this->scheduledInstantThisWeek($now, (int) $state[self::$keyWeekday], $hour, $minute),
            self::$freqMonthly => $this->scheduledInstantThisMonth($now, (int) $state[self::$keyMonthday], $hour, $minute),
            default            => $this->scheduledInstantToday($now, $hour, $minute),
        };

        if ($slot === null) {
            return false;
        }

        $lastRaw = trim((string) $state[self::$keyLastRun]);
        if ($lastRaw === '') {
            return true;
        }

        try {
            $last = new DateTimeImmutable($lastRaw, $tz);
        } catch (\Throwable $e) {
            return true;
        }

        return $last < $slot;
    }

    /**
     * Hoy a la hora programada, solo si ya pasó ese instante.
     */
    private function scheduledInstantToday(DateTimeImmutable $now, int $hour, int $minute): ?DateTimeImmutable
    {
        $todaySlot = $now->setTime($hour, $minute, 0);
        if ($now < $todaySlot) {
            return null;
        }

        return $todaySlot;
    }

    /**
     * Semana ISO (lunes=1 … domingo=7), alineada con el selector (PHP w: domingo=0 … sábado=6).
     * Si el día programado aún no ocurre en esta semana ISO, no hay ventana (p. ej. domingo con respaldo los lunes).
     */
    private function scheduledInstantThisWeek(DateTimeImmutable $now, int $weekday, int $hour, int $minute): ?DateTimeImmutable
    {
        $isoTarget = $weekday === 0 ? 7 : $weekday;
        $nNow      = (int) $now->format('N');
        if ($isoTarget > $nNow) {
            return null;
        }
        $diff = $nNow - $isoTarget;
        $slot = $now->setTime($hour, $minute, 0)->modify('-' . $diff . ' days');
        if ($now < $slot) {
            return null;
        }

        return $slot;
    }

    /**
     * Este mes (día 1–28) a la hora indicada, si ya pasó.
     */
    private function scheduledInstantThisMonth(DateTimeImmutable $now, int $dom, int $hour, int $minute): ?DateTimeImmutable
    {
        $tz  = $now->getTimezone();
        $y   = (int) $now->format('Y');
        $mo  = (int) $now->format('n');
        $slot = $this->ymdAtTime($y, $mo, $dom, $hour, $minute, $tz);
        if ($now < $slot) {
            return null;
        }

        return $slot;
    }

    private function ymdAtTime(int $y, int $m, int $d, int $hour, int $minute, DateTimeZone $tz): DateTimeImmutable
    {
        $d = max(1, min(28, $d));

        return new DateTimeImmutable(sprintf('%04d-%02d-%02d %02d:%02d:00', $y, $m, $d, $hour, $minute), $tz);
    }

    /**
     * @return array{0: int, 1: int}|null
     */
    private function parseHourMinute(string $time): ?array
    {
        if (! preg_match('/^(\d{1,2}):(\d{2})$/', $time, $m)) {
            return null;
        }

        return [(int) $m[1], (int) $m[2]];
    }

    private function resolveTimezone(): DateTimeZone
    {
        $name = trim($this->appConfig->getValue('timezone'));
        if ($name !== '') {
            try {
                return new DateTimeZone($name);
            } catch (\Throwable $e) {
                // caer al default de la app
            }
        }

        try {
            return new DateTimeZone(config('App')->appTimezone ?? 'UTC');
        } catch (\Throwable $e) {
            return new DateTimeZone('UTC');
        }
    }
}
