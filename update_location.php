<?php
// update_location.php
// Place this file in your ROOT directory (same level as db.php)

header('Content-Type: application/json');
require_once 'db.php';

// Enable CORS if needed
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $student_id = intval($data['student_id'] ?? 0);
    $latitude = floatval($data['latitude'] ?? 0);
    $longitude = floatval($data['longitude'] ?? 0);
    $accuracy = floatval($data['accuracy'] ?? 0);
    $address = isset($data['address']) ? $conn->real_escape_string($data['address']) : '';
    $battery_level = intval($data['battery_level'] ?? 0);
    
    if ($student_id && $latitude && $longitude) {
        try {
            // Check if student location exists
            $check_stmt = $conn->prepare("SELECT id FROM student_locations WHERE student_id = ?");
            $check_stmt->bind_param("i", $student_id);
            $check_stmt->execute();
            $result = $check_stmt->get_result();
            
            if ($result->num_rows > 0) {
                // Update existing location
                $stmt = $conn->prepare("
                    UPDATE student_locations 
                    SET latitude = ?, 
                        longitude = ?, 
                        accuracy = ?, 
                        address = ?, 
                        battery_level = ?, 
                        is_online = TRUE, 
                        timestamp = NOW(),
                        updated_at = NOW()
                    WHERE student_id = ?
                ");
                $stmt->bind_param("dddsii", $latitude, $longitude, $accuracy, $address, $battery_level, $student_id);
            } else {
                // Insert new location
                $stmt = $conn->prepare("
                    INSERT INTO student_locations 
                    (student_id, latitude, longitude, accuracy, address, battery_level, is_online, timestamp)
                    VALUES (?, ?, ?, ?, ?, ?, TRUE, NOW())
                ");
                $stmt->bind_param("idddsi", $student_id, $latitude, $longitude, $accuracy, $address, $battery_level);
            }
            
            $stmt->execute();
            
            // Store in history
            $history_stmt = $conn->prepare("
                INSERT INTO location_history 
                (student_id, latitude, longitude, accuracy, address, battery_level, timestamp)
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $history_stmt->bind_param("idddsi", $student_id, $latitude, $longitude, $accuracy, $address, $battery_level);
            $history_stmt->execute();
            
            echo json_encode([
                'success' => true,
                'message' => 'Location updated successfully',
                'data' => [
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'timestamp' => date('Y-m-d H:i:s')
                ]
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error updating location: ' . $e->getMessage()
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid data provided. Required: student_id, latitude, longitude'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method. Use POST'
    ]);
}
?>