<?php
/**
 * PULSO - Advanced Analytics Dashboard
 * Novas métricas e visualizações em tempo real
 */
?>
<div class="pulso-analytics-container">
    <h2>📊 Dashboard Analytics Avançado</h2>
    
    <!-- KPI Cards -->
    <div class="kpi-grid">
        <div class="kpi-card" data-metric="listeners-today">
            <div class="kpi-icon">👥</div>
            <div class="kpi-value"><?= number_format($listeners_today ?? 1250) ?></div>
            <div class="kpi-label">Ouvintes Hoje</div>
            <div class="kpi-trend positive">↑ <?= $trend_listeners ?? 12.5 ?>%</div>
        </div>
        
        <div class="kpi-card" data-metric="engagement-rate">
            <div class="kpi-icon">⚡</div>
            <div class="kpi-value"><?= $engagement_rate ?? 78.5 ?>%</div>
            <div class="kpi-label">Engajamento</div>
            <div class="kpi-trend positive">↑ <?= $trend_engagement ?? 8.3 ?>%</div>
        </div>
        
        <div class="kpi-card" data-metric="avg-session">
            <div class="kpi-icon">⏱️</div>
            <div class="kpi-value"><?= $avg_session ?? 45 ?></div>
            <div class="kpi-label">Sessão Média (min)</div>
            <div class="kpi-trend neutral">→ +<?= $trend_session ?? 2 ?> min</div>
        </div>
        
        <div class="kpi-card" data-metric="active-listeners">
            <div class="kpi-icon">🔴</div>
            <div class="kpi-value"><?= $active_now ?? 248 ?></div>
            <div class="kpi-label">Online Agora</div>
            <div class="kpi-trend positive">✓ Transmitindo</div>
        </div>
    </div>

    <!-- Charts Row 1 -->
    <div class="charts-row">
        <div class="chart-container">
            <h3>📈 Ouvintes por Hora</h3>
            <canvas id="chart-listeners-hour" height="300"></canvas>
        </div>
        
        <div class="chart-container">
            <h3>🎵 Top 5 Músicas</h3>
            <canvas id="chart-top-songs" height="300"></canvas>
        </div>
    </div>

    <!-- Charts Row 2 -->
    <div class="charts-row">
        <div class="chart-container">
            <h3>👥 Demográfico</h3>
            <canvas id="chart-demographic" height="300"></canvas>
        </div>
        
        <div class="chart-container">
            <h3>📱 Dispositivos</h3>
            <canvas id="chart-devices" height="300"></canvas>
        </div>
    </div>

    <!-- Real-time Activity -->
    <div class="activity-container">
        <h3>⚡ Atividade em Tempo Real</h3>
        <div class="activity-feed" id="activity-feed">
            <div class="activity-item">
                <span class="badge">+</span>
                <span class="text">João entrou na transmissão</span>
                <span class="time">agora</span>
            </div>
            <div class="activity-item">
                <span class="badge">♫</span>
                <span class="text">Música "Song Name" foi solicitada</span>
                <span class="time">há 2 min</span>
            </div>
            <div class="activity-item">
                <span class="badge">🎁</span>
                <span class="text">Maria ganhou prémio da promoção</span>
                <span class="time">há 5 min</span>
            </div>
        </div>
    </div>
</div>

<style>
.pulso-analytics-container {
    padding: 20px;
    background: linear-gradient(135deg, #0a0e27 0%, #1a1f3a 100%);
    color: #fff;
    border-radius: 8px;
}

.pulso-analytics-container h2 {
    margin-top: 0;
    color: #fff;
    margin-bottom: 30px;
}

.kpi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 20px;
    margin-bottom: 40px;
}

.kpi-card {
    background: rgba(255, 255, 255, 0.05);
    padding: 25px;
    border-radius: 12px;
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-left: 4px solid #00d4ff;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    cursor: pointer;
    position: relative;
    overflow: hidden;
}

.kpi-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, rgba(0, 212, 255, 0.1) 0%, transparent 100%);
    opacity: 0;
    transition: opacity 0.3s ease;
}

.kpi-card:hover {
    transform: translateY(-4px);
    border-left-color: #00ffff;
    box-shadow: 0 8px 24px rgba(0, 212, 255, 0.2);
}

.kpi-card:hover::before {
    opacity: 1;
}

.kpi-icon {
    font-size: 2em;
    margin-bottom: 10px;
}

.kpi-value {
    font-size: 2.8em;
    font-weight: bold;
    color: #00d4ff;
    margin-bottom: 5px;
    position: relative;
    z-index: 1;
}

