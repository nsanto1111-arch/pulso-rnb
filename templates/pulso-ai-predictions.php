<?php
/**
 * PULSO - AI-Powered Predictions
 * Previsões inteligentes baseadas em machine learning
 */
?>
<div class="pulso-predictions-container">
    <h2>🤖 Previsões com IA</h2>
    
    <!-- Churn Prediction -->
    <div class="prediction-card">
        <div class="card-header">
            <h3>⚠️ Previsão de Churn</h3>
            <span class="status-badge critical">Alto Risco</span>
        </div>
        <div class="prediction-content">
            <p class="description">Ouvintes com risco alto de parar de escutar nos próximos 30 dias:</p>
            
            <div class="risk-list">
                <div class="risk-item high-risk">
                    <div class="risk-avatar">👤</div>
                    <div class="risk-details">
                        <div class="risk-name">João Silva</div>
                        <div class="risk-reason">Sem atividade há 7 dias, usuário histórico</div>
                        <div class="risk-score">
                            <span class="score-label">Risco:</span>
                            <span class="score-bar">
                                <span class="score-fill" style="width: 85%"></span>
                            </span>
                            <span class="score-value">85%</span>
                        </div>
                    </div>
                    <div class="risk-actions">
                        <button class="btn-action re-engage">Re-engajar</button>
                    </div>
                </div>
                
                <div class="risk-item high-risk">
                    <div class="risk-avatar">👤</div>
                    <div class="risk-details">
                        <div class="risk-name">Maria Santos</div>
                        <div class="risk-reason">Redução de 60% em escutas, pode estar mudando de estação</div>
                        <div class="risk-score">
                            <span class="score-label">Risco:</span>
                            <span class="score-bar">
                                <span class="score-fill" style="width: 72%"></span>
                            </span>
                            <span class="score-value">72%</span>
                        </div>
                    </div>
                    <div class="risk-actions">
                        <button class="btn-action re-engage">Re-engajar</button>
                    </div>
                </div>
                
                <div class="risk-item medium-risk">
                    <div class="risk-avatar">👤</div>
                    <div class="risk-details">
                        <div class="risk-name">Pedro Costa</div>
                        <div class="risk-reason">Mudança de padrão de escuta</div>
                        <div class="risk-score">
                            <span class="score-label">Risco:</span>
                            <span class="score-bar">
                                <span class="score-fill" style="width: 45%"></span>
                            </span>
                            <span class="score-value">45%</span>
                        </div>
                    </div>
                    <div class="risk-actions">
                        <button class="btn-action monitor">Monitorar</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Music Recommendations -->
    <div class="prediction-card">
        <div class="card-header">
            <h3>🎵 Recomendações de Músicas</h3>
            <span class="status-badge info">Baseado em IA</span>
        </div>
        <div class="prediction-content">
            <p class="description">Músicas recomendadas para aumentar engajamento:</p>
            
            <div class="recommendations-grid">
                <div class="rec-song">
                    <div class="song-cover">🎵</div>
                    <div class="song-info">
                        <div class="song-title">Nova Música Perfeita</div>
                        <div class="song-artist">Artista X</div>
                        <div class="match-score">Match: 94%</div>
                    </div>
                    <div class="rec-actions">
                        <button class="btn-tiny">Tocar</button>
                    </div>
                </div>
                
                <div class="rec-song">
                    <div class="song-cover">🎵</div>
                    <div class="song-info">
                        <div class="song-title">Sucesso em Alta</div>
                        <div class="song-artist">Artista Y</div>
                        <div class="match-score">Match: 89%</div>
                    </div>
                    <div class="rec-actions">
                        <button class="btn-tiny">Tocar</button>
                    </div>
                </div>
                
                <div class="rec-song">
                    <div class="song-cover">🎵</div>
                    <div class="song-info">
                        <div class="song-title">Tendência Viral</div>
                        <div class="song-artist">Artista Z</div>
                        <div class="match-score">Match: 87%</div>
                    </div>
                    <div class="rec-actions">
                        <button class="btn-tiny">Tocar</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Peak Time Predictions -->
    <div class="prediction-card">
        <div class="card-header">
            <h3>📈 Previsão de Picos de Audiência</h3>
            <span class="status-badge success">Próximas 24h</span>
        </div>
        <div class="prediction-content">
            <canvas id="peak-forecast-chart" height="200"></canvas>
            <div class="peak-insights">
                <div class="insight">
                    <span class="insight-icon">🔴</span>
                    <div class="insight-text">
                        <strong>Pico Esperado:</strong> 14h (456 ouvintes estimados) - Aumente conteúdo premium
                    </div>
                </div>
                <div class="insight">
                    <span class="insight-icon">⚡</span>
                    <div class="insight-text">
                        <strong>Vale Esperado:</strong> 04h (89 ouvintes estimados) - Replicar conteúdo bem-sucedido
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Anomaly Detection -->
    <div class="prediction-card alert">
        <div class="card-header">
            <h3>⚠️ Detecção de Anomalias</h3>
            <span class="status-badge warning">2 Anomalias Detectadas</span>
        </div>
        <div class="prediction-content">
            <div class="anomaly-item">
                <span class="anomaly-icon">🚨</span>
                <div class="anomaly-details">
                    <div class="anomaly-title">Pico Incomum de Atividades</div>
                    <div class="anomaly-desc">Aumento de 340% em requisições de música às 03h - possível bot</div>
                    <button class="btn-action investigate">Investigar</button>
                </div>
            </div>
            <div class="anomaly-item">
                <span class="anomaly-icon">⚠️</span>
                <div class="anomaly-details">
                    <div class="anomaly-title">Múltiplas Contas do Mesmo IP</div>
                    <div class="anomaly-desc">15 contas diferentes do IP 192.168.1.100 - possível fraude</div>
                    <button class="btn-action block">Bloquear</button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.pulso-predictions-container {
    padding: 20px;
    background: #f5f7fa;
}

