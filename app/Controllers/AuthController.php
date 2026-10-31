<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Flash;
use App\Models\User;

class AuthController extends Controller
{
    public function home(): void
    {
        if (Auth::check()) {
            $this->redirect('/dashboard');
        }

        $this->redirect('/login');
    }

    public function showLogin(): void
    {
        if (Auth::check()) {
            $this->redirect('/dashboard');
        }

        $this->render('auth/login', [
            'title' => 'Connexion',
        ], false);
    }

    public function login(): void
    {
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $errors = [];

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Indiquez une adresse email valide.';
        }

        if ($password === '') {
            $errors['password'] = 'Indiquez votre mot de passe.';
        }

        if ($errors !== []) {
            $this->redirectBackWithErrors('/login', $errors, $_POST);
        }

        $user = User::findByEmail($email);
        $valid = $user !== null
            && (int) $user['actif'] === 1
            && password_verify($password, (string) $user['password_hash']);

        if (!$valid) {
            $this->redirectBackWithErrors(
                '/login',
                ['form' => 'Identifiants incorrects ou compte inactif.'],
                $_POST,
                'Identifiants incorrects ou compte inactif.'
            );
        }

        Auth::login($user);
        Flash::success('Connexion réussie.');
        $this->redirect('/dashboard');
    }

    public function logout(): void
    {
        Auth::logout();
        $this->redirect('/login');
    }
}
