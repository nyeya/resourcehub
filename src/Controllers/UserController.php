<?php
/**
 * User Controller
 * Handles user account administration, role modifications, active status toggles, and safety guards.
 */

class UserController {
    private $userModel;

    public function __construct() {
        $this->userModel = new UserService();
    }

    public function index() {
        Auth::requireRole('administrator');

        $users = $this->userModel->getAll();

        view('users/index', [
            'users' => $users,
        ]);
    }

    public function create() {
        Auth::requireRole('administrator');

        $user = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            require_post_csrf(url('users', ['action' => 'create']));
            try {
                $newId = $this->userModel->create($_POST);
                flash('success', "User '{$_POST['email']}' created.");
                redirect(url('users'));
            } catch (Exception $e) {
                flash('danger', $e->getMessage());
                $user = $_POST;
            }
        }

        view('users/form', [
            'id'   => null,
            'user' => $user,
        ]);
    }

    public function edit($id = null) {
        Auth::requireRole('administrator');

        $id = (int)($id ?: ($_GET['id'] ?? 0));
        $user = $this->userModel->getById($id);

        if (!$user) {
            http_response_code(404);
            view('errors/404');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            require_post_csrf(url('users', ['action' => 'edit', 'id' => $id]));
            try {
                $this->userModel->update($id, $_POST);
                flash('success', "User updated successfully.");
                redirect(url('users'));
            } catch (Exception $e) {
                flash('danger', $e->getMessage());
                $user = array_merge($user, $_POST);
            }
        }

        view('users/form', [
            'id'   => $id,
            'user' => $user,
        ]);
    }
}

