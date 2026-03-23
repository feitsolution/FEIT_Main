<!-- action_buttons.php -->
<div class="inquiry-action-btns d-flex gap-1">
    <button type="button" class="btn btn-view"
        title="View Details"
        data-bs-toggle="modal" data-bs-target="#viewModal<?= $row['id'] ?>">
        <i class="fas fa-eye"></i>
    </button>
    <!--<button type="button" class="btn btn-approve action-button"-->
    <!--    data-bs-toggle="tooltip" data-bs-placement="top" title="Approve"-->
    <!--    data-action="approved"-->
    <!--    data-inquiry-id="<?= htmlspecialchars($row['id']) ?>"-->
    <!--    <?= $row['status'] === 'approved' || $row['status'] === 'rejected' ? 'disabled' : '' ?>>-->
    <!--    <i class="fas fa-check"></i>-->
    <!--</button>-->
    <!--<button type="button" class="btn btn-reject action-button"-->
    <!--    data-bs-toggle="tooltip" data-bs-placement="top" title="Reject"-->
    <!--    data-action="rejected"-->
    <!--    data-inquiry-id="<?= htmlspecialchars($row['id']) ?>"-->
    <!--    <?= $row['status'] === 'approved' || $row['status'] === 'rejected' ? 'disabled' : '' ?>>-->
    <!--    <i class="fas fa-times"></i>-->
    <!--</button>-->
</div>
