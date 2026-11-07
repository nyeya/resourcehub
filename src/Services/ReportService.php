<?php
/**
 * Report & Analytics Service
 * Business rules: summary analytics calculations, fleet distributions, and sanitized CSV exports.
 */

class ReportService {
    private $db;

    public function __construct($pdo = null) {
        $this->db = $pdo ?: Database::getConnection();
    }

    public function getSummaryMetrics() {
        $metrics = [];

        // Equipment by category
        $catStmt = $this->db->query("
            SELECT c.name, COUNT(e.id) as count
            FROM categories c
            LEFT JOIN equipment e ON e.category_id = c.id
            GROUP BY c.id, c.name
            ORDER BY count DESC
        ");
        $metrics['by_category'] = $catStmt->fetchAll();

        // Equipment by condition
        $condStmt = $this->db->query("
            SELECT condition, COUNT(id) as count
            FROM equipment
            GROUP BY condition
            ORDER BY count DESC
        ");
        $metrics['by_condition'] = $condStmt->fetchAll();

        // Equipment by status
        $statusStmt = $this->db->query("
            SELECT status, COUNT(id) as count
            FROM equipment
            GROUP BY status
            ORDER BY count DESC
        ");
        $metrics['by_status'] = $statusStmt->fetchAll();

        // Loans by status
        $loanStatusStmt = $this->db->query("
            SELECT status, COUNT(id) as count
            FROM loans
            GROUP BY status
            ORDER BY count DESC
        ");
        $metrics['loans_by_status'] = $loanStatusStmt->fetchAll();

        // Loans by category (Optional extension: small chart of loans by category)
        $loansByCatStmt = $this->db->query("
            SELECT c.name, COUNT(l.id) as count
            FROM categories c
            LEFT JOIN equipment e ON e.category_id = c.id
            LEFT JOIN loans l ON l.asset_id = e.id
            GROUP BY c.id, c.name
            ORDER BY count DESC, c.name ASC
        ");
        $metrics['loans_by_category'] = $loansByCatStmt->fetchAll();

        // 30-day loan volume
        $volStmt = $this->db->query("
            SELECT COUNT(*) as total_requests,
                   COUNT(CASE WHEN status = 'approved' THEN 1 END) as total_approved,
                   COUNT(CASE WHEN status = 'checked_out' THEN 1 END) as total_active,
                   COUNT(CASE WHEN status = 'checked_out' AND end_date < CURRENT_DATE THEN 1 END) as total_overdue
            FROM loans
        ");
        $metrics['loan_overview'] = $volStmt->fetch();

        return $metrics;
    }

    public function exportEquipmentCsv($filters = []) {
        $eqModel = new Equipment($this->db);
        $items = $eqModel->getAll($filters);

        $filename = 'resourcehub_equipment_' . date('Y-m-d_His') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $out = fopen('php://output', 'w');
        fputcsv($out, ['Asset Tag', 'Name', 'Category', 'Location', 'Model', 'Serial Number', 'Condition', 'Status', 'Date Registered']);

        foreach ($items as $item) {
            fputcsv($out, [
                csv_neutralize($item['asset_tag']),
                csv_neutralize($item['name']),
                csv_neutralize($item['category_name']),
                csv_neutralize($item['location_name'] . ' (' . $item['location_room'] . ')'),
                csv_neutralize($item['model']),
                csv_neutralize($item['serial_number']),
                csv_neutralize($item['condition']),
                csv_neutralize($item['status']),
                csv_neutralize($item['created_at']),
            ]);
        }
        fclose($out);
        exit;
    }

    public function exportLoansCsv($filters = [], $limitToUserId = null) {
        $loanModel = new Loan($this->db);
        $loans = $loanModel->getAll($filters, $limitToUserId);

        $filename = 'resourcehub_loans_' . date('Y-m-d_His') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $out = fopen('php://output', 'w');
        fputcsv($out, ['Loan ID', 'Asset Tag', 'Equipment Name', 'Borrower Name', 'Borrower Email', 'Start Date', 'End Date', 'Status', 'Overdue', 'Purpose', 'Approved By', 'Checked Out By', 'Returned By', 'Return Condition']);

        foreach ($loans as $loan) {
            fputcsv($out, [
                $loan['id'],
                csv_neutralize($loan['asset_tag']),
                csv_neutralize($loan['asset_name']),
                csv_neutralize($loan['user_name']),
                csv_neutralize($loan['user_email']),
                $loan['start_date'],
                $loan['end_date'],
                $loan['status'],
                $loan['is_overdue'] ? 'YES' : 'NO',
                csv_neutralize($loan['purpose']),
                csv_neutralize($loan['approver_name']),
                csv_neutralize($loan['checkout_by_name']),
                csv_neutralize($loan['return_by_name']),
                csv_neutralize($loan['return_condition']),
            ]);
        }
        fclose($out);
        exit;
    }
}

// Aliased as Report for backward and forward compatibility
class Report extends ReportService {}

