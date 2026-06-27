<?php

namespace App\Http\Controllers\Trms;

use App\Models\Course;

class CourseController
{
    private Course $model;

    public function __construct()
    {
        $this->model = new Course();
    }

    public function index(): void
    {
        header('Content-Type: application/json');
        echo json_encode($this->model->where('program_id', 'trms'));
    }
}