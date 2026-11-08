<?php
$pageTitle = "Loan #" . $loan['id'];
$activePage = 'loans';
include RH_TEMPLATES . '/layout/header.php';
?>

<div class="mb-3">
    <a href="<?= url('loans') ?>" class="text-decoration-none text-secondary small">
        &larr; Back to loans
    </a>
</div>

<div class="row g-4">
    <div class="col-12 col-lg-8">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <span class="text-secondary small">Loan #<?= (int)$loan['id'] ?></span>
                    <h5 class="fw-bold mb-0 text-dark"><?= e($loan['asset_name']) ?></h5>
                </div>
                <div>
                    <span class="badge badge-<?= e($loan['status']) ?>">
                        <span class="badge-dot"></span>
                        <?= e(ucfirst(str_replace('_', ' ', $loan['status']))) ?>
                    </span>
                    <?php if (!empty($loan['is_overdue'])): ?>
                        <span class="badge badge-overdue ms-1"><span class="badge-dot"></span> Overdue</span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card-body">
                <div class="row g-3 mb-4">
                    <div class="col-sm-6">
                        <div class="text-secondary small">Equipment</div>
                        <a href="<?= url('equipment', ['action' => 'view', 'id' => $loan['asset_id']]) ?>" class="fw-bold text-dark text-decoration-none">
                            <?= e($loan['asset_name']) ?>
                        </a>
                        <div class="font-monospace text-secondary small"><?= e($loan['asset_tag']) ?></div>
                    </div>

                    <div class="col-sm-6">
                        <div class="text-secondary small">Borrower</div>
                        <div class="fw-semibold text-dark"><?= e($loan['user_name']) ?></div>
                        <div class="text-secondary small"><?= e($loan['user_email']) ?></div>
                    </div>

                    <div class="col-sm-6">
                        <div class="text-secondary small">Dates</div>
                        <div class="fw-semibold text-dark"><?= e(format_date($loan['start_date'])) ?> &rarr; <?= e(format_date($loan['end_date'])) ?></div>
                    </div>

                    <div class="col-sm-6">
                        <div class="text-secondary small">Submitted</div>
                        <div class="small text-dark"><?= e(format_datetime($loan['created_at'])) ?></div>
                    </div>

                    <div class="col-12">
                        <div class="text-secondary small mb-1">Purpose</div>
                        <div class="p-3 rounded border small" style="background-color: var(--bg-surface-subtle); border-color: var(--border-color) !important; color: var(--text-primary);">
                            <?= nl2br(e($loan['purpose'])) ?>
                        </div>
                    </div>

                    <?php if (!empty($loan['rejection_reason'])): ?>
                        <div class="col-12">
                            <div class="alert alert-danger mb-0 small">
                                <strong>Declined reason:</strong> <?= e($loan['rejection_reason']) ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($loan['status'] === 'returned'): ?>
                        <div class="col-sm-6">
                            <div class="text-secondary small">Returned on</div>
                            <div class="small fw-semibold text-dark"><?= e(format_datetime($loan['returned_at'])) ?></div>
                        </div>
                        <div class="col-sm-6">
                            <div class="text-secondary small">Condition on return</div>
                            <div class="text-capitalize small fw-semibold text-dark"><?= e($loan['return_condition']) ?></div>
                        </div>
                        <?php if (!empty($loan['return_notes'])): ?>
                            <div class="col-12">
                                <div class="text-secondary small mb-1">Return notes</div>
                                <div class="p-2 rounded border small" style="background-color: var(--bg-surface-subtle); border-color: var(--border-color) !important; color: var(--text-primary);"><?= e($loan['return_notes']) ?></div>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <!-- Actions -->
                <?php if ($loan['status'] === 'pending'): ?>
                    <div class="border-top pt-3 d-flex justify-content-between align-items-center" style="border-color: var(--border-color) !important;">
                        <?php if ($isOwner || Auth::isCoordinator()): ?>
                            <form method="POST" action="<?= url('loans', ['action' => 'view', 'id' => $loan['id']]) ?>" onsubmit="return confirm('Cancel this request?');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="cancel">
                                <button type="submit" class="btn btn-secondary btn-sm text-danger">Cancel request</button>
                            </form>
                        <?php else: ?>
                            <div></div>
                        <?php endif; ?>

                        <?php if (Auth::isCoordinator()): ?>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-secondary btn-sm text-danger" data-bs-toggle="collapse" data-bs-target="#declineBox">
                                    Decline
                                </button>
                                <form method="POST" action="<?= url('loans', ['action' => 'view', 'id' => $loan['id']]) ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="approve">
                                    <button type="submit" class="btn btn-primary btn-sm">Approve</button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if (Auth::isCoordinator()): ?>
                        <div class="collapse mt-3" id="declineBox">
                            <div class="p-3 rounded border" style="background-color: var(--bg-surface-subtle); border-color: var(--border-color) !important;">
                                <form method="POST" action="<?= url('loans', ['action' => 'view', 'id' => $loan['id']]) ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="reject">
                                    <label class="form-label small">Reason for declining</label>
                                    <textarea class="form-control form-control-sm mb-2" name="rejection_reason" rows="2" required placeholder="Provide reason..."></textarea>
                                    <div class="d-flex justify-content-end">
                                        <button type="submit" class="btn btn-danger btn-sm">Confirm decline</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    <?php endif; ?>

                <?php elseif ($loan['status'] === 'approved'): ?>
                    <div class="border-top pt-3 d-flex justify-content-between align-items-center" style="border-color: var(--border-color) !important;">
                        <?php if ($isOwner || Auth::isCoordinator()): ?>
                            <form method="POST" action="<?= url('loans', ['action' => 'view', 'id' => $loan['id']]) ?>" onsubmit="return confirm('Cancel this approved reservation?');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="cancel">
                                <button type="submit" class="btn btn-secondary btn-sm text-danger">Cancel reservation</button>
                            </form>
                        <?php else: ?>
                            <span class="text-secondary small">Approved reservation</span>
                        <?php endif; ?>

                        <?php if (Auth::isCoordinator()): ?>
                            <form method="POST" action="<?= url('loans', ['action' => 'view', 'id' => $loan['id']]) ?>">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="checkout">
                                <button type="submit" class="rh-action-btn-pill">Confirm checkout</button>
                            </form>
                        <?php endif; ?>
                    </div>

                <?php elseif ($loan['status'] === 'checked_out' && Auth::isCoordinator()): ?>
                    <div class="border-top pt-3" style="border-color: var(--border-color) !important;">
                        <div class="p-3 rounded border" style="background-color: var(--bg-surface-subtle); border-color: var(--border-color) !important;">
                            <div class="fw-bold small mb-2 text-dark">Record return</div>
                            <form method="POST" action="<?= url('loans', ['action' => 'view', 'id' => $loan['id']]) ?>">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="return">
                                <div class="row g-2 mb-2">
                                    <div class="col-sm-4">
                                        <label class="form-label small">Condition</label>
                                        <select class="form-select form-select-sm" name="return_condition" required>
                                            <option value="good" selected>Good</option>
                                            <option value="fair">Fair</option>
                                            <option value="damaged">Damaged (Sends to repair)</option>
                                        </select>
                                    </div>
                                    <div class="col-sm-8">
                                        <label class="form-label small">Notes</label>
                                        <input type="text" class="form-control form-control-sm" name="return_notes" placeholder="Notes on condition...">
                                    </div>
                                </div>
                                <div class="d-flex justify-content-end">
                                    <button type="submit" class="rh-action-btn-pill">Complete return</button>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- History Trail -->
    <div class="col-12 col-lg-4">
        <div class="card">
            <div class="card-header">
                <span class="fw-bold" style="font-size: 1rem;">History</span>
            </div>
            <div class="card-body p-3">
                <?php if (empty($history)): ?>
                    <div class="text-secondary small text-center py-3">No history recorded.</div>
                <?php else: ?>
                    <div class="d-flex flex-column gap-3">
                        <?php foreach ($history as $h): ?>
                            <div class="border-bottom pb-2" style="border-color: var(--border-color) !important;">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="badge badge-<?= e($h['action']) ?>" style="font-size: 0.68rem;">
                                        <span class="badge-dot"></span> <?= e(ucfirst($h['action'])) ?>
                                    </span>
                                    <span class="text-secondary" style="font-size: 0.72rem;"><?= e(format_datetime($h['created_at'])) ?></span>
                                </div>
                                <div class="small fw-semibold text-dark"><?= e($h['performer_name'] ?? 'System') ?></div>
                                <?php if (!empty($h['notes'])): ?>
                                    <div class="text-secondary small"><?= e($h['notes']) ?></div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include RH_TEMPLATES . '/layout/footer.php'; ?>

