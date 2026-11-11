<?php
$pageTitle = 'Database Backup & Restore';
$activePage = 'backup';
include RH_TEMPLATES . '/layout/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <h2 class="fw-bold tracking-tight mb-1 text-dark">Database Backup &amp; Restore</h2>
        <div class="text-secondary small">Create snapshots, download standard SQL archives, and restore system state with safety confirmation.</div>
    </div>
    <div class="d-flex flex-wrap align-items-center gap-2">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#createSnapshotModal">
            <i class="bi bi-camera-fill me-1"></i> Create Snapshot
        </button>
        <a href="<?= url('backup', ['action' => 'download']) ?>" class="rh-action-btn-pill">
            <i class="bi bi-download me-1"></i> Download SQL Backup
        </a>
    </div>
</div>

<!-- Database & System Metrics Cards -->
<div class="row g-3 mb-4">
    <div class="col-12 col-md-4">
        <div class="card h-100">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-secondary small fw-semibold text-uppercase">Target Database</span>
                    <span class="badge badge-available"><span class="badge-dot"></span> Online</span>
                </div>
                <div class="fs-4 fw-bold text-dark font-monospace mb-1"><?= e($dbStats['database_name']) ?></div>
                <div class="text-secondary small">
                    <i class="bi bi-hdd-network me-1"></i> <?= e($dbStats['host']) ?>:<?= e($dbStats['port']) ?>
                    <?php if (!empty($dbStats['db_size'])): ?>
                        &bull; <span class="fw-medium text-dark"><?= e($dbStats['db_size']) ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card h-100">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-secondary small fw-semibold text-uppercase">Database Engine</span>
                    <i class="bi bi-database text-secondary"></i>
                </div>
                <div class="fs-5 fw-bold text-dark mb-1">PostgreSQL</div>
                <div class="text-secondary small text-truncate" title="<?= e($dbStats['pg_version']) ?>">
                    <?= e($dbStats['pg_version']) ?>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card h-100">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-secondary small fw-semibold text-uppercase">Live Records</span>
                    <i class="bi bi-layers text-secondary"></i>
                </div>
                <div class="d-flex flex-wrap gap-2 pt-1">
                    <span class="badge border bg-light text-dark font-monospace" style="border-color: var(--border-color) !important;">
                        <strong><?= (int)$dbStats['counts']['equipment'] ?></strong> Assets
                    </span>
                    <span class="badge border bg-light text-dark font-monospace" style="border-color: var(--border-color) !important;">
                        <strong><?= (int)$dbStats['counts']['loans'] ?></strong> Loans
                    </span>
                    <span class="badge border bg-light text-dark font-monospace" style="border-color: var(--border-color) !important;">
                        <strong><?= (int)$dbStats['counts']['users'] ?></strong> Users
                    </span>
                    <span class="badge border bg-light text-dark font-monospace" style="border-color: var(--border-color) !important;">
                        <strong><?= (int)$dbStats['counts']['maintenance'] ?></strong> Repairs
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Left Column: Available Snapshots -->
    <div class="col-12 col-lg-8">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between py-3">
                <div>
                    <span class="fw-bold text-dark">Server Backup Snapshots</span>
                    <span class="text-secondary small ms-2">(<?= count($backups) ?> saved)</span>
                </div>
            </div>
            <div class="card-body p-0">
                <?php if (empty($backups)): ?>
                    <div class="p-5 text-center text-secondary">
                        <i class="bi bi-archive fs-1 d-block mb-3 opacity-50"></i>
                        <p class="mb-2 fw-medium text-dark">No local snapshots saved yet.</p>
                        <p class="small text-secondary mb-3">Create your first server snapshot or download an immediate SQL backup archive.</p>
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#createSnapshotModal">
                            <i class="bi bi-camera-fill me-1"></i> Create Snapshot
                        </button>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table mb-0 align-middle">
                            <thead>
                                <tr>
                                    <th>Snapshot Archive</th>
                                    <th>Created</th>
                                    <th>Size</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($backups as $b): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <i class="bi bi-file-earmark-code text-secondary fs-5"></i>
                                                <span class="fw-medium text-dark font-monospace small"><?= e($b['filename']) ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="text-secondary small"><?= e($b['created_human']) ?></span>
                                        </td>
                                        <td>
                                            <span class="badge border bg-light text-dark font-monospace" style="font-size: 0.72rem; border-color: var(--border-color) !important;">
                                                <?= e($b['size_formatted']) ?>
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <div class="btn-group btn-group-sm">
                                                <a href="<?= url('backup', ['action' => 'download', 'file' => $b['filename']]) ?>" class="btn btn-secondary btn-sm" title="Download SQL file">
                                                    <i class="bi bi-download"></i>
                                                </a>
                                                <button type="button" class="btn btn-secondary btn-sm text-danger" title="Restore this snapshot"
                                                        onclick="openRestoreModal('server', '<?= e($b['filename']) ?>', '<?= e($b['size_formatted']) ?>')">
                                                    <i class="bi bi-arrow-counterclockwise"></i> Restore
                                                </button>
                                                <button type="button" class="btn btn-secondary btn-sm text-secondary" title="Delete snapshot"
                                                        onclick="openDeleteModal('<?= e($b['filename']) ?>')">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Right Column: Restore from External File -->
    <div class="col-12 col-lg-4">
        <div class="card mb-4">
            <div class="card-header py-3">
                <span class="fw-bold text-dark">Restore from SQL File</span>
            </div>
            <div class="card-body p-4">
                <p class="small text-secondary mb-3">
                    Upload an external PostgreSQL SQL dump file to restore into the active database.
                </p>

                <div class="alert alert-warning py-2 px-3 small d-flex align-items-center gap-2 mb-3">
                    <i class="bi bi-exclamation-triangle-fill flex-shrink-0 text-warning fs-5"></i>
                    <div>Restoration replaces all existing records in the database.</div>
                </div>

                <form id="uploadRestoreForm" method="POST" action="<?= url('backup', ['action' => 'restore']) ?>" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <input type="hidden" name="source" value="upload">
                    <input type="hidden" name="confirm_restore" id="uploadConfirmRestoreInput" value="0">
                    <input type="hidden" name="confirm_text" id="uploadConfirmTextInput" value="">

                    <div class="mb-3">
                        <label for="backup_file" class="form-label small fw-semibold">SQL Archive (.sql) <span class="text-danger">*</span></label>
                        <input class="form-control form-control-sm" type="file" id="backup_file" name="backup_file" accept=".sql" required>
                    </div>

                    <button type="button" class="btn btn-danger btn-sm w-100" onclick="initiateUploadRestore()">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Upload &amp; Restore
                    </button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header py-3">
                <span class="fw-bold text-dark">CLI Disaster Recovery</span>
            </div>
            <div class="card-body p-4">
                <p class="small text-secondary mb-2">
                    You can also create or restore dumps directly from the shell:
                </p>
                <div class="bg-light p-2 rounded border small font-monospace mb-2 text-dark user-select-all" style="font-size: 0.72rem;">
                    pg_dump -U <?= e($dbStats['username'] ?? 'resourcehub_app') ?> -d <?= e($dbStats['database_name'] ?? 'resourcehub') ?> -f backup.sql
                </div>
                <div class="bg-light p-2 rounded border small font-monospace text-dark user-select-all" style="font-size: 0.72rem;">
                    psql -U <?= e($dbStats['username'] ?? 'resourcehub_app') ?> -d <?= e($dbStats['database_name'] ?? 'resourcehub') ?> -f backup.sql
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal 1: Create Snapshot Modal -->
<div class="modal fade" id="createSnapshotModal" tabindex="-1" aria-labelledby="createSnapshotModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="<?= url('backup', ['action' => 'create']) ?>">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="createSnapshotModalLabel">
                        <i class="bi bi-camera-fill me-2 text-primary"></i> Create Server Snapshot
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="small text-secondary mb-3">
                        A full PostgreSQL snapshot of <strong><?= e($dbStats['database_name'] ?? 'resourcehub') ?></strong> will be saved to the protected server backups folder.
                    </p>
                    <div class="mb-3">
                        <label for="backup_label" class="form-label small fw-semibold">Snapshot Label (Optional)</label>
                        <input type="text" class="form-control form-control-sm" id="backup_label" name="backup_label" placeholder="e.g. pre_quarterly_audit">
                        <div class="form-text small">Only letters, numbers, hyphens, and underscores allowed.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="rh-action-btn-pill">
                        <i class="bi bi-check-lg me-1"></i> Create Snapshot Now
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal 2: Mandatory Confirmation Modal for Database Restoration -->
<div class="modal fade" id="confirmRestoreModal" tabindex="-1" aria-labelledby="confirmRestoreModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-danger">
            <form id="serverRestoreForm" method="POST" action="<?= url('backup', ['action' => 'restore']) ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="source" id="restoreSource" value="server">
                <input type="hidden" name="filename" id="restoreFilename" value="">

                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title fw-bold d-flex align-items-center" id="confirmRestoreModalLabel">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i> Confirm Database Restoration
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="alert alert-danger py-2 px-3 small mb-3">
                        <strong>CRITICAL WARNING:</strong> Restoring this backup will permanently overwrite all tables in <strong><?= e($dbStats['database_name'] ?? 'resourcehub') ?></strong>.
                        Any loans, equipment modifications, or user changes recorded after this backup was taken will be replaced.
                    </div>

                    <div class="mb-3 p-3 bg-light rounded border">
                        <div class="text-secondary small">Target Backup Source:</div>
                        <div class="fw-bold font-monospace text-dark text-break" id="restoreTargetLabel">filename.sql</div>
                        <div class="text-secondary small mt-1" id="restoreTargetSize">Size: 0 KB</div>
                    </div>

                    <div class="mb-3">
                        <label for="confirmationInput" class="form-label small fw-semibold">
                            Type <span class="badge bg-danger">RESTORE</span> in capital letters to confirm:
                        </label>
                        <input type="text" class="form-control font-monospace" id="confirmationInput" name="confirm_text" autocomplete="off" placeholder="RESTORE" oninput="checkConfirmationMatch()">
                    </div>

                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="confirmationCheckbox" name="confirm_restore" value="1" onchange="checkConfirmationMatch()">
                        <label class="form-check-label small text-secondary" for="confirmationCheckbox">
                            I understand that this action is irreversible and will replace the current database state.
                        </label>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger btn-sm" id="executeRestoreBtn" disabled>
                        <i class="bi bi-exclamation-octagon-fill me-1"></i> Proceed with Restoration
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal 3: Delete Snapshot Confirmation -->
<div class="modal fade" id="deleteSnapshotModal" tabindex="-1" aria-labelledby="deleteSnapshotModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <form method="POST" action="<?= url('backup', ['action' => 'delete']) ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="filename" id="deleteFilename" value="">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="deleteSnapshotModalLabel">Delete Snapshot</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-3">
                    <p class="small text-secondary mb-0">
                        Are you sure you want to permanently delete snapshot <strong class="text-dark font-monospace" id="deleteFilenameLabel"></strong>?
                    </p>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let currentRestoreMode = 'server';

