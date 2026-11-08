<?php
$pageTitle = $asset['name'];
$activePage = 'equipment';
include RH_TEMPLATES . '/layout/header.php';
?>

<div class="mb-3">
    <a href="<?= url('equipment') ?>" class="text-decoration-none text-secondary small">
        &larr; Back to equipment
    </a>
</div>

<div class="row g-4">
    <div class="col-12 col-lg-8">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <span class="font-monospace small text-secondary"><?= e($asset['asset_tag']) ?></span>
                    <h5 class="fw-bold mb-0 text-dark"><?= e($asset['name']) ?></h5>
                </div>
                <span class="badge badge-<?= e($asset['status']) ?>">
                    <span class="badge-dot"></span>
                    <?= e(ucfirst(str_replace('_', ' ', $asset['status']))) ?>
                </span>
            </div>

            <div class="card-body">
                <div class="row g-3 mb-4">
                    <div class="col-sm-6">
                        <div class="text-secondary small">Category</div>
                        <div class="fw-semibold text-dark"><?= e($asset['category_name']) ?></div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-secondary small">Location</div>
                        <div class="fw-semibold text-dark"><?= e($asset['location_name']) ?> (<?= e($asset['location_room']) ?>)</div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-secondary small">Model</div>
                        <div class="fw-semibold text-dark"><?= e($asset['model'] ?: 'Standard') ?></div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-secondary small">Serial number</div>
                        <div class="font-monospace small text-dark"><?= e($asset['serial_number'] ?: 'N/A') ?></div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-secondary small">Condition</div>
                        <div class="text-capitalize fw-semibold text-dark"><?= e($asset['condition']) ?></div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-secondary small">Added</div>
                        <div class="small text-dark"><?= e(format_date($asset['created_at'])) ?></div>
                    </div>
                </div>

                <?php if (!empty($asset['notes'])): ?>
                    <div class="p-3 rounded border" style="background-color: var(--bg-surface-subtle); border-color: var(--border-color) !important;">
                        <div class="text-secondary small fw-medium mb-1">Notes &amp; Inclusions</div>
                        <div class="small text-dark"><?= nl2br(e($asset['notes'])) ?></div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="card-footer d-flex justify-content-between align-items-center">
                <div class="d-flex gap-2">
                    <?php if (Auth::isCoordinator()): ?>
                        <a href="<?= url('equipment', ['action' => 'edit', 'id' => $asset['id']]) ?>" class="btn btn-secondary btn-sm">
                            Edit
                        </a>
                        <?php if ($asset['status'] === 'available'): ?>
                            <a href="<?= url('maintenance', ['action' => 'create', 'asset_id' => $asset['id']]) ?>" class="btn btn-secondary btn-sm">
                                Report repair
                            </a>
                        <?php endif; ?>
                        <?php if ($asset['status'] !== 'retired'): ?>
                            <form method="POST" action="<?= url('equipment', ['action' => 'view', 'id' => $asset['id']]) ?>" onsubmit="return confirm('Are you sure you want to retire this item? It will no longer be available for loans.');" class="d-inline">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="retire">
                                <button type="submit" class="btn btn-secondary btn-sm text-danger">
                                    Retire
                                </button>
                            </form>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <div>
                    <?php if ($asset['status'] === 'available'): ?>
                        <a href="<?= url('loans', ['action' => 'request', 'asset_id' => $asset['id']]) ?>" class="rh-action-btn-pill">
                            <i class="bi bi-plus-lg"></i> Request item
                        </a>
                    <?php else: ?>
                        <button class="btn btn-secondary btn-sm" disabled>
                            Item <?= e(ucfirst($asset['status'])) ?>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Loan history -->
    <div class="col-12 col-lg-4">
        <div class="card">
            <div class="card-header">
                <span class="fw-bold" style="font-size: 1rem;"><?= Auth::isCoordinator() ? 'Past loans' : 'Your past loans' ?></span>
            </div>
            <div class="card-body p-0">
                <?php if (empty($loans)): ?>
                    <div class="text-center py-4 text-secondary small">
                        <?= Auth::isCoordinator() ? 'No loans recorded.' : 'You have not borrowed this item previously.' ?>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach (array_slice($loans, 0, 5) as $l): ?>
                            <a href="<?= url('loans', ['action' => 'view', 'id' => $l['id']]) ?>" class="list-group-item list-group-item-action p-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="badge badge-<?= e($l['status']) ?>" style="font-size: 0.7rem;">
                                        <span class="badge-dot"></span> <?= e(ucfirst(str_replace('_', ' ', $l['status']))) ?>
                                    </span>
                                    <span class="text-secondary small">#<?= (int)$l['id'] ?></span>
                                </div>
                                <?php if (Auth::isCoordinator()): ?>
                                    <div class="small fw-semibold text-dark"><?= e($l['user_name'] ?? 'Borrower') ?></div>
                                <?php endif; ?>
                                <div class="text-secondary small"><?= e(format_date($l['start_date'])) ?> &rarr; <?= e(format_date($l['end_date'])) ?></div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include RH_TEMPLATES . '/layout/footer.php'; ?>

