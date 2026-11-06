<?php
$isEdit = !empty($id);
$pageTitle = $isEdit ? ('Edit: ' . ($asset['name'] ?? 'Equipment')) : 'Register New Equipment';
$activePage = 'equipment';
include RH_TEMPLATES . '/layout/header.php';
?>

<div class="mb-3">
    <a href="<?= $isEdit ? url('equipment', ['action' => 'view', 'id' => (int)$id]) : url('equipment') ?>" class="text-decoration-none text-secondary small">
        &larr; <?= $isEdit ? 'Back to item details' : 'Back to equipment' ?>
    </a>
</div>

<div class="row justify-content-center">
    <div class="col-12 col-lg-8">
        <div class="card">
            <div class="card-header">
                <span class="fw-bold" style="font-size: 1.05rem;">
                    <?= $isEdit ? ('Edit equipment &bull; ' . e($asset['name'] ?? '')) : 'Register new equipment' ?>
                </span>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="<?= $isEdit ? url('equipment', ['action' => 'edit', 'id' => (int)$id]) : url('equipment', ['action' => 'create']) ?>" autocomplete="off">
                    <?= csrf_field() ?>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="asset_tag" class="form-label">Asset tag <span class="text-danger">*</span></label>
                            <input type="text" class="form-control font-monospace" id="asset_tag" name="asset_tag" required
                                   value="<?= e($asset['asset_tag'] ?? '') ?>" placeholder="e.g. EQ-1025">
                        </div>

                        <div class="col-md-8">
                            <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" required
                                   value="<?= e($asset['name'] ?? '') ?>" placeholder="e.g. Sony Alpha a7 IV">
                        </div>

                        <div class="col-md-6">
                            <label for="category_id" class="form-label">Category <span class="text-danger">*</span></label>
                            <select class="form-select" id="category_id" name="category_id" required>
                                <option value="">Select category...</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= (int)$cat['id'] ?>" <?= ((int)($asset['category_id'] ?? 0) === (int)$cat['id']) ? 'selected' : '' ?>>
                                        <?= e($cat['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label for="location_id" class="form-label">Location <span class="text-danger">*</span></label>
                            <select class="form-select" id="location_id" name="location_id" required>
                                <option value="">Select location...</option>
                                <?php foreach ($locations as $loc): ?>
                                    <option value="<?= (int)$loc['id'] ?>" <?= ((int)($asset['location_id'] ?? 0) === (int)$loc['id']) ? 'selected' : '' ?>>
                                        <?= e($loc['name']) ?> (<?= e($loc['room']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label for="model" class="form-label">Model</label>
                            <input type="text" class="form-control" id="model" name="model"
                                   value="<?= e($asset['model'] ?? '') ?>" placeholder="e.g. ILCE-7M4">
                        </div>

                        <div class="col-md-6">
                            <label for="serial_number" class="form-label">Serial number</label>
                            <input type="text" class="form-control font-monospace" id="serial_number" name="serial_number"
                                   value="<?= e($asset['serial_number'] ?? '') ?>" placeholder="e.g. SN-499102">
                        </div>

                        <div class="col-md-6">
                            <label for="condition" class="form-label">Condition <span class="text-danger">*</span></label>
                            <select class="form-select" id="condition" name="condition" required>
                                <option value="new" <?= (($asset['condition'] ?? '') === 'new') ? 'selected' : '' ?>>New</option>
                                <option value="good" <?= (($asset['condition'] ?? 'good') === 'good') ? 'selected' : '' ?>>Good</option>
                                <option value="fair" <?= (($asset['condition'] ?? '') === 'fair') ? 'selected' : '' ?>>Fair</option>
                                <option value="damaged" <?= (($asset['condition'] ?? '') === 'damaged') ? 'selected' : '' ?>>Damaged</option>
                            </select>
                        </div>

                        <?php if ($isEdit): ?>
                        <div class="col-md-6">
                            <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="available" <?= (($asset['status'] ?? 'available') === 'available') ? 'selected' : '' ?>>Available</option>
                                <option value="maintenance" <?= (($asset['status'] ?? '') === 'maintenance') ? 'selected' : '' ?>>In repair</option>
                                <option value="retired" <?= (($asset['status'] ?? '') === 'retired') ? 'selected' : '' ?>>Retired</option>
                            </select>
                        </div>
                        <?php endif; ?>

                        <div class="col-12">
                            <label for="notes" class="form-label">Notes &amp; accessories</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Included cables, lenses, or handling instructions..."><?= e($asset['notes'] ?? '') ?></textarea>
                        </div>
                    </div>

                    <div class="border-top my-4" style="border-color: var(--border-color) !important;"></div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="<?= $isEdit ? url('equipment', ['action' => 'view', 'id' => (int)$id]) : url('equipment') ?>" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="rh-action-btn-pill">
                            <?= $isEdit ? 'Save changes &rarr;' : 'Register equipment &rarr;' ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include RH_TEMPLATES . '/layout/footer.php'; ?>

