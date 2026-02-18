<?php

namespace App\Controllers;

use App\Models\PersonModel;

abstract class PersonController extends SecureArea
{
    protected \App\Models\PersonModel $personModel;

    public function __construct()
    {
        parent::__construct();
        helper('config');
        $this->personModel = model(PersonModel::class);
    }

    abstract public function getFormWidth(): int;

    public function getControllerName(): string
    {
        $class = get_class($this);
        $parts = explode('\\', $class);
        return strtolower(end($parts));
    }

    public function mailto()
    {
        $ids = $this->request->getPost('ids');
        if ($ids) {
            $people = $this->personModel->getMultipleInfo((array) $ids);
            $emails = [];
            foreach ($people as $person) {
                if (!empty($person->email)) {
                    $emails[] = $person->email;
                }
            }
            return $this->response->setBody(implode(',', $emails));
        }
        return $this->response->setBody('#');
    }

    public function suggest()
    {
        // Implementado en cada controlador hijo (Customers, etc.)
        return $this->response->setBody('');
    }

    public function getRow()
    {
        helper('table');
        $personId = $this->request->getPost('row_id');
        $person = $this->personModel->getInfo((int) $personId);
        $row = get_person_data_row($person, $this);
        return $this->response->setBody($row);
    }
}
