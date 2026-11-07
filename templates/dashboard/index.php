<?php
$pageTitle = 'Overview';
$activePage = 'dashboard';
include RH_TEMPLATES . '/layout/header.php';
?>

<!-- Header Bar: Greeting & Actions -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <div class="text-secondary small fw-medium mb-1">Hi <?= e(Auth::name()) ?>,</div>
        <h2 class="fw-bold tracking-tight mb-0 text-white" style="font-size: 1.75rem;">👋 Welcome back!</h2>
    </div>

    <div class="d-flex align-items-center gap-2">
        <a href="<?= url('equipment') ?>" class="btn btn-secondary btn-sm">
            <i class="bi bi-search"></i>
            <span>Browse equipment</span>
        </a>
        <a href="<?= url('loans', ['action' => 'request']) ?>" class="rh-action-btn-pill">
            <i class="bi bi-plus-lg"></i>
            <span>Request item</span>
        </a>
    </div>
</div>

<!-- Hero Overview Card: Fleet Status -->
<div class="rh-hero-card">
    <div class="rh-hero-header">
        <div class="d-flex align-items-center gap-3">
            <span class="rh-hero-title">Equipment Fleet Status</span>
            <span class="badge badge-available">
                <span class="badge-dot"></span> System Normal
            </span>
        </div>
    </div>

    <!-- Main Value & Wave Chart Row -->
    <div class="row align-items-center g-4">
        <div class="col-12 col-lg-5">
            <div class="rh-hero-display">
                <div class="rh-hero-number"><?= $availabilityRate ?>%</div>
                <div class="rh-trend-badge up">
                    <i class="bi bi-arrow-up-right"></i> Available
                </div>
            </div>
            <div class="text-secondary small fw-medium">
                <strong class="text-white"><?= $availableAssets ?> of <?= $totalAssets ?></strong> equipment items currently ready for loan.
            </div>
        </div>

        <!-- Wave Graphic -->
        <div class="col-12 col-lg-7">
            <div class="rh-wave-chart-wrap">
                <svg class="rh-wave-svg" viewBox="0 0 600 120" preserveAspectRatio="none">
                    <defs>
                        <linearGradient id="purpleGradient" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stop-color="#9d5cf7" stop-opacity="0.35"/>
                            <stop offset="60%" stop-color="#6366f1" stop-opacity="0.10"/>
                            <stop offset="100%" stop-color="#161823" stop-opacity="0.0"/>
                        </linearGradient>
                    </defs>
                    <path class="rh-wave-fill" d="
                        M 0 75
                        C 50 72, 80 88, 130 84
                        C 180 80, 220 52, 280 50
                        C 330 48, 380 78, 430 72
                        C 470 66, 490 28, 540 32
                        C 570 34, 590 55, 600 50
                        L 600 120
                        L 0 120
                        Z" />
                    <path class="rh-wave-stroke" d="
                        M 0 75
                        C 50 72, 80 88, 130 84
                        C 180 80, 220 52, 280 50
                        C 330 48, 380 78, 430 72
                        C 470 66, 490 28, 540 32
                        C 570 34, 590 55, 600 50" />
                </svg>

                <div class="rh-chart-weekdays">
                    <span>Mo</span>
                    <span>Tue</span>
                    <span>Wed</span>
                    <span>Thu</span>
                    <span>Fri</span>
                    <span>Sat</span>
                    <span>Sun</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom Submetrics Strip -->
    <div class="rh-submetrics-strip">
        <div class="rh-submetric-item">
            <span class="rh-submetric-label">Available Items</span>
            <div class="rh-submetric-val-row">
                <span class="rh-submetric-indicator up"><i class="bi bi-arrow-up-right"></i></span>
                <span class="rh-submetric-value"><?= $availableAssets ?></span>
                <span class="text-secondary small ms-1">ready</span>
            </div>
        </div>

        <div class="rh-submetric-item">
            <span class="rh-submetric-label">On Loan</span>
            <div class="rh-submetric-val-row">
                <span class="rh-submetric-indicator down"><i class="bi bi-arrow-right"></i></span>
                <span class="rh-submetric-value"><?= $activeLoans ?></span>
                <span class="text-secondary small ms-1">checked out</span>
            </div>
        </div>

        <div class="rh-submetric-item">
            <span class="rh-submetric-label">In Maintenance</span>
            <div class="rh-submetric-val-row">
                <span class="rh-submetric-indicator up"><i class="bi bi-tools"></i></span>
                <span class="rh-submetric-value"><?= $openMaintenance ?></span>
                <span class="text-secondary small ms-1">repair</span>
            </div>
        </div>

        <div class="rh-submetric-item">
            <span class="rh-submetric-label">Overdue &amp; Pending</span>
            <div class="rh-submetric-val-row">
                <span class="rh-submetric-indicator down"><i class="bi bi-clock-history"></i></span>
                <span class="rh-submetric-value"><?= ($overdueLoans + $pendingRequests) ?></span>
                <span class="text-secondary small ms-1">(<?= $overdueLoans ?> overdue)</span>
            </div>
        </div>
    </div>
