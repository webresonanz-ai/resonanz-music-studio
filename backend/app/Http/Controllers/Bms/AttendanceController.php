<?php

namespace App\Http\Controllers\Bms;

use App\Core\Database;

class AttendanceController
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function index(): void
    {
        $sql = "SELECT a.*, e.title as event_name, m.name as member_name 
                FROM attendance a 
                JOIN events e ON a.event_id = e.id 
                JOIN members m ON a.member_id = m.id";
        $stmt = $this->db->query($sql);
        $attendance = $stmt->fetchAll();
        
        header('Content-Type: application/json');
        echo json_encode($attendance);
    }

    public function store(): void
    {
        $data = $_POST;
        
        $sql = "INSERT INTO attendance (event_id, member_id, status) VALUES (:event_id, :member_id, :status)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'event_id' => $data['event_id'] ?? 0,
            'member_id' => $data['member_id'] ?? 0,
            'status' => $data['status'] ?? 'present'
        ]);
        
        http_response_code(201);
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Attendance recorded']);
    }
}