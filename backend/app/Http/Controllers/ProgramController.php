<?php

namespace App\Http\Controllers;

use App\Core\Database;

class ProgramController
{
    public function index(): void
    {
        $db = Database::getInstance();
        $stmt = $db->query("SELECT * FROM programs");
        $programs = $stmt->fetchAll();
        
        header('Content-Type: application/json');
        echo json_encode($programs);
    }
}