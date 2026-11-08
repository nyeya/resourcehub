<?php
$pageTitle = 'Overview';
$activePage = 'dashboard';
include RH_TEMPLATES . '/layout/header.php';
?>

<!-- Header -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <div class="text-secondary small fw-medium mb-1">Hi <?= e(Auth::name()) ?>,</div>
        <h2 class="fw-bold tracking-tight mb-0 text-dark" style="font-size: 1.45rem;">👋 Welcome back!</h2>
    </div>

    <div class="d-flex align-items-center gap-2">
        <a href="<?= url('equipment') ?>" class="rh-pill-btn">
            <i class="bi bi-search"></i>
            <span>Browse equipment</span>
        </a>
        <a href="<?= url('loans', ['action' => 'request']) ?>" class="rh-action-btn-pill">
            <i class="bi bi-plus-lg"></i>
            <span>Request item</span>
        </a>
    </div>
</div>

<!-- Overdue alert -->
<?php if (!empty($overdueLoans) && $overdueLoans > 0): ?>
    <div class="alert alert-danger d-flex align-items-center justify-content-between mb-4 shadow-sm" style="border-radius: var(--radius-lg);">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-exclamation-triangle-fill fs-5"></i>
            <div>
                <strong>Overdue Notice:</strong> <?= (int)$overdueLoans ?> <?= ($overdueLoans === 1 ? 'item is' : 'items are') ?> past scheduled return date.
            </div>
        </div>
        <a href="<?= url('loans', ['status' => 'overdue']) ?>" class="btn btn-danger btn-sm px-3" style="border-radius: var(--radius-full);">
            View overdue &rarr;
        </a>
    </div>
<?php endif; ?>

