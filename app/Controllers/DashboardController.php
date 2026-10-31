<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\Mission;

class DashboardController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();
        $onlyUserId = Auth::managesMissions() ? null : Auth::id();

        $this->render('dashboard/index', [
            'title' => 'Tableau de bord',
            'summary' => Mission::summary($onlyUserId),
            'recent' => Mission::recent($onlyUserId, 8),
            'destinations' => Mission::topDestinations($onlyUserId, 5),
            'monthly' => Mission::monthly($onlyUserId, 6),
            'scopeLabel' => $onlyUserId === null
                ? 'Ensemble des missions'
                : 'Vos missions',
        ]);
    }
}
