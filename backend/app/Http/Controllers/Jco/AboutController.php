<?php

namespace App\Http\Controllers\Jco;

class AboutController
{
    public function index(): void
    {
        header('Content-Type: application/json');
        echo json_encode([
            'program' => 'JCO',
            'title' => 'Jakarta Concert Orchestra',
            'description' => 'Experience the magic of classical music with our world-class orchestra.'
        ]);
    }
}