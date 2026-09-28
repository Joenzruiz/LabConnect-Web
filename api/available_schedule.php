<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

$doctor_id = $_GET['doctor_id'] ?? null;
if (!$doctor_id) {
    http_response_code(400);
    echo json_encode(["error" => "Missing doctor_id"]);
    exit;
}

try {
    // 1. Get confirmed schedule blocks for this doctor from today onwards
    $blocks = $pdo->prepare("SELECT schedule_date, start_time, end_time FROM doctor_schedule_blocks WHERE doctor_id=? AND status='Confirmed' AND schedule_date >= CURRENT_DATE ORDER BY schedule_date");
    $blocks->execute([$doctor_id]);
    $blocks = $blocks->fetchAll(PDO::FETCH_ASSOC);

    // 2. Get existing non-cancelled appointments for this doctor from today onwards to block out those times
    $appts = $pdo->prepare("SELECT appointment_date, appointment_time FROM appointments WHERE doctor_id=? AND status IN ('Pending','Confirmed','Approved') AND appointment_date >= CURRENT_DATE");
    $appts->execute([$doctor_id]);
    $appts = $appts->fetchAll(PDO::FETCH_ASSOC);
    
    $booked_times = [];
    foreach ($appts as $a) {
        // Only keep H:i format
        $booked_times[$a['appointment_date']][] = substr($a['appointment_time'], 0, 5);
    }

    $schedule = [];
    foreach ($blocks as $b) {
        $date = $b['schedule_date'];
        if (!isset($schedule[$date])) {
            $schedule[$date] = [];
        }

        $start = strtotime($date . ' ' . $b['start_time']);
        $end = strtotime($date . ' ' . $b['end_time']);
        
        // Generate 30 minute slots
        $current = $start;
        while ($current + 1800 <= $end) {
            $time_str_24h = date('H:i', $current);
            $time_str_12h = date('h:i A', $current);
            
            // Check if slot is already booked
            if (empty($booked_times[$date]) || !in_array($time_str_24h, $booked_times[$date])) {
                if (!in_array($time_str_12h, $schedule[$date])) {
                    $schedule[$date][] = $time_str_12h;
                }
            }
            $current += 1800; // +30 mins
        }
    }

    echo json_encode($schedule);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Database error: " . $e->getMessage()]);
}
