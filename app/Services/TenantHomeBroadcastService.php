<?php

namespace App\Services;

use App\Libraries\TenantResolver;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\Config as DbConfig;
use CodeIgniter\HTTP\Files\UploadedFile;
use Config\Database as AppDatabaseConfig;

/**
 * Aviso (imagen y/o mensaje) configurado en el tenant default y visible en /home de laboratorios cliente.
 */
class TenantHomeBroadcastService
{
    public const KEY_ENABLED = 'tenant_home_broadcast_enabled';
    public const KEY_TITLE   = 'tenant_home_broadcast_title';
    public const KEY_MESSAGE = 'tenant_home_broadcast_message';
    public const KEY_IMAGE   = 'tenant_home_broadcast_image';

    private const CACHE_KEY = 'tenant_home_broadcast_v1';
    private const CACHE_TTL = 300;

    private TenantResolver $resolver;

    public function __construct(?TenantResolver $resolver = null)
    {
        $this->resolver = $resolver ?? new TenantResolver();
    }

    public function isMultiTenant(): bool
    {
        return $this->resolver->resolveDefaultTenantKey() !== null;
    }

    public function isNonDefaultTenantSession(): bool
    {
        return (new TenantSubscriptionService())->isNonDefaultTenantSession();
    }

    /**
     * Datos para el formulario en /config (solo tenant default).
     *
     * @return array{enabled: string, title: string, message: string, image: string, image_url: string}
     */
    public function getFormState(): array
    {
        $cfg = $this->readConfigFromDefaultTenant();

        $image      = trim((string) ($cfg[self::KEY_IMAGE] ?? ''));
        $hasContent = $image !== '' || trim((string) ($cfg[self::KEY_TITLE] ?? '')) !== ''
            || trim((string) ($cfg[self::KEY_MESSAGE] ?? '')) !== '';
        $enabled = ($cfg[self::KEY_ENABLED] ?? '0') === '1';

        return [
            'enabled'     => $enabled ? '1' : '0',
            'is_inactive' => $hasContent && ! $enabled,
            'title'       => (string) ($cfg[self::KEY_TITLE] ?? ''),
            'message'     => (string) ($cfg[self::KEY_MESSAGE] ?? ''),
            'image'       => $image,
            'image_url'   => $image !== '' ? $this->imageUrlForPath($image, true) : '',
        ];
    }

    /**
     * Aviso para mostrar en el dashboard de tenants hijos; null si no aplica.
     *
     * @return array{show: bool, title: string, message: string, image_url: string}|null
     */
    public function getForDashboard(): ?array
    {
        if (! $this->isMultiTenant()) {
            return null;
        }

        return $this->buildDisplayPayload();
    }

    /**
     * @return array{show: bool, title: string, message: string, image_url: string}|null
     */
    private function buildDisplayPayload(bool $forConfigPreview = false): ?array
    {
        $cfg = $this->readConfigFromDefaultTenant();

        $title   = trim((string) ($cfg[self::KEY_TITLE] ?? ''));
        $message = trim((string) ($cfg[self::KEY_MESSAGE] ?? ''));
        $image   = trim((string) ($cfg[self::KEY_IMAGE] ?? ''));

        if ($title === '' && $message === '' && $image === '') {
            return null;
        }

        if (($cfg[self::KEY_ENABLED] ?? '0') !== '1') {
            return null;
        }

        if (! $forConfigPreview && ! $this->isNonDefaultTenantSession()) {
            return null;
        }

        return [
            'show'       => true,
            'title'      => $title,
            'message'    => $message,
            'image_url'  => $image !== '' ? $this->buildImagePublicUrl($image) : '',
        ];
    }

    /**
     * Ruta relativa de la imagen guardada en el tenant default (vacío si no hay).
     */
    public function getStoredImageRelativePath(): string
    {
        $cfg = $this->readConfigFromDefaultTenant();

        return trim((string) ($cfg[self::KEY_IMAGE] ?? ''));
    }

    /**
     * Sirve la imagen vía la app (mismo host del tenant activo); evita 404 en otro vhost/dominio.
     */
    public function buildImagePublicUrl(string $relativePath): string
    {
        return $this->imageUrlForPath($relativePath, false);
    }

    private function imageUrlForPath(string $relativePath, bool $preferDirectFile): string
    {
        $path = $this->resolveImageFileOnDisk($relativePath);
        if ($path === null) {
            return '';
        }

        $v = (string) @filemtime($path);

        if ($preferDirectFile) {
            return base_url($relativePath) . ($v !== '' ? '?v=' . rawurlencode($v) : '');
        }

        return site_url('home/tenant-broadcast-image') . ($v !== '' ? '?v=' . rawurlencode($v) : '');
    }

    /**
     * Ruta absoluta segura bajo public/ para servir el archivo.
     */
    public function resolveImageFileOnDisk(string $relativePath): ?string
    {
        $relativePath = ltrim(str_replace('\\', '/', $relativePath), '/');
        if ($relativePath === '' || str_contains($relativePath, '..')) {
            return null;
        }
        if (! preg_match('#^images/tenant-broadcast-[a-zA-Z0-9]+\.(jpe?g|png|gif|webp)$#i', $relativePath)) {
            return null;
        }

        $full = FCPATH . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);

        return is_file($full) && is_readable($full) ? $full : null;
    }

    /**
     * @param array<string, mixed> $post
     */
    public function saveFromRequest(array $post, ?UploadedFile $imageFile = null): array
    {
        if (! $this->isMultiTenant()) {
            return ['success' => false, 'message' => 'Multi-tenant no está configurado.'];
        }

        $defaultKey = $this->resolver->resolveDefaultTenantKey();
        if ($defaultKey === null) {
            return ['success' => false, 'message' => 'No hay tenant por defecto definido.'];
        }

        $current = $this->readConfigFromDefaultTenant();

        $title   = mb_substr(trim((string) ($post[self::KEY_TITLE] ?? '')), 0, 200);
        $message = mb_substr(trim((string) ($post[self::KEY_MESSAGE] ?? '')), 0, 4000);

        $imagePath = trim((string) ($current[self::KEY_IMAGE] ?? ''));

        $removeImage = isset($post['tenant_home_broadcast_remove_image'])
            && (string) $post['tenant_home_broadcast_remove_image'] === '1';

        if ($removeImage) {
            $imagePath = '';
        }

        if ($imageFile !== null && $imageFile->getError() !== UPLOAD_ERR_NO_FILE) {
            if (! $imageFile->isValid()) {
                return ['success' => false, 'message' => 'La imagen no se subió correctamente.'];
            }
            $configService = new ConfigService();
            $uploaded      = $configService->uploadPublicConfigImage($imageFile, 'tenant-broadcast-');
            if ($uploaded === null) {
                return ['success' => false, 'message' => 'Imagen no válida. Use JPG, PNG, GIF o WebP (máx. 2 MB).'];
            }
            $imagePath = $uploaded;
        }

        $hasContent = $title !== '' || $message !== '' || $imagePath !== '';
        $forceOff   = isset($post['tenant_home_broadcast_disable']) && (string) $post['tenant_home_broadcast_disable'] === '1';
        $showOn     = isset($post[self::KEY_ENABLED]) && (string) $post[self::KEY_ENABLED] === '1';

        if (! $hasContent) {
            if ($showOn && ! $forceOff) {
                return ['success' => false, 'message' => 'Indique un título, un mensaje o una imagen para activar el aviso.'];
            }
            $enabled = '0';
        } elseif ($forceOff) {
            $enabled = '0';
        } elseif ($showOn) {
            $enabled = '1';
        } else {
            $enabled = '0';
        }

        $batch = [
            self::KEY_ENABLED => $enabled,
            self::KEY_TITLE   => $title,
            self::KEY_MESSAGE => $message,
            self::KEY_IMAGE   => $imagePath,
        ];

        if (! $this->writeConfigToDefaultTenant($batch)) {
            return ['success' => false, 'message' => 'No se pudo guardar en la base del tenant principal.'];
        }

        $this->invalidateCache();

        if (! $hasContent) {
            $msg = 'Aviso eliminado. Ya no se mostrará en los laboratorios cliente.';
        } elseif ($enabled === '1') {
            $msg = 'Aviso del dashboard guardado. Los laboratorios cliente lo verán en /home.';
        } else {
            $msg = 'Aviso oculto en los laboratorios cliente. El contenido se conserva; puede reactivarlo con «Mostrar aviso».';
        }

        return ['success' => true, 'message' => $msg];
    }

    public function invalidateCache(): void
    {
        \Config\Services::cache()->delete($this->cacheKey());
    }

    /**
     * @return array<string, string>
     */
    private function readConfigFromDefaultTenant(): array
    {
        $cache = \Config\Services::cache();
        $key   = $this->cacheKey();
        $hit   = $cache->get($key);
        if (is_array($hit)) {
            return $hit;
        }

        $keys = [self::KEY_ENABLED, self::KEY_TITLE, self::KEY_MESSAGE, self::KEY_IMAGE];
        $out  = array_fill_keys($keys, '');

        $db = $this->connectDefaultTenant();
        if ($db === null) {
            return $out;
        }

        try {
            $prefix = (string) ($db->getPrefix() ?? '');
            $table  = $prefix . 'app_config';
            $rows   = $db->table($table)->whereIn('key', $keys)->get()->getResultArray();
            foreach ($rows as $row) {
                $k = (string) ($row['key'] ?? '');
                if ($k !== '' && array_key_exists($k, $out)) {
                    $out[$k] = (string) ($row['value'] ?? '');
                }
            }
        } catch (\Throwable $e) {
            log_message('error', 'TenantHomeBroadcast: lectura fallida: ' . $e->getMessage());
        } finally {
            $this->disconnectProbe($db);
        }

        $cache->save($key, $out, self::CACHE_TTL);

        return $out;
    }

    /**
     * @param array<string, string> $batch
     */
    private function writeConfigToDefaultTenant(array $batch): bool
    {
        $db = $this->connectDefaultTenant();
        if ($db === null) {
            return false;
        }

        try {
            $prefix = (string) ($db->getPrefix() ?? '');
            $table  = $prefix . 'app_config';
            $db->transStart();
            foreach ($batch as $cfgKey => $value) {
                $exists = $db->table($table)->where('key', $cfgKey)->countAllResults() > 0;
                if ($exists) {
                    $db->table($table)->where('key', $cfgKey)->update(['value' => (string) $value]);
                } else {
                    $db->table($table)->insert(['key' => $cfgKey, 'value' => (string) $value]);
                }
            }
            $db->transComplete();

            return $db->transStatus();
        } catch (\Throwable $e) {
            log_message('error', 'TenantHomeBroadcast: escritura fallida: ' . $e->getMessage());

            return false;
        } finally {
            $this->disconnectProbe($db);
        }
    }

    private function connectDefaultTenant(): ?BaseConnection
    {
        $defaultKey = $this->resolver->resolveDefaultTenantKey();
        if ($defaultKey === null) {
            return null;
        }

        $tenantDb = $this->resolver->resolveDatabaseConfig($defaultKey);
        if ($tenantDb === []) {
            return null;
        }

        $dbConfig = config(AppDatabaseConfig::class);
        $cfg      = array_merge($dbConfig->default, $tenantDb);
        $cfg['DBDebug'] = false;

        try {
            return DbConfig::connect($cfg, 'tenant_home_broadcast', false);
        } catch (\Throwable $e) {
            log_message('error', 'TenantHomeBroadcast: conexión fallida: ' . $e->getMessage());

            return null;
        }
    }

    private function disconnectProbe(BaseConnection $db): void
    {
        try {
            $db->close();
        } catch (\Throwable $e) {
            // ignorar
        }
        $ref = new \ReflectionClass(DbConfig::class);
        if (! $ref->hasProperty('instances')) {
            return;
        }
        $prop = $ref->getProperty('instances');
        $prop->setAccessible(true);
        /** @var array<string, mixed> $instances */
        $instances = $prop->getValue();
        if (! is_array($instances)) {
            return;
        }
        unset($instances['tenant_home_broadcast']);
        $prop->setValue($instances);
    }

    private function cacheKey(): string
    {
        $defaultKey = $this->resolver->resolveDefaultTenantKey() ?? '';
        $dbCfg      = $this->resolver->resolveDatabaseConfig($defaultKey) ?: [];

        $sig = implode("\0", [
            $defaultKey,
            (string) ($dbCfg['hostname'] ?? ''),
            (string) ($dbCfg['port'] ?? ''),
            (string) ($dbCfg['database'] ?? ''),
            (string) ($dbCfg['DBPrefix'] ?? ''),
        ]);

        return self::CACHE_KEY . '_' . hash('sha256', $sig);
    }

}
