<?php
$pageTitle = 'Report maintenance';
$activePage = 'maintenance';
include RH_TEMPLATES . '/layout/header.php';
?>

<div class="mb-3">
    <a href="<?= url('maintenance') ?>" class="text-decoration-none text-secondary small">
        &larr; Back to maintenance
    </a>
</div>

<div class="row justify-content-center">
    <div class="col-12 col-md-8 col-lg-6">
        <div class="card">
            <div class="card-header">
                <span class="fw-bold" style="font-size: 1.05rem;">Open maintenance ticket</span>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="<?= url('maintenance', ['action' => 'open']) ?>">
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label for="asset_id" class="form-label">Equipment <span class="text-danger">*</span></label>
                        <select class="form-select" id="asset_id" name="asset_id" required>
                            <option value="">Select equipment...</option>
                            <?php foreach ($equipmentList as $eq): ?>
                                <option value="<?= (int)$eq['id'] ?>" <?= ((int)$selectedAssetId === (int)$eq['id']) ? 'selected' : '' ?>>
                                    <?= e($eq['name']) ?> (<?= e($eq['asset_tag']) ?>) &mdash; <?= e($eq['condition']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Equipment must not be actively on loan.</div>
                    </div>

                    <div class="mb-4">
                        <label for="issue_description" class="form-label">Issue description <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="issue_description" name="issue_description" rows="3" required
                                  placeholder="Describe the defect, damage or service required..."><?= e($issueDescription) ?></textarea>
                    </div>

                    <div class="border-top my-4" style="border-color: var(--border-color) !important;"></div>

                    <div class="d-flex justify-content-between align-items-center">
                        <a href="<?= url('maintenance') ?>" class="btn btn-secondary btn-sm">Cancel</a>
                        <button type="submit" class="rh-action-btn-pill">
                            Submit ticket &rarr;
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include RH_TEMPLATES . '/layout/footer.php'; ?>

