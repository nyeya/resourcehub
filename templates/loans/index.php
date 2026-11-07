<?php
$pageTitle = Auth::isCoordinator() ? "Loans" : "My Loans";
$activePage = 'loans';
include RH_TEMPLATES . '/layout/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <h2 class="fw-bold tracking-tight mb-1 text-white"><?= e($pageTitle) ?></h2>
        <div class="text-secondary small">Manage equipment reservations, checkouts, and returns.</div>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="<?= url('reports', ['action' => 'export_loans', 'status' => $currentStatus]) ?>" class="btn btn-secondary btn-sm">
            <i class="bi bi-download"></i> Export CSV
        </a>
        <a href="<?= url('loans', ['action' => 'request']) ?>" class="rh-action-btn-pill">
            <i class="bi bi-plus-lg"></i> New request
        </a>
    </div>
</div>

<!-- Tabs -->
<div class="mb-4 overflow-x-auto pb-1">
    <div class="rh-tabs">
        <a class="rh-tab-item <?= $currentStatus === '' ? 'active' : '' ?>" href="<?= url('loans') ?>">All</a>
        <a class="rh-tab-item <?= $currentStatus === 'pending' ? 'active' : '' ?>" href="<?= url('loans', ['status' => 'pending']) ?>">Pending</a>
        <a class="rh-tab-item <?= $currentStatus === 'approved' ? 'active' : '' ?>" href="<?= url('loans', ['status' => 'approved']) ?>">Approved</a>
        <a class="rh-tab-item <?= $currentStatus === 'checked_out' ? 'active' : '' ?>" href="<?= url('loans', ['status' => 'checked_out']) ?>">On loan</a>
        <a class="rh-tab-item <?= $currentStatus === 'overdue' ? 'active text-danger' : 'text-danger' ?>" href="<?= url('loans', ['status' => 'overdue']) ?>">Overdue</a>
        <a class="rh-tab-item <?= $currentStatus === 'returned' ? 'active' : '' ?>" href="<?= url('loans', ['status' => 'returned']) ?>">Returned</a>
        <a class="rh-tab-item <?= $currentStatus === 'rejected' ? 'active' : '' ?>" href="<?= url('loans', ['status' => 'rejected']) ?>">Declined</a>
        <a class="rh-tab-item <?= $currentStatus === 'cancelled' ? 'active' : '' ?>" href="<?= url('loans', ['status' => 'cancelled']) ?>">Cancelled</a>
    </div>
</div>

<!-- Table -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Item</th>
                        <?php if (Auth::isCoordinator()): ?>
                            <th>Borrower</th>
                        <?php endif; ?>
                        <th>Dates</th>
                        <th>Purpose</th>
                        <th>Status</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($loans)): ?>
                        <tr>
                            <td colspan="<?= Auth::isCoordinator() ? '7' : '6' ?>" class="text-center py-5 text-secondary small">
                                No loan records found.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($loans as $l): ?>
                            <tr>
                                <td>
                                    <span class="text-secondary small">#<?= (int)$l['id'] ?></span>
                                </td>
                                <td>
                                    <a href="<?= url('equipment', ['action' => 'view', 'id' => $l['asset_id']]) ?>" class="fw-bold text-white text-decoration-none">
                                        <?= e($l['asset_name']) ?>
                                    </a>
                                    <div class="font-monospace text-secondary" style="font-size: 0.72rem;"><?= e($l['asset_tag']) ?></div>
                                </td>
                                <?php if (Auth::isCoordinator()): ?>
                                    <td class="small">
                                        <div class="fw-semibold text-white"><?= e($l['user_name']) ?></div>
                                        <div class="text-secondary" style="font-size: 0.72rem;"><?= e($l['user_email']) ?></div>
                                    </td>
                                <?php endif; ?>
                                <td class="small">
                                    <div class="text-white"><?= e(format_date($l['start_date'])) ?> &rarr; <?= e(format_date($l['end_date'])) ?></div>
                                    <?php if (!empty($l['is_overdue'])): ?>
                                        <span class="badge badge-overdue mt-1"><span class="badge-dot"></span> Overdue</span>
                                    <?php endif; ?>
                                </td>
                                <td class="small text-secondary text-truncate" style="max-width: 200px;" title="<?= e($l['purpose']) ?>">
                                    <?= e($l['purpose']) ?>
                                </td>
                                <td>
                                    <span class="badge badge-<?= e($l['status']) ?>">
                                        <span class="badge-dot"></span>
                                        <?= e(ucfirst(str_replace('_', ' ', $l['status']))) ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <a href="<?= url('loans', ['action' => 'view', 'id' => $l['id']]) ?>" class="btn btn-secondary btn-sm">
                                        Manage
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include RH_TEMPLATES . '/layout/footer.php'; ?>

