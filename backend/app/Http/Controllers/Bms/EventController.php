<?php

namespace App\Http\Controllers\Bms;

use App\Models\Schedule;

class EventController
{
    private Schedule $model;

    public function __construct()
    {
        $this->model = new Schedule();
    }

    public function index(): void
    {
        header('Content-Type: application/json');
        echo json_encode($this->model->findByProgram('bms'));
    }
}