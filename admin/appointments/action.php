<?php
session_start();
require_once '../../config/database.php';

if (($_SESSION['user_role'] ?? '') !== 'admin' && ($_SESSION['user_role'] ?? '') !== 'staff') {
    header('Location: ../../index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $appointment_id = $_POST['appointment_id'] ?? null;
    
    if ($appointment_id && in_array($action, ['approve', 'reject', 'complete'])) {
        $status = 'Pending';
        if ($action === 'approve') $status = 'Confirmed';
        if ($action === 'reject') $status = 'Cancelled';
        if ($action === 'complete') $status = 'Completed';
        
        $stmt = $pdo->prepare("UPDATE appointments SET status = :status WHERE appointment_id = :id");
        $stmt->execute(['status' => $status, 'id' => $appointment_id]);
        
        // Log it or notify?
    }
}
header('Location: ' . $_SERVER['HTTP_REFERER']);
exit;