function openRestoreModal(source, filename, sizeFormatted) {
    currentRestoreMode = source;
    document.getElementById('restoreSource').value = source;
    document.getElementById('restoreFilename').value = filename;
    document.getElementById('restoreTargetLabel').textContent = filename;
    document.getElementById('restoreTargetSize').textContent = sizeFormatted ? ('Archive size: ' + sizeFormatted) : '';
    
    // Reset confirmation inputs
    document.getElementById('confirmationInput').value = '';
    document.getElementById('confirmationCheckbox').checked = false;
    document.getElementById('executeRestoreBtn').disabled = true;

    const modal = new bootstrap.Modal(document.getElementById('confirmRestoreModal'));
    modal.show();
}

function initiateUploadRestore() {
    const fileInput = document.getElementById('backup_file');
    if (!fileInput.files || fileInput.files.length === 0) {
        alert('Please select a .sql file to upload.');
        return;
    }
    const file = fileInput.files[0];
    const sizeKb = (file.size / 1024).toFixed(1) + ' KB';
    openRestoreModal('upload', file.name, sizeKb);
}

function checkConfirmationMatch() {
    const textVal = (document.getElementById('confirmationInput').value || '').trim().toUpperCase();
    const checked = document.getElementById('confirmationCheckbox').checked;
    const btn = document.getElementById('executeRestoreBtn');

    // Both typing RESTORE and checking the box, or either provides explicit confirmation
    const isConfirmed = (textVal === 'RESTORE') && checked;
    btn.disabled = !isConfirmed;
}

// Hook restore form submit to handle upload vs server form submission
document.getElementById('serverRestoreForm').addEventListener('submit', function(e) {
    if (currentRestoreMode === 'upload') {
        e.preventDefault();
        // Transfer confirmation to the upload form and submit it
        document.getElementById('uploadConfirmRestoreInput').value = '1';
        document.getElementById('uploadConfirmTextInput').value = document.getElementById('confirmationInput').value;
        document.getElementById('uploadRestoreForm').submit();
    }
});

function openDeleteModal(filename) {
    document.getElementById('deleteFilename').value = filename;
    document.getElementById('deleteFilenameLabel').textContent = filename;
    const modal = new bootstrap.Modal(document.getElementById('deleteSnapshotModal'));
    modal.show();
}
</script>

<?php include RH_TEMPLATES . '/layout/footer.php'; ?>

