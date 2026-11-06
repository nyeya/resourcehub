<?php
$pageTitle = 'Sign In';
include RH_TEMPLATES . '/layout/header.php';
?>

<div class="rh-auth-card mx-auto">
    <div class="rh-auth-header">
        <div class="rh-auth-logo">
            <i class="bi bi-layers-fill"></i>
        </div>
        <h1 class="rh-auth-title">Welcome to ResourceHub</h1>
        <p class="rh-auth-subtitle">Dark Pro &bull; Sign in to access workspace</p>
    </div>

    <?php if (!empty($loginError)): ?>
        <div class="alert alert-danger py-2 px-3 mb-3 small d-flex align-items-center gap-2" role="alert">
            <i class="bi bi-exclamation-circle-fill"></i>
            <div><?= e($loginError) ?></div>
        </div>
    <?php endif; ?>

    <form method="POST" action="<?= url('login') ?>" autocomplete="off" id="loginForm">
        <?= csrf_field() ?>

        <div class="mb-3">
            <label for="email" class="form-label">Email address</label>
            <input type="email" class="form-control" id="email" name="email" value="<?= e($emailValue ?? '') ?>" required autofocus placeholder="name@organisation.local">
        </div>

        <div class="mb-4">
            <label for="password" class="form-label">Password</label>
            <input type="password" class="form-control" id="password" name="password" required placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;">
        </div>

        <button type="submit" class="rh-action-btn-pill w-100 justify-content-center py-2 fs-6">
            Sign in &rarr;
        </button>
    </form>

    <div class="d-flex align-items-center my-4">
        <div class="flex-grow-1" style="height: 1px; background: rgba(255, 255, 255, 0.08);"></div>
        <span class="px-2 text-secondary small" style="font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.08em; font-weight: 700;">Demo Accounts</span>
        <div class="flex-grow-1" style="height: 1px; background: rgba(255, 255, 255, 0.08);"></div>
    </div>

    <!-- Demo Account Tiles -->
    <div class="d-flex flex-column gap-2">
        <button type="button" class="rh-sso-card" onclick="selectRole('admin@resourcehub.local', 'Password123!')">
            <div class="rh-sso-user">
                <div class="rh-sso-avatar admin">A</div>
                <div>
                    <div class="rh-sso-name">Alice Admin</div>
                    <div class="rh-sso-meta">Administrator &bull; admin@...</div>
                </div>
            </div>
            <i class="bi bi-arrow-right rh-sso-arrow"></i>
        </button>

        <button type="button" class="rh-sso-card" onclick="selectRole('coordinator@resourcehub.local', 'Password123!')">
            <div class="rh-sso-user">
                <div class="rh-sso-avatar coord">C</div>
                <div>
                    <div class="rh-sso-name">Charlie Coordinator</div>
                    <div class="rh-sso-meta">Coordinator &bull; coordinator@...</div>
                </div>
            </div>
            <i class="bi bi-arrow-right rh-sso-arrow"></i>
        </button>

        <button type="button" class="rh-sso-card" onclick="selectRole('staff1@resourcehub.local', 'Password123!')">
            <div class="rh-sso-user">
                <div class="rh-sso-avatar staff">S</div>
                <div>
                    <div class="rh-sso-name">Sarah Staff</div>
                    <div class="rh-sso-meta">Staff Researcher &bull; staff1@...</div>
                </div>
            </div>
            <i class="bi bi-arrow-right rh-sso-arrow"></i>
        </button>
    </div>
</div>

<script>
function selectRole(email, password) {
    document.getElementById('email').value = email;
    document.getElementById('password').value = password;
    document.getElementById('loginForm').submit();
}
</script>

<?php include RH_TEMPLATES . '/layout/footer.php'; ?>

