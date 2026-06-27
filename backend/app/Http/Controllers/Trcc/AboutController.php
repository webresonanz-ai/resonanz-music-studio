<?php

namespace App\Http\Controllers\Trcc;

class AboutController
{
    public function index(): void
    {
        header('Content-Type: application/json');
        echo json_encode([
            'program' => 'TRCC',
            'title' => 'The Resonanz Children Choir',
            'description' => 'Nurturing young voices and building confidence through choral excellence.'
        ]);
    }
}