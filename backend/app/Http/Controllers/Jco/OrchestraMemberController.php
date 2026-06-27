<?php

namespace App\Http\Controllers\Jco;

use App\Models\Member;

class OrchestraMemberController
{
    private Member $model;

    public function __construct()
    {
        $this->model = new Member();
    }

    public function index(): void
    {
        header('Content-Type: application/json');
        echo json_encode($this->model->findByProgram('jco'));
    }
}