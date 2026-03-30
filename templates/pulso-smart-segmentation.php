<?php
/**
 * PULSO - Smart Segmentation
 * Segmentação inteligente de ouvintes
 */
?>
<div class="pulso-segmentation-container">
    <h2>🎯 Segmentação Inteligente</h2>
    
    <div class="segmentation-grid">
        <!-- Comportamento -->
        <div class="segment-card">
            <h3>📊 Por Comportamento</h3>
            <div class="segment-list">
                <div class="segment-item">
                    <span class="segment-badge" style="background: #4CAF50;">High</span>
                    <div class="segment-info">
                        <div class="segment-name">Ouvintes Frequentes</div>
                        <div class="segment-count">245 ouvintes</div>
                        <div class="segment-metrics">
                            <span>↑ 8.5% crescimento</span>
                        </div>
                    </div>
                    <button class="btn-segment">Ações</button>
                </div>
                <div class="segment-item">
                    <span class="segment-badge" style="background: #FFC107;">Medium</span>
                    <div class="segment-info">
                        <div class="segment-name">Ouvintes Ocasionais</div>
                        <div class="segment-count">512 ouvintes</div>
                        <div class="segment-metrics">
                            <span>→ Estável</span>
                        </div>
                    </div>
                    <button class="btn-segment">Ações</button>
                </div>
                <div class="segment-item">
                    <span class="segment-badge" style="background: #F44336;">Low</span>
                    <div class="segment-info">
                        <div class="segment-name">Ouvintes em Risco</div>
                        <div class="segment-count">128 ouvintes</div>
                        <div class="segment-metrics">
                            <span>↓ 12% redução</span>
                        </div>
                    </div>
                    <button class="btn-segment">Re-engajar</button>
                </div>
            </div>
        </div>

        <!-- Preferências Musicais -->
        <div class="segment-card">
            <h3>🎵 Por Preferências Musicais</h3>
            <div class="segment-list">
                <div class="segment-item">
                    <div class="segment-name">Fãs de Rock</div>
                    <div class="segment-count">156 ouvintes</div>
                    <div class="progress-bar">
                        <div class="progress" style="width: 45%"></div>
                    </div>
                </div>
                <div class="segment-item">
                    <div class="segment-name">Fãs de Pop</div>
                    <div class="segment-count">198 ouvintes</div>
                    <div class="progress-bar">
                        <div class="progress" style="width: 58%"></div>
                    </div>
                </div>
                <div class="segment-item">
                    <div class="segment-name">Fãs de MPB</div>
                    <div class="segment-count">214 ouvintes</div>
                    <div class="progress-bar">
                        <div class="progress" style="width: 62%"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Horários -->
        <div class="segment-card">
            <h3>⏰ Por Horários de Escuta</h3>
            <div class="segment-list">
                <div class="segment-item">
                    <div class="segment-name">Madrugada (00-06h)</div>
                    <div class="segment-count">89 ouvintes</div>
                    <div class="segment-metrics"><span>11% do total</span></div>
                </div>
                <div class="segment-item">
                    <div class="segment-name">Manhã (06-12h)</div>
                    <div class="segment-count">456 ouvintes</div>
                    <div class="segment-metrics"><span>52% do total</span></div>
                </div>
                <div class="segment-item">
                    <div class="segment-name">Tarde (12-18h)</div>
                    <div class="segment-count">289 ouvintes</div>
                    <div class="segment-metrics"><span>28% do total</span></div>
                </div>
                <div class="segment-item">
                    <div class="segment-name">Noite (18-00h)</div>
                    <div class="segment-count">145 ouvintes</div>
                    <div class="segment-metrics"><span>9% do total</span></div>
                </div>
            </div>
        </div>
    </div>

    <!-- AI Recommendations -->
    <div class="ai-recommendations">
        <h3>🤖 Recomendações de IA</h3>
        <div class="recommendations-list">
            <div class="recommendation">
                <span class="icon">💡</span>
                <div class="rec-text">
                    <div class="rec-title">Campanha de Re-engajamento</div>
                    <div class="rec-desc">128 ouvintes em risco podem sair. Recomendamos uma campanha com músicas favoritas e sorteio.</div>
                </div>
                <button class="btn-recommendation">Executar</button>
            </div>
            <div class="recommendation">
                <span class="icon">🎯</span>
                <div class="rec-text">
                    <div class="rec-title">Cross-Selling de Promoções</div>
                    <div class="rec-desc">Fãs de Rock não têm alto engajamento com promoções. Teste com promoções de shows locais.</div>
                </div>
                <button class="btn-recommendation">Executar</button>
            </div>
            <div class="recommendation">
                <span class="icon">📈</span>
                <div class="rec-text">
                    <div class="rec-title">Programação Otimizada</div>
                    <div class="rec-desc">Aumente conteúdo de MPB entre 12-18h para capturar audiência matinal em transição.</div>
                </div>
                <button class="btn-recommendation">Executar</button>
            </div>
        </div>
    </div>
</div>

<style>
.pulso-segmentation-container {
    padding: 20px;
}

.pulso-segmentation-container h2 {
    color: #333;
    margin-bottom: 30px;
}

.segmentation-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 20px;
    margin-bottom: 40px;
}

.segment-card {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.segment-card h3 {
    margin-top: 0;
    color: #333;
    margin-bottom: 20px;
}

.segment-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.segment-item {
    display: flex;
    align-items: center;
    padding: 12px;
    background: #f5f5f5;
    border-radius: 6px;
    gap: 12px;
}

.segment-badge {
    padding: 4px 8px;
    border-radius: 4px;
    color: white;
    font-weight: bold;
    font-size: 0.8em;
    flex-shrink: 0;
}

.segment-info {
    flex-grow: 1;
}

.segment-name {
    font-weight: 600;
    color: #333;
}

.segment-count {
    font-size: 0.9em;
    color: #666;
}

.segment-metrics {
    font-size: 0.8em;
    color: #999;
}

.progress-bar {
    width: 100%;
    height: 6px;
    background: #e0e0e0;
    border-radius: 3px;
    margin-top: 8px;
}

.progress {
    height: 100%;
    background: linear-gradient(90deg, #00d4ff, #0099ff);
    border-radius: 3px;
}

.btn-segment {
    padding: 6px 12px;
    background: #1976d2;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 0.85em;
    flex-shrink: 0;
}

.btn-segment:hover {
    background: #1565c0;
}

.ai-recommendations {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 30px;
    border-radius: 8px;
    color: white;
}

.ai-recommendations h3 {
    margin-top: 0;
    color: white;
    margin-bottom: 20px;
}

.recommendations-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.recommendation {
    display: flex;
    align-items: flex-start;
    gap: 15px;
    padding: 15px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 6px;
}

.recommendation .icon {
    font-size: 1.5em;
    flex-shrink: 0;
}

.rec-text {
    flex-grow: 1;
}

.rec-title {
    font-weight: 600;
    margin-bottom: 5px;
}

.rec-desc {
    font-size: 0.9em;
    color: rgba(255, 255, 255, 0.9);
}

.btn-recommendation {
    padding: 8px 16px;
    background: white;
    color: #667eea;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-weight: 600;
    flex-shrink: 0;
}

.btn-recommendation:hover {
    background: #f0f0f0;
}
</style>