.kpi-label {
    color: rgba(255, 255, 255, 0.7);
    font-size: 0.95em;
    margin-bottom: 10px;
    position: relative;
    z-index: 1;
}

.kpi-trend {
    font-size: 0.9em;
    padding: 6px 12px;
    border-radius: 6px;
    display: inline-block;
    position: relative;
    z-index: 1;
}

.kpi-trend.positive {
    background: rgba(76, 175, 80, 0.2);
    color: #4caf50;
}

.kpi-trend.neutral {
    background: rgba(255, 193, 7, 0.2);
    color: #ffc107;
}

.charts-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 20px;
    margin-bottom: 40px;
}

.chart-container {
    background: rgba(255, 255, 255, 0.05);
    padding: 25px;
    border-radius: 12px;
    border: 1px solid rgba(255, 255, 255, 0.1);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

.chart-container h3 {
    margin-top: 0;
    color: #fff;
    margin-bottom: 20px;
    font-size: 1.1em;
}

.activity-container {
    background: rgba(255, 255, 255, 0.05);
    padding: 25px;
    border-radius: 12px;
    border: 1px solid rgba(255, 255, 255, 0.1);
    margin-bottom: 20px;
}

.activity-container h3 {
    margin-top: 0;
    color: #fff;
    margin-bottom: 20px;
}

.activity-feed {
    max-height: 400px;
    overflow-y: auto;
}

.activity-item {
    display: flex;
    align-items: center;
    padding: 12px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    gap: 15px;
    animation: slideIn 0.3s ease;
}

.activity-item:last-child {
    border-bottom: none;
}

.activity-item .badge {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: linear-gradient(135deg, #00d4ff, #0099ff);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    flex-shrink: 0;
}

.activity-item .text {
    flex-grow: 1;
    color: rgba(255, 255, 255, 0.9);
}

.activity-item .time {
    color: rgba(255, 255, 255, 0.5);
    font-size: 0.85em;
    white-space: nowrap;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateX(-10px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

@media (max-width: 768px) {
    .kpi-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .charts-row {
        grid-template-columns: 1fr;
    }
}
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    initializeCharts();
    startRealtimeUpdates();
});

function initializeCharts() {
    // Listeners by hour
    const ctx1 = document.getElementById('chart-listeners-hour');
    if (ctx1) {
        new Chart(ctx1.getContext('2d'), {
            type: 'line',
            data: {
                labels: ['00h', '04h', '08h', '12h', '16h', '20h', '23h'],
                datasets: [{
                    label: 'Ouvintes',
                    data: [150, 120, 280, 450, 520, 480, 380],
                    borderColor: '#00d4ff',
                    backgroundColor: 'rgba(0, 212, 255, 0.1)',
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: '#00d4ff',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        labels: { color: '#fff' }
                    }
                },
                scales: {
                    y: {
                        ticks: { color: 'rgba(255, 255, 255, 0.7)' },
                        grid: { color: 'rgba(255, 255, 255, 0.1)' }
                    },
                    x: {
                        ticks: { color: 'rgba(255, 255, 255, 0.7)' },
                        grid: { color: 'rgba(255, 255, 255, 0.1)' }
                    }
                }
            }
        });
    }

    // Top songs
    const ctx2 = document.getElementById('chart-top-songs');
    if (ctx2) {
        new Chart(ctx2.getContext('2d'), {
            type: 'bar',
            data: {
                labels: ['Song 1', 'Song 2', 'Song 3', 'Song 4', 'Song 5'],
                datasets: [{
                    label: 'Reproduções',
                    data: [145, 128, 112, 98, 87],
                    backgroundColor: [
                        'rgba(0, 212, 255, 0.8)',
                        'rgba(0, 150, 255, 0.8)',
                        'rgba(100, 200, 255, 0.8)',
                        'rgba(0, 200, 255, 0.6)',
                        'rgba(0, 180, 220, 0.6)'
                    ],
                    borderColor: 'rgba(0, 212, 255, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                indexAxis: 'y',
                plugins: {
                    legend: { labels: { color: '#fff' } }
                },
                scales: {
                    y: {
                        ticks: { color: 'rgba(255, 255, 255, 0.7)' },
                        grid: { color: 'rgba(255, 255, 255, 0.1)' }
                    },
                    x: {
                        ticks: { color: 'rgba(255, 255, 255, 0.7)' },
                        grid: { color: 'rgba(255, 255, 255, 0.1)' }
                    }
                }
            }
        });
    }
}

function startRealtimeUpdates() {
    // Update activity feed every 10 seconds
    setInterval(() => {
        // Aqui você faria fetch real para backend
        console.log('Updating metrics...');
    }, 10000);
}
</script>
