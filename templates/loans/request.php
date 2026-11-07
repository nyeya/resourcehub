<?php
$pageTitle = 'Request equipment';
$activePage = 'loans';
include RH_TEMPLATES . '/layout/header.php';
?>

<div class="mb-3">
    <a href="<?= url('loans') ?>" class="text-decoration-none text-secondary small">
        &larr; Back to loans
    </a>
</div>

<div class="row justify-content-center">
    <div class="col-12 col-md-8 col-lg-6">
        <div class="card">
            <div class="card-header">
                <span class="fw-bold" style="font-size: 1.05rem;">New loan request</span>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="<?= url('loans', ['action' => 'request']) ?>">
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label for="asset_id" class="form-label">Equipment <span class="text-danger">*</span></label>
                        <select class="form-select" id="asset_id" name="asset_id" required>
                            <option value="">Select available equipment...</option>
                            <?php foreach ($availableEquipment as $eq): ?>
                                <option value="<?= (int)$eq['id'] ?>" <?= ((int)$selectedAssetId === (int)$eq['id']) ? 'selected' : '' ?>>
                                    <?= e($eq['name']) ?> (<?= e($eq['asset_tag']) ?>) &mdash; <?= e($eq['location_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-sm-6">
                            <label for="start_date" class="form-label">Start date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="start_date" name="start_date" required min="<?= date('Y-m-d') ?>"
                                   value="<?= e($startDate) ?>">
                        </div>
                        <div class="col-sm-6">
                            <label for="end_date" class="form-label">Return date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="end_date" name="end_date" required min="<?= date('Y-m-d') ?>"
                                   value="<?= e($endDate) ?>">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="purpose" class="form-label">Purpose <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="purpose" name="purpose" rows="3" required
                                  placeholder="Describe the task, project or field research..."><?= e($purpose) ?></textarea>
                    </div>

                    <div class="border-top my-4" style="border-color: var(--border-color) !important;"></div>

                    <div class="d-flex justify-content-between align-items-center">
                        <a href="<?= url('loans') ?>" class="btn btn-secondary btn-sm">Cancel</a>
                        <button type="submit" class="rh-action-btn-pill">
                            Submit request &rarr;
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include RH_TEMPLATES . '/layout/footer.php'; ?>

