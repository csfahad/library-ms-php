<?php
/**
 * AJAX endpoint for book request operations
 * Library Management System
 */

require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Set JSON response header
header('Content-Type: application/json');

// Function to send JSON response and exit
function sendJsonResponse($success, $message, $data = null) {
    $response = ['success' => $success, 'message' => $message];
    if ($data !== null) {
        $response['data'] = $data;
    }
    echo json_encode($response);
    exit;
}

// Require login
if (!isLoggedIn()) {
    sendJsonResponse(false, 'Authentication required.');
}

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(false, 'Invalid request method.');
}

// Get current user
$currentUser = getCurrentUser();
$action = $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'submit_request':
            // Only students can submit requests
            if ($_SESSION['user_role'] !== 'student') {
                echo json_encode(['success' => false, 'message' => 'Only students can submit book requests.']);
                exit;
            }
            
            $bookId = intval($_POST['book_id'] ?? 0);
            if ($bookId <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid book ID.']);
                exit;
            }
            
            $result = submitBookRequest($currentUser['id'], $bookId);
            echo json_encode($result);
            break;
            
        case 'cancel_request':
            // Students can cancel their own pending requests
            if ($_SESSION['user_role'] !== 'student') {
                sendJsonResponse(false, 'Unauthorized action.');
            }
            
            $requestId = intval($_POST['request_id'] ?? 0);
            if ($requestId <= 0) {
                sendJsonResponse(false, 'Invalid request ID.');
            }
            
            try {
                $db = getDB();
                
                // Check if the request exists and belongs to the user
                $checkStmt = $db->prepare("SELECT request_id, status FROM book_requests 
                                         WHERE request_id = :request_id AND user_id = :user_id");
                $checkStmt->bindParam(':request_id', $requestId, PDO::PARAM_INT);
                $checkStmt->bindParam(':user_id', $currentUser['id'], PDO::PARAM_INT);
                $checkStmt->execute();
                $request = $checkStmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$request) {
                    sendJsonResponse(false, 'Request not found or you do not have permission to cancel it.');
                }
                
                if ($request['status'] !== 'pending') {
                    sendJsonResponse(false, 'Only pending requests can be cancelled.');
                }
                
                // Update the request to cancelled status
                $stmt = $db->prepare("UPDATE book_requests SET status = 'cancelled' 
                                     WHERE request_id = :request_id AND user_id = :user_id AND status = 'pending'");
                $stmt->bindParam(':request_id', $requestId, PDO::PARAM_INT);
                $stmt->bindParam(':user_id', $currentUser['id'], PDO::PARAM_INT);
                
                if ($stmt->execute() && $stmt->rowCount() > 0) {
                    sendJsonResponse(true, 'Request cancelled successfully.');
                } else {
                    sendJsonResponse(false, 'Failed to cancel request. Please try again.');
                }
                
            } catch (Exception $e) {
                sendJsonResponse(false, 'An error occurred while cancelling request.');
            }
            break;
            
        case 'approve_request':
            // Only admins can approve requests
            if ($_SESSION['user_role'] !== 'admin') {
                echo json_encode(['success' => false, 'message' => 'Admin access required.']);
                exit;
            }
            
            $requestId = intval($_POST['request_id'] ?? 0);
            $notes = trim($_POST['notes'] ?? '');
            
            if ($requestId <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid request ID.']);
                exit;
            }
            
            $result = approveBookRequest($requestId, $currentUser['id'], $notes);
            echo json_encode($result);
            break;
            
        case 'reject_request':
            // Only admins can reject requests
            if ($_SESSION['user_role'] !== 'admin') {
                echo json_encode(['success' => false, 'message' => 'Admin access required.']);
                exit;
            }
            
            $requestId = intval($_POST['request_id'] ?? 0);
            $reason = trim($_POST['reason'] ?? '');
            
            if ($requestId <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid request ID.']);
                exit;
            }
            
            $result = rejectBookRequest($requestId, $currentUser['id'], $reason);
            echo json_encode($result);
            break;
            
        case 'return_book':
            // Only admins can process returns
            if ($_SESSION['user_role'] !== 'admin') {
                echo json_encode(['success' => false, 'message' => 'Admin access required.']);
                exit;
            }
            
            $requestId = intval($_POST['request_id'] ?? 0);
            $fine = floatval($_POST['fine'] ?? 0.00);
            
            if ($requestId <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid request ID.']);
                exit;
            }
            
            $result = returnBookRequest($requestId, $fine);
            echo json_encode($result);
            break;
            
        default:
            sendJsonResponse(false, 'Invalid action.');
            break;
    }
    
} catch (Exception $e) {
    sendJsonResponse(false, 'An unexpected error occurred.');
}
?>
