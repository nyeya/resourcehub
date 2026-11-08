<?php
$pageTitle = 'Access Denied';
http_response_code(403);
include RH_TEMPLATES . '/layout/header.php';
?>

<div class="row justify-content-center py-5">
    <div class="col-12 col-md-8 col-lg-6 text-center">
        <div class="d-inline-flex align-items-center justify-content-center bg-rose-50 text-rose-600 rounded-circle mb-3" style="width: 64px; height: 64px;">
            <i class="bi bi-shield-x fs-1"></i>
        </div>
        <h2 class="fw-bold tracking-tight mb-2 text-dark">403 &mdash; Access Restricted</h2>
        <p class="text-secondary mb-4 small" style="max-width: 420px; margin: 0 auto;">
            You do not possess the required authorization level or ownership privileges to access or perform operations on this record.
        </p>
        <div class="d-flex justify-content-center gap-2">
            <a href="<?= url('dashboard') ?>" class="btn btn-secondary btn-sm">
                <i class="bi bi-grid-1x2 me-1"></i> Return to Dashboard
            </a>
            <a href="<?= url('equipment') ?>" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-search me-1"></i> Browse Equipment
            </a>
        </div>
    </div>
</div>

<?php include RH_TEMPLATES . '/layout/footer.php'; ?>

