<?php
session_start();
require '../config/database.php';

// Only allow admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

if (!isset($_GET['id'])) {
    die("Ticket ID not provided.");
}

$ticket_id = $_GET['id'];

// Fetch ticket
$stmt = $conn->prepare("SELECT * FROM tickets WHERE id = ?");
$stmt->bind_param("i", $ticket_id);
$stmt->execute();
$result = $stmt->get_result();
$ticket = $result->fetch_assoc();

if (!$ticket) {
    die("Ticket not found.");
}

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $status = $_POST['status'];
    $resolution = $_POST['resolution'];

    $stmt = $conn->prepare("UPDATE tickets 
                            SET status = ?, resolution = ?, updated_at = NOW()
                            WHERE id = ?");
    $stmt->bind_param("ssi", $status, $resolution, $ticket_id);
    $stmt->execute();

    header("Location: update_ticket.php?id=$ticket_id&success=1");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Update Ticket</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-4">

<h3>Update Ticket #<?= $ticket['id'] ?></h3>

<?php if (isset($_GET['success'])): ?>
<div class="alert alert-success">Ticket updated successfully!</div>
<?php endif; ?>

<form method="POST">

    <div class="mb-3">
        <label>Status</label>
        <select name="status" class="form-control" required>
            <option value="Open" <?= $ticket['status']=='Open'?'selected':'' ?>>Open</option>
            <option value="In Progress" <?= $ticket['status']=='In Progress'?'selected':'' ?>>In Progress</option>
            <option value="Pending User Confirmation" <?= $ticket['status']=='Pending User Confirmation'?'selected':'' ?>>Pending User Confirmation</option>
            <option value="Resolved" <?= $ticket['status']=='Resolved'?'selected':'' ?>>Resolved</option>
            <option value="Closed" <?= $ticket['status']=='Closed'?'selected':'' ?>>Closed</option>
        </select>
    </div>

    <div class="mb-3">
        <label>Resolution Notes</label>
        <textarea name="resolution" class="form-control" rows="4"><?= $ticket['resolution'] ?? '' ?></textarea>
    </div>

    <button class="btn btn-dark">Update Ticket</button>
</form>

</body>
</html>
