<?php

namespace App\Controllers;

/**
 * Placeholder para módulo item_kits. Implementar cuando se requiera.
 */
class ItemKits extends SecureArea
{
    protected ?string $moduleId = 'item_kits';

    public function index()
    {
        return view('module_placeholder', [
            'module_id'       => 'item_kits',
            'allowed_modules' => $this->allowed_modules,
            'user_info'       => $this->user_info,
        ]);
    }
}
