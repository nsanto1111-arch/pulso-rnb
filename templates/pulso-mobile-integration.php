<?php
/**
 * PULSO - Mobile App Integration
 * Integração com aplicativo mobile
 */
?>
<div class="pulso-mobile-container">
    <h2>📱 Integração Mobile</h2>
    
    <!-- QR Code para Download -->
    <div class="mobile-section">
        <div class="qr-download">
            <div class="qr-code">
                <div class="qr-placeholder">📱</div>
                <p>Escaneie para baixar o app</p>
            </div>
            <div class="download-links">
                <a href="#" class="store-link ios">
                    <span>🍎</span> App Store
                </a>
                <a href="#" class="store-link android">
                    <span>🤖</span> Google Play
                </a>
            </div>
        </div>
    </div>

    <!-- Estatísticas do App -->
    <div class="app-stats">
        <div class="stat-card">
            <div class="stat-number">12,450</div>
            <div class="stat-label">Downloads</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">4.8⭐</div>
            <div class="stat-label">Avaliação</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">8,920</div>
            <div class="stat-label">Usuários Ativos</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">98%</div>
            <div class="stat-label">Retenção</div>
        </div>
    </div>

    <!-- Features do App -->
    <div class="app-features">
        <h3>✨ Recursos do Aplicativo</h3>
        <div class="features-grid">
            <div class="feature">
                <div class="feature-icon">🎵</div>
                <div class="feature-name">Streaming ao Vivo</div>
                <div class="feature-desc">Ouça em qualidade alta ou economize dados</div>
                <span class="badge">✓ Ativo</span>
            </div>
            <div class="feature">
                <div class="feature-icon">🔔</div>
                <div class="feature-name">Push Notifications</div>
                <div class="feature-desc">Receba alertas de promoções e eventos</div>
                <span class="badge">✓ Ativo</span>
            </div>
            <div class="feature">
                <div class="feature-icon">🎁</div>
                <div class="feature-name">Requisição de Músicas</div>
                <div class="feature-desc">Solicite suas músicas favoritas</div>
                <span class="badge">✓ Ativo</span>
            </div>
            <div class="feature">
                <div class="feature-icon">🎰</div>
                <div class="feature-name">Participação em Promoções</div>
                <div class="feature-desc">Participe de sorteios e concursos</div>
                <span class="badge">✓ Ativo</span>
            </div>
            <div class="feature">
                <div class="feature-icon">❤️</div>
                <div class="feature-name">Favoritos</div>
                <div class="feature-desc">Salve suas músicas favoritas</div>
                <span class="badge">✓ Ativo</span>
            </div>
            <div class="feature">
                <div class="feature-icon">👥</div>
                <div class="feature-name">Comunidade</div>
                <div class="feature-desc">Conecte-se com outros ouvintes</div>
                <span class="badge">🚧 Em Breve</span>
            </div>
        </div>
    </div>

    <!-- Notificações Push -->
    <div class="push-notifications">
        <h3>📲 Gerenciar Notificações</h3>
        <div class="notification-settings">
            <div class="notification-item">
                <input type="checkbox" id="promo-notif" checked>
                <label for="promo-notif">
                    <strong>Notificações de Promoções</strong>
                    <span>Receba alertas de promoções e sorteios</span>
                </label>
            </div>
            <div class="notification-item">
                <input type="checkbox" id="event-notif" checked>
                <label for="event-notif">
                    <strong>Eventos Especiais</strong>
                    <span>Notifique-me de shows e eventos ao vivo</span>
                </label>
            </div>
            <div class="notification-item">
                <input type="checkbox" id="music-notif" checked>
                <label for="music-notif">
                    <strong>Novas Músicas</strong>
                    <span>Alerte quando novas músicas são adicionadas</span>
                </label>
            </div>
            <div class="notification-item">
                <input type="checkbox" id="follow-notif">
                <label for="follow-notif">
                    <strong>Artistas Seguidos</strong>
                    <span>Notifique quando artistas que sigo tocam</span>
                </label>
            </div>
        </div>
    </div>
</div>

<style>
.pulso-mobile-container {
    padding: 20px;
    background: #f5f7fa;
}

.pulso-mobile-container h2 {
    color: #333;
    margin-bottom: 30px;
}

.mobile-section {
    text-align: center;
    margin-bottom: 40px;
}

.qr-download {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 30px;
    flex-wrap: wrap;
}

.qr-code {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    text-align: center;
}

.qr-placeholder {
    font-size: 4em;
    margin-bottom: 10px;
}

.download-links {
    display: flex;
    gap: 10px;
    flex-direction: column;
}

.store-link {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 12px 24px;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 600;
    color: white;
    cursor: pointer;
}

.store-link.ios {
    background: #000;
}

.store-link.android {
    background: #3ddc84;
    color: #000;
}

.app-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
    margin-bottom: 40px;
}

.stat-card {
    background: white;
    padding: 20px;
    border-radius: 8px;
    text-align: center;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.stat-number {
    font-size: 2em;
    font-weight: bold;
    color: #1976d2;
    margin-bottom: 5px;
}

.stat-label {
    color: #666;
    font-size: 0.9em;
}

.app-features {
    background: white;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.app-features h3 {
    margin-top: 0;
    color: #333;
}

.features-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
}

.feature {
    padding: 15px;
    background: #f5f5f5;
    border-radius: 6px;
    text-align: center;
    position: relative;
}

.feature-icon {
    font-size: 2.5em;
    margin-bottom: 10px;
}

.feature-name {
    font-weight: 600;
    color: #333;
    margin-bottom: 5px;
}

.feature-desc {
    font-size: 0.85em;
    color: #666;
}

.badge {
    display: inline-block;
    margin-top: 10px;
    padding: 4px 8px;
    border-radius: 3px;
    font-size: 0.8em;
    background: #c8e6c9;
    color: #2e7d32;
}

.badge:contains("Em Breve") {
    background: #fff3e0;
    color: #e65100;
}

.push-notifications {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.push-notifications h3 {
    margin-top: 0;
    color: #333;
}

.notification-settings {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.notification-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 12px;
    background: #f5f5f5;
    border-radius: 6px;
}

.notification-item input[type="checkbox"] {
    margin-top: 4px;
    cursor: pointer;
}

.notification-item label {
    flex-grow: 1;
    cursor: pointer;
}

.notification-item strong {
    display: block;
    color: #333;
    margin-bottom: 3px;
}

.notification-item span {
    font-size: 0.85em;
    color: #666;
}
</style>
