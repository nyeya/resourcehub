<?php
$isEdit = !empty($id);
$pageTitle = $isEdit ? 'Edit User' : 'New User';
$activePage = 'users';
include RH_TEMPLATES . '/layout/header.php';
?>

<div class="mb-3">
    <a href="<?= url('users') ?>" class="text-decoration-none text-secondary small">
        &larr; Back to users
    </a>
</div>

<div class="row justify-content-center">
    <div class="col-12 col-md-8 col-lg-6">
        <div class="card">
            <div class="card-header">
                <span class="fw-bold" style="font-size: 1.05rem;">
                    <?= $isEdit ? ('Edit user &bull; ' . e($user['name'] ?? '')) : 'Create new user' ?>
                </span>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="<?= $isEdit ? url('users', ['action' => 'edit', 'id' => (int)$id]) : url('users', ['action' => 'create']) ?>" autocomplete="off">
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label for="name" class="form-label">Full name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" required
                               value="<?= e($user['name'] ?? '') ?>" placeholder="Jane Doe">
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email address <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="email" name="email" required
                               value="<?= e($user['email'] ?? '') ?>" placeholder="name@organisation.local">
                    </div>

                    <div class="mb-3">
                        <label for="role" class="form-label">Role <span class="text-danger">*</span></label>
                        <select class="form-select" id="role" name="role" required>
                            <option value="staff" <?= (($user['role'] ?? 'staff') === 'staff') ? 'selected' : '' ?>>Staff</option>
                            <option value="coordinator" <?= (($user['role'] ?? '') === 'coordinator') ? 'selected' : '' ?>>Coordinator</option>
                            <option value="administrator" <?= (($user['role'] ?? '') === 'administrator') ? 'selected' : '' ?>>Administrator</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">
                            Password <?= $isEdit ? '(leave empty to keep current)' : '<span class="text-danger">*</span>' ?>
                        </label>
                        <input type="password" class="form-control" id="password" name="password" <?= $isEdit ? '' : 'required' ?>>
                    </div>

                    <div class="mb-4 form-check">
                        <input type="hidden" name="is_active" value="0">
                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1"
                               <?= (!isset($user['is_active']) || !empty($user['is_active'])) ? 'checked' : '' ?>
                               <?= ($isEdit && (int)$id === (int)Auth::id()) ? 'disabled' : '' ?>>
                        <label class="form-check-label small text-secondary" for="is_active">
                            Active account
                        </label>
                        <?php if ($isEdit && (int)$id === (int)Auth::id()): ?>
                            <input type="hidden" name="is_active" value="1">
                            <div class="form-text text-warning small">You cannot deactivate your own account.</div>
                        <?php endif; ?>
                    </div>

                    <div class="border-top my-4" style="border-color: var(--border-color) !important;"></div>

                    <div class="d-flex justify-content-between align-items-center">
                        <a href="<?= url('users') ?>" class="btn btn-secondary btn-sm">Cancel</a>
                        <button type="submit" class="rh-action-btn-pill">
                            <?= $isEdit ? 'Save changes &rarr;' : 'Create user &rarr;' ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include RH_TEMPLATES . '/layout/footer.php'; ?>

