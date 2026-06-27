<?php

namespace App\Http\Controllers\Bms;

class AboutController
{
    public function index(): void
    {
        header('Content-Type: application/json');
        echo json_encode([
            'program' => 'BMS',
            'title' => 'Batavia Madrigal Singers',
            'description' => 'Join our vibrant band community and experience the joy of ensemble performance.'
        ]);
    }
}