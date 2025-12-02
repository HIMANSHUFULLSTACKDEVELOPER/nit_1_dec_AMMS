<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors in output
ini_set('log_errors', 1);

// Start output buffering to prevent any accidental output
ob_start();

require_once 'db.php';

// Set JSON header
header('Content-Type: application/json');

// Initialize response
$response = ['success' => false, 'message' => ''];

try {
    // Validate session
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
        throw new Exception('Not authenticated');
    }

    $user_id = (int)$_SESSION['user_id'];
    $user_role = $_SESSION['role'];
    
    // Get action from POST or GET
    $action = isset($_POST['action']) ? $_POST['action'] : (isset($_GET['action']) ? $_GET['action'] : '');
    
    if (empty($action)) {
        throw new Exception('No action specified');
    }

    switch ($action) {
        case 'get_contacts':
            $contacts = [];
            
            if ($user_role === 'teacher') {
                // Get all students
                $query = "SELECT DISTINCT 
                    s.id,
                    s.full_name as name,
                    s.roll_number,
                    s.email,
                    c.section,
                    c.class_name,
                    d.dept_name,
                    'student' as type,
                    (SELECT COUNT(*) 
                     FROM chat_messages cm 
                     WHERE cm.sender_id = s.id 
                     AND cm.sender_type = 'student'
                     AND cm.receiver_id = ? 
                     AND cm.receiver_type = 'teacher'
                     AND cm.is_read = 0) as unread_count,
                    (SELECT message 
                     FROM chat_messages cm2 
                     WHERE (cm2.sender_id = s.id AND cm2.sender_type = 'student' AND cm2.receiver_id = ? AND cm2.receiver_type = 'teacher')
                     OR (cm2.sender_id = ? AND cm2.sender_type = 'teacher' AND cm2.receiver_id = s.id AND cm2.receiver_type = 'student')
                     ORDER BY cm2.created_at DESC 
                     LIMIT 1) as last_message
                FROM students s
                LEFT JOIN classes c ON s.class_id = c.id
                LEFT JOIN departments d ON s.department_id = d.id
                WHERE s.is_active = 1
                ORDER BY s.full_name";
                
                $stmt = $conn->prepare($query);
                $stmt->bind_param("iii", $user_id, $user_id, $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                while ($row = $result->fetch_assoc()) {
                    $contacts[] = [
                        'id' => (int)$row['id'],
                        'name' => $row['name'],
                        'subtitle' => $row['section'] ?: $row['class_name'],
                        'meta' => "Roll: " . $row['roll_number'] . " | " . $row['dept_name'],
                        'type' => 'student',
                        'unread_count' => (int)$row['unread_count'],
                        'last_message' => $row['last_message'] ?: ''
                    ];
                }
                
            } elseif ($user_role === 'student') {
                // Get all teachers
                $query = "SELECT DISTINCT 
                    u.id,
                    u.full_name as name,
                    u.email,
                    d.dept_name,
                    'teacher' as type,
                    (SELECT COUNT(*) 
                     FROM chat_messages cm 
                     WHERE cm.sender_id = u.id 
                     AND cm.sender_type = 'teacher'
                     AND cm.receiver_id = ? 
                     AND cm.receiver_type = 'student'
                     AND cm.is_read = 0) as unread_count,
                    (SELECT message 
                     FROM chat_messages cm2 
                     WHERE (cm2.sender_id = u.id AND cm2.sender_type = 'teacher' AND cm2.receiver_id = ? AND cm2.receiver_type = 'student')
                     OR (cm2.sender_id = ? AND cm2.sender_type = 'student' AND cm2.receiver_id = u.id AND cm2.receiver_type = 'teacher')
                     ORDER BY cm2.created_at DESC 
                     LIMIT 1) as last_message
                FROM users u
                LEFT JOIN departments d ON u.department_id = d.id
                WHERE u.role = 'teacher' AND u.is_active = 1
                ORDER BY u.full_name";
                
                $stmt = $conn->prepare($query);
                $stmt->bind_param("iii", $user_id, $user_id, $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                while ($row = $result->fetch_assoc()) {
                    $contacts[] = [
                        'id' => (int)$row['id'],
                        'name' => $row['name'],
                        'subtitle' => $row['dept_name'] ?: 'Teacher',
                        'meta' => $row['email'],
                        'type' => 'teacher',
                        'unread_count' => (int)$row['unread_count'],
                        'last_message' => $row['last_message'] ?: ''
                    ];
                }
            }elseif ($user_role === 'hod') {
    // Get HOD's department
    $dept_query = "SELECT department_id FROM users WHERE id = ? AND role = 'hod'";
    $dept_stmt = $conn->prepare($dept_query);
    $dept_stmt->bind_param("i", $user_id);
    $dept_stmt->execute();
    $dept_result = $dept_stmt->get_result();
    $dept_row = $dept_result->fetch_assoc();
    $department_id = $dept_row['department_id'];
    
    // Get all teachers in the HOD's department
    $query = "SELECT DISTINCT 
        u.id,
        u.full_name as name,
        u.email,
        d.dept_name,
        'teacher' as type,
        (SELECT COUNT(*) 
         FROM chat_messages cm 
         WHERE cm.sender_id = u.id 
         AND cm.sender_type = 'teacher'
         AND cm.receiver_id = ? 
         AND cm.receiver_type = 'hod'
         AND cm.is_read = 0) as unread_count,
        (SELECT message 
         FROM chat_messages cm2 
         WHERE (cm2.sender_id = u.id AND cm2.sender_type = 'teacher' AND cm2.receiver_id = ? AND cm2.receiver_type = 'hod')
         OR (cm2.sender_id = ? AND cm2.sender_type = 'hod' AND cm2.receiver_id = u.id AND cm2.receiver_type = 'teacher')
         ORDER BY cm2.created_at DESC 
         LIMIT 1) as last_message
    FROM users u
    LEFT JOIN departments d ON u.department_id = d.id
    WHERE u.role = 'teacher' 
    AND u.department_id = ?
    AND u.is_active = 1
    ORDER BY u.full_name";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iiii", $user_id, $user_id, $user_id, $department_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $contacts[] = [
            'id' => (int)$row['id'],
            'name' => $row['name'],
            'subtitle' => $row['dept_name'] ?: 'Teacher',
            'meta' => $row['email'],
            'type' => 'teacher',
            'unread_count' => (int)$row['unread_count'],
            'last_message' => $row['last_message'] ?: ''
        ];
    }
}
            
            $response['success'] = true;
            $response['contacts'] = $contacts;
            $response['total'] = count($contacts);
            break;

        case 'get_messages':
            if (!isset($_POST['contact_id']) || !isset($_POST['contact_type'])) {
                throw new Exception('Missing contact information');
            }
            
            $contact_id = (int)$_POST['contact_id'];
            $contact_type = $_POST['contact_type'];
            
            // Validate contact type
            if (!in_array($contact_type, ['teacher', 'student'])) {
                throw new Exception('Invalid contact type');
            }
            
            $messages = [];
            $query = "SELECT 
                cm.*,
                DATE_FORMAT(cm.created_at, '%h:%i %p') as time
            FROM chat_messages cm
            WHERE (
                (cm.sender_id = ? AND cm.sender_type = ? AND cm.receiver_id = ? AND cm.receiver_type = ?)
                OR
                (cm.sender_id = ? AND cm.sender_type = ? AND cm.receiver_id = ? AND cm.receiver_type = ?)
            )
            ORDER BY cm.created_at ASC";
            
            $stmt = $conn->prepare($query);
            $stmt->bind_param("isisisis", 
                $user_id, $user_role, $contact_id, $contact_type,
                $contact_id, $contact_type, $user_id, $user_role
            );
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $is_sent = ($row['sender_id'] == $user_id && $row['sender_type'] == $user_role);
                $messages[] = [
                    'id' => (int)$row['id'],
                    'message' => $row['message'],
                    'time' => $row['time'],
                    'is_sent' => $is_sent,
                    'is_read' => (int)$row['is_read']
                ];
            }
            
            $response['success'] = true;
            $response['messages'] = $messages;
            break;

        case 'send_message':
            if (!isset($_POST['receiver_id']) || !isset($_POST['receiver_type']) || !isset($_POST['message'])) {
                throw new Exception('Missing required fields');
            }
            
            $receiver_id = (int)$_POST['receiver_id'];
            $receiver_type = $_POST['receiver_type'];
            $message = trim($_POST['message']);
            
            if (empty($message)) {
                throw new Exception('Message cannot be empty');
            }
            
            // Validate receiver type
            if (!in_array($receiver_type, ['teacher', 'student'])) {
                throw new Exception('Invalid receiver type');
            }
            
            // Verify receiver exists
            if ($receiver_type === 'teacher') {
                $check_query = "SELECT id FROM users WHERE id = ? AND role = 'teacher' AND is_active = 1";
            } else {
                $check_query = "SELECT id FROM students WHERE id = ? AND is_active = 1";
            }
            
            $stmt = $conn->prepare($check_query);
            $stmt->bind_param("i", $receiver_id);
            $stmt->execute();
            if ($stmt->get_result()->num_rows === 0) {
                throw new Exception('Receiver not found');
            }
            
            // Insert message
            $insert_query = "INSERT INTO chat_messages 
                (sender_id, sender_type, receiver_id, receiver_type, message, is_read, created_at) 
                VALUES (?, ?, ?, ?, ?, 0, NOW())";
            
            $stmt = $conn->prepare($insert_query);
            $stmt->bind_param("isiss", $user_id, $user_role, $receiver_id, $receiver_type, $message);
            
            if (!$stmt->execute()) {
                throw new Exception('Failed to insert message: ' . $stmt->error);
            }
            
            $response['success'] = true;
            $response['message'] = 'Message sent successfully';
            $response['message_id'] = $conn->insert_id;
            break;

        case 'mark_read':
            if (!isset($_POST['contact_id']) || !isset($_POST['contact_type'])) {
                throw new Exception('Missing contact information');
            }
            
            $contact_id = (int)$_POST['contact_id'];
            $contact_type = $_POST['contact_type'];
            
            // Mark all messages from this contact as read
            $update_query = "UPDATE chat_messages 
                SET is_read = 1 
                WHERE sender_id = ? 
                AND sender_type = ? 
                AND receiver_id = ? 
                AND receiver_type = ? 
                AND is_read = 0";
            
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("isis", $contact_id, $contact_type, $user_id, $user_role);
            $stmt->execute();
            
            $response['success'] = true;
            $response['marked_count'] = $stmt->affected_rows;
            break;

        case 'get_unread_count':
            $query = "SELECT COUNT(*) as count 
                FROM chat_messages 
                WHERE receiver_id = ? 
                AND receiver_type = ? 
                AND is_read = 0";
            
            $stmt = $conn->prepare($query);
            $stmt->bind_param("is", $user_id, $user_role);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            
            $response['success'] = true;
            $response['count'] = (int)$result['count'];
            break;

        default:
            throw new Exception('Invalid action');
    }

} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
    error_log("Chat Handler Error: " . $e->getMessage());
}

// Clear any output buffer and send JSON response
ob_end_clean();
echo json_encode($response);
exit;
?>