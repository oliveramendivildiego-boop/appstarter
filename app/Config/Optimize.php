<?php

namespace Config;

/**
 * Optimization Configuration.
 *
 * NOTE: This class does not extend BaseConfig for performance reasons.
 *       So you cannot replace the property values with Environment Variables.
 *
 * WARNING: Do not use these options when running the app in the Worker Mode.
 */
class Optimize
{
    /**
     * --------------------------------------------------------------------------
     * Config Caching
     * --------------------------------------------------------------------------
     *
     * IMPORTANTE: debe estar deshabilitado en esta app multi-tenant.
     *
     * Cuando está habilitado, CodeIgniter serializa las instancias de Config
     * (incluyendo Config\Database y Config\Session) en
     * writable/cache/FactoriesCache_config y las restaura en cada request sin
     * volver a ejecutar el constructor. Eso bypassa
     * Config\Database::applyTenantDatabaseConfig(), que es donde se selecciona
     * la BD del tenant según subdominio/header/query/sesión. Resultado: todos
     * los tenants quedan apuntando a la BD del .env (default).
     *
     * @see https://codeigniter.com/user_guide/concepts/factories.html#config-caching
     */
    public bool $configCacheEnabled = false;

    /**
     * --------------------------------------------------------------------------
     * File Locator Caching
     * --------------------------------------------------------------------------
     *
     * Deshabilitado: tras deploy puede servir rutas de clases obsoletas (p. ej. Config\Kint).
     *
     * @see https://codeigniter.com/user_guide/concepts/autoloader.html#file-locator-caching
     */
    public bool $locatorCacheEnabled = false;
}
