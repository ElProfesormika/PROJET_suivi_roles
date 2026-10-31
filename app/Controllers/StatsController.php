<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\Mission;

class StatsController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();
        $onlyUserId = Auth::managesMissions() ? null : Auth::id();

        $this->render('stats/index', [
            'title' => 'Statistiques',
            'summary' => Mission::summary($onlyUserId),
            'destinations' => Mission::topDestinations($onlyUserId, 8),
            'monthly' => Mission::monthly($onlyUserId, 6),
            'teachers' => $onlyUserId === null ? Mission::byTeacher() : [],
            'departments' => $onlyUserId === null ? Mission::byDepartment() : [],
            'global' => $onlyUserId === null,
            'scopeLabel' => $onlyUserId === null
                ? 'Statistiques globales'
                : 'Statistiques personnelles',
        ]);
    }
}
