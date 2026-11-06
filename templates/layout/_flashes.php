<?php
$flashes = get_flashes();
?>
<?php if (!empty($flashes)): ?>
<div class="rh-flash-container mb-4">
    <?php foreach ($flashes as $f): ?>
        <div class="alert alert-<?= e($f['type']) ?> alert-dismissible fade show d-flex align-items-center gap-2 mb-2 shadow-sm" role="alert">
            <i class="bi bi-info-circle-fill"></i>
            <div><?= e($f['message']) ?></div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

