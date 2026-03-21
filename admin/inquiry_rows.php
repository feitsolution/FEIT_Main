<tr data-inquiry-id="<?= htmlspecialchars($row['id']) ?>">
    <td>
        <span class="inquiry-name"><?= htmlspecialchars($row['first_name']) . ' ' . htmlspecialchars($row['last_name']) ?></span>
    </td>
    <td><?= htmlspecialchars($row['email']) ?></td>
    <td class="message-cell">
        <div class="message-content" data-bs-toggle="tooltip"
            data-bs-placement="top" title="<?= htmlspecialchars($row['mesage']) ?>">
            <?= htmlspecialchars($row['mesage']) ?>
        </div>
    </td>
    <td><?= htmlspecialchars($row['company']) ?></td>
    <td><?= htmlspecialchars($row['created_at']) ?></td>
    <td>
        <?php if ($row['status'] === 'approved'): ?>
            <span class="status-badge badge-soft badge-soft-success">Approved</span>
        <?php elseif ($row['status'] === 'rejected'): ?>
            <span class="status-badge badge-soft badge-soft-danger">Rejected</span>
        <?php else: ?>
            <span class="status-badge badge-soft badge-soft-warning">Pending</span>
        <?php endif; ?>
    </td>
    <td>
        <?php
        $inquiry_id = htmlspecialchars($row['id']);
        $status = htmlspecialchars($row['status']);
        include 'action_buttons.php';
        ?>
    </td>
</tr>
