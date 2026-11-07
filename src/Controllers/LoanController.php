<?php
/**
 * Loan Controller
 * Handles reservations, review, approvals, checkouts, physical returns, and cancellations.
 */

class LoanController {
    private $loanModel;

    public function __construct() {
        $this->loanModel = new LoanService();
    }

    public function index() {
        Auth::requireLogin();

        $filters = [
            'status' => $_GET['status'] ?? '',
            'search' => $_GET['search'] ?? '',
        ];

        $userLimit = Auth::isCoordinator() ? null : Auth::id();
        $loans = $this->loanModel->getAll($filters, $userLimit);
        $currentStatus = $filters['status'] ?? '';

        view('loans/index', [
            'loans'         => $loans,
            'filters'       => $filters,
            'currentStatus' => $currentStatus,
        ]);
    }

    public function request() {
        Auth::requireLogin();

        $eqModel = new EquipmentService();
        $availableEquipment = $eqModel->getAll(['status' => 'available']);
        $selectedAssetId = (int)($_GET['asset_id'] ?? 0);
        $startDate = date('Y-m-d');
        $endDate = date('Y-m-d', strtotime('+3 days'));
        $purpose = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            require_post_csrf(url('loans', array_filter(['action' => 'request', 'asset_id' => $selectedAssetId])));
            $assetId = (int)($_POST['asset_id'] ?? 0);
            $startDate = trim($_POST['start_date'] ?? '');
            $endDate = trim($_POST['end_date'] ?? '');
            $purpose = trim($_POST['purpose'] ?? '');

            try {
                $loanId = $this->loanModel->request($assetId, Auth::id(), $startDate, $endDate, $purpose);
                flash('success', "Loan request submitted. Reference #{$loanId}.");
                redirect(url('loans', ['action' => 'view', 'id' => $loanId]));
            } catch (Exception $e) {
                flash('danger', $e->getMessage());
                $selectedAssetId = $assetId;
            }
        }

        view('loans/request', [
            'availableEquipment' => $availableEquipment,
            'selectedAssetId'    => $selectedAssetId,
            'startDate'          => $startDate,
            'endDate'            => $endDate,
            'purpose'            => $purpose,
        ]);
    }

    public function view($id = null) {
        Auth::requireLogin();

        $id = (int)($id ?: ($_GET['id'] ?? 0));

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            require_post_csrf(url('loans', ['action' => 'view', 'id' => $id]));
            $action = $_POST['action'] ?? ($_GET['action'] ?? '');

            if ($action === 'approve') {
                Auth::requireRole(['coordinator', 'administrator']);
                try {
                    $this->loanModel->approve($id, Auth::id());
                    flash('success', "Loan #{$id} approved.");
                } catch (Exception $e) {
                    flash('danger', $e->getMessage());
                }
                redirect(url('loans', ['action' => 'view', 'id' => $id]));
            } elseif ($action === 'reject') {
                Auth::requireRole(['coordinator', 'administrator']);
                $reason = trim($_POST['rejection_reason'] ?? '');
                try {
                    $this->loanModel->reject($id, Auth::id(), $reason);
                    flash('info', "Loan #{$id} was declined.");
                } catch (Exception $e) {
                    flash('danger', $e->getMessage());
                }
                redirect(url('loans', ['action' => 'view', 'id' => $id]));
            } elseif ($action === 'checkout') {
                Auth::requireRole(['coordinator', 'administrator']);
                try {
                    $this->loanModel->checkout($id, Auth::id());
                    flash('success', "Item checked out for Loan #{$id}.");
                } catch (Exception $e) {
                    flash('danger', $e->getMessage());
                }
                redirect(url('loans', ['action' => 'view', 'id' => $id]));
            } elseif ($action === 'return') {
                Auth::requireRole(['coordinator', 'administrator']);
                $returnCond = $_POST['return_condition'] ?? 'good';
                $returnNotes = $_POST['return_notes'] ?? null;
                try {
                    $this->loanModel->returnItem($id, Auth::id(), $returnCond, $returnNotes);
                    flash('success', "Return recorded for Loan #{$id}.");
                } catch (Exception $e) {
                    flash('danger', $e->getMessage());
                }
                redirect(url('loans', ['action' => 'view', 'id' => $id]));
            } elseif ($action === 'cancel') {
                try {
                    $this->loanModel->cancel($id, Auth::id());
                    flash('info', "Loan request #{$id} cancelled.");
                    redirect(url('loans'));
                } catch (Exception $e) {
                    flash('danger', $e->getMessage());
                    redirect(url('loans', ['action' => 'view', 'id' => $id]));
                }
            }
        }

        // Staff can only view their own loan; coordinator/admin can view all
        $userLimit = Auth::isCoordinator() ? null : Auth::id();
        $loan = $this->loanModel->getById($id, $userLimit);

        if (!$loan) {
            $anyLoan = $this->loanModel->getById($id);
            if ($anyLoan && !Auth::isCoordinator()) {
                http_response_code(403);
                view('errors/403');
                exit;
            }

            http_response_code(404);
            view('errors/404');
            exit;
        }

        $history = $this->loanModel->getHistory($id);
        $isOwner = ((int)$loan['user_id'] === (int)Auth::id());

        view('loans/view', [
            'loan'    => $loan,
            'history' => $history,
            'isOwner' => $isOwner,
            'id'      => $id,
        ]);
    }
}

