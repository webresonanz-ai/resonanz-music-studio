<?php

namespace App\Http\Controllers;

use App\Core\Database;

class HomeController
{
    public function index(): void
    {
        header('Content-Type: application/json');
        echo json_encode([
            'message' => 'Welcome to Resonanz Music Studio API',
            'version' => '1.0.0',
            'programs' => ['trms', 'bms', 'jco', 'trcc']
        ]);
    }
}