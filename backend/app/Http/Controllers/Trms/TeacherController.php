<?php

namespace App\Http\Controllers\Trms;

use App\Models\Teacher;

class TeacherController
{
    private Teacher $model;

    public function __construct()
    {
        $this->model = new Teacher();
    }

    public function index(): void
    {
        header('Content-Type: application/json');
        echo json_encode($this->model->where('program_id', 'trms'));
    }
}