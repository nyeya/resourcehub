<?php
/**
 * Maintenance Service & Defect Management
 * Business rules: blocking maintenance on checked-out items, resolving tickets, condition restoration.
 */

class MaintenanceService {
    private $db;

    public function __construct($pdo = null) {
        $this->db = $pdo ?: Database::getConnection();
    }

    private function baseMaintenanceQuery() {
        return "
            SELECT m.*,
                   e.asset_tag, e.name AS asset_name, e.model AS asset_model, e.condition AS asset_condition,
                   op.name AS opened_by_name,
                   cl.name AS closed_by_name
            FROM maintenance m
            JOIN equipment e ON m.asset_id = e.id
            JOIN users op ON m.opened_by = op.id
            LEFT JOIN users cl ON m.closed_by = cl.id
        ";
    }

    public function getAll($status = null) {
        $sql = $this->baseMaintenanceQuery() . " WHERE 1=1";
        $params = [];

        if ($status !== null && in_array($status, ['open', 'resolved', 'cancelled'], true)) {
            $sql .= " AND m.status = :status";
            $params[':status'] = $status;
        }

        $sql .= " ORDER BY (m.status = 'open') DESC, m.opened_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // Alias for compatibility
    public function getMaintenanceList($status = null) {
        return $this->getAll($status);
    }

    public function getById($id) {
        $stmt = $this->db->prepare($this->baseMaintenanceQuery() . " WHERE m.id = :id");
        $stmt->execute([':id' => (int)$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    // Alias for compatibility
    public function getMaintenanceById($id) {
        return $this->getById($id);
    }

    public function open($assetId, $coordinatorId, $description) {
        $description = trim($description);
        if ($description === '') {
            throw new Exception("An issue description is required.");
        }

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("SELECT * FROM equipment WHERE id = :id FOR UPDATE");
            $stmt->execute([':id' => (int)$assetId]);
            $asset = $stmt->fetch();

            if (!$asset) {
                throw new Exception("Equipment record not found.");
            }

            // Reject if item is currently checked out
            $chkStmt = $this->db->prepare("
                SELECT COUNT(*) FROM loans
                WHERE asset_id = :asset_id AND status = 'checked_out'
            ");
            $chkStmt->execute([':asset_id' => (int)$assetId]);
            if ($chkStmt->fetchColumn() > 0) {
                throw new Exception("Action rejected: Cannot open maintenance on an actively checked-out asset. The equipment must be returned first.");
            }

            // Reject if asset already has an open maintenance record
            $openStmt = $this->db->prepare("
                SELECT COUNT(*) FROM maintenance
                WHERE asset_id = :asset_id AND status = 'open'
            ");
            $openStmt->execute([':asset_id' => (int)$assetId]);
            if ($openStmt->fetchColumn() > 0) {
                throw new Exception("An open maintenance ticket already exists for this equipment.");
            }

            // Update equipment status to 'maintenance' and condition to 'damaged'
            $eqUpdate = $this->db->prepare("
                UPDATE equipment
                SET status = 'maintenance', condition = 'damaged', updated_at = CURRENT_TIMESTAMP
                WHERE id = :id
            ");
            $eqUpdate->execute([':id' => (int)$assetId]);

            // Insert maintenance record
            $maint = $this->db->prepare("
                INSERT INTO maintenance (asset_id, opened_by, issue_description, status)
                VALUES (:asset_id, :opened_by, :issue, 'open')
                RETURNING id
            ");
            $maint->execute([
                ':asset_id'  => (int)$assetId,
                ':opened_by' => (int)$coordinatorId,
                ':issue'     => $description,
            ]);

            $newId = (int)$maint->fetchColumn();
            $this->db->commit();
            return $newId;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    // Alias for compatibility
    public function openMaintenance($assetId, $coordinatorId, $description) {
        return $this->open($assetId, $coordinatorId, $description);
    }

    public function close($maintenanceId, $coordinatorId, $resolutionNotes, $restoredCondition) {
        $resolutionNotes = trim($resolutionNotes);
        if ($resolutionNotes === '') {
            throw new Exception("Resolution notes are required to close maintenance.");
        }

        if (!in_array($restoredCondition, ['new', 'good', 'fair'], true)) {
            throw new Exception("Restored condition must be serviceable ('new', 'good', or 'fair').");
        }

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("SELECT * FROM maintenance WHERE id = :id FOR UPDATE");
            $stmt->execute([':id' => (int)$maintenanceId]);
            $maint = $stmt->fetch();

            if (!$maint) {
                throw new Exception("Maintenance record not found.");
            }

            if ($maint['status'] !== 'open') {
                throw new Exception("Cannot close: Ticket is already marked {$maint['status']}.");
            }

            $update = $this->db->prepare("
                UPDATE maintenance
                SET status = 'resolved', closed_by = :cl_by, closed_at = CURRENT_TIMESTAMP,
                    resolution_notes = :notes, restored_condition = :cond
                WHERE id = :id
            ");
            $update->execute([
                ':cl_by' => (int)$coordinatorId,
                ':notes' => $resolutionNotes,
                ':cond'  => $restoredCondition,
                ':id'    => (int)$maintenanceId,
            ]);

            $eqUpdate = $this->db->prepare("
                UPDATE equipment
                SET status = 'available', condition = :cond, updated_at = CURRENT_TIMESTAMP
                WHERE id = :asset_id
            ");
            $eqUpdate->execute([
                ':cond'     => $restoredCondition,
                ':asset_id' => $maint['asset_id'],
            ]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    // Alias for compatibility
    public function closeMaintenance($maintenanceId, $coordinatorId, $resolutionNotes, $restoredCondition) {
        return $this->close($maintenanceId, $coordinatorId, $resolutionNotes, $restoredCondition);
    }
}

// Aliased as Maintenance for backward and forward compatibility
class Maintenance extends MaintenanceService {}

