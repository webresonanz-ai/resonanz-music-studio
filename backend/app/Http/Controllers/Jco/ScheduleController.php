<?php

namespace App\Http\Controllers\Jco;

use App\Models\Schedule;

class ScheduleController
{
    private Schedule $model;

    public function __construct()
    {
        $this->model = new Schedule();
    }

    public function index(): void
    {
        header('Content-Type: application/json');
        echo json_encode($this->model->findByProgram('jco'));
    }
}
