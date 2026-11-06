<?php
/**
 * Equipment Service & Catalogue Management
 * Business rules: asset tagging, unique checks, retirement restrictions with active loans, pagination.
 */

class EquipmentService {
    private $db;

    public const CONDITIONS = ['new', 'good', 'fair', 'damaged'];
    public const STATUSES   = ['available', 'maintenance', 'retired'];

    public function __construct($pdo = null) {
        $this->db = $pdo ?: Database::getConnection();
    }

    public function getCategories() {
        $stmt = $this->db->query("SELECT * FROM categories ORDER BY name ASC");
        return $stmt->fetchAll();
    }

    public function getLocations() {
        $stmt = $this->db->query("SELECT * FROM locations ORDER BY name ASC");
        return $stmt->fetchAll();
    }

    public function countAll($filters = []) {
        $sql = "
            SELECT COUNT(*)
            FROM equipment e
            JOIN categories c ON e.category_id = c.id
            JOIN locations l ON e.location_id = l.id
            WHERE 1=1
        ";
        $params = [];

        if (!empty($filters['search'])) {
            $sql .= " AND (e.name ILIKE :search OR e.asset_tag ILIKE :search OR e.model ILIKE :search)";
            $params[':search'] = '%' . trim($filters['search']) . '%';
        }

        if (!empty($filters['category_id'])) {
            $sql .= " AND e.category_id = :category_id";
            $params[':category_id'] = (int)$filters['category_id'];
        }

        if (!empty($filters['location_id'])) {
            $sql .= " AND e.location_id = :location_id";
            $params[':location_id'] = (int)$filters['location_id'];
        }

        if (!empty($filters['status'])) {
            $sql .= " AND e.status = :status";
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['condition'])) {
            $sql .= " AND e.condition = :condition";
            $params[':condition'] = $filters['condition'];
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    public function getAll($filters = [], $sortBy = 'name', $sortDir = 'asc', $page = null, $perPage = 10) {
        $allowedSorts = [
            'name'       => 'e.name',
            'asset_tag'  => 'e.asset_tag',
            'category'   => 'c.name',
            'location'   => 'l.name',
            'condition'  => 'e.condition',
            'status'     => 'e.status',
            'created_at' => 'e.created_at',
        ];

        $sortColumn = $allowedSorts[$sortBy] ?? 'e.name';
        $direction = strtolower($sortDir) === 'desc' ? 'DESC' : 'ASC';

        $sql = "
            SELECT e.*, c.name AS category_name, l.name AS location_name, l.room AS location_room
            FROM equipment e
            JOIN categories c ON e.category_id = c.id
            JOIN locations l ON e.location_id = l.id
            WHERE 1=1
        ";
        $params = [];

        if (!empty($filters['search'])) {
            $sql .= " AND (e.name ILIKE :search OR e.asset_tag ILIKE :search OR e.model ILIKE :search)";
            $params[':search'] = '%' . trim($filters['search']) . '%';
        }

        if (!empty($filters['category_id'])) {
            $sql .= " AND e.category_id = :category_id";
            $params[':category_id'] = (int)$filters['category_id'];
        }

        if (!empty($filters['location_id'])) {
            $sql .= " AND e.location_id = :location_id";
            $params[':location_id'] = (int)$filters['location_id'];
        }

        if (!empty($filters['status'])) {
            $sql .= " AND e.status = :status";
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['condition'])) {
            $sql .= " AND e.condition = :condition";
            $params[':condition'] = $filters['condition'];
        }

        $sql .= " ORDER BY {$sortColumn} {$direction}";

        if ($page !== null && $perPage > 0) {
            $offset = max(0, ((int)$page - 1) * (int)$perPage);
            $sql .= " LIMIT " . (int)$perPage . " OFFSET " . (int)$offset;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getEquipment($filters = [], $sortBy = 'name', $sortDir = 'asc') {
        return $this->getAll($filters, $sortBy, $sortDir);
    }

    public function getById($id) {
        $stmt = $this->db->prepare("
            SELECT e.*, c.name AS category_name, c.description AS category_description,
                   l.name AS location_name, l.building AS location_building, l.room AS location_room
            FROM equipment e
            JOIN categories c ON e.category_id = c.id
            JOIN locations l ON e.location_id = l.id
            WHERE e.id = :id
        ");
        $stmt->execute([':id' => (int)$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function getAssetById($id) {
        return $this->getById($id);
    }

    public function create($data) {
        $params = $this->validateAndFormatData($data);

        $stmt = $this->db->prepare("
            INSERT INTO equipment (asset_tag, name, category_id, location_id, model, serial_number, condition, status, notes)
            VALUES (:asset_tag, :name, :category_id, :location_id, :model, :serial_number, :condition, :status, :notes)
            RETURNING id
        ");
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    public function createAsset($data) {
        return $this->create($data);
    }

    public function update($id, $data) {
        $asset = $this->getById($id);
        if (!$asset) {
            throw new Exception("Equipment record not found.");
        }

        $params = $this->validateAndFormatData($data, (int)$id, $asset);
        $params[':id'] = (int)$id;

        if ($params[':status'] === 'retired') {
            $activeStmt = $this->db->prepare("SELECT COUNT(*) FROM loans WHERE asset_id = :id AND status = 'checked_out'");
            $activeStmt->execute([':id' => (int)$id]);
            if ($activeStmt->fetchColumn() > 0) {
                throw new Exception("Cannot retire equipment while it is actively checked out on loan. Record return first.");
            }
        }

        $stmt = $this->db->prepare("
            UPDATE equipment
            SET asset_tag = :asset_tag, name = :name, category_id = :category_id, location_id = :location_id,
                model = :model, serial_number = :serial_number, condition = :condition, status = :status,
                notes = :notes, updated_at = CURRENT_TIMESTAMP
            WHERE id = :id
        ");
        return $stmt->execute($params);
    }

    public function updateAsset($id, $data) {
        return $this->update($id, $data);
    }

    public function retire($id) {
        $asset = $this->getById($id);
        if (!$asset) {
            throw new Exception("Equipment record not found.");
        }

        $activeStmt = $this->db->prepare("SELECT COUNT(*) FROM loans WHERE asset_id = :id AND status = 'checked_out'");
        $activeStmt->execute([':id' => (int)$id]);
        if ($activeStmt->fetchColumn() > 0) {
            throw new Exception("Cannot retire equipment while it is actively checked out on loan. Record return first.");
        }

        $stmt = $this->db->prepare("UPDATE equipment SET status = 'retired', updated_at = CURRENT_TIMESTAMP WHERE id = :id");
        return $stmt->execute([':id' => (int)$id]);
    }

    public function getDashboardStats() {
        return [
            'total_assets'      => (int)$this->db->query("SELECT COUNT(*) FROM equipment")->fetchColumn(),
            'available_assets'  => (int)$this->db->query("SELECT COUNT(*) FROM equipment WHERE status = 'available'")->fetchColumn(),
            'open_maintenance'  => (int)$this->db->query("SELECT COUNT(*) FROM maintenance WHERE status = 'open'")->fetchColumn(),
            'active_loans'      => (int)$this->db->query("SELECT COUNT(*) FROM loans WHERE status = 'checked_out'")->fetchColumn(),
            'overdue_loans'     => (int)$this->db->query("SELECT COUNT(*) FROM loans WHERE status = 'checked_out' AND end_date < CURRENT_DATE")->fetchColumn(),
            'pending_requests'  => (int)$this->db->query("SELECT COUNT(*) FROM loans WHERE status = 'pending'")->fetchColumn(),
        ];
    }

    private function validateAndFormatData($data, $id = null, $defaults = []) {
        $assetTag   = trim($data['asset_tag'] ?? '');
        $name       = trim($data['name'] ?? '');
        $categoryId = (int)($data['category_id'] ?? 0);
        $locationId = (int)($data['location_id'] ?? 0);
        $model      = trim($data['model'] ?? '');
        $serialNo   = trim($data['serial_number'] ?? '');
        $condition  = $data['condition'] ?? ($defaults['condition'] ?? 'good');
        $status     = $data['status'] ?? ($defaults['status'] ?? 'available');
        $notes      = trim($data['notes'] ?? '');

        if ($assetTag === '' || $name === '' || $categoryId <= 0 || $locationId <= 0) {
            throw new Exception("Asset tag, name, category, and location are required.");
        }

        if (!in_array($condition, self::CONDITIONS, true)) {
            throw new Exception("Invalid condition specified.");
        }

        if (!in_array($status, self::STATUSES, true)) {
            throw new Exception("Invalid status specified.");
        }

        $sql = "SELECT COUNT(*) FROM equipment WHERE LOWER(asset_tag) = LOWER(:tag)";
        $params = [':tag' => $assetTag];
        if ($id !== null) {
            $sql .= " AND id != :id";
            $params[':id'] = (int)$id;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception("Duplicate asset tag: '{$assetTag}' is already in use.");
        }

        return [
            ':asset_tag'     => $assetTag,
            ':name'          => $name,
            ':category_id'   => $categoryId,
            ':location_id'   => $locationId,
            ':model'         => $model ?: null,
            ':serial_number' => $serialNo ?: null,
            ':condition'     => $condition,
            ':status'        => $status,
            ':notes'         => $notes ?: null,
        ];
    }
}

// Aliased as Equipment for backward and forward compatibility
class Equipment extends EquipmentService {}

