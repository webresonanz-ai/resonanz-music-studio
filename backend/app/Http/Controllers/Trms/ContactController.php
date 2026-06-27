<?php

namespace App\Http\Controllers\Trms;

use App\Core\Database;

class ContactController
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function store(): void
    {
        $data = $_POST;
        
        $sql = "INSERT INTO contact_messages (program_id, name, email, subject, message) VALUES (:program_id, :name, :email, :subject, :message)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'program_id' => 'trms',
            'name' => $data['name'] ?? '',
            'email' => $data['email'] ?? '',
            'subject' => $data['subject'] ?? '',
            'message' => $data['message'] ?? ''
        ]);
        
        http_response_code(201);
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Message sent successfully']);
    }
}