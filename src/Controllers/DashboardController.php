<?php
/**
 * Dashboard Controller
 * Handles fleet overview, analytics, metrics, and activity feeds.
 */

class DashboardController {
    public function index() {
        Auth::requireLogin();

        $eqModel = new EquipmentService();
        $loanModel = new LoanService();

        $stats = $eqModel->getDashboardStats();

        // Staff only sees their own recent loans; Coordinator/Admin sees all
        $userLimit = Auth::isCoordinator() ? null : Auth::id();
        $recentLoans = $loanModel->getAll([], $userLimit, 6);

        // Metrics calculations
        $totalAssets = (int)($stats['total_assets'] ?? 0);
        $availableAssets = (int)($stats['available_assets'] ?? 0);
        $activeLoans = (int)($stats['active_loans'] ?? 0);
        $openMaintenance = (int)($stats['open_maintenance'] ?? 0);
        $overdueLoans = (int)($stats['overdue_loans'] ?? 0);
        $pendingRequests = (int)($stats['pending_requests'] ?? 0);

        // Availability percentage
        $availabilityRate = ($totalAssets > 0) ? round(($availableAssets / $totalAssets) * 100, 1) : 100.0;

        // Categories for KPI display
        $categories = $eqModel->getCategories();

        view('dashboard/index', [
            'stats'            => $stats,
            'recentLoans'      => $recentLoans,
            'totalAssets'      => $totalAssets,
            'availableAssets'  => $availableAssets,
            'activeLoans'      => $activeLoans,
            'openMaintenance'  => $openMaintenance,
            'overdueLoans'     => $overdueLoans,
            'pendingRequests'  => $pendingRequests,
            'availabilityRate' => $availabilityRate,
            'categories'       => $categories,
        ]);
    }
}
