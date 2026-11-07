<?php
$pageTitle = 'Maintenance';
$activePage = 'maintenance';
include RH_TEMPLATES . '/layout/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <h2 class="fw-bold tracking-tight mb-1 text-white">Maintenance</h2>
        <div class="text-secondary small">Repairs, defects, and hardware condition logs.</div>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="<?= url('maintenance', ['action' => 'open']) ?>" class="rh-action-btn-pill">
            <i class="bi bi-tools"></i> Report repair
        </a>
    </div>
</div>

<!-- Tabs -->
<div class="mb-4">
    <div class="rh-tabs">
        <a class="rh-tab-item <?= $currentStatus === '' ? 'active' : '' ?>" href="<?= url('maintenance') ?>">All</a>
        <a class="rh-tab-item <?= $currentStatus === 'open' ? 'active' : '' ?>" href="<?= url('maintenance', ['status' => 'open']) ?>">Open</a>
        <a class="rh-tab-item <?= $currentStatus === 'resolved' ? 'active' : '' ?>" href="<?= url('maintenance', ['status' => 'resolved']) ?>">Resolved</a>
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
                        <th>Opened by</th>
                        <th>Date</th>
                        <th>Issue</th>
                        <th>Status</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($tickets)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-secondary small">
                                No maintenance tickets found.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($tickets as $t): ?>
                            <tr>
                                <td>
                                    <span class="text-secondary small">#<?= (int)$t['id'] ?></span>
                                </td>
                                <td>
                                    <a href="<?= url('equipment', ['action' => 'view', 'id' => $t['asset_id']]) ?>" class="fw-bold text-white text-decoration-none">
                                        <?= e($t['asset_name']) ?>
                                    </a>
                                    <div class="font-monospace text-secondary small"><?= e($t['asset_tag']) ?></div>
                                </td>
                                <td class="small fw-semibold text-white"><?= e($t['opened_by_name']) ?></td>
                                <td class="small text-secondary"><?= e(format_datetime($t['opened_at'])) ?></td>
                                <td class="small text-truncate" style="max-width: 250px;" title="<?= e($t['issue_description']) ?>">
                                    <span class="text-white"><?= e($t['issue_description']) ?></span>
                                    <?php if ($t['status'] === 'resolved' && !empty($t['resolution_notes'])): ?>
                                        <div class="text-secondary" style="font-size: 0.72rem;">
                                            <i class="bi bi-check text-success"></i> <?= e($t['resolution_notes']) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($t['status'] === 'open'): ?>
                                        <span class="badge badge-maintenance"><span class="badge-dot"></span> In repair</span>
                                    <?php else: ?>
                                        <span class="badge badge-available"><span class="badge-dot"></span> Resolved</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <?php if ($t['status'] === 'open'): ?>
                                        <button type="button" class="btn btn-secondary btn-sm" data-bs-toggle="collapse" data-bs-target="#resolveBox-<?= (int)$t['id'] ?>">
                                            Resolve
                                        </button>
                                    <?php else: ?>
                                        <span class="text-secondary small"><?= e(format_datetime($t['closed_at'])) ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php if ($t['status'] === 'open'): ?>
                                <tr class="collapse" id="resolveBox-<?= (int)$t['id'] ?>">
                                    <td colspan="7" class="p-3" style="background-color: var(--bg-surface-subtle);">
                                        <form method="POST" action="<?= url('maintenance') ?>">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
                                            <div class="row g-2 align-items-center">
                                                <div class="col-md-6">
                                                    <input type="text" class="form-control form-control-sm" name="resolution_notes" placeholder="Resolution notes (what was repaired, tested, or replaced)..." required>
                                                </div>
                                                <div class="col-md-3">
                                                    <select name="restored_condition" class="form-select form-select-sm">
                                                        <option value="good" selected>Restored: Good</option>
                                                        <option value="new">Restored: New</option>
                                                        <option value="fair">Restored: Fair</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-3 d-flex gap-2">
                                                    <button type="submit" class="rh-action-btn-pill w-100 justify-content-center">Complete resolution</button>
                                                </div>
                                            </div>
                                        </form>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include RH_TEMPLATES . '/layout/footer.php'; ?>

