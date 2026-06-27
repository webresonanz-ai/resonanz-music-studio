<?php

namespace App\Http\Controllers\Jco;

use App\Models\Concert;

class ConcertController
{
    private Concert $model;

    public function __construct()
    {
        $this->model = new Concert();
    }

    public function index(): void
    {
        header('Content-Type: application/json');
        echo json_encode($this->model->findAll());
    }
}