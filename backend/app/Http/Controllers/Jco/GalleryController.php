<?php

namespace App\Http\Controllers\Jco;

use App\Models\Gallery;

class GalleryController
{
    private Gallery $model;

    public function __construct()
    {
        $this->model = new Gallery();
    }

    public function index(): void
    {
        header('Content-Type: application/json');
        echo json_encode($this->model->findByProgram('jco'));
    }
}