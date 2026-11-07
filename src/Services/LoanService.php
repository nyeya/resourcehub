<?php
/**
 * Loan Service & Business Logic
 * Enforces loan reservation rules, inclusive date boundary conflicts,
 * overdue loan blocking, concurrency row locking (FOR UPDATE), and lifecycle transitions.
 */

class LoanService {
    private $db;

    public function __construct($pdo = null) {
        $this->db = $pdo ?: Database::getConnection();
    }

    private function baseLoanQuery() {
        return "
            SELECT l.*,
                   e.asset_tag, e.name AS asset_name, e.model AS asset_model,
                   e.condition AS current_asset_condition, e.status AS current_asset_status,
                   u.name AS user_name, u.email AS user_email,
                   app.name AS approver_name,
                   chk.name AS checkout_by_name,
                   ret.name AS return_by_name,
                   CASE 
                       WHEN l.status = 'checked_out' AND l.end_date < CURRENT_DATE THEN TRUE 
                       ELSE FALSE 
                   END AS is_overdue
            FROM loans l
            JOIN equipment e ON l.asset_id = e.id
            JOIN users u ON l.user_id = u.id
            LEFT JOIN users app ON l.approved_by = app.id
            LEFT JOIN users chk ON l.checked_out_by = chk.id
            LEFT JOIN users ret ON l.returned_by = ret.id
        ";
    }

