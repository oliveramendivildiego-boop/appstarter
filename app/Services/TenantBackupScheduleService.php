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
        $enabled = ! empty($post['tenant_backup_schedule_enabled']) ? '1' : '0';

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
        $candidate = match ($freq) {
            self::$freqWeekly  => $this->computeWeeklyCandidate($now, (int) $state[self::$keyWeekday], $hour, $minute),
            self::$freqMonthly => $this->computeMonthlyCandidate($now, (int) $state[self::$keyMonthday], $hour, $minute),
            default            => $this->computeDailyCandidate($now, $hour, $minute),
        };

        if ($candidate === null || $now < $candidate) {
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

        return $last < $candidate;
    }

    private function computeDailyCandidate(DateTimeImmutable $now, int $hour, int $minute): ?DateTimeImmutable
    {
        $tz = $now->getTimezone();
        $todaySlot = $this->dateAtTime($now, $hour, $minute);
        if ($now >= $todaySlot) {
            return $todaySlot;
        }

        return $todaySlot->modify('-1 day');
    }

    private function computeWeeklyCandidate(DateTimeImmutable $now, int $weekday, int $hour, int $minute): ?DateTimeImmutable
    {
        $wNow = (int) $now->format('w');
        $diff = ($wNow - $weekday + 7) % 7;
        $base = $now->setTime($hour, $minute, 0);
        $slot = $base->modify('-' . $diff . ' days');
        if ($now < $slot) {
            $slot = $slot->modify('-7 days');
        }

        return $slot;
    }

    private function computeMonthlyCandidate(DateTimeImmutable $now, int $dom, int $hour, int $minute): ?DateTimeImmutable
    {
        $tz = $now->getTimezone();
        $y  = (int) $now->format('Y');
        $mo = (int) $now->format('n');

        $slotThis = $this->ymdAtTime($y, $mo, $dom, $hour, $minute, $tz);
        if ($now >= $slotThis) {
            return $slotThis;
        }

        $prev = $slotThis->modify('-1 month');

        return $prev;
    }

    private function ymdAtTime(int $y, int $m, int $d, int $hour, int $minute, DateTimeZone $tz): DateTimeImmutable
    {
        $d = max(1, min(28, $d));

        return new DateTimeImmutable(sprintf('%04d-%02d-%02d %02d:%02d:00', $y, $m, $d, $hour, $minute), $tz);
    }

    private function dateAtTime(DateTimeImmutable $date, int $hour, int $minute): DateTimeImmutable
    {
        return $date->setTime($hour, $minute, 0);
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
