<?php
session_start();
require '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

// 1. Main query
$result = $conn->query("
    SELECT t.*, u.full_name
    FROM tickets t
    JOIN users u ON t.user_id = u.id
    ORDER BY t.created_at DESC
");

// 2. General Statistics
$total = $conn->query("SELECT COUNT(*) as count FROM tickets")->fetch_assoc()['count'];
$open = $conn->query("SELECT COUNT(*) as count FROM tickets WHERE status='open'")->fetch_assoc()['count'];
$pending = $conn->query("SELECT COUNT(*) as count FROM tickets WHERE status='pending' OR status='pending confirmation'")->fetch_assoc()['count']; 
$resolved = $conn->query("SELECT COUNT(*) as count FROM tickets WHERE status='resolved'")->fetch_assoc()['count'];

// 3. Category Statistics (New)
$cat_stats = $conn->query("
    SELECT category, COUNT(*) as count 
    FROM tickets 
    GROUP BY category
");
$categories = [];
while($row = $cat_stats->fetch_assoc()) {
    $categories[$row['category']] = $row['count'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard | Helpdesk</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <style>
        body { background-color: #f4f6f9; font-family: 'Segoe UI', sans-serif; }
        .table thead { background-color: #212529; color: white; }
        
        /* Pulse Animation for Critical Tickets */
        .pulse-animation {
            animation: pulse-red 2s infinite;
            box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.7);
        }
        @keyframes pulse-red {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.7); }
            70% { transform: scale(1); box-shadow: 0 0 0 10px rgba(220, 53, 69, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(220, 53, 69, 0); }
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm px-4">
    <a class="navbar-brand fw-bold" href="#">Helpdesk Admin</a>
    <div class="ms-auto d-flex align-items-center">
        <span class="text-light me-3 small">Welcome, <strong><?= htmlspecialchars($_SESSION['full_name']) ?></strong></span>
        <a href="../auth/logout.php" class="btn btn-danger btn-sm">Logout</a>
    </div>
</nav>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-dark text-white shadow-sm border-0">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div><small class="text-uppercase opacity-75">Total</small><h3 class="mb-0"><?= $total ?></h3></div>
                <i class="bi bi-ticket-perforated fs-1 opacity-25"></i>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-primary text-white shadow-sm border-0">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div><small class="text-uppercase opacity-75">Open</small><h3 class="mb-0"><?= $open ?></h3></div>
                <i class="bi bi-exclamation-octagon fs-1 opacity-25"></i>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-dark shadow-sm border-0">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div><small class="text-uppercase opacity-75">Pending</small><h3 class="mb-0"><?= $pending ?></h3></div>
                <i class="bi bi-clock-history fs-1 opacity-25"></i>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white shadow-sm border-0">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div><small class="text-uppercase opacity-75">Resolved</small><h3 class="mb-0"><?= $resolved ?></h3></div>
                <i class="bi bi-check-all fs-1 opacity-25"></i>
            </div>
        </div>
    </div>
</div>

    <h6 class="text-muted fw-bold mb-3"><i class="bi bi-bar-chart-fill me-2"></i>INCIDENTS BY CATEGORY</h6>
    <div class="row mb-4">
        <?php 
        $display_cats = [
            'Hardware' => ['icon' => 'cpu', 'color' => 'secondary'],
            'Software' => ['icon' => 'window-stacked', 'color' => 'info text-white'],
            'Network'  => ['icon' => 'wifi', 'color' => 'success'],
            'Account'  => ['icon' => 'person-lock', 'color' => 'warning text-dark']
        ];

        foreach ($display_cats as $name => $style): 
            $count = $categories[$name] ?? 0; // Show 0 if no tickets exist for this cat
        ?>
        <div class="col">
            <div class="card shadow-sm border-0 text-center p-2">
                <div class="card-body p-1">
                    <div class="badge bg-<?= $style['color'] ?> rounded-circle p-3 mb-2">
                        <i class="bi bi-<?= $style['icon'] ?> fs-4"></i>
                    </div>
                    <h5 class="fw-bold mb-0"><?= $count ?></h5>
                    <small class="text-muted"><?= $name ?></small>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table id="ticketsTable" class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Issue</th>
                            <th>Category</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Resolution</th>
                            <th>File</th>
                            <th>Assigned To</th>
                            <th style="width: 250px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()) { ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($row['full_name']) ?></strong></td>
                            <td>
                                <div class="fw-bold"><?= htmlspecialchars($row['title']) ?></div>
                                <small class="text-muted"><?= htmlspecialchars($row['description']) ?></small>
                            </td>

                            <td>
                                <?php 
                                $cat = htmlspecialchars($row['category']); 
                                $icon = match($cat) {
                                    'Hardware' => '<i class="bi bi-cpu text-secondary"></i>',
                                    'Software' => '<i class="bi bi-window-stacked text-primary"></i>',
                                    'Network'  => '<i class="bi bi-wifi text-success"></i>',
                                    'Account'  => '<i class="bi bi-person-lock text-warning"></i>',
                                    default    => '<i class="bi bi-patch-question"></i>'
                                };
                                echo "$icon <span class='small'>$cat</span>";
                                ?>
                            </td>

                            <td>
                                <?php
                                $prio = $row['priority'];
                                $prioBadge = match($prio) {
                                    'Low' => 'bg-info text-dark',
                                    'Medium' => 'bg-primary',
                                    'High' => 'bg-warning text-dark',
                                    'Critical' => 'bg-danger pulse-animation',
                                    default => 'bg-secondary'
                                };
                                echo "<span class='badge $prioBadge'>$prio</span>";
                                ?>
                            </td>

                            <td>
                                <?php
                                $status = $row['status'];
                                $badge = match($status) {
                                    'open' => 'dark',
                                    'pending' => 'warning text-dark',
                                    'pending confirmation' => 'info text-white',
                                    'resolved' => 'success',
                                    default => 'secondary'
                                };
                                echo "<span class='badge bg-$badge text-capitalize'>$status</span>";
                                ?>
                            </td>

                            <td><small><?= htmlspecialchars($row['resolution']) ?: '<i>No update</i>' ?></small></td>

                            <td>
                                <?php if (!empty($row['attachment'])): ?>
                                    <a href="../uploads/<?= htmlspecialchars($row['attachment']) ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-paperclip"></i> View
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted small">None</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?php if(!empty($row['assigned_to'])): ?>
                                    <span class="badge border text-dark bg-light"><?= htmlspecialchars($row['assigned_to']) ?></span>
                                <?php else: ?>
                                    <span class="text-muted small">Unassigned</span>
                                <?php endif; ?>
                            </td>

                           <td>
    <form method="POST" action="admin_update_ticket.php" class="bg-light p-2 rounded border shadow-sm">
        <input type="hidden" name="ticket_id" value="<?= $row['id'] ?>">
        
        <label class="small fw-bold text-muted">Resolution Notes:</label>
        <textarea name="resolution" class="form-control form-control-sm mb-2" rows="2" placeholder="Describe the fix..."><?= htmlspecialchars($row['resolution']) ?></textarea>

        <label class="small fw-bold text-muted">Assign Responsibility:</label>
        <select name="assigned_to" class="form-select form-select-sm mb-2">
            <option value="">-- Unassigned --</option>
            <option value="IT Technician" <?= $row['assigned_to'] == 'IT Technician' ? 'selected' : '' ?>>IT Technician</option>
            <option value="IT Support" <?= $row['assigned_to'] == 'IT Support' ? 'selected' : '' ?>>IT Support</option>
            <option value="System Administrator" <?= $row['assigned_to'] == 'System Administrator' ? 'selected' : '' ?>>System Admin</option>
            <option value="Network Engineer" <?= $row['assigned_to'] == 'Network Engineer' ? 'selected' : '' ?>>Network Engineer</option>
        </select>

        <label class="small fw-bold text-muted">Current Status:</label>
        <select name="status" class="form-select form-select-sm mb-2">
            <option value="open" <?= $row['status']=='open'?'selected':'' ?>>Open</option>
            <option value="pending" <?= $row['status']=='pending'?'selected':'' ?>>Pending</option>
            <option value="pending confirmation" <?= $row['status']=='pending confirmation'?'selected':'' ?>>Pending Confirmation</option>
            <option value="resolved" <?= $row['status']=='resolved'?'selected':'' ?>>Resolved</option>
        </select>

        <button type="submit" class="btn btn-primary btn-sm w-100 fw-bold">
            <i class="bi bi-save"></i> Save Changes
        </button>
    </form>
</td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() { 
    $('#ticketsTable').DataTable({
        "order": [[4, "asc"]] // Sort by priority/status order
    }); 
});
</script>

</body>
</html>
