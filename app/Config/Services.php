<?php

namespace Config;

use CodeIgniter\Config\BaseService;
use Config\Toolbar as ToolbarConfig;

/**
 * Services Configuration file.
 *
 * Services are simply other classes/libraries that the system uses
 * to do its job. This is used by CodeIgniter to allow the core of the
 * framework to be swapped out easily without affecting the usage within
 * the rest of your application.
 *
 * This file holds any application-specific services, or service overrides
 * that you might need. An example has been included with the general
 * method format you should use for your service methods. For more examples,
 * see the core Services file at system/Config/Services.php.
 */
class Services extends BaseService
{
    /**
     * Toolbar con corrección de inputs kint-search (id/name para accesibilidad).
     */
    public static function toolbar(?ToolbarConfig $config = null, bool $getShared = true)
    {
        if ($getShared) {
            return static::getSharedInstance('toolbar', $config);
        }

        $config ??= config(ToolbarConfig::class);

        return new \App\Debug\Toolbar($config);
    }
}
