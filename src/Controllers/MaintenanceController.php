<?php
/**
 * Maintenance Controller
 * Handles equipment repairs, defect logging, and condition restorations.
 */

class MaintenanceController {
    private $maintModel;

    public function __construct() {
        $this->maintModel = new MaintenanceService();
    }

    public function index() {
        Auth::requireRole(['coordinator', 'administrator']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            require_post_csrf(url('maintenance'));
            $id = (int)($_POST['id'] ?? ($_GET['id'] ?? 0));
            $resolution = trim($_POST['resolution_notes'] ?? '');
            $restoredCond = $_POST['restored_condition'] ?? 'good';

            try {
                $this->maintModel->close($id, Auth::id(), $resolution, $restoredCond);
                flash('success', "Ticket #{$id} resolved. Equipment restored to circulation.");
            } catch (Exception $e) {
                flash('danger', $e->getMessage());
            }
            redirect(url('maintenance'));
        }

        $status = $_GET['status'] ?? null;
        $tickets = $this->maintModel->getAll($status);
        $currentStatus = $status ?? '';

        view('maintenance/index', [
            'tickets'       => $tickets,
            'currentStatus' => $currentStatus,
        ]);
    }

    public function open() {
        Auth::requireRole(['coordinator', 'administrator']);

        $eqModel = new EquipmentService();
        $equipmentList = $eqModel->getAll();
        $selectedAssetId = (int)($_GET['asset_id'] ?? 0);
        $issueDescription = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            require_post_csrf(url('maintenance', ['action' => 'open']));
            $assetId = (int)($_POST['asset_id'] ?? 0);
            $desc = trim($_POST['issue_description'] ?? '');

            try {
                $ticketId = $this->maintModel->open($assetId, Auth::id(), $desc);
                flash('success', "Maintenance ticket #{$ticketId} opened.");
                redirect(url('maintenance'));
            } catch (Exception $e) {
                flash('danger', $e->getMessage());
                $selectedAssetId = $assetId;
                $issueDescription = $desc;
            }
        }

        view('maintenance/form', [
            'equipmentList'    => $equipmentList,
            'selectedAssetId'  => $selectedAssetId,
            'issueDescription' => $issueDescription,
        ]);
    }
}

