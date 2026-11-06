<?php
/**
 * Auth Controller
 * Handles user sign in, authentication checks, and sign out workflows.
 */

class AuthController {
    public function login() {
        if (Auth::check()) {
            redirect(url('dashboard'));
        }

        $loginError = null;
        $emailValue = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $submittedToken = $_POST['csrf_token'] ?? null;
            if (!verify_csrf_token($submittedToken)) {
                http_response_code(400);
                $loginError = 'Security check failed. Please refresh the page and try again.';
            } else {
                $emailValue = trim($_POST['email'] ?? '');
                $password = $_POST['password'] ?? '';

                if ($emailValue === '' || $password === '') {
                    $loginError = 'Please provide both email address and password.';
                } elseif (Auth::attempt($emailValue, $password)) {
                    flash('success', 'Welcome back, ' . Auth::name() . '!');
                    redirect(url('dashboard'));
                } else {
                    $loginError = 'Invalid email credentials or inactive account access.';
                }
            }
        }

        view('auth/login', [
            'loginError' => $loginError,
            'emailValue' => $emailValue,
        ]);
    }

    public function logout() {
        Auth::logout();
        flash('info', 'You have been signed out successfully.');
        redirect(url('login'));
    }
}