</div>

<!-- Overdue Alert Notification (if any) -->
<?php if ($overdueLoans > 0): ?>
    <div class="alert alert-danger d-flex align-items-center justify-content-between mb-4">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-exclamation-triangle-fill fs-5"></i>
            <div>
                <strong>Overdue Notice:</strong> <?= $overdueLoans ?> <?= ($overdueLoans === 1 ? 'item is' : 'items are') ?> past scheduled return date.
            </div>
        </div>
        <a href="<?= url('loans', ['status' => 'overdue']) ?>" class="btn btn-danger btn-sm px-3">
            View overdue &rarr;
        </a>
    </div>
<?php endif; ?>

<!-- Main Dashboard Grid -->
<div class="row g-4">
    <!-- Recent Loans Table -->
    <div class="col-12 col-xl-8">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="fw-bold" style="font-size: 1.05rem;"><?= Auth::isCoordinator() ? 'Recent Loans' : 'My Recent Loans' ?></span>
                <a href="<?= url('loans') ?>" class="text-decoration-none small text-secondary">View all &rsaquo;</a>
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
                                        No recent loan records.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recentLoans as $loan): ?>
                                    <tr>
                                        <td>
                                            <a href="<?= url('equipment', ['action' => 'view', 'id' => $loan['asset_id']]) ?>" class="fw-bold text-white text-decoration-none">
                                                <?= e($loan['asset_name']) ?>
                                            </a>
                                            <div class="font-monospace text-secondary" style="font-size: 0.72rem;"><?= e($loan['asset_tag']) ?></div>
                                        </td>
                                        <?php if (Auth::isCoordinator()): ?>
                                            <td>
                                                <div class="fw-semibold small text-white"><?= e($loan['user_name']) ?></div>
                                                <div class="text-secondary" style="font-size: 0.72rem;"><?= e($loan['user_email']) ?></div>
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

    <!-- Sidebar Cards: Allocation & Actions -->
    <div class="col-12 col-xl-4">
        <div class="d-flex flex-column gap-4">
            
            <!-- Fleet Allocation Rings -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span class="fw-bold" style="font-size: 1.05rem;">Fleet Allocation</span>
                    <a href="<?= url('equipment') ?>" class="text-decoration-none small text-secondary">Detail &rsaquo;</a>
                </div>
                <div class="card-body">
                    <div class="rh-rings-container">
                        <div class="rh-ring-legend">
                            <div class="rh-ring-legend-item">
                                <span class="rh-ring-legend-label">
                                    <span class="rh-ring-dot" style="background-color: #9d5cf7; box-shadow: 0 0 6px #9d5cf7;"></span>
                                    Available
                                </span>
                                <span class="rh-ring-legend-val"><?= $availableAssets ?></span>
                            </div>
                            <div class="rh-ring-legend-item">
                                <span class="rh-ring-legend-label">
                                    <span class="rh-ring-dot" style="background-color: #3b82f6; box-shadow: 0 0 6px #3b82f6;"></span>
                                    On Loan
                                </span>
                                <span class="rh-ring-legend-val"><?= $activeLoans ?></span>
                            </div>
                            <div class="rh-ring-legend-item">
                                <span class="rh-ring-legend-label">
                                    <span class="rh-ring-dot" style="background-color: #f97316; box-shadow: 0 0 6px #f97316;"></span>
                                    In Repair
                                </span>
                                <span class="rh-ring-legend-val"><?= $openMaintenance ?></span>
                            </div>
                            <div class="rh-ring-legend-item">
                                <span class="rh-ring-legend-label">
                                    <span class="rh-ring-dot" style="background-color: #f43f5e; box-shadow: 0 0 6px #f43f5e;"></span>
                                    Overdue
                                </span>
                                <span class="rh-ring-legend-val"><?= $overdueLoans ?></span>
                            </div>
                        </div>

                        <!-- Concentric Rings Graphic -->
                        <div class="d-flex justify-content-center align-items-center" style="width: 140px; height: 140px;">
                            <svg width="130" height="130" viewBox="0 0 130 130">
                                <circle cx="65" cy="65" r="54" stroke="#222533" stroke-width="8" fill="none" />
                                <circle cx="65" cy="65" r="41" stroke="#222533" stroke-width="8" fill="none" />
                                <circle cx="65" cy="65" r="28" stroke="#222533" stroke-width="8" fill="none" />

                                <circle cx="65" cy="65" r="54" stroke="#9d5cf7" stroke-width="8" fill="none"
                                        stroke-linecap="round" stroke-dasharray="339" stroke-dashoffset="<?= max(20, 339 - ($totalAssets > 0 ? (int)(339 * ($availableAssets / $totalAssets)) : 0)) ?>"
                                        transform="rotate(-90 65 65)"
                                        style="filter: drop-shadow(0 0 6px rgba(157, 92, 247, 0.6));" />

                                <circle cx="65" cy="65" r="41" stroke="#3b82f6" stroke-width="8" fill="none"
                                        stroke-linecap="round" stroke-dasharray="257" stroke-dashoffset="<?= max(20, 257 - ($totalAssets > 0 ? (int)(257 * ($activeLoans / $totalAssets)) : 0)) ?>"
                                        transform="rotate(-90 65 65)"
                                        style="filter: drop-shadow(0 0 6px rgba(59, 130, 246, 0.6));" />

                                <circle cx="65" cy="65" r="28" stroke="#f97316" stroke-width="8" fill="none"
                                        stroke-linecap="round" stroke-dasharray="175" stroke-dashoffset="<?= max(20, 175 - ($totalAssets > 0 ? (int)(175 * (($openMaintenance + $overdueLoans) / max(1, $totalAssets))) : 0)) ?>"
                                        transform="rotate(-90 65 65)"
                                        style="filter: drop-shadow(0 0 6px rgba(249, 115, 22, 0.6));" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card">
                <div class="card-header">
                    <span class="fw-bold" style="font-size: 1.05rem;">Quick Actions</span>
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
                            <i class="bi bi-people text-light me-2"></i> Manage users
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Account Details -->
            <div class="card">
                <div class="card-header">
                    <span class="fw-bold" style="font-size: 1.05rem;">Account</span>
                </div>
                <div class="card-body p-3 small">
                    <div class="d-flex justify-content-between py-2 border-bottom" style="border-color: var(--border-color) !important;">
                        <span class="text-secondary">User</span>
                        <span class="fw-semibold text-white"><?= e(Auth::name()) ?></span>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom" style="border-color: var(--border-color) !important;">
                        <span class="text-secondary">Email</span>
                        <span class="text-secondary font-monospace" style="font-size: 0.8rem;"><?= e(Auth::email()) ?></span>
                    </div>
                    <div class="d-flex justify-content-between py-2">
                        <span class="text-secondary">Role</span>
                        <span class="text-capitalize fw-semibold text-white"><?= e(Auth::role()) ?></span>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<?php include RH_TEMPLATES . '/layout/footer.php'; ?>