<!-- Dashboard content -->
<div class="row g-4 mb-4">

    <!-- Main area (9 cols) -->
    <div class="col-12 col-xl-9 col-lg-8">
        <div class="d-flex flex-column gap-4">

            <!-- KPI metrics -->
            <div class="row g-3">
                <div class="col-12 col-sm-6 col-md-3">
                    <div class="rh-kpi-card">
                        <div class="rh-kpi-header">
                            <span class="rh-kpi-label">Total Fleet</span>
                            <div class="rh-kpi-icon blue"><i class="bi bi-box-seam"></i></div>
                        </div>
                        <div class="rh-kpi-value"><?= (int)$totalAssets ?></div>
                        <div class="rh-kpi-subtext">
                            <span><?= count($categories) ?> categories tracked</span>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-sm-6 col-md-3">
                    <div class="rh-kpi-card">
                        <div class="rh-kpi-header">
                            <span class="rh-kpi-label">Available Now</span>
                            <div class="rh-kpi-icon green"><i class="bi bi-check2-circle"></i></div>
                        </div>
                        <div class="rh-kpi-value"><?= (int)$availableAssets ?></div>
                        <div class="rh-kpi-subtext">
                            <span class="badge badge-available">
                                <span class="badge-dot"></span> <?= $availabilityRate ?>% ready
                            </span>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-sm-6 col-md-3">
                    <div class="rh-kpi-card">
                        <div class="rh-kpi-header">
                            <span class="rh-kpi-label">On Loan</span>
                            <div class="rh-kpi-icon peach"><i class="bi bi-arrow-left-right"></i></div>
                        </div>
                        <div class="rh-kpi-value"><?= (int)$activeLoans ?></div>
                        <div class="rh-kpi-subtext">
                            <span>In active circulation</span>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-sm-6 col-md-3">
                    <div class="rh-kpi-card">
                        <div class="rh-kpi-header">
                            <span class="rh-kpi-label">Attention Needed</span>
                            <div class="rh-kpi-icon rose"><i class="bi bi-tools"></i></div>
                        </div>
                        <div class="rh-kpi-value"><?= ($openMaintenance + $overdueLoans) ?></div>
                        <div class="rh-kpi-subtext">
                            <span><?= $openMaintenance ?> repair, <?= $overdueLoans ?> overdue</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent loans table -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <span class="fw-bold" style="font-size: 0.98rem;"><?= Auth::isCoordinator() ? 'Recent Loans' : 'My Recent Loans' ?></span>
                        <div class="text-secondary small font-normal">Tracking of assets across organization.</div>
                    </div>
                    <a href="<?= url('loans') ?>" class="rh-pill-btn">View all &rsaquo;</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <?php if (Auth::isCoordinator()): ?>
                                        <th>Borrower</th>
                                    <?php endif; ?>
                                    <th>Dates</th>
                                    <th>Status</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recentLoans)): ?>
                                    <tr>
                                        <td colspan="<?= Auth::isCoordinator() ? '5' : '4' ?>" class="text-center py-5 text-secondary small">
                                            No recent loan records found.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($recentLoans as $loan): ?>
                                        <tr>
                                            <td>
                                                <a href="<?= url('equipment', ['action' => 'view', 'id' => $loan['asset_id']]) ?>" class="fw-bold text-dark text-decoration-none">
                                                    <?= e($loan['asset_name']) ?>
                                                </a>
                                                <div class="font-monospace text-secondary" style="font-size: 0.72rem;"><?= e($loan['asset_tag']) ?></div>
                                            </td>
                                            <?php if (Auth::isCoordinator()): ?>
                                                <td>
                                                    <div class="d-flex align-items-center gap-2">
                                                        <div class="rh-avatar" style="width: 26px; height: 26px; font-size: 0.72rem;">
                                                            <?= strtoupper(substr($loan['user_name'] ?? 'U', 0, 1)) ?>
                                                        </div>
                                                        <div>
                                                            <div class="fw-semibold small text-dark"><?= e($loan['user_name']) ?></div>
                                                            <div class="text-secondary" style="font-size: 0.7rem;"><?= e($loan['user_email']) ?></div>
                                                        </div>
                                                    </div>
                                                </td>
                                            <?php endif; ?>
                                            <td class="small text-secondary">
                                                <div><?= e(format_date($loan['start_date'])) ?> &rarr; <?= e(format_date($loan['end_date'])) ?></div>
                                                <?php if (!empty($loan['is_overdue'])): ?>
                                                    <span class="badge badge-overdue mt-1"><span class="badge-dot"></span> Overdue</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge badge-<?= e($loan['status']) ?>">
                                                    <span class="badge-dot"></span>
                                                    <?= e(ucfirst(str_replace('_', ' ', $loan['status']))) ?>
                                                </span>
                                            </td>
                                            <td class="text-end">
                                                <a href="<?= url('loans', ['action' => 'view', 'id' => $loan['id']]) ?>" class="btn btn-secondary btn-sm">
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

        </div>
    </div>

    <!-- Side panel (3 cols) -->
    <div class="col-12 col-xl-3 col-lg-4">
        <div class="d-flex flex-column gap-4">

            <!-- Quick actions -->
            <div class="card">
                <div class="card-header">
                    <span class="fw-bold" style="font-size: 0.98rem;">Quick Actions</span>
                </div>
                <div class="card-body p-3 d-flex flex-column gap-2">
                    <a href="<?= url('loans', ['action' => 'request']) ?>" class="btn btn-secondary justify-content-start w-100 text-start py-2">
                        <i class="bi bi-plus-circle text-primary me-2"></i> Request an item
                    </a>
                    <a href="<?= url('equipment') ?>" class="btn btn-secondary justify-content-start w-100 text-start py-2">
                        <i class="bi bi-search text-secondary me-2"></i> Search equipment
                    </a>
                    <?php if (Auth::isCoordinator()): ?>
                        <a href="<?= url('equipment', ['action' => 'create']) ?>" class="btn btn-secondary justify-content-start w-100 text-start py-2">
                            <i class="bi bi-box-seam text-success me-2"></i> Add new equipment
                        </a>
                        <a href="<?= url('maintenance', ['action' => 'create']) ?>" class="btn btn-secondary justify-content-start w-100 text-start py-2">
                            <i class="bi bi-wrench text-warning me-2"></i> Report maintenance
                        </a>
                        <a href="<?= url('reports') ?>" class="btn btn-secondary justify-content-start w-100 text-start py-2">
                            <i class="bi bi-bar-chart text-info me-2"></i> Download reports
                        </a>
                    <?php endif; ?>
                    <?php if (Auth::isAdministrator()): ?>
                        <a href="<?= url('users') ?>" class="btn btn-secondary justify-content-start w-100 text-start py-2">
                            <i class="bi bi-people text-primary me-2"></i> Manage users
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Current account -->
            <div class="card">
                <div class="card-header">
                    <span class="fw-bold" style="font-size: 0.95rem;">Current Account</span>
                </div>
                <div class="card-body p-3 small">
                    <div class="d-flex justify-content-between py-2 border-bottom" style="border-color: var(--border-subtle) !important;">
                        <span class="text-secondary">Logged in as</span>
                        <span class="fw-semibold text-dark"><?= e(Auth::name()) ?></span>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom" style="border-color: var(--border-subtle) !important;">
                        <span class="text-secondary">Email</span>
                        <span class="text-secondary font-monospace" style="font-size: 0.75rem;"><?= e(Auth::email()) ?></span>
                    </div>
                    <div class="d-flex justify-content-between py-2">
                        <span class="text-secondary">Assigned Role</span>
                        <span class="badge badge-approved text-capitalize"><?= e(Auth::role()) ?></span>
                    </div>
                </div>
            </div>

        </div>
    </div>

</div>

<?php include RH_TEMPLATES . '/layout/footer.php'; ?>
