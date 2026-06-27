<?php

namespace App\Http\Middleware;

class RequestMiddleware
{
    private array $rules = [];

    public function __construct(array $rules = [])
    {
        $this->rules = $rules;
    }

    public function handle(): void
    {
        $data = $_POST;
        
        foreach ($this->rules as $field => $rules) {
            $ruleList = explode('|', $rules);
            
            foreach ($ruleList as $rule) {
                $this->validateRule($field, $rule, $data);
            }
        }
    }

    private function validateRule(string $field, string $rule, array &$data): void
    {
        $value = $data[$field] ?? null;
        
        if (strpos($rule, 'required') !== false && empty($value)) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['error' => "The {$field} field is required"]);
            exit;
        }
        
        if (strpos($rule, 'email') !== false && !empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['error' => "The {$field} must be a valid email"]);
            exit;
        }
    }
}