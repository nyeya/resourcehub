<?php
$pageTitle = 'Equipment';
$activePage = 'equipment';
include RH_TEMPLATES . '/layout/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <h2 class="fw-bold tracking-tight mb-1 text-white">Equipment</h2>
        <div class="text-secondary small">Shared organization tools and devices inventory.</div>
    </div>
    <div class="d-flex align-items-center gap-2">
        <?php if (Auth::isCoordinator()): ?>
            <a href="<?= url('reports', ['action' => 'export_equipment']) ?>" class="btn btn-secondary btn-sm">
                <i class="bi bi-download"></i> Export CSV
            </a>
            <a href="<?= url('equipment', ['action' => 'create']) ?>" class="rh-action-btn-pill">
                <i class="bi bi-plus-lg"></i> Add item
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body p-3">
        <form method="GET" action="index.php" class="row g-2 align-items-center">
            <input type="hidden" name="page" value="equipment">
            <div class="col-12 col-md-3">
                <input type="text" class="form-control" name="search" placeholder="Search by name, tag, model..." value="<?= e($filters['search']) ?>">
            </div>

            <div class="col-6 col-md-2">
                <select name="category_id" class="form-select">
                    <option value="">All categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= (int)$cat['id'] ?>" <?= ($filters['category_id'] == $cat['id']) ? 'selected' : '' ?>>
                            <?= e($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-6 col-md-2">
                <select name="location_id" class="form-select">
                    <option value="">All locations</option>
                    <?php foreach ($locations as $loc): ?>
                        <option value="<?= (int)$loc['id'] ?>" <?= ($filters['location_id'] == $loc['id']) ? 'selected' : '' ?>>
                            <?= e($loc['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-6 col-md-2">
                <select name="condition" class="form-select">
                    <option value="">All conditions</option>
                    <option value="new" <?= ($filters['condition'] === 'new') ? 'selected' : '' ?>>New</option>
                    <option value="good" <?= ($filters['condition'] === 'good') ? 'selected' : '' ?>>Good</option>
                    <option value="fair" <?= ($filters['condition'] === 'fair') ? 'selected' : '' ?>>Fair</option>
                    <option value="damaged" <?= ($filters['condition'] === 'damaged') ? 'selected' : '' ?>>Damaged</option>
                </select>
            </div>

            <div class="col-6 col-md-2">
                <select name="status" class="form-select">
                    <option value="">All statuses</option>
                    <option value="available" <?= $filters['status'] === 'available' ? 'selected' : '' ?>>Available</option>
                    <option value="maintenance" <?= $filters['status'] === 'maintenance' ? 'selected' : '' ?>>In repair</option>
                    <option value="retired" <?= $filters['status'] === 'retired' ? 'selected' : '' ?>>Retired</option>
                </select>
            </div>

            <div class="col-12 col-md-1 d-flex gap-1">
                <button type="submit" class="btn btn-primary btn-sm w-100">
                    Filter
                </button>
                <a href="<?= url('equipment') ?>" class="btn btn-secondary btn-sm" title="Clear">
                    <i class="bi bi-x-lg"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Equipment Table -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Tag</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Location</th>
                        <th>Condition</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($items)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-secondary small">
                                No equipment found matching your criteria.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td>
                                    <span class="font-monospace text-secondary fw-semibold small"><?= e($item['asset_tag']) ?></span>
                                </td>
                                <td>
                                    <a href="<?= url('equipment', ['action' => 'view', 'id' => $item['id']]) ?>" class="fw-bold text-white text-decoration-none">
                                        <?= e($item['name']) ?>
                                    </a>
                                    <?php if (!empty($item['model'])): ?>
                                        <div class="text-secondary" style="font-size: 0.72rem;"><?= e($item['model']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="small text-secondary"><?= e($item['category_name']) ?></td>
                                <td class="small">
                                    <div class="text-white"><?= e($item['location_name']) ?></div>
                                    <div class="text-secondary" style="font-size: 0.72rem;"><?= e($item['location_room']) ?></div>
                                </td>
                                <td>
                                    <span class="badge border text-capitalize" style="border-color: var(--border-subtle) !important; color: var(--text-secondary);"><?= e($item['condition']) ?></span>
                                </td>
                                <td>
                                    <span class="badge badge-<?= e($item['status']) ?>">
                                        <span class="badge-dot"></span>
                                        <?= e(ucfirst(str_replace('_', ' ', $item['status']))) ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="d-inline-flex gap-1">
                                        <a href="<?= url('equipment', ['action' => 'view', 'id' => $item['id']]) ?>" class="btn btn-secondary btn-sm">
                                            View
                                        </a>
                                        <?php if ($item['status'] === 'available'): ?>
                                            <a href="<?= url('loans', ['action' => 'request', 'asset_id' => $item['id']]) ?>" class="btn btn-primary btn-sm">
                                                Request
                                            </a>
                                        <?php endif; ?>
                                        <?php if (Auth::isCoordinator()): ?>
                                            <a href="<?= url('equipment', ['action' => 'edit', 'id' => $item['id']]) ?>" class="btn btn-secondary btn-sm" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination Footer -->
    <?php if ($totalPages > 1 || $totalItems > 0): ?>
        <div class="card-footer d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2 py-3">
            <div class="text-secondary small">
                Showing <strong class="text-white"><?= $startItem ?></strong> to <strong class="text-white"><?= $endItem ?></strong> of <strong class="text-white"><?= $totalItems ?></strong> assets
            </div>
            <?php if ($totalPages > 1): ?>
                <nav aria-label="Equipment pagination">
                    <ul class="pagination pagination-sm mb-0">
                        <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= e(page_url($page - 1, $filters)) ?>" aria-label="Previous">
                                &laquo;
                            </a>
                        </li>
                        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                            <li class="page-item <?= ($p === $page) ? 'active' : '' ?>">
                                <a class="page-link" href="<?= e(page_url($p, $filters)) ?>">
                                    <?= $p ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= e(page_url($page + 1, $filters)) ?>" aria-label="Next">
                                &raquo;
                            </a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php include RH_TEMPLATES . '/layout/footer.php'; ?>
