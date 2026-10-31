<?php
$hasActivity = false;
foreach ($monthly as $point) {
    if ((int) $point['nb'] > 0) {
        $hasActivity = true;
        break;
    }
}
$chartPayload = [
    'labels' => array_column($monthly, 'label'),
    'missions' => array_map('intval', array_column($monthly, 'nb')),
    'km' => array_map('intval', array_column($monthly, 'km')),
    'statuts' => [
        (int) $summary['en_attente'],
        (int) $summary['validees'],
        (int) $summary['refusees'],
    ],
];
?>
<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header bg-white">Kilométrage des six derniers mois</div>
            <div class="card-body">
                <?php if (!$hasActivity): ?>
                    <p class="empty-note mb-0">Aucune mission sur cette période.</p>
                <?php else: ?>
                    <div class="chart-box"><canvas id="chart-mois"></canvas></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header bg-white">Répartition des statuts</div>
            <div class="card-body">
                <?php if ((int) $summary['total'] === 0): ?>
                    <p class="empty-note mb-0">Aucune mission enregistrée.</p>
                <?php else: ?>
                    <div class="chart-box"><canvas id="chart-statuts"></canvas></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php if ($hasActivity || (int) $summary['total'] > 0): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.6/dist/chart.umd.min.js"></script>
<script>
const payload = <?= json_encode($chartPayload, JSON_UNESCAPED_UNICODE) ?>;
const navy = '#16324f';
const teal = '#1c7f8e';
const gold = '#b08948';
const burgundy = '#8d2b3d';

if (document.getElementById('chart-mois')) {
    new Chart(document.getElementById('chart-mois'), {
        type: 'bar',
        data: {
            labels: payload.labels,
            datasets: [{
                label: 'Kilomètres',
                data: payload.km,
                backgroundColor: teal,
                borderRadius: 8,
                maxBarThickness: 42
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { precision: 0 } },
                x: { grid: { display: false } }
            }
        }
    });
}

if (document.getElementById('chart-statuts')) {
    new Chart(document.getElementById('chart-statuts'), {
        type: 'doughnut',
        data: {
            labels: ['En attente', 'Validées', 'Refusées'],
            datasets: [{
                data: payload.statuts,
                backgroundColor: [gold, teal, burgundy],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom' } }
        }
    });
}
</script>
<?php endif; ?>
