<?php
declare(strict_types=1);

namespace Controllers;

use Core\Controller;
use Core\Auth;

class AuthController extends Controller
{
    public function showLogin(): void
    {
        if (Auth::check()) {
            $this->redirect('/dashboard');
        }
        $error  = $_GET['error']  ?? null;
        $reason = $_GET['reason'] ?? null;
        $this->view('pages/login', compact('error', 'reason'), null);
    }

    public function login(): void
    {
        $identifier = trim($this->post('identifier', ''));
        $password   = $this->post('password', '');

        if (!$identifier || !$password) {
            $this->redirect('/login?error=missing');
        }

        if (Auth::attempt($identifier, $password)) {
            // Auth::attempt כבר קורא ל-loadAllPerms() פנימית
            $this->redirect('/dashboard');
        } else {
            $this->redirect('/login?error=invalid');
        }
    }

    public function logout(): void
    {
        Auth::logout();
        $this->redirect('/login');
    }
}