    public function getAll($filters = [], $limitToUserId = null, $limit = null) {
        $sql = $this->baseLoanQuery() . " WHERE 1=1";
        $params = [];

        if ($limitToUserId !== null) {
            $sql .= " AND l.user_id = :limit_user_id";
            $params[':limit_user_id'] = (int)$limitToUserId;
        }

        if (!empty($filters['status'])) {
            if ($filters['status'] === 'overdue') {
                $sql .= " AND l.status = 'checked_out' AND l.end_date < CURRENT_DATE";
            } else {
                $sql .= " AND l.status = :status";
                $params[':status'] = $filters['status'];
            }
        }

        if (!empty($filters['asset_id'])) {
            $sql .= " AND l.asset_id = :asset_id";
            $params[':asset_id'] = (int)$filters['asset_id'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (e.name ILIKE :search OR e.asset_tag ILIKE :search OR u.name ILIKE :search OR l.purpose ILIKE :search)";
            $params[':search'] = '%' . trim($filters['search']) . '%';
        }

        $sql .= " ORDER BY l.id DESC";

        if ($limit !== null && $limit > 0) {
            $sql .= " LIMIT " . (int)$limit;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // Alias for compatibility
    public function getLoans($filters = [], $limitToUserId = null, $limit = null) {
        return $this->getAll($filters, $limitToUserId, $limit);
    }

    public function getById($id, $limitToUserId = null) {
        $sql = $this->baseLoanQuery() . " WHERE l.id = :id";
        $params = [':id' => (int)$id];

        if ($limitToUserId !== null) {
            $sql .= " AND l.user_id = :limit_user_id";
            $params[':limit_user_id'] = (int)$limitToUserId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    // Alias for compatibility
    public function getLoanById($id, $limitToUserId = null) {
        return $this->getById($id, $limitToUserId);
    }

    public function getHistory($loanId) {
        $stmt = $this->db->prepare("
            SELECT h.*, u.name AS performer_name, u.role AS performer_role
            FROM loan_history h
            LEFT JOIN users u ON h.performed_by = u.id
            WHERE h.loan_id = :loan_id
            ORDER BY h.id ASC
        ");
        $stmt->execute([':loan_id' => (int)$loanId]);
        return $stmt->fetchAll();
    }

    // Alias for compatibility
    public function getLoanHistory($loanId) {
        return $this->getHistory($loanId);
    }

    public function hasOverdueLoans($assetId) {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM loans
            WHERE asset_id = :asset_id
              AND status = 'checked_out'
              AND end_date < CURRENT_DATE
        ");
        $stmt->execute([':asset_id' => (int)$assetId]);
        return (int)$stmt->fetchColumn() > 0;
    }

    public function checkConflict($assetId, $startDate, $endDate, $excludeLoanId = null) {
        $sql = "
            SELECT l.*, u.name AS borrower_name
            FROM loans l
            JOIN users u ON l.user_id = u.id
            WHERE l.asset_id = :asset_id
              AND l.status IN ('approved', 'checked_out')
              AND l.start_date <= :end_date
              AND l.end_date >= :start_date
        ";
        $params = [
            ':asset_id'   => (int)$assetId,
            ':start_date' => $startDate,
            ':end_date'   => $endDate,
        ];

        if ($excludeLoanId !== null) {
            $sql .= " AND l.id != :exclude_id";
            $params[':exclude_id'] = (int)$excludeLoanId;
        }

        $sql .= " LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $conflict = $stmt->fetch();
        return $conflict ?: null;
    }

    public function request($assetId, $userId, $startDate, $endDate, $purpose) {
        $purpose = trim($purpose);
        if ($purpose === '') {
            throw new Exception("Loan purpose is required.");
        }

        if (empty($startDate) || empty($endDate)) {
            throw new Exception("Both start date and end date are required.");
        }

        if ($startDate < date('Y-m-d')) {
            throw new Exception("Invalid dates: Start date must be today or later.");
        }

        if ($endDate < $startDate) {
            throw new Exception("Invalid dates: End date cannot be earlier than start date.");
        }

        $eqStmt = $this->db->prepare("SELECT * FROM equipment WHERE id = :id");
        $eqStmt->execute([':id' => (int)$assetId]);
        $asset = $eqStmt->fetch();

        if (!$asset) {
            throw new Exception("Requested equipment does not exist.");
        }

        if ($asset['status'] === 'retired') {
            throw new Exception("This equipment is retired and cannot be requested.");
        }

        if ($asset['status'] === 'maintenance' || $asset['condition'] === 'damaged') {
            throw new Exception("This equipment is currently damaged or under maintenance.");
        }

        $maintStmt = $this->db->prepare("SELECT COUNT(*) FROM maintenance WHERE asset_id = :asset_id AND status = 'open'");
        $maintStmt->execute([':asset_id' => (int)$assetId]);
        if ($maintStmt->fetchColumn() > 0) {
            throw new Exception("This equipment is currently damaged or under maintenance.");
        }

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("
                INSERT INTO loans (asset_id, user_id, start_date, end_date, status, purpose)
                VALUES (:asset_id, :user_id, :start_date, :end_date, 'pending', :purpose)
                RETURNING id
            ");
            $stmt->execute([
                ':asset_id'   => (int)$assetId,
                ':user_id'    => (int)$userId,
                ':start_date' => $startDate,
                ':end_date'   => $endDate,
                ':purpose'    => $purpose,
            ]);

            $loanId = (int)$stmt->fetchColumn();
            $this->recordHistory($loanId, 'requested', (int)$userId, 'Loan request submitted by borrower.');

            $this->db->commit();
            return $loanId;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    // Alias for compatibility
    public function requestLoan($assetId, $userId, $startDate, $endDate, $purpose) {
        return $this->request($assetId, $userId, $startDate, $endDate, $purpose);
    }

    public function approve($loanId, $coordinatorId) {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("SELECT * FROM loans WHERE id = :id FOR UPDATE");
            $stmt->execute([':id' => (int)$loanId]);
            $loan = $stmt->fetch();

            if (!$loan) {
                throw new Exception("Loan not found.");
            }

            if ($loan['status'] !== 'pending') {
                throw new Exception("Cannot approve: Only pending loans can be approved (current status: {$loan['status']}).");
            }

            if ($loan['end_date'] < date('Y-m-d')) {
                throw new Exception("Approval rejected: The requested loan period has already passed.");
            }

            $eqStmt = $this->db->prepare("SELECT * FROM equipment WHERE id = :id FOR UPDATE");
            $eqStmt->execute([':id' => (int)$loan['asset_id']]);
            $asset = $eqStmt->fetch();

            if (!$asset || $asset['status'] === 'retired' || $asset['status'] === 'maintenance' || $asset['condition'] === 'damaged') {
                throw new Exception("Approval rejected: The asset is damaged, retired or currently under maintenance.");
            }

            $maintStmt = $this->db->prepare("SELECT COUNT(*) FROM maintenance WHERE asset_id = :asset_id AND status = 'open'");
            $maintStmt->execute([':asset_id' => (int)$loan['asset_id']]);
            if ($maintStmt->fetchColumn() > 0) {
                throw new Exception("Approval rejected: The asset is damaged, retired or currently under maintenance.");
            }

            if ($this->hasOverdueLoans((int)$loan['asset_id'])) {
                throw new Exception("Approval rejected: This asset currently has an overdue checkout.");
            }

            $conflict = $this->checkConflict((int)$loan['asset_id'], $loan['start_date'], $loan['end_date'], (int)$loanId);
            if ($conflict) {
                throw new Exception("Approval rejected: Conflicting approved loan exists for dates {$conflict['start_date']} to {$conflict['end_date']} (Loan #{$conflict['id']}).");
            }

            $update = $this->db->prepare("
                UPDATE loans
                SET status = 'approved', approved_by = :app_by, approved_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP
                WHERE id = :id
            ");
            $update->execute([
                ':app_by' => (int)$coordinatorId,
                ':id'     => (int)$loanId,
            ]);

            $this->recordHistory((int)$loanId, 'approved', (int)$coordinatorId, 'Loan request approved by coordinator.');
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    // Alias for compatibility
    public function approveLoan($loanId, $coordinatorId) {
        return $this->approve($loanId, $coordinatorId);
    }

    public function reject($loanId, $coordinatorId, $reason) {
        $reason = trim($reason);
        if ($reason === '') {
            throw new Exception("A rejection reason is required.");
        }

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("SELECT * FROM loans WHERE id = :id FOR UPDATE");
            $stmt->execute([':id' => (int)$loanId]);
            $loan = $stmt->fetch();

            if (!$loan) {
                throw new Exception("Loan not found.");
            }

            if ($loan['status'] !== 'pending') {
                throw new Exception("Cannot reject: Only pending loans can be rejected.");
            }

            $update = $this->db->prepare("
                UPDATE loans
                SET status = 'rejected', rejection_reason = :reason, approved_by = :app_by, updated_at = CURRENT_TIMESTAMP
                WHERE id = :id
            ");
            $update->execute([
                ':reason' => $reason,
                ':app_by' => (int)$coordinatorId,
                ':id'     => (int)$loanId,
            ]);

            $this->recordHistory((int)$loanId, 'rejected', (int)$coordinatorId, 'Loan request rejected: ' . $reason);
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    // Alias for compatibility
    public function rejectLoan($loanId, $coordinatorId, $reason) {
        return $this->reject($loanId, $coordinatorId, $reason);
    }

    public function checkout($loanId, $coordinatorId) {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("SELECT * FROM loans WHERE id = :id FOR UPDATE");
            $stmt->execute([':id' => (int)$loanId]);
            $loan = $stmt->fetch();

            if (!$loan) {
                throw new Exception("Loan not found.");
            }

            if ($loan['status'] !== 'approved') {
                throw new Exception("Checkout rejected: Loan status is '{$loan['status']}', expected 'approved'.");
            }

            if ($loan['end_date'] < date('Y-m-d')) {
                throw new Exception("Checkout rejected: The approved loan period has already expired.");
            }

            if (date('Y-m-d') < $loan['start_date']) {
                throw new Exception("Checkout rejected: The approved loan period has not started yet (scheduled for {$loan['start_date']}).");
            }

            $eqStmt = $this->db->prepare("SELECT * FROM equipment WHERE id = :id FOR UPDATE");
            $eqStmt->execute([':id' => (int)$loan['asset_id']]);
            $asset = $eqStmt->fetch();

            if (!$asset || $asset['status'] === 'retired' || $asset['status'] === 'maintenance' || $asset['condition'] === 'damaged') {
                throw new Exception("Checkout rejected: The asset is damaged, retired or currently under maintenance.");
            }

            $maintStmt = $this->db->prepare("SELECT COUNT(*) FROM maintenance WHERE asset_id = :asset_id AND status = 'open'");
            $maintStmt->execute([':asset_id' => (int)$loan['asset_id']]);
            if ($maintStmt->fetchColumn() > 0) {
                throw new Exception("Checkout rejected: The asset is damaged, retired or currently under maintenance.");
            }

            $activeStmt = $this->db->prepare("
                SELECT COUNT(*) FROM loans
                WHERE asset_id = :asset_id AND status = 'checked_out' AND id != :loan_id
            ");
            $activeStmt->execute([':asset_id' => $loan['asset_id'], ':loan_id' => $loanId]);
            if ($activeStmt->fetchColumn() > 0) {
                throw new Exception("Checkout rejected: Equipment is currently checked out to another user.");
            }

            $updateLoan = $this->db->prepare("
                UPDATE loans
                SET status = 'checked_out', checked_out_by = :chk_by, checked_out_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP
                WHERE id = :id
            ");
            $updateLoan->execute([
                ':chk_by' => (int)$coordinatorId,
                ':id'     => (int)$loanId,
            ]);

            $this->recordHistory((int)$loanId, 'checked_out', (int)$coordinatorId, 'Equipment physically checked out and handed over to borrower.');
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    // Alias for compatibility
    public function checkoutLoan($loanId, $coordinatorId) {
        return $this->checkout($loanId, $coordinatorId);
    }

    public function returnItem($loanId, $coordinatorId, $returnCondition, $returnNotes = null) {
        if (!in_array($returnCondition, Equipment::CONDITIONS, true)) {
            throw new Exception("Invalid return condition selected.");
        }

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("SELECT * FROM loans WHERE id = :id FOR UPDATE");
            $stmt->execute([':id' => (int)$loanId]);
            $loan = $stmt->fetch();

            if (!$loan) {
                throw new Exception("Loan not found.");
            }

            if ($loan['status'] !== 'checked_out') {
                throw new Exception("Return rejected: Loan is not currently checked out (current status: '{$loan['status']}').");
            }

            $updateLoan = $this->db->prepare("
                UPDATE loans
                SET status = 'returned', returned_by = :ret_by, returned_at = CURRENT_TIMESTAMP,
                    return_condition = :ret_cond, return_notes = :ret_notes, updated_at = CURRENT_TIMESTAMP
                WHERE id = :id
            ");
            $updateLoan->execute([
                ':ret_by'    => (int)$coordinatorId,
                ':ret_cond'  => $returnCondition,
                ':ret_notes' => $returnNotes ?: null,
                ':id'        => (int)$loanId,
            ]);

            if ($returnCondition === 'damaged') {
                $eqUpdate = $this->db->prepare("
                    UPDATE equipment
                    SET condition = 'damaged', status = 'maintenance', updated_at = CURRENT_TIMESTAMP
                    WHERE id = :asset_id
                ");
                $eqUpdate->execute([':asset_id' => $loan['asset_id']]);

                $maint = $this->db->prepare("
                    INSERT INTO maintenance (asset_id, opened_by, issue_description, status)
                    VALUES (:asset_id, :opened_by, :issue, 'open')
                ");
                $maint->execute([
                    ':asset_id'  => $loan['asset_id'],
                    ':opened_by' => (int)$coordinatorId,
                    ':issue'     => 'Equipment returned in damaged condition from Loan #' . $loanId . '. Notes: ' . ($returnNotes ?: 'Damaged on return.'),
                ]);
            } else {
                $eqUpdate = $this->db->prepare("
                    UPDATE equipment
                    SET condition = :cond, status = 'available', updated_at = CURRENT_TIMESTAMP
                    WHERE id = :asset_id
                ");
                $eqUpdate->execute([
                    ':cond'     => $returnCondition,
                    ':asset_id' => $loan['asset_id'],
                ]);
            }

            $this->recordHistory((int)$loanId, 'returned', (int)$coordinatorId, "Equipment returned in '{$returnCondition}' condition. " . ($returnNotes ?: ''));
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    // Alias for compatibility
    public function returnLoan($loanId, $coordinatorId, $returnCondition, $returnNotes = null) {
        return $this->returnItem($loanId, $coordinatorId, $returnCondition, $returnNotes);
    }

    public function cancel($loanId, $userId) {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("SELECT * FROM loans WHERE id = :id FOR UPDATE");
            $stmt->execute([':id' => (int)$loanId]);
            $loan = $stmt->fetch();

            if (!$loan) {
                throw new Exception("Loan not found.");
            }

            $isOwner = ((int)$loan['user_id'] === (int)$userId);
            $isCoordinator = Auth::isCoordinator();

            if (!$isOwner && !$isCoordinator) {
                throw new Exception("Access denied: You can only cancel your own loan requests.");
            }

            if (!in_array($loan['status'], ['pending', 'approved'], true)) {
                throw new Exception("Cannot cancel: Only pending or approved loans before checkout can be cancelled.");
            }

            $update = $this->db->prepare("
                UPDATE loans
                SET status = 'cancelled', updated_at = CURRENT_TIMESTAMP
                WHERE id = :id
            ");
            $update->execute([':id' => (int)$loanId]);

            $cancellerRole = $isOwner ? 'requester' : 'coordinator';
            $this->recordHistory((int)$loanId, 'cancelled', (int)$userId, "Loan request cancelled by {$cancellerRole}.");
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    // Alias for compatibility
    public function cancelLoan($loanId, $userId) {
        return $this->cancel($loanId, $userId);
    }

    private function recordHistory($loanId, $action, $userId, $notes) {
        $hist = $this->db->prepare("
            INSERT INTO loan_history (loan_id, action, performed_by, notes)
            VALUES (:loan_id, :action, :performer, :notes)
        ");
        $hist->execute([
            ':loan_id'   => (int)$loanId,
            ':action'    => $action,
            ':performer' => (int)$userId,
            ':notes'     => $notes,
        ]);
    }
}

// Aliased as Loan for backward and forward compatibility
class Loan extends LoanService {}

