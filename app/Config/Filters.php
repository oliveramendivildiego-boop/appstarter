<?php

namespace Config;

use CodeIgniter\Config\Filters as BaseFilters;
use CodeIgniter\Filters\Cors;
use CodeIgniter\Filters\CSRF;
use CodeIgniter\Filters\DebugToolbar;
use CodeIgniter\Filters\ForceHTTPS;
use CodeIgniter\Filters\Honeypot;
use CodeIgniter\Filters\InvalidChars;
use CodeIgniter\Filters\PageCache;
use CodeIgniter\Filters\PerformanceMetrics;
use CodeIgniter\Filters\SecureHeaders;

class Filters extends BaseFilters
{
    /**
     * Configures aliases for Filter classes to
     * make reading things nicer and simpler.
     *
     * @var array<string, class-string|list<class-string>>
     *
     * [filter_name => classname]
     * or [filter_name => [classname1, classname2, ...]]
     */
    public array $aliases = [
        'kintformfix'   => \App\Filters\KintFormFixFilter::class,
        'tenant'        => \App\Filters\TenantFilter::class,
        'auth'          => \App\Filters\AuthFilter::class,
        'permission'    => \App\Filters\PermissionFilter::class,
        'csrf'          => CSRF::class,
        'toolbar'       => DebugToolbar::class,
        'honeypot'      => Honeypot::class,
        'invalidchars'  => InvalidChars::class,
        'secureheaders' => SecureHeaders::class,
        'cors'          => Cors::class,
        'forcehttps'    => ForceHTTPS::class,
        'pagecache'     => PageCache::class,
        'performance'   => PerformanceMetrics::class,
        'tenant_backup_web_tick' => \App\Filters\TenantBackupWebTickFilter::class,
    ];

    /**
     * List of special required filters.
     *
     * The filters listed here are special. They are applied before and after
     * other kinds of filters, and always applied even if a route does not exist.
     *
     * Filters set by default provide framework functionality. If removed,
     * those functions will no longer work.
     *
     * @see https://codeigniter.com/user_guide/incoming/filters.html#provided-filters
     *
     * @var array{before: list<string>, after: list<string>}
     */
    public array $required = [
        'before' => [
            'forcehttps', // Force Global Secure Requests
            'pagecache',  // Web Page Caching
        ],
        'after' => [
            'pagecache',   // Web Page Caching
            'performance', // Performance Metrics
            // 'toolbar',  // Debug Toolbar — deshabilitado: evita 500 (intl/fórmulas)
            'kintformfix', // Corrige inputs kint-search sin id/name (accesibilidad)
        ],
    ];

    /**
     * List of filter aliases that are always
     * applied before and after every request.
     *
     * @var array{
     *     before: array<string, array{except: list<string>|string}>|list<string>,
     *     after: array<string, array{except: list<string>|string}>|list<string>
     * }
     */
    public array $globals = [
        'before' => [
            'tenant',
            'csrf' => ['except' => ['login', 'login/*', 'login/google_login', 'qr/*', 'customers/search', 'customers/suggest', 'employees/search', 'employees/suggest', 'doctors/search', 'doctors/suggest', 'doctor/search', 'registers/search_paciente', 'registers/search_doctor', 'registers/search_prueba', 'toquotes/exportPdf', 'toquotes/exportPdfById/*']],
        ],
        'after' => [
            'tenant_backup_web_tick',
            // 'honeypot',
            // 'secureheaders',
        ],
    ];

    /**
     * List of filter aliases that works on a
     * particular HTTP method (GET, POST, etc.).
     *
     * Example:
     * 'POST' => ['foo', 'bar']
     *
     * If you use this, you should disable auto-routing because auto-routing
     * permits any HTTP method to access a controller. Accessing the controller
     * with a method you don't expect could bypass the filter.
     *
     * @var array<string, list<string>>
     */
    public array $methods = [];

    /**
     * List of filter aliases that should run on any
     * before or after URI patterns.
     *
     * Example:
     * 'isLoggedIn' => ['before' => ['account/*', 'profiles/*']]
     *
     * @var array<string, array<string, list<string>>>
     */
    public array $filters = [
        'auth' => [
            'before' => ['home*', 'subscription-blocked', 'account*', 'no_access*', 'config*', 'customers*', 'doctors*', 'employees*', 'registers*', 'labotests*', 'toquotes*', 'expediente*', 'reports*', 'controlcalidad*', 'reactivos*', 'inventario*', 'equipos*', 'auditoria*', 'item_kits', 'items*', 'sales*', 'suppliers*', 'receivings*', 'giftcards*', 'seguridad*', 'doctor*'],
            'except' => ['registers/servePdfFile/*', 'resultados', 'resultados/*', 'cron/*'],
        ],
    ];
}
