<?php

namespace App\Http\Controllers\Bms;

use App\Models\Event;

class EventController
{
    private Event $model;

    public function __construct()
    {
        $this->model = new Event();
    }

    public function index(): void
    {
        header('Content-Type: application/json');
        echo json_encode($this->model->findByProgram('bms'));
    }
}