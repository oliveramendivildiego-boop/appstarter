<?php

namespace App\Services;

use App\Models\AnalisisDeliveryNotificationModel;
use App\Models\AppConfigModel;
use App\Models\RegisterModel;

/**
 * Notificaciones persistentes de análisis pendientes de entrega a médico/paciente.
 */
class DeliveryNotificationService
{
    public const KEY_ENABLED = 'delivery_notifications_enabled';
    public const KEY_SCOPE   = 'delivery_notifications_scope';
    public const KEY_ENABLED_AT = 'delivery_notifications_enabled_at';

    private const CACHE_COUNT_KEY = 'delivery_notif_pending_count_v2';
    private const CACHE_COUNT_TTL = 60;

    protected ConfigService $configService;
    protected RegisterModel $registerModel;
    protected AnalisisDeliveryNotificationModel $notificationModel;

    public function __construct(
        ?ConfigService $configService = null,
        ?RegisterModel $registerModel = null,
        ?AnalisisDeliveryNotificationModel $notificationModel = null
    ) {
        $this->configService     = $configService ?? new ConfigService();
        $this->registerModel     = $registerModel ?? model(RegisterModel::class);
        $this->notificationModel = $notificationModel ?? model(AnalisisDeliveryNotificationModel::class);
    }

    public function isFeatureAvailable(): bool
    {
        return $this->notificationModel->tableExists()
            && $this->registerModel->hasRegistroColumn('notificar_entrega');
    }

    public function isEnabled(): bool
    {
        if (! $this->isFeatureAvailable()) {
            return false;
        }

        return ($this->configService->getAllAsArray()[self::KEY_ENABLED] ?? '0') === '1';
    }

    public static function normalizeScope(string $raw): string
    {
        $v = strtolower(trim($raw));

        return $v === 'selected' ? 'selected' : 'all';
    }

    public function getScope(): string
    {
        return self::normalizeScope(
            (string) ($this->configService->getAllAsArray()[self::KEY_SCOPE] ?? 'all')
        );
    }

    /** True si el checkbox «Notificar entrega» debe mostrarse en Recepción (/registers). */
    public function showNotificarEntregaOnRegisterForm(): bool
    {
        return $this->isEnabled() && $this->getScope() === 'selected';
    }

    /** ¿Esta recepción debe participar en el flujo de notificación? */
    public function registroParticipatesInNotifications(object|array|null $registro): bool
    {
        if (! $this->isEnabled() || $registro === null) {
            return false;
        }
        if ($this->getScope() === 'all') {
            return true;
        }

        $flag = is_object($registro)
            ? (int) ($registro->notificar_entrega ?? 0)
            : (int) ($registro['notificar_entrega'] ?? 0);

        return $flag === 1;
    }

    public function saveConfigFromPost(array $post): void
    {
        $appConfig   = model(AppConfigModel::class);
        $wasEnabled  = $this->isEnabled();
        $enabled     = isset($post[self::KEY_ENABLED]) && (string) $post[self::KEY_ENABLED] === '1' ? '1' : '0';
        $scope       = self::normalizeScope((string) ($post[self::KEY_SCOPE] ?? 'all'));

        $appConfig->saveValue(self::KEY_ENABLED, $enabled);
        $appConfig->saveValue(self::KEY_SCOPE, $scope);
        $enabledAtRaw = trim((string) ($this->configService->getAllAsArray()[self::KEY_ENABLED_AT] ?? ''));
        if ($enabled === '1' && (! $wasEnabled || $enabledAtRaw === '')) {
            $appConfig->saveValue(self::KEY_ENABLED_AT, RegisterService::mysqlNowForReport());
        }
        $this->invalidatePendingCountCache();
        $this->configService->invalidateCache();
    }

    /** Fecha/hora desde la cual se generan alertas (no aplica a órdenes ya validadas antes). */
    public function getEnabledAt(): ?string
    {
        if (! $this->isEnabled()) {
            return null;
        }

        $raw = trim((string) ($this->configService->getAllAsArray()[self::KEY_ENABLED_AT] ?? ''));
        if ($raw !== '') {
            return $raw;
        }

        $now      = RegisterService::mysqlNowForReport();
        $fallback = substr($now, 0, 10) . ' 00:00:00';
        model(AppConfigModel::class)->saveValue(self::KEY_ENABLED_AT, $fallback);
        $this->configService->invalidateCache();

        return $fallback;
    }

