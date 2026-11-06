<?php
/**
 * Equipment Controller
 * Handles catalogue listing, search, filtering, item views, registration, editing, and retirement.
 */

class EquipmentController {
    private $eqModel;

    public function __construct() {
        $this->eqModel = new EquipmentService();
    }

    public function index() {
        Auth::requireLogin();

        $categories = $this->eqModel->getCategories();
        $locations = $this->eqModel->getLocations();

        $filters = [
            'search'      => $_GET['search'] ?? '',
            'category_id' => $_GET['category_id'] ?? '',
            'location_id' => $_GET['location_id'] ?? '',
            'status'      => $_GET['status'] ?? '',
            'condition'   => $_GET['condition'] ?? '',
        ];

        $page = max(1, (int)($_GET['p'] ?? ($_GET['page_num'] ?? 1)));
        $perPage = 8;
        $totalItems = $this->eqModel->countAll($filters);
        $totalPages = max(1, (int)ceil($totalItems / $perPage));
        if ($page > $totalPages) {
            $page = $totalPages;
        }

        $items = $this->eqModel->getAll($filters, 'name', 'asc', $page, $perPage);

        if (!function_exists('page_url')) {
            function page_url($p, $filters) {
                return url('equipment', array_merge($filters, ['p' => $p]));
            }
        }

        $startItem = $totalItems > 0 ? (($page - 1) * $perPage) + 1 : 0;
        $endItem = min($totalItems, $page * $perPage);

        view('equipment/index', [
            'categories' => $categories,
            'locations'  => $locations,
            'filters'    => $filters,
            'items'      => $items,
            'page'       => $page,
            'totalPages' => $totalPages,
            'totalItems' => $totalItems,
            'startItem'  => $startItem,
            'endItem'    => $endItem,
        ]);
    }

    public function view($id = null) {
        Auth::requireLogin();

        $id = (int)($id ?: ($_GET['id'] ?? 0));
        $asset = $this->eqModel->getById($id);

        if (!$asset) {
            http_response_code(404);
            view('errors/404');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            require_post_csrf(url('equipment', ['action' => 'view', 'id' => $id]));
            $action = $_POST['action'] ?? '';
            if ($action === 'retire') {
                Auth::requireRole(['coordinator', 'administrator']);
                try {
                    $this->eqModel->retire($id);
                    flash('success', "Equipment '{$asset['name']}' has been retired.");
                    redirect(url('equipment', ['action' => 'view', 'id' => $id]));
                } catch (Exception $e) {
                    flash('danger', $e->getMessage());
                    redirect(url('equipment', ['action' => 'view', 'id' => $id]));
                }
            }
        }

        $loanModel = new LoanService();
        $userLimit = Auth::isCoordinator() ? null : Auth::id();
        $loans = $loanModel->getAll(['asset_id' => $id], $userLimit);

        view('equipment/view', [
            'asset' => $asset,
            'loans' => $loans,
            'id'    => $id,
        ]);
    }

    public function create() {
        Auth::requireRole(['coordinator', 'administrator']);

        $categories = $this->eqModel->getCategories();
        $locations = $this->eqModel->getLocations();
        $asset = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            require_post_csrf(url('equipment', ['action' => 'create']));
            try {
                $newId = $this->eqModel->create($_POST);
                flash('success', "Item registered with Tag: {$_POST['asset_tag']}.");
                redirect(url('equipment', ['action' => 'view', 'id' => $newId]));
            } catch (Exception $e) {
                flash('danger', $e->getMessage());
                $asset = $_POST;
            }
        }

        view('equipment/form', [
            'id'         => null,
            'asset'      => $asset,
            'categories' => $categories,
            'locations'  => $locations,
        ]);
    }

    public function edit($id = null) {
        Auth::requireRole(['coordinator', 'administrator']);

        $id = (int)($id ?: ($_GET['id'] ?? 0));
        $asset = $this->eqModel->getById($id);

        if (!$asset) {
            http_response_code(404);
            view('errors/404');
            exit;
        }

        $categories = $this->eqModel->getCategories();
        $locations = $this->eqModel->getLocations();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            require_post_csrf(url('equipment', ['action' => 'edit', 'id' => $id]));
            try {
                $this->eqModel->update($id, $_POST);
                flash('success', "Equipment updated successfully.");
                redirect(url('equipment', ['action' => 'view', 'id' => $id]));
            } catch (Exception $e) {
                flash('danger', $e->getMessage());
                $asset = array_merge($asset, $_POST);
            }
        }

        view('equipment/form', [
            'id'         => $id,
            'asset'      => $asset,
            'categories' => $categories,
            'locations'  => $locations,
        ]);
    }
}

