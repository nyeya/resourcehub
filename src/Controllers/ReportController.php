<?php
/**
 * Report Controller
 * Handles fleet metrics, category distribution chart, and safe CSV exports.
 */

class ReportController {
    private $reportModel;

    public function __construct() {
        $this->reportModel = new ReportService();
    }

    public function index() {
        Auth::requireLogin();

        $action = $_GET['action'] ?? 'index';

        if ($action === 'export_loans') {
            return $this->exportLoans();
        }
        if ($action === 'export_equipment') {
            return $this->exportEquipment();
        }

        Auth::requireRole(['coordinator', 'administrator']);

        $metrics = $this->reportModel->getSummaryMetrics();
        $totalLoansCategory = array_sum(array_column($metrics['loans_by_category'] ?? [], 'count'));

        view('reports/index', [
            'metrics'            => $metrics,
            'totalLoansCategory' => $totalLoansCategory,
        ]);
    }

    public function exportLoans() {
        Auth::requireLogin();
        $userLimit = Auth::isCoordinator() ? null : Auth::id();
        $this->reportModel->exportLoansCsv($_GET, $userLimit);
        exit;
    }

    public function exportEquipment() {
        Auth::requireRole(['coordinator', 'administrator']);
        $this->reportModel->exportEquipmentCsv($_GET);
        exit;
    }
}

