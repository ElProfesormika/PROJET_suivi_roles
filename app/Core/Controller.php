<?php

declare(strict_types=1);

namespace App\Core;

class Controller
{
    public function render(string $view, array $data = [], bool $layout = true): void
    {
        $viewFile = APP_ROOT . '/app/Views/' . $view . '.php';
        if (!is_file($viewFile)) {
            http_response_code(500);
            echo 'Vue introuvable.';
            return;
        }

        extract($data, EXTR_SKIP);

        if (!$layout) {
            require $viewFile;
            Flash::clearFormState();
            return;
        }

        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        require APP_ROOT . '/app/Views/layouts/header.php';
        echo $content;
        require APP_ROOT . '/app/Views/layouts/footer.php';
        Flash::clearFormState();
    }

    protected function redirect(string $path): void
    {
        if (!str_starts_with($path, '/')) {
            $path = '/' . $path;
        }

        header('Location: ' . url($path));
        exit;
    }

    protected function requireAuth(): array
    {
        $user = Auth::user();
        if ($user === null) {
            Flash::danger('Connectez-vous pour accéder à cette page.');
            $this->redirect('/login');
        }

        return $user;
    }

    protected function requireRole(array $roles): array
    {
        $user = $this->requireAuth();
        if (!in_array($user['role'], $roles, true)) {
            $this->forbid();
        }

        return $user;
    }

    protected function forbid(): void
    {
        http_response_code(403);
        $this->render('errors/403', [
            'title' => 'Acces refuse',
        ]);
        exit;
    }

    protected function notFound(): void
    {
        http_response_code(404);
        $this->render('errors/404', [
            'title' => 'Page introuvable',
        ]);
        exit;
    }

    protected function rejectCsrf(): void
    {
        if (!Csrf::verify($_POST['_token'] ?? null)) {
            http_response_code(419);
            $this->render('errors/419', [
                'title' => 'Session expiree',
            ]);
            exit;
        }
    }

    protected function redirectBackWithErrors(string $path, array $errors, array $input, string $message = 'Le formulaire contient des erreurs.'): void
    {
        Flash::danger($message);
        Flash::withErrors($errors);
        Flash::withInput($input);
        $this->redirect($path);
    }
}
