<?php

namespace App\Http\Controllers\Trcc;

use App\Models\Testimonial;

class TestimonialController
{
    private Testimonial $model;

    public function __construct()
    {
        $this->model = new Testimonial();
    }

    public function index(): void
    {
        header('Content-Type: application/json');
        echo json_encode($this->model->findByProgram('trcc'));
    }
}