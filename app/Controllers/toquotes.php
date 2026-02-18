<?php

namespace App\Controllers;

use App\Models\ToquoteModel;
use CodeIgniter\HTTP\ResponseInterface;

class Toquotes extends SecureArea
{
    protected ?string $moduleId = 'toquotes';

    protected ToquoteModel $toquoteModel;

    public function __construct()
    {
        parent::__construct();
        $this->toquoteModel = model(ToquoteModel::class);
    }

    public function index()
    {
        $categories = $this->toquoteModel->getGroupedForQuote();

        return view('toquotes/manage', [
            'categories'      => $categories,
            'allowed_modules' => $this->allowed_modules,
            'user_info'       => $this->user_info,
        ]);
    }

    public function savetoquotelog(): ResponseInterface
    {
        $cotizo = $this->request->getPost('cotizo') ?? '';
        $costo  = (int) ($this->request->getPost('costo') ?? 0);

        if ($this->toquoteModel->saveLog($cotizo, $costo)) {
            return $this->response->setJSON(['success' => true, 'message' => lang('Toquotes.toquotes_saved')]);
        }
        return $this->response->setJSON(['success' => false, 'message' => lang('Toquotes.toquotes_error')]);
    }
}
