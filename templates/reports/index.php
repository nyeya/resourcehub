<?php
$pageTitle = 'Reports';
$activePage = 'reports';
include RH_TEMPLATES . '/layout/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <h2 class="fw-bold tracking-tight mb-1 text-white">Reports</h2>
        <div class="text-secondary small">Download data exports and view fleet distribution summaries.</div>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="<?= url('reports', ['action' => 'export_equipment']) ?>" class="btn btn-secondary btn-sm">
            <i class="bi bi-download"></i> Equipment CSV
        </a>
        <a href="<?= url('reports', ['action' => 'export_loans']) ?>" class="rh-action-btn-pill">
            <i class="bi bi-download"></i> Loans CSV
        </a>
    </div>
</div>

<!-- Optional Extension: Small Chart of Loans by Category -->
<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="fw-bold" style="font-size: 1.05rem;">Loans by Category (Distribution Chart)</span>
                <span class="badge border text-secondary small" style="border-color: var(--border-color) !important;">
                    Total: <?= (int)$totalLoansCategory ?> loans
                </span>
            </div>
            <div class="card-body p-4">
                <div class="d-flex flex-column gap-3">
                    <?php 
                    $colors = ['#9d5cf7', '#3b82f6', '#10b981', '#f59e0b'];
                    foreach ($metrics['loans_by_category'] as $idx => $lCat): 
                        $pct = $totalLoansCategory > 0 ? round(($lCat['count'] / $totalLoansCategory) * 100, 1) : 0;
                        $color = $colors[$idx % count($colors)];
                    ?>
                        <div>
                            <div class="d-flex justify-content-between align-items-center mb-1 small">
                                <span class="fw-semibold text-white"><?= e($lCat['name']) ?></span>
                                <span class="text-secondary"><?= (int)$lCat['count'] ?> loans (<?= $pct ?>%)</span>
                            </div>
                            <div class="progress" style="height: 10px; background-color: var(--bg-surface-subtle); border-radius: 999px;">
                                <div class="progress-bar" role="progressbar" style="width: <?= $pct ?>%; background-color: <?= $color ?>; border-radius: 999px;" aria-valuenow="<?= $pct ?>" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- By Category -->
    <div class="col-12 col-md-4">
        <div class="card h-100">
            <div class="card-header">
                <span class="fw-bold" style="font-size: 1.05rem;">By category</span>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <?php foreach ($metrics['by_category'] as $c): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-2 px-3 small">
                            <span class="text-white"><?= e($c['name']) ?></span>
                            <span class="badge border" style="border-color: var(--border-color) !important; color: var(--text-primary);"><?= (int)$c['count'] ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>

    <!-- By Condition -->
    <div class="col-12 col-md-4">
        <div class="card h-100">
            <div class="card-header">
                <span class="fw-bold" style="font-size: 1.05rem;">By condition</span>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <?php foreach ($metrics['by_condition'] as $cond): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-2 px-3 small">
                            <span class="text-capitalize text-white"><?= e($cond['condition']) ?></span>
                            <span class="badge border" style="border-color: var(--border-color) !important; color: var(--text-primary);"><?= (int)$cond['count'] ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>

    <!-- Loans by Status -->
    <div class="col-12 col-md-4">
        <div class="card h-100">
            <div class="card-header">
                <span class="fw-bold" style="font-size: 1.05rem;">Loans by status</span>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <?php foreach ($metrics['loans_by_status'] as $ls): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-2 px-3 small">
                            <span class="badge badge-<?= e($ls['status']) ?>">
                                <span class="badge-dot"></span>
                                <?= e(ucfirst(str_replace('_', ' ', $ls['status']))) ?>
                            </span>
                            <span class="badge border" style="border-color: var(--border-color) !important; color: var(--text-primary);"><?= (int)$ls['count'] ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php include RH_TEMPLATES . '/layout/footer.php'; ?>

