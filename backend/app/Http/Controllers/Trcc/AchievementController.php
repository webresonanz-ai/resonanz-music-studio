<?php

namespace App\Http\Controllers\Trcc;

use App\Models\Achievement;

class AchievementController
{
    private Achievement $model;

    public function __construct()
    {
        $this->model = new Achievement();
    }

    public function index(): void
    {
        header('Content-Type: application/json');
        echo json_encode($this->model->findByProgram('trcc'));
    }
}