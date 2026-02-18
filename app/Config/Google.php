<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Google extends BaseConfig
{
    public string $clientId     = '';
    public string $clientSecret = '';
    public string $redirectUri  = '';

    public function __construct()
    {
        parent::__construct();
        $this->clientId     = env('googleClientId', '');
        $this->clientSecret = env('googleClientSecret', '');
        $this->redirectUri  = rtrim(config('App')->baseURL, '/') . '/login/google/callback';
    }
}