.pulso-predictions-container h2 {
    color: #333;
    margin-bottom: 30px;
}

.prediction-card {
    background: white;
    border-radius: 8px;
    margin-bottom: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    overflow: hidden;
}

.prediction-card.alert {
    border-left: 4px solid #f44336;
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    background: #f9f9f9;
    border-bottom: 1px solid #e0e0e0;
}

.card-header h3 {
    margin: 0;
    color: #333;
}

.status-badge {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.85em;
    font-weight: 600;
}

.status-badge.critical {
    background: #ffcdd2;
    color: #c62828;
}

.status-badge.info {
    background: #bbdefb;
    color: #1565c0;
}

.status-badge.success {
    background: #c8e6c9;
    color: #2e7d32;
}

.status-badge.warning {
    background: #ffe0b2;
    color: #e65100;
}

.prediction-content {
    padding: 20px;
}

.description {
    color: #666;
    margin-bottom: 20px;
    font-size: 0.95em;
}

.risk-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.risk-item {
    display: flex;
    gap: 15px;
    padding: 15px;
    background: #f5f5f5;
    border-radius: 6px;
    border-left: 3px solid #ddd;
}

.risk-item.high-risk {
    border-left-color: #f44336;
    background: rgba(244, 67, 54, 0.05);
}

.risk-item.medium-risk {
    border-left-color: #ff9800;
    background: rgba(255, 152, 0, 0.05);
}

.risk-avatar {
    font-size: 2em;
    flex-shrink: 0;
}

.risk-details {
    flex-grow: 1;
}

.risk-name {
    font-weight: 600;
    color: #333;
}

.risk-reason {
    font-size: 0.9em;
    color: #666;
    margin-bottom: 8px;
}

.risk-score {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.9em;
}

.score-bar {
    display: inline-block;
    width: 100px;
    height: 4px;
    background: #e0e0e0;
    border-radius: 2px;
}

.score-fill {
    display: block;
    height: 100%;
    background: linear-gradient(90deg, #ff9800, #f44336);
    border-radius: 2px;
}

.score-value {
    font-weight: 600;
    color: #f44336;
}

.recommendations-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
}

.rec-song {
    display: flex;
    flex-direction: column;
    padding: 15px;
    background: #f5f5f5;
    border-radius: 6px;
    text-align: center;
}

.song-cover {
    font-size: 3em;
    margin-bottom: 10px;
}

.song-title {
    font-weight: 600;
    color: #333;
}

.song-artist {
    font-size: 0.9em;
    color: #666;
}

.match-score {
    font-size: 0.85em;
    color: #4caf50;
    font-weight: 600;
    margin: 8px 0;
}

.btn-action {
    padding: 6px 12px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 0.9em;
    font-weight: 600;
}

.btn-action.re-engage {
    background: #2196f3;
    color: white;
}

.btn-action.monitor {
    background: #ff9800;
    color: white;
}

.btn-action.investigate {
    background: #f44336;
    color: white;
}

.btn-action.block {
    background: #d32f2f;
    color: white;
}

.btn-tiny {
    padding: 4px 8px;
    background: #1976d2;
    color: white;
    border: none;
    border-radius: 3px;
    cursor: pointer;
    font-size: 0.8em;
}

.peak-insights {
    margin-top: 20px;
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.insight {
    display: flex;
    gap: 10px;
    padding: 10px;
    background: #f5f5f5;
    border-radius: 4px;
}

.insight-icon {
    font-size: 1.2em;
}

.insight-text {
    font-size: 0.9em;
    color: #333;
}

.anomaly-item {
    display: flex;
    gap: 15px;
    padding: 15px;
    background: #fff3e0;
    border-radius: 6px;
    margin-bottom: 10px;
    border-left: 3px solid #ff9800;
}

.anomaly-icon {
    font-size: 1.5em;
    flex-shrink: 0;
}

.anomaly-details {
    flex-grow: 1;
}

.anomaly-title {
    font-weight: 600;
    color: #333;
    margin-bottom: 5px;
}

.anomaly-desc {
    font-size: 0.9em;
    color: #666;
    margin-bottom: 10px;
}
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Peak forecast chart
    const ctx = document.getElementById('peak-forecast-chart');
    if (ctx) {
        new Chart(ctx.getContext('2d'), {
            type: 'line',
            data: {
                labels: ['00h', '04h', '08h', '12h', '16h', '20h', '24h'],
                datasets: [{
                    label: 'Audiência Prevista',
                    data: [89, 124, 245, 398, 456, 380, 156],
                    borderColor: '#1976d2',
                    backgroundColor: 'rgba(25, 118, 210, 0.1)',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4,
                    pointBackgroundColor: '#1976d2'
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: true } },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    }
});
</script>
