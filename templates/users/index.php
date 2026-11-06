<?php
$pageTitle = 'Users';
$activePage = 'users';
include RH_TEMPLATES . '/layout/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <h2 class="fw-bold tracking-tight mb-1 text-white">Users</h2>
        <div class="text-secondary small">Manage accounts, active statuses, and access permissions.</div>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="<?= url('users', ['action' => 'create']) ?>" class="rh-action-btn-pill">
            <i class="bi bi-plus-lg"></i> Add user
        </a>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Registered</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td><span class="text-secondary small">#<?= (int)$u['id'] ?></span></td>
                            <td>
                                <span class="fw-semibold text-white small"><?= e($u['name']) ?></span>
                                <?php if ((int)$u['id'] === Auth::id()): ?>
                                    <span class="badge border ms-1" style="font-size: 0.65rem; border-color: var(--border-color) !important; color: var(--text-primary);">You</span>
                                <?php endif; ?>
                            </td>
                            <td><span class="text-secondary small"><?= e($u['email']) ?></span></td>
                            <td>
                                <span class="text-capitalize small fw-semibold text-white"><?= e($u['role']) ?></span>
                            </td>
                            <td>
                                <?php if (!empty($u['is_active'])): ?>
                                    <span class="badge badge-available"><span class="badge-dot"></span> Active</span>
                                <?php else: ?>
                                    <span class="badge badge-rejected"><span class="badge-dot"></span> Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td><span class="text-secondary small"><?= e(format_date($u['created_at'])) ?></span></td>
                            <td class="text-end">
                                <a href="<?= url('users', ['action' => 'edit', 'id' => $u['id']]) ?>" class="btn btn-secondary btn-sm">
                                    Edit
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include RH_TEMPLATES . '/layout/footer.php'; ?>