    public function invalidatePendingCountCache(): void
    {
        \Config\Services::cache()->delete(self::CACHE_COUNT_KEY);
    }

    public function getPendingCount(): int
    {
        if (! $this->isEnabled()) {
            return 0;
        }

        $cache = \Config\Services::cache();
        $cached = $cache->get(self::CACHE_COUNT_KEY);
        if (is_int($cached)) {
            return max(0, $cached);
        }

        $count = $this->resolvePendingCount();
        $cache->save(self::CACHE_COUNT_KEY, $count, self::CACHE_COUNT_TTL);

        return $count;
    }

    private function resolvePendingCount(): int
    {
        return count($this->listPendientesEntrega());
    }

    /**
     * @param list<int> $registroIds
     * @return list<int>
     */
    public function getPendingRegistroIds(array $registroIds): array
    {
        if (! $this->isEnabled() || $registroIds === []) {
            return [];
        }

        $requireCheck = $this->getScope() === 'selected'
            && $this->registerModel->hasRegistroColumn('notificar_entrega');

        return $this->notificationModel->filterRegistroIdsWithPending($registroIds, $requireCheck);
    }

    /**
     * @param list<int> $registroIds
     * @return list<int>
     */
    public function getHighlightRegistroIds(array $registroIds): array
    {
        if (! $this->isEnabled() || $registroIds === []) {
            return [];
        }

        if ($this->getScope() === 'selected') {
            return $this->registerModel->filterRegistroIdsConNotificarEntrega($registroIds);
        }

        $highlight = $this->notificationModel->filterRegistroIdsWithPending($registroIds, false);
        $seen      = array_fill_keys($highlight, true);
        $enabledAt = $this->getEnabledAt();
        if ($enabledAt === null) {
            return $highlight;
        }

        $acknowledged = array_fill_keys($this->filterRegistroIdsDeliveryAcknowledged($registroIds), true);
        $ingresos     = $this->registerModel->getIngresosByRegistroIds($registroIds);
        $cutoff       = $this->enabledCutoffDate();

        foreach ($registroIds as $rid) {
            $rid = (int) $rid;
            if ($rid < 1 || isset($seen[$rid]) || isset($acknowledged[$rid]) || $cutoff === null) {
                continue;
            }
            $ingreso = trim((string) ($ingresos[$rid] ?? ''));
            if ($ingreso !== '' && substr($ingreso, 0, 10) >= $cutoff) {
                $highlight[] = $rid;
                $seen[$rid]  = true;
            }
        }

        return array_values($highlight);
    }

    /** ¿Resaltar fila en /registers/lista con borde naranja parpadeante? */
    public function shouldHighlightRegistroInLista(object|array|null $registro): bool
    {
        if (! $this->isEnabled() || $registro === null) {
            return false;
        }
        $registroId = (int) (is_object($registro) ? ($registro->registro_id ?? 0) : ($registro['registro_id'] ?? 0));
        if ($registroId < 1) {
            return false;
        }

        return in_array($registroId, $this->getHighlightRegistroIds([$registroId]), true);
    }

    /** ¿Mostrar acción «Notificar» en viewreport / lista? */
    public function shouldShowNotifyButtonForRegistro(object|array|null $registro): bool
    {
        return $this->shouldHighlightRegistroInLista($registro);
    }

