<?php

namespace App\Http\Controllers\Trms;

use App\Models\News;

class NewsController
{
    private News $model;

    public function __construct()
    {
        $this->model = new News();
    }

    public function index(): void
    {
        header('Content-Type: application/json');
        echo json_encode($this->model->findByProgram('trms'));
    }
}