<?php

namespace App\Http\Controllers\Trms;

use App\Core\Database;

class ScheduleController
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function index(): void
    {
        $stmt = $this->db->query("SELECT * FROM courses WHERE program_id = 'trms' ORDER BY name");
        $schedules = $stmt->fetchAll();
        
        header('Content-Type: application/json');
        echo json_encode($schedules);
    }
}