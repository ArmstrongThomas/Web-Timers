<?php
session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the JSON data
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    // Validate timezone string
    if (isset($data['timezone']) && is_string($data['timezone'])) {
        // Validate against list of valid timezone identifiers
        $valid_timezones = DateTimeZone::listIdentifiers();
        
        if (in_array($data['timezone'], $valid_timezones, true)) {
            $_SESSION['user_timezone'] = $data['timezone'];
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid timezone']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Missing or invalid timezone parameter']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
}
