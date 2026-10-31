<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Flash;
use App\Models\User;

class UserController extends Controller
{
    public function index(): void
    {
        $this->requireRole(['admin']);

        $this->render('users/index', [
            'title' => 'Utilisateurs',
            'users' => User::all(),
        ]);
    }

    public function create(): void
    {
        $this->requireRole(['admin']);

        $this->render('users/create', [
            'title' => 'Nouvel utilisateur',
            'user' => null,
        ]);
    }

    public function store(): void
    {
        $this->requireRole(['admin']);
        $payload = $this->validate($_POST, null, true);

        if ($payload['errors'] !== []) {
            $this->redirectBackWithErrors('/users/create', $payload['errors'], $_POST);
        }

        User::create($payload['data']);
        Flash::success('L\'utilisateur a été créé.');
        $this->redirect('/users');
    }

    public function edit(string $id): void
    {
        $this->requireRole(['admin']);
        $user = $this->userOrFail($id);

        $this->render('users/edit', [
            'title' => 'Modifier l\'utilisateur',
            'user' => $user,
        ]);
    }

    public function update(string $id): void
    {
        $this->requireRole(['admin']);
        $user = $this->userOrFail($id);
        $payload = $this->validate($_POST, $user, false);

        if ($payload['errors'] !== []) {
            $this->redirectBackWithErrors('/users/' . $user['id'] . '/edit', $payload['errors'], $_POST);
        }

        User::update((int) $user['id'], $payload['data']);

        if ($payload['password_hash'] !== null) {
            User::updatePassword((int) $user['id'], $payload['password_hash']);
        }

        Flash::success('L\'utilisateur a été mis à jour.');
        $this->redirect('/users');
    }

    public function destroy(string $id): void
    {
        $this->requireRole(['admin']);
        $user = $this->userOrFail($id);

        if ((int) $user['id'] === Auth::id()) {
            Flash::danger('Vous ne pouvez pas désactiver votre propre compte.');
            $this->redirect('/users');
        }

        if ($user['role'] === 'admin' && (int) $user['actif'] === 1 && User::countActiveAdmins((int) $user['id']) === 0) {
            Flash::danger('Impossible de désactiver le dernier administrateur actif.');
            $this->redirect('/users');
        }

        User::setActif((int) $user['id'], 0);
        Flash::success('Le compte a été désactivé.');
        $this->redirect('/users');
    }

    public function passwordForm(): void
    {
        $this->requireAuth();

        $this->render('users/password', [
            'title' => 'Changer mon mot de passe',
        ]);
    }

    public function passwordUpdate(): void
    {
        $sessionUser = $this->requireAuth();
        $current = (string) ($_POST['current_password'] ?? '');
        $password = (string) ($_POST['password'] ?? '');
        $confirm = (string) ($_POST['password_confirm'] ?? '');
        $errors = [];

        $user = User::find((int) $sessionUser['id']);
        if ($user === null || !password_verify($current, (string) $user['password_hash'])) {
            $errors['current_password'] = 'Le mot de passe actuel est incorrect.';
        }

        if (mb_strlen($password) < 8) {
            $errors['password'] = 'Le nouveau mot de passe doit contenir au moins 8 caractères.';
        }

        if ($password !== $confirm) {
            $errors['password_confirm'] = 'La confirmation ne correspond pas.';
        }

        if ($errors !== []) {
            $this->redirectBackWithErrors('/password', $errors, $_POST);
        }

        User::updatePassword((int) $user['id'], password_hash($password, PASSWORD_DEFAULT));
        Flash::success('Votre mot de passe a été modifié.');
        $this->redirect('/dashboard');
    }

    private function userOrFail(string $id): array
    {
        if (!ctype_digit($id)) {
            $this->notFound();
        }

        $user = User::find((int) $id);
        if ($user === null) {
            $this->notFound();
        }

        return $user;
    }

    private function validate(array $post, ?array $current, bool $passwordRequired): array
    {
        $errors = [];
        $nom = trim((string) ($post['nom'] ?? ''));
        $prenom = trim((string) ($post['prenom'] ?? ''));
        $email = trim((string) ($post['email'] ?? ''));
        $role = (string) ($post['role'] ?? '');
        $departement = trim((string) ($post['departement'] ?? ''));
        $telephone = trim((string) ($post['telephone'] ?? ''));
        $actif = isset($post['actif']) ? 1 : 0;
        $password = (string) ($post['password'] ?? '');
        $confirm = (string) ($post['password_confirm'] ?? '');

        if ($nom === '' || mb_strlen($nom) > 100) {
            $errors['nom'] = 'Indiquez un nom (100 caractères maximum).';
        }

        if ($prenom === '' || mb_strlen($prenom) > 100) {
            $errors['prenom'] = 'Indiquez un prénom (100 caractères maximum).';
        }

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 190) {
            $errors['email'] = 'Indiquez une adresse email valide.';
        } else {
            $existing = User::findByEmail($email);
            $currentId = $current === null ? 0 : (int) $current['id'];
            if ($existing !== null && (int) $existing['id'] !== $currentId) {
                $errors['email'] = 'Cet email est déjà utilisé.';
            }
        }

        if (!in_array($role, ['admin', 'responsable', 'enseignant'], true)) {
            $errors['role'] = 'Sélectionnez un rôle.';
        }

        if (mb_strlen($departement) > 120) {
            $errors['departement'] = 'Le département est limité à 120 caractères.';
        }

        if (mb_strlen($telephone) > 30) {
            $errors['telephone'] = 'Le téléphone est limité à 30 caractères.';
        }

        $passwordHash = null;
        if ($passwordRequired || $password !== '' || $confirm !== '') {
            if (mb_strlen($password) < 8) {
                $errors['password'] = 'Le mot de passe doit contenir au moins 8 caractères.';
            }
            if ($password !== $confirm) {
                $errors['password_confirm'] = 'La confirmation ne correspond pas.';
            }
            if (!isset($errors['password']) && !isset($errors['password_confirm'])) {
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            }
        }

        if ($current !== null) {
            $isSelf = (int) $current['id'] === Auth::id();
            $remainsAdmin = $role === 'admin' && $actif === 1;
            $wasActiveAdmin = $current['role'] === 'admin' && (int) $current['actif'] === 1;

            if ($isSelf && !$remainsAdmin) {
                $errors['role'] = 'Vous ne pouvez pas retirer votre propre rôle d\'administrateur ni désactiver votre compte.';
            }

            if ($wasActiveAdmin && !$remainsAdmin && User::countActiveAdmins((int) $current['id']) === 0) {
                $errors['role'] = 'Il doit rester au moins un administrateur actif.';
            }
        }

        return [
            'errors' => $errors,
            'password_hash' => $passwordHash,
            'data' => [
                'nom' => $nom,
                'prenom' => $prenom,
                'email' => $email,
                'password_hash' => $passwordHash ?? '',
                'role' => $role,
                'departement' => $departement === '' ? null : $departement,
                'telephone' => $telephone === '' ? null : $telephone,
                'actif' => $actif,
            ],
        ];
    }
}
