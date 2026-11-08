<?php
$pageTitle = 'Not Found';
http_response_code(404);
include RH_TEMPLATES . '/layout/header.php';
?>

<div class="row justify-content-center py-5">
    <div class="col-12 col-md-8 col-lg-6 text-center">
        <div class="d-inline-flex align-items-center justify-content-center bg-slate-100 text-slate-600 rounded-circle mb-3" style="width: 64px; height: 64px;">
            <i class="bi bi-question-circle fs-1"></i>
        </div>
        <h2 class="fw-bold tracking-tight mb-2 text-dark">404 &mdash; Record Not Found</h2>
        <p class="text-secondary mb-4 small" style="max-width: 420px; margin: 0 auto;">
            The requested page or database record could not be found, has expired, or may have been relocated.
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