    /**
     * Confirma que se notificó la entrega al médico/paciente para esta recepción.
     */
    public function confirmRegistroDeliveryNotified(int $registroId, int $personId): bool
    {
        if ($registroId < 1 || ! $this->isEnabled()) {
            return false;
        }
        if ($this->registerModel->isRegistroAnulado($registroId)) {
            return false;
        }

        $registro = $this->registerModel->getInfoRefill($registroId);
        if (! $this->registroParticipatesInNotifications($registro)) {
            return false;
        }

        $marked = $this->notificationModel->markAttendedForRegistro($registroId, $personId);

        if ($this->getScope() === 'selected' && $this->registerModel->hasRegistroColumn('notificar_entrega')) {
            $this->registerModel->saveRegistro(['notificar_entrega' => 0], $registroId);
            $this->invalidatePendingCountCache();

            return true;
        }

        if ($marked !== []) {
            $this->invalidatePendingCountCache();

            return true;
        }

        if ($this->getScope() === 'all' && $this->isRegistroAwaitingDeliveryAcknowledgement($registro)) {
            $this->invalidatePendingCountCache();

            return true;
        }

        return false;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listPendingForRegistro(int $registroId): array
    {
        if (! $this->isEnabled() || $registroId < 1) {
            return [];
        }

        return $this->notificationModel->listPendingForRegistro($registroId);
    }

    /**
     * Listado unificado para /registers/deliveryNotifications y contadores.
     *
     * @return list<array<string, mixed>>
     */
    public function listPendientesEntrega(): array
    {
        if (! $this->isEnabled()) {
            return [];
        }

        if ($this->getScope() === 'selected') {
            return $this->registerModel->listRegistrosConNotificarEntrega();
        }

        $rows         = $this->notificationModel->listPending();
        $seenRegistro = [];
        foreach ($rows as $row) {
            $rid = (int) ($row['registro_id'] ?? 0);
            if ($rid > 0) {
                $seenRegistro[$rid] = true;
            }
        }

        $enabledAt = $this->getEnabledAt();
        if ($enabledAt === null) {
            return $rows;
        }

        foreach ($this->registerModel->listIngresosDesdeWithoutDeliveryAck($enabledAt, array_keys($seenRegistro)) as $reg) {
            $rows[] = [
                'registro_id'          => (int) ($reg['registro_id'] ?? 0),
                'numero_orden'         => $reg['numero_orden'] ?? '',
                'ingreso'              => $reg['ingreso'] ?? '',
                'first_name'           => $reg['first_name'] ?? '',
                'last_name_fa'         => $reg['last_name_fa'] ?? '',
                'paciente'             => $reg['paciente'] ?? '',
                'analisis_nombre'      => 'Recepción pendiente de notificar',
                'validated_at'         => null,
                'prianacategoria_id'   => 0,
                'es_recepcion_pendiente' => 1,
            ];
        }

        return $rows;
    }

    /**
     * Evalúa y crea notificaciones pendientes tras guardar/validar resultados.
     */
    public function syncForRegistro(int $registroId): void
    {
        if ($registroId < 1 || ! $this->isEnabled()) {
            return;
        }
        if ($this->registerModel->isRegistroAnulado($registroId)) {
            return;
        }

        $registro = $this->registerModel->getInfoRefill($registroId);
        if (! $this->registroParticipatesInNotifications($registro)) {
            return;
        }

        $pruebaIds = $this->extractPrianacategoriaIds((string) ($registro->pruebas ?? ''));
        if ($pruebaIds === []) {
            return;
        }

        $analisisRows = $this->registerModel->getInfoAnalisis($registroId);
        $byName = [];
        foreach ($analisisRows as $row) {
            $key = trim((string) ($row['name'] ?? ''));
            if ($key !== '') {
                $byName[$key] = trim((string) ($row['regvalues'] ?? ''));
            }
        }

        $labMode = $this->configService->getLabValidationMode();
        $hasResulaValidation = $this->hasResulanalisisValidation($registroId);
        $enabledAt = $this->getEnabledAt();
        $created = false;

        foreach ($pruebaIds as $priaId) {
            if (! $this->isPrianacategoriaFinalized($registroId, $priaId, $byName, $labMode, $hasResulaValidation)) {
                continue;
            }

            $validatedAt = $this->resolveValidatedAtForPrianacategoria($registroId, $priaId);
            $syncNow     = RegisterService::mysqlNowForReport();
            if ($enabledAt !== null) {
                if ($validatedAt !== null && $validatedAt < $enabledAt) {
                    continue;
                }
            }
            if ($validatedAt === null) {
                $validatedAt = $syncNow;
            }

            if ($this->notificationModel->createPending($registroId, $priaId, $validatedAt)) {
                $created = true;
            }
        }

        if ($created) {
            $this->invalidatePendingCountCache();
        }
    }

    /**
     * @return list<int>
     */
    public function markDelivered(int $registroId, int $personId, ?int $prianacategoriaId = null): array
    {
        $ids = $this->notificationModel->markAttendedForRegistro($registroId, $personId, $prianacategoriaId);
        if ($ids !== []) {
            $this->invalidatePendingCountCache();
        }

        return $ids;
    }

    /**
     * @param array<string, string> $byName
     */
    private function isPrianacategoriaFinalized(
        int $registroId,
        int $priaId,
        array $byName,
        string $labMode,
        bool $hasResulaValidation
    ): bool {
        if (! $this->hasNonEmptyResultsForPrianacategoria($priaId, $byName)) {
            return false;
        }

        if ($hasResulaValidation) {
            return true;
        }

        if ($labMode === 'none') {
            return true;
        }

        if ($this->hasSignaturesForPrianacategoria($priaId, $byName, $labMode)) {
            return true;
        }

        return $this->hasMuestraValidada($registroId);
    }

    /**
     * @param array<string, string> $byName
     */
    private function hasNonEmptyResultsForPrianacategoria(int $priaId, array $byName): bool
    {
        $nocCache = [];
        $cCache   = [];
        foreach ($byName as $key => $value) {
            if (! $this->registerModel->isResultadoPruebaRegvalueKey($key)) {
                continue;
            }
            if (trim($value) === '') {
                continue;
            }
            if ($this->registerModel->resolvePrianacategoriaIdFromRegvalueName($key, $nocCache, $cCache) === $priaId) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string, string> $byName
     */
    private function hasSignaturesForPrianacategoria(int $priaId, array $byName, string $labMode): bool
    {
        if ($labMode === 'analisis') {
            $val = trim((string) ($byName['lab_val_pri_' . $priaId] ?? ''));
            $app = trim((string) ($byName['lab_app_pri_' . $priaId] ?? ''));
            if ($val !== '' || $app !== '') {
                return true;
            }
            foreach ($byName as $k => $v) {
                if (preg_match('/^lab_app_(grp_[a-f0-9]{16}|pri_\d+)$/', $k) === 1 && trim((string) $v) !== '') {
                    return true;
                }
            }

            return false;
        }

        if ($labMode === 'area') {
            foreach ($byName as $k => $v) {
                if (preg_match('/^lab_app_grp_[a-f0-9]{16}$/', $k) === 1 && trim((string) $v) !== '') {
                    return true;
                }
                if (preg_match('/^lab_val_grp_[a-f0-9]{16}$/', $k) === 1 && trim((string) $v) !== '') {
                    return true;
                }
            }
        }

        return false;
    }

    private function hasResulanalisisValidation(int $registroId): bool
    {
        foreach ($this->registerModel->getResulanalisisByRegistro($registroId) as $row) {
            if ((int) ($row['validado_tecnico'] ?? 0) === 1 || (int) ($row['validado_medico'] ?? 0) === 1) {
                return true;
            }
            if ((int) ($row['estado_id'] ?? 0) === 1 && trim((string) ($row['valor'] ?? '')) !== '') {
                return true;
            }
        }

        return false;
    }

    private function hasMuestraValidada(int $registroId): bool
    {
        try {
            $row = model(\App\Models\MuestraModel::class)->getByRegistro($registroId);
            if (! is_array($row) || $row === []) {
                return false;
            }
            $estado = (int) ($row['estado'] ?? 0);

            return $estado >= 3;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function resolveValidatedAt(int $registroId): string
    {
        foreach ($this->registerModel->getResulanalisisByRegistro($registroId) as $row) {
            $fv = trim((string) ($row['fecha_validacion'] ?? ''));
            if ($fv !== '') {
                return $fv;
            }
        }

        $fijada = $this->registerModel->getReporteFechaHoraFijadaMysql($registroId);
        if ($fijada !== null && $fijada !== '') {
            return $fijada;
        }

        return RegisterService::mysqlNowForReport();
    }

    /** Fecha de validación de una prueba; null si no hay referencia histórica fiable. */
    private function resolveValidatedAtForPrianacategoria(int $registroId, int $priaId): ?string
    {
        foreach ($this->registerModel->getResulanalisisByRegistro($registroId) as $row) {
            if ((int) ($row['prianacategoria_id'] ?? 0) !== $priaId) {
                continue;
            }
            $fv = trim((string) ($row['fecha_validacion'] ?? ''));
            if ($fv !== '') {
                return $fv;
            }
        }

        return $this->registerModel->getReporteFechaHoraFijadaMysql($registroId);
    }

    private function enabledCutoffDate(): ?string
    {
        $enabledAt = $this->getEnabledAt();

        return $enabledAt !== null ? substr($enabledAt, 0, 10) : null;
    }

    private function registroIngresoOnOrAfterEnabledDate(object|array $registro): bool
    {
        $cutoff = $this->enabledCutoffDate();
        if ($cutoff === null) {
            return false;
        }
        $ingreso = trim((string) (is_object($registro) ? ($registro->ingreso ?? '') : ($registro['ingreso'] ?? '')));
        if ($ingreso === '') {
            return false;
        }

        return substr($ingreso, 0, 10) >= $cutoff;
    }

    private function isRegistroAwaitingDeliveryAcknowledgement(object|array $registro): bool
    {
        $anulado = (int) (is_object($registro) ? ($registro->anulado ?? 0) : ($registro['anulado'] ?? 0));
        if ($anulado === 1) {
            return false;
        }
        $registroId = (int) (is_object($registro) ? ($registro->registro_id ?? 0) : ($registro['registro_id'] ?? 0));
        if ($registroId < 1) {
            return false;
        }
        if ($this->notificationModel->hasPendingForRegistro($registroId)) {
            return true;
        }
        if ($this->hasRegistroDeliveryAcknowledged($registroId)) {
            return false;
        }

        return $this->registroIngresoOnOrAfterEnabledDate($registro);
    }

    public function hasRegistroDeliveryAcknowledged(int $registroId): bool
    {
        if ($registroId < 1) {
            return false;
        }
        if ($this->notificationModel->hasAttendedForRegistro($registroId)) {
            return true;
        }

        $db = \Config\Database::connect();
        $a  = $db->prefixTable('auditoria');

        return $db->table('auditoria')
            ->where("{$a}.modulo", 'registers')
            ->where("{$a}.accion", 'notificar_entrega_analisis')
            ->where("{$a}.registro_id", (string) $registroId)
            ->countAllResults() > 0;
    }

    /**
     * @param list<int> $registroIds
     * @return list<int>
     */
    private function filterRegistroIdsDeliveryAcknowledged(array $registroIds): array
    {
        $ids = array_values(array_unique(array_filter(array_map(
            static fn ($id): int => (int) $id,
            $registroIds
        ), static fn (int $id): bool => $id > 0)));

        if ($ids === []) {
            return [];
        }

        $out = [];
        foreach ($this->notificationModel->filterRegistroIdsWithAttended($ids) as $rid) {
            $out[(int) $rid] = true;
        }

        $db = \Config\Database::connect();
        $a  = $db->prefixTable('auditoria');
        foreach ($db->table('auditoria')
            ->select("{$a}.registro_id")
            ->distinct()
            ->where("{$a}.modulo", 'registers')
            ->where("{$a}.accion", 'notificar_entrega_analisis')
            ->whereIn("{$a}.registro_id", array_map('strval', $ids))
            ->get()
            ->getResultArray() as $row) {
            $rid = (int) ($row['registro_id'] ?? 0);
            if ($rid > 0) {
                $out[$rid] = true;
            }
        }

        return array_map('intval', array_keys($out));
    }

    /**
     * @return list<int>
     */
    private function extractPrianacategoriaIds(string $csv): array
    {
        $out = [];
        foreach (explode(',', $csv) as $raw) {
            $id = (int) trim($raw);
            if ($id > 0) {
                $out[$id] = $id;
            }
        }

        return array_values($out);
    }
}
