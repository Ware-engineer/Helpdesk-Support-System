<?php
require '../config/database.php';

$result = $conn->query("SELECT t.*, u.full_name FROM tickets t 
JOIN users u ON t.user_id = u.id");

while ($row = $result->fetch_assoc()) {
?>
<tr>
    <td><?= $row['full_name'] ?></td>
    <td><?= $row['title'] ?></td>
    <td><?= $row['description'] ?></td>

    <td>
        <?php
        $status = $row['status'];
        $badge = 'secondary';

        if ($status == 'open') $badge = 'dark';
        if ($status == 'pending') $badge = 'warning';
        if ($status == 'pending confirmation') $badge = 'info';
        if ($status == 'resolved') $badge = 'success';
        ?>
        <span class="badge bg-<?= $badge ?>"><?= $status ?></span>
    </td>

    <td><?= $row['resolution'] ?></td>

    <td>
        <form method="POST" action="admin_update_ticket.php">
            <input type="hidden" name="ticket_id" value="<?= $row['id'] ?>">

            <input type="text" name="resolution" class="form-control form-control-sm mb-1">

            <select name="status" class="form-select form-select-sm mb-1">
                <option value="open">Open</option>
                <option value="pending">Pending</option>
                <option value="pending confirmation">Pending Confirmation</option>
                <option value="resolved">Resolved</option>
            </select>

            <button class="btn btn-primary btn-sm w-100">Update</button>
        </form>
    </td>
</tr>
<?php } ?>
