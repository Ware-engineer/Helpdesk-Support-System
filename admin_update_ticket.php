<?php
session_start();
require '../config/database.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Sanitize inputs
    $ticket_id = mysqli_real_escape_string($conn, $_POST['ticket_id']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    $resolution = mysqli_real_escape_string($conn, $_POST['resolution']);
    
    // 2. Handle Text-based Assigned Role
    $assigned_to = !empty($_POST['assigned_to']) ? "'" . mysqli_real_escape_string($conn, $_POST['assigned_to']) . "'" : "NULL";

    // 3. Update Query
    $update_query = "UPDATE tickets 
                     SET resolution = '$resolution', 
                         status = '$status', 
                         assigned_to = $assigned_to 
                     WHERE id = '$ticket_id'";

    if ($conn->query($update_query)) {
        
        // 4. Notification logic
        $stmt = $conn->prepare("SELECT u.email, t.title FROM tickets t JOIN users u ON t.user_id = u.id WHERE t.id = ?");
        $stmt->bind_param("i", $ticket_id);
        $stmt->execute();
        $data = $stmt->get_result()->fetch_assoc();

        if ($data) {
            $email = $data['email'];
            $title = $data['title'];
            $subject = "Ticket Update: $title";
            $message = "Hello,\n\nYour ticket has been updated to: $status.\nResolution: $resolution\n\nThank you.";
            $headers = "From: no-reply@ticketsystem.com";
            @mail($email, $subject, $message, $headers);
        }

        // 5. Success Redirect
        header("Location: admin_dashboard.php?msg=success");
        exit;
    } else {
        // This will help you see the exact error if the SQL fails
        die("Database Error: " . $conn->error);
    }
}
?>
