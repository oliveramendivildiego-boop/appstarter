<?php

namespace App\Services;

/**
 * Enlace de un solo uso para entrar a otro host (ej. quantum.local) manteniendo usuario y modo fantasma.
 * Requiere writable compartido entre vhosts y el mismo encryption.key (o tenantHandoff.secret en .env).
 */
class TenantHandoffService
{
    private const TTL = 120;

    private const DIR = 'tenant_handoff';

    public function create(
        int $personId,
        string $username,
        string $tenantKey,
        int $centralPersonId = 0
    ): string {
        $dir = rtrim(WRITEPATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . self::DIR;
        if (! is_dir($dir) && ! @mkdir($dir, 0750, true) && ! is_dir($dir)) {
            throw new \RuntimeException('No se pudo crear el directorio de handoff.');
        }

        $token = bin2hex(random_bytes(24));
        $payload = [
            'person_id'               => $personId,
            'username'                => strtolower(trim($username)),
            'ghost_target_tenant_key' => $tenantKey,
            'suppress_tenant_audit'   => true,
            'exp'                     => time() + self::TTL,
        ];
        if ($centralPersonId > 0 && $centralPersonId !== $personId) {
            $payload['ghost_central_person_id'] = $centralPersonId;
        }
        ksort($payload);
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            throw new \RuntimeException('No se pudo serializar el handoff.');
        }
        $sig = hash_hmac('sha256', $json, $this->secret());
        $file = [
            'payload' => $payload,
            'sig'     => $sig,
        ];
        $path = $dir . DIRECTORY_SEPARATOR . $token . '.json';
        if (file_put_contents($path, json_encode($file), LOCK_EX) === false) {
            throw new \RuntimeException('No se pudo escribir el token de handoff.');
        }

        return $token;
    }

    /**
     * @return array<string, mixed>|null payload sin sig
     */
    public function consume(string $token): ?array
    {
        $token = strtolower(preg_replace('/[^a-f0-9]/', '', $token) ?? '');
        if (strlen($token) < 32) {
            return null;
        }
        $path = rtrim(WRITEPATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . self::DIR . DIRECTORY_SEPARATOR . $token . '.json';
        if (! is_file($path)) {
            return null;
        }
        $raw = @file_get_contents($path);
        @unlink($path);
        if ($raw === false || $raw === '') {
            return null;
        }
        $file = json_decode($raw, true);
        if (! is_array($file) || ! isset($file['payload'], $file['sig']) || ! is_array($file['payload'])) {
            return null;
        }
        $payload = $file['payload'];
        ksort($payload);
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            return null;
        }
        $expect = hash_hmac('sha256', $json, $this->secret());
        if (! hash_equals($expect, (string) $file['sig'])) {
            return null;
        }
        if (time() > (int) ($payload['exp'] ?? 0)) {
            return null;
        }

        return $payload;
    }

    private function secret(): string
    {
        $k = env('tenantHandoff.secret');
        if (is_string($k) && $k !== '') {
            return $k;
        }
        $enc = config('Encryption');
        $key = is_object($enc) ? (string) ($enc->key ?? '') : '';

        return $key !== '' ? $key : 'tenant-handoff-fallback-change-me';
    }
}
