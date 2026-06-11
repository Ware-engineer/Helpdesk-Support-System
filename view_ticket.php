<?php
session_start();
require '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

$status_filter = $_GET['status'] ?? '';
$priority_filter = $_GET['priority'] ?? '';

$query = "
SELECT tickets.*, users.full_name,
       tech.full_name AS technician_name
FROM tickets
JOIN users ON tickets.user_id = users.id
LEFT JOIN users AS tech ON tickets.assigned_to = tech.id
";

if ($status_filter != '') {
    $query .= " AND tickets.status = '$status_filter'";
}

if ($priority_filter != '') {
    $query .= " AND tickets.priority = '$priority_filter'";
}

$query .= " ORDER BY tickets.created_at DESC";

$result = $conn->query($query);
?>

<!DOCTYPE html>
<html>
<head>
    <title>All Tickets</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-4">

<h3>All Support Tickets</h3>

<table class="table table-bordered table-striped">
    <thead class="table-dark">
        <tr>
            <th>ID</th>
            <th>User</th>
            <th>Title</th>
            <th>Priority</th>
            <th>Status</th>
            <th>Resolution</th>
            <th>Resolved At</th>
            <th>Action</th>
            <th>Assigned To</th>
        </tr>
    </thead>
    <tbody>

    <?php while ($row = $result->fetch_assoc()): ?>
    <tr>
        <td><?= $row['id'] ?></td>
        <td><?= htmlspecialchars($row['full_name']) ?></td>
        <td><?= htmlspecialchars($row['title']) ?></td>
        <td><?= $row['priority'] ?></td>
        <td><?= $row['status'] ?></td>
        <td><?= htmlspecialchars($row['resolution'] ?? '') ?></td>
        <td><?= $row['resolved_at'] ?? '' ?></td>
        
        <td>
            <a href="update_ticket.php?id=<?= $row['id'] ?>" 
               class="btn btn-sm btn-primary">Update</a>
               <td><?= htmlspecialchars($row['technician_name'] ?? '') ?></td>
               
        </td>
    </tr>
    <?php endwhile; ?>

    </tbody>
</table>

<a href="dashboard.php" class="btn btn-secondary">Back</a>

</body>
</html>
