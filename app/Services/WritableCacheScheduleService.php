<?php

namespace App\Services;

use App\Models\AppConfigModel;
use DateTimeImmutable;
use DateTimeZone;

/**
 * Programación de limpieza automática de writable/cache (app_config + cron/spark).
 */
class WritableCacheScheduleService
{
    /** @var string */
    public static $keyEnabled = 'writable_cache_schedule_enabled';

    /** @var string */
    public static $keyFrequency = 'writable_cache_schedule_frequency';

    /** @var string */
    public static $keyTime = 'writable_cache_schedule_time';

    /** @var string */
    public static $keyWeekday = 'writable_cache_schedule_weekday';

    /** @var string */
    public static $keyLastRun = 'writable_cache_schedule_last_run';

    /** @var string */
    public static $freqDaily = 'daily';

    /** @var string */
    public static $freqWeekly = 'weekly';

    /** @var AppConfigModel */
    private $appConfig;

    public function __construct(?AppConfigModel $appConfig = null)
    {
        $this->appConfig = $appConfig ?? model(AppConfigModel::class);
    }

    /**
     * @return array<string, string|int>
     */
    public function getFormState(): array
    {
        $enabled = $this->appConfig->getValue(self::$keyEnabled);
        $freq    = strtolower(trim($this->appConfig->getValue(self::$keyFrequency)));
        if (! in_array($freq, [self::$freqDaily, self::$freqWeekly], true)) {
            $freq = self::$freqDaily;
        }
        $time = trim($this->appConfig->getValue(self::$keyTime));
        if ($time === '' || ! preg_match('/^\d{1,2}:\d{2}$/', $time)) {
            $time = '03:00';
        }
        $parts = explode(':', $time);
        $time  = sprintf('%02d:%02d', max(0, min(23, (int) ($parts[0] ?? 0))), max(0, min(59, (int) ($parts[1] ?? 0))));

        $wd = (int) $this->appConfig->getValue(self::$keyWeekday);
        $wd = max(0, min(6, $wd));

        return [
            self::$keyEnabled   => ($enabled === '1') ? '1' : '0',
            self::$keyFrequency => $freq,
            self::$keyTime      => $time,
            self::$keyWeekday   => $wd,
            self::$keyLastRun   => trim($this->appConfig->getValue(self::$keyLastRun)),
        ];
    }

    /**
     * @param array<string, mixed> $post
     *
     * @return array{success: bool, message: string, data?: array<string, string>}
     */
    public function saveFromPost(array $post): array
    {
        $enabled     = ! empty($post['writable_cache_schedule_enabled']) ? '1' : '0';
        $prevEnabled = $this->appConfig->getValue(self::$keyEnabled);

        $freq = strtolower(trim((string) ($post['writable_cache_schedule_frequency'] ?? '')));
        if (! in_array($freq, [self::$freqDaily, self::$freqWeekly], true)) {
            return ['success' => false, 'message' => 'Frecuencia no válida.'];
        }

        $timeRaw = trim((string) ($post['writable_cache_schedule_time'] ?? ''));
        if ($timeRaw === '' || ! preg_match('/^\d{1,2}:\d{2}$/', $timeRaw)) {
            return ['success' => false, 'message' => 'Hora no válida (use HH:MM).'];
        }
        $tp   = explode(':', $timeRaw);
        $time = sprintf('%02d:%02d', max(0, min(23, (int) ($tp[0] ?? 0))), max(0, min(59, (int) ($tp[1] ?? 0))));

        $wd = (int) ($post['writable_cache_schedule_weekday'] ?? 1);
        $wd = max(0, min(6, $wd));

        $batch = [
            self::$keyEnabled   => $enabled,
            self::$keyFrequency => $freq,
            self::$keyTime      => $time,
            self::$keyWeekday   => (string) $wd,
        ];

        if (! $this->appConfig->batchSave($batch)) {
            return ['success' => false, 'message' => 'No se pudo guardar la programación.'];
        }

        if ($enabled === '1') {
            $lr = trim($this->appConfig->getValue(self::$keyLastRun));
            if ($prevEnabled !== '1' || $lr === '') {
                $this->markLastRunNow();
            }
        }

        return ['success' => true, 'message' => 'Programación de limpieza de caché guardada.', 'data' => $batch];
    }

    public function markLastRunNow(): void
    {
        $this->appConfig->saveValue(self::$keyLastRun, RegisterService::mysqlNowForReport());
    }

    public function isAutomationEnabled(): bool
    {
        return $this->appConfig->getValue(self::$keyEnabled) === '1';
    }

    public function getResolvedTimezoneIdentifier(): string
    {
        return $this->resolveTimezone()->getName();
    }

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
        $slot = $freq === self::$freqWeekly
            ? $this->scheduledInstantThisWeek($now, (int) $state[self::$keyWeekday], $hour, $minute)
            : $this->scheduledInstantToday($now, $hour, $minute);

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

    private function scheduledInstantToday(DateTimeImmutable $now, int $hour, int $minute): ?DateTimeImmutable
    {
        $todaySlot = $now->setTime($hour, $minute, 0);
        if ($now < $todaySlot) {
            return null;
        }

        return $todaySlot;
    }

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
