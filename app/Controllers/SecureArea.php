<?php

namespace App\Controllers;

use App\Models\EmployeeModel;
use App\Services\GhostTenantAccessService;
use App\Services\TenantSubscriptionService;
use CodeIgniter\HTTP\Exceptions\RedirectException;

abstract class SecureArea extends BaseController
{
    protected ?string $moduleId = null;
    protected array $allowed_modules = [];
    /** @var object|null */
    protected $user_info = null;

    public function __construct()
    {
        helper(['url', 'form']);
    }

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $employeeModel = model(EmployeeModel::class);
        if (!$employeeModel->isLoggedIn()) {
            throw new RedirectException(redirect()->to(site_url('login')));
        }
        if (session()->has('doctor_id')) {
            throw new RedirectException(redirect()->to(site_url('doctor/home')));
        }

        $personId = (int) session()->get('person_id');
        $this->user_info = $this->getCachedUserInfo($employeeModel, $personId);
        $ghostSupport = GhostTenantAccessService::isGhostSupportSession();
        if (! $ghostSupport && ! $employeeModel->hasPermission($this->moduleId, $personId)) {
            throw new RedirectException(redirect()->to(site_url('no_access/' . $this->moduleId)));
        }

        $subSvc = new TenantSubscriptionService();
        if (! $ghostSupport && $subSvc->isChildTenantSubscriptionExpired()) {
            helper('url');
            $uri = trim((string) uri_string(), '/');
            $allowed = [
                'subscription-blocked',
                'home/logout',
                'tenant-subscription',
                'status/checkEmployeeActive',
            ];
            $ok = false;
            foreach ($allowed as $prefix) {
                if ($uri === $prefix || str_starts_with($uri, $prefix . '/')) {
                    $ok = true;
                    break;
                }
            }
            if (! $ok) {
                throw new RedirectException(redirect()->to(site_url('subscription-blocked')));
            }
        }
        $this->allowed_modules = $this->getCachedAllowedModules($employeeModel, $personId);
    }

    private function getCachedUserInfo(EmployeeModel $employeeModel, int $personId)
    {
        $cacheKey = 'user_info_' . $personId;
        if (session()->has($cacheKey)) {
            return session()->get($cacheKey);
        }
        $info = $employeeModel->getInfo($personId);
        session()->set($cacheKey, $info);
        return $info;
    }

    private function getCachedAllowedModules(EmployeeModel $employeeModel, int $personId): array
    {
        $cacheKey = 'allowed_modules_' . $personId;
        if (session()->has($cacheKey)) {
            return session()->get($cacheKey);
        }
        $modules = $employeeModel->getAllowedModules($personId);
        session()->set($cacheKey, $modules);
        return $modules;
    }
}
