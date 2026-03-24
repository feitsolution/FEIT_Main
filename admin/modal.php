<!-- View Modal -->
<div class="modal fade" id="viewModal<?= $row['id'] ?>" tabindex="-1"
                                            aria-labelledby="viewModalLabel" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content">
                                                    <div class="modal-header bg-light">
                                                        <h5 class="modal-title" id="viewModalLabel"><i class="fas fa-envelope-open-text me-2"></i>Inquiry Details</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                            aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="row g-3">
                                                            <div class="col-md-6">
                                                                <div class="p-3 bg-light rounded">
                                                                    <small class="text-muted text-uppercase fw-semibold">Name</small>
                                                                    <p class="mb-0 mt-1"><?= htmlspecialchars($row['first_name']) . ' ' . htmlspecialchars($row['last_name']) ?></p>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <div class="p-3 bg-light rounded">
                                                                    <small class="text-muted text-uppercase fw-semibold">Email</small>
                                                                    <p class="mb-0 mt-1"><?= htmlspecialchars($row['email']) ?></p>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <div class="p-3 bg-light rounded">
                                                                    <small class="text-muted text-uppercase fw-semibold">Company</small>
                                                                    <p class="mb-0 mt-1"><?= htmlspecialchars($row['company']) ?: '<em class="text-muted">N/A</em>' ?></p>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <div class="p-3 bg-light rounded">
                                                                    <small class="text-muted text-uppercase fw-semibold">Created At</small>
                                                                    <p class="mb-0 mt-1"><?= htmlspecialchars($row['created_at']) ?></p>
                                                                </div>
                                                            </div>
                                                            <div class="col-12">
                                                                <div class="p-3 bg-light rounded">
                                                                    <small class="text-muted text-uppercase fw-semibold">Status</small>
                                                                    <p class="mb-0 mt-1">
                                                                        <?php if ($row['status'] === 'approved'): ?>
                                                                            <span class="badge-soft badge-soft-success">Approved</span>
                                                                        <?php elseif ($row['status'] === 'rejected'): ?>
                                                                            <span class="badge-soft badge-soft-danger">Rejected</span>
                                                                        <?php else: ?>
                                                                            <span class="badge-soft badge-soft-warning">Pending</span>
                                                                        <?php endif; ?>
                                                                    </p>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <hr>
                                                        <div>
                                                            <small class="text-muted text-uppercase fw-semibold">Message</small>
                                                            <div class="p-3 bg-light rounded border mt-2">
                                                                <?= nl2br(htmlspecialchars($row['mesage'])) ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary"
                                                            data-bs-dismiss="modal">Close</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>