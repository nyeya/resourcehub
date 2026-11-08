<?php
$activePage = $activePage ?? '';

// Quick counts for the sidebar badges
$sidebarStats = null;
if (Auth::check()) {
    try {
        $eqModel = new Equipment();
        $sidebarStats = $eqModel->getDashboardStats();
    } catch (Exception $t) {
        $sidebarStats = null;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <base href="<?= base_url() ?>">
    <title><?= isset($pageTitle) ? e($pageTitle) . ' &bull; ' : '' ?>ResourceHub</title>
    <link rel="icon" type="image/png" href="assets/img/logo.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/app.css">
</head>
<body>

<?php if (Auth::check()): ?>
<div class="rh-app">
    <script>
        // Immediately restore collapsed sidebar state on desktop to avoid flash
        if (window.innerWidth >= 768 && localStorage.getItem('rh_sidebar_collapsed') === '1') {
            document.currentScript.parentElement.classList.add('is-collapsed');
        }
    </script>

    <!-- Sidebar -->
    <aside class="rh-sidebar" id="rhSidebar">
        <!-- Brand -->
        <div class="rh-sidebar-header">
            <a class="rh-workspace-switcher" href="<?= url('dashboard') ?>" title="ResourceHub" onclick="if(document.querySelector('.rh-app')?.classList.contains('is-collapsed')){toggleSidebar(); return false;}">
                <div class="rh-workspace-avatar">
                    <img src="assets/img/logo-mark.png" alt="ResourceHub Logo" class="rh-workspace-logo">
                </div>
                <div class="rh-workspace-info">
                    <span class="rh-workspace-name">ResourceHub</span>
                    <span class="rh-workspace-tier">Equipment Management</span>
                </div>
            </a>
            <button class="rh-sidebar-collapse-toggle d-none d-md-flex" type="button" onclick="toggleSidebar()" aria-label="Toggle sidebar" title="Toggle sidebar">
                <i class="bi bi-chevron-left"></i>
            </button>
            <button class="rh-sidebar-close-btn d-md-none" type="button" onclick="toggleSidebar()" aria-label="Close sidebar" title="Close sidebar">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <!-- Navigation -->
        <nav class="rh-sidebar-nav">
            <div class="rh-nav-group-title">Main</div>
            <a class="rh-sidebar-link <?= $activePage === 'dashboard' ? 'active' : '' ?>" href="<?= url('dashboard') ?>" title="Overview">
                <span class="rh-link-content">
                    <i class="bi bi-grid-fill"></i>
                    <span class="rh-link-text">Overview</span>
                </span>
                <i class="bi bi-chevron-right rh-sidebar-chevron"></i>
            </a>

            <a class="rh-sidebar-link <?= $activePage === 'equipment' ? 'active' : '' ?>" href="<?= url('equipment') ?>" title="Equipment">
                <span class="rh-link-content">
                    <i class="bi bi-box-seam-fill"></i>
                    <span class="rh-link-text">Equipment</span>
                </span>
                <?php if (!empty($sidebarStats['total_assets'])): ?>
                    <span class="rh-badge-count"><?= (int)$sidebarStats['total_assets'] ?></span>
                <?php else: ?>
                    <i class="bi bi-chevron-right rh-sidebar-chevron"></i>
                <?php endif; ?>
            </a>

            <a class="rh-sidebar-link <?= $activePage === 'loans' ? 'active' : '' ?>" href="<?= url('loans') ?>" title="<?= Auth::isCoordinator() ? 'Loans' : 'My Loans' ?>">
                <span class="rh-link-content">
                    <i class="bi bi-arrow-left-right"></i>
                    <span class="rh-link-text"><?= Auth::isCoordinator() ? 'Loans' : 'My Loans' ?></span>
                </span>
                <?php if (!empty($sidebarStats['overdue_loans']) && $sidebarStats['overdue_loans'] > 0): ?>
                    <span class="rh-badge-count is-alert"><?= (int)$sidebarStats['overdue_loans'] ?></span>
                <?php elseif (!empty($sidebarStats['active_loans'])): ?>
                    <span class="rh-badge-count"><?= (int)$sidebarStats['active_loans'] ?></span>
                <?php else: ?>
                    <i class="bi bi-chevron-right rh-sidebar-chevron"></i>
                <?php endif; ?>
            </a>

            <?php if (Auth::isCoordinator()): ?>
            <div class="rh-nav-group-title">Operations</div>
            <a class="rh-sidebar-link <?= $activePage === 'maintenance' ? 'active' : '' ?>" href="<?= url('maintenance') ?>" title="Maintenance">
                <span class="rh-link-content">
                    <i class="bi bi-tools"></i>
                    <span class="rh-link-text">Maintenance</span>
                </span>
                <?php if (!empty($sidebarStats['open_maintenance']) && $sidebarStats['open_maintenance'] > 0): ?>
                    <span class="rh-badge-count is-alert"><?= (int)$sidebarStats['open_maintenance'] ?></span>
                <?php else: ?>
                    <i class="bi bi-chevron-right rh-sidebar-chevron"></i>
                <?php endif; ?>
            </a>
            <a class="rh-sidebar-link <?= $activePage === 'reports' ? 'active' : '' ?>" href="<?= url('reports') ?>" title="Reports">
                <span class="rh-link-content">
                    <i class="bi bi-pie-chart-fill"></i>
                    <span class="rh-link-text">Reports</span>
                </span>
                <i class="bi bi-chevron-right rh-sidebar-chevron"></i>
            </a>
            <?php endif; ?>

            <?php if (Auth::isAdministrator()): ?>
            <div class="rh-nav-group-title">Administration</div>
            <a class="rh-sidebar-link <?= $activePage === 'users' ? 'active' : '' ?>" href="<?= url('users') ?>" title="Users">
                <span class="rh-link-content">
                    <i class="bi bi-people-fill"></i>
                    <span class="rh-link-text">Users</span>
                </span>
                <i class="bi bi-chevron-right rh-sidebar-chevron"></i>
            </a>
            <?php endif; ?>
        </nav>

        <!-- User profile -->
        <div class="rh-sidebar-footer">
            <div class="rh-user-profile" title="<?= e(Auth::name()) ?> &bull; <?= e(Auth::role()) ?>">
                <div class="rh-user-profile-inner">
                    <div class="rh-avatar"><?= strtoupper(substr(Auth::name() ?? 'U', 0, 1)) ?></div>
                    <div class="rh-user-details">
                        <span class="rh-user-fullname"><?= e(Auth::name()) ?></span>
                        <span class="rh-user-role"><?= e(Auth::role()) ?></span>
                    </div>
                </div>
                <i class="bi bi-chevron-expand rh-user-chevron"></i>
            </div>
        </div>
    </aside>

    <!-- Mobile backdrop -->
    <div class="rh-sidebar-backdrop" id="rhSidebarBackdrop" onclick="toggleSidebar()"></div>

    <!-- Main content container -->
    <div class="rh-main-wrapper">
        <!-- Topbar -->
        <header class="rh-topbar">
            <!-- Breadcrumbs -->
            <div class="d-flex align-items-center gap-2">
                <button class="rh-mobile-menu-btn d-md-none" type="button" onclick="toggleSidebar()" aria-label="Open sidebar navigation" title="Open navigation">
                    <i class="bi bi-list"></i>
                </button>
                
                <nav class="rh-breadcrumb" aria-label="breadcrumb">
                    <a href="<?= url('dashboard') ?>" class="rh-breadcrumb-item">ResourceHub</a>
                    <span class="rh-breadcrumb-sep">&rsaquo;</span>
                    <?php if (isset($activePage) && $activePage !== 'dashboard'): ?>
                        <a href="<?= url($activePage) ?>" class="rh-breadcrumb-item text-capitalize"><?= e($activePage) ?></a>
                        <span class="rh-breadcrumb-sep">&rsaquo;</span>
                    <?php endif; ?>
                    <span class="rh-breadcrumb-current"><?= isset($pageTitle) ? e($pageTitle) : 'Overview' ?></span>
                </nav>
            </div>

            <!-- Topbar actions -->
            <div class="rh-topbar-actions">
                <a href="<?= url('loans', ['action' => 'request']) ?>" class="rh-action-btn-pill">
                    <i class="bi bi-plus-lg"></i>
                    <span class="d-none d-sm-inline">New request</span>
                </a>

                <!-- User profile dropdown -->
                <div class="dropdown">
                    <button class="rh-profile-dropdown-btn" type="button" id="rhProfileDropdown" data-bs-toggle="dropdown" aria-expanded="false" title="Account settings">
                        <div class="rh-topbar-avatar">
                            <span class="rh-avatar-letter"><?= strtoupper(substr(Auth::name() ?? 'U', 0, 1)) ?></span>
                            <span class="rh-avatar-status"></span>
                        </div>
                        <div class="rh-profile-info d-none d-lg-flex flex-column text-start">
                            <span class="rh-profile-name"><?= e(Auth::name()) ?></span>
                            <span class="rh-profile-role"><?= e(Auth::role()) ?></span>
                        </div>
                        <i class="bi bi-chevron-down rh-profile-chevron d-none d-sm-inline-block"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end rh-dropdown-menu" aria-labelledby="rhProfileDropdown">
                        <li class="rh-dropdown-header">
                            <div class="fw-bold small"><?= e(Auth::name()) ?></div>
                            <div class="text-secondary font-monospace" style="font-size: 0.72rem;"><?= e(Auth::email()) ?></div>
                            <span class="badge badge-approved mt-2" style="font-size: 0.65rem;"><?= ucfirst(e(Auth::role())) ?></span>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="<?= url('loans') ?>">
                                <i class="bi bi-journal-bookmark"></i>
                                <span><?= Auth::isCoordinator() ? 'All Loans' : 'My Loans' ?></span>
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center gap-2 py-2 text-danger" href="<?= url('logout') ?>">
                                <i class="bi bi-box-arrow-right"></i>
                                <span>Logout</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </header>

        <!-- Page Content Area -->
        <main class="rh-content">
            <?php include __DIR__ . '/_flashes.php'; ?>
<?php else: ?>
    <!-- Public / Auth Layout Container -->
    <main class="rh-auth-canvas">
        <div class="container py-4">
            <?php include __DIR__ . '/_flashes.php'; ?>
<?php endif; ?>
