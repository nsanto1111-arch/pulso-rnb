<?php
// Preparar TODOS os dados
$kpis = $dados['kpis'] ?? [];
$insights = $dados['insights'] ?? [];
$horaria = $dados['horaria'] ?? [];
$previsao = $dados['previsao'] ?? [];
$correlacoes = $dados['correlacoes'] ?? [];
$municipios = $dados['municipios'] ?? [];

// Dados existentes
$paisesJson = json_encode(array_column($dados['paises'] ?? [], 'pais'));
$paisesTotalJson = json_encode(array_column($dados['paises'] ?? [], 'total'));
$idadesLabels = json_encode(array_keys($dados['idades'] ?? []));
$idadesValues = json_encode(array_values($dados['idades'] ?? []));
$generosLabels = json_encode(array_keys($dados['generos'] ?? []));
$generosValues = json_encode(array_values($dados['generos'] ?? []));
$generosMusicaisLabels = json_encode(array_keys($dados['generosMusicais'] ?? []));
$generosMusicaisValues = json_encode(array_values($dados['generosMusicais'] ?? []));
$crescimentoLabels = json_encode(array_column($dados['crescimento'] ?? [], 'data'));
$crescimentoValues = json_encode(array_column($dados['crescimento'] ?? [], 'novos'));

$totalOuvintes = $dados['distribuicao']['stats']['total'] ?? 0;
?>

<div class="demograficos-pro-v2" x-data="{ activeTab: 'executivo' }">
    
    <!-- MEGA STATS CARDS - KPIs Executivos -->
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:1.5rem;margin-bottom:2rem">
        <div style="background:linear-gradient(135deg,rgba(59,130,246,0.2),rgba(37,99,235,0.05));border:1px solid rgba(59,130,246,0.3);border-radius:20px;padding:2rem;position:relative;overflow:hidden">
            <div style="position:absolute;top:-20px;right:-20px;font-size:80px;opacity:0.1">👥</div>
            <div style="font-size:11px;color:rgba(255,255,255,0.6);margin-bottom:0.5rem;font-weight:700;letter-spacing:2px">ACTIVOS HOJE</div>
            <div style="font-size:48px;font-weight:900;color:#3b82f6;line-height:1;margin-bottom:0.5rem"><?= $kpis['activos_hoje'] ?? 0 ?></div>
            <div style="font-size:13px;color:<?= ($kpis['variacao_diaria'] ?? 0) >= 0 ? '#10b981' : '#ef4444' ?>;font-weight:600">
                <?= ($kpis['variacao_diaria'] ?? 0) >= 0 ? '↑' : '↓' ?> <?= abs($kpis['variacao_diaria'] ?? 0) ?>% vs ontem
            </div>
        </div>
        
        <div style="background:linear-gradient(135deg,rgba(16,185,129,0.2),rgba(5,150,105,0.05));border:1px solid rgba(16,185,129,0.3);border-radius:20px;padding:2rem;position:relative;overflow:hidden">
            <div style="position:absolute;top:-20px;right:-20px;font-size:80px;opacity:0.1">📈</div>
            <div style="font-size:11px;color:rgba(255,255,255,0.6);margin-bottom:0.5rem;font-weight:700;letter-spacing:2px">CRESCIMENTO MÊS</div>
            <div style="font-size:48px;font-weight:900;color:#10b981;line-height:1;margin-bottom:0.5rem">+<?= $kpis['taxa_crescimento_mes'] ?? 0 ?>%</div>
            <div style="font-size:13px;color:rgba(255,255,255,0.6);font-weight:600">
                <?= $kpis['novos_7d'] ?? 0 ?> novos esta semana
            </div>
        </div>
        
        <div style="background:linear-gradient(135deg,rgba(251,191,36,0.2),rgba(245,158,11,0.05));border:1px solid rgba(251,191,36,0.3);border-radius:20px;padding:2rem;position:relative;overflow:hidden">
            <div style="position:absolute;top:-20px;right:-20px;font-size:80px;opacity:0.1">💎</div>
            <div style="font-size:11px;color:rgba(255,255,255,0.6);margin-bottom:0.5rem;font-weight:700;letter-spacing:2px">ENGAJAMENTO</div>
            <div style="font-size:48px;font-weight:900;color:#fbbf24;line-height:1;margin-bottom:0.5rem"><?= $kpis['taxa_engagement'] ?? 0 ?></div>
            <div style="font-size:13px;color:rgba(255,255,255,0.6);font-weight:600">
                part. por ouvinte
            </div>
        </div>
        
        <div style="background:linear-gradient(135deg,rgba(139,92,246,0.2),rgba(124,58,237,0.05));border:1px solid rgba(139,92,246,0.3);border-radius:20px;padding:2rem;position:relative;overflow:hidden">
            <div style="position:absolute;top:-20px;right:-20px;font-size:80px;opacity:0.1">💰</div>
            <div style="font-size:11px;color:rgba(255,255,255,0.6);margin-bottom:0.5rem;font-weight:700;letter-spacing:2px">VALOR AUDIÊNCIA</div>
            <div style="font-size:48px;font-weight:900;color:#8b5cf6;line-height:1;margin-bottom:0.5rem"><?= number_format($kpis['valor_audiencia'] ?? 0) ?>€</div>
            <div style="font-size:13px;color:rgba(255,255,255,0.6);font-weight:600">
                estimativa mensal
            </div>
        </div>
    </div>
    
    <!-- INSIGHTS AUTOMÁTICOS COM IA -->
    <?php if (!empty($insights)): ?>
    <div style="background:linear-gradient(135deg,rgba(0,229,255,0.1),rgba(124,58,237,0.05));border:1px solid rgba(0,229,255,0.2);border-radius:16px;padding:1.5rem;margin-bottom:2rem">
        <div style="display:flex;align-items:center;gap:1rem;margin-bottom:1rem">
            <div style="font-size:28px">🤖</div>
            <div>
                <h3 style="margin:0;font-size:18px;font-weight:800">Insights Automáticos</h3>
                <p style="margin:0;font-size:13px;color:var(--text-3)">Análise inteligente da sua audiência</p>
            </div>
        </div>
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:1rem">
            <?php foreach (array_slice($insights, 0, 3) as $insight): ?>
            <div style="background:rgba(0,0,0,0.3);border:1px solid rgba(255,255,255,0.06);border-radius:12px;padding:1.25rem">
                <div style="font-size:32px;margin-bottom:0.75rem"><?= $insight['icone'] ?></div>
                <div style="font-weight:700;margin-bottom:0.5rem;font-size:14px"><?= $insight['titulo'] ?></div>
                <div style="font-size:12px;color:var(--text-3);margin-bottom:0.75rem"><?= $insight['descricao'] ?></div>
                <div style="font-size:11px;color:var(--accent);font-weight:600">💡 <?= $insight['accao'] ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- TABS NAVIGATION -->
    <div class="tabs-container">
        <div class="tabs-nav">
            <button class="tab-button" :class="{ 'active': activeTab === 'executivo' }" @click="activeTab = 'executivo'">
                📊 Painel Executivo
            </button>
            <button class="tab-button" :class="{ 'active': activeTab === 'geografia' }" @click="activeTab = 'geografia'">
                🗺️ Geografia
            </button>
            <button class="tab-button" :class="{ 'active': activeTab === 'audiencia' }" @click="activeTab = 'audiencia'">
                👥 Audiência
            </button>
            <button class="tab-button" :class="{ 'active': activeTab === 'gostos' }" @click="activeTab = 'gostos'">
                🎵 Gostos
            </button>
            <button class="tab-button" :class="{ 'active': activeTab === 'temporal' }" @click="activeTab = 'temporal'">
                📈 Análise Temporal
            </button>
            <button class="tab-button" :class="{ 'active': activeTab === 'comparador' }" @click="activeTab = 'comparador'">
                ⚡ Comparador
            </button>
        </div>

        <!-- ABA 1: PAINEL EXECUTIVO -->
        <div class="tab-content" x-show="activeTab === 'executivo'" x-transition>
            <div style="display:grid;grid-template-columns:2fr 1fr;gap:2rem">
                <div>
                    <div class="card" style="margin-bottom:1.5rem">
                        <h3 style="margin-bottom:1rem">📈 Evolução Últimos 90 Dias</h3>
                        <canvas id="chartEvolucao" style="height:300px"></canvas>
                    </div>
                    
                    <div class="card">
                        <h3 style="margin-bottom:1rem">⏰ Actividade por Hora do Dia</h3>
                        <canvas id="chartHoraria" style="height:250px"></canvas>
                    </div>
                </div>
                
                <div>
                    <div class="card" style="margin-bottom:1.5rem">
                        <h3 style="margin-bottom:1rem">🔮 Previsão Próximo Mês</h3>
                        <div style="text-align:center;padding:2rem">
                            <div style="font-size:64px;font-weight:900;color:var(--accent);margin-bottom:0.5rem">
                                +<?= $previsao['previsao_proximo_mes'] ?? 0 ?>
                            </div>
                            <div style="font-size:14px;color:var(--text-3);margin-bottom:1rem">novos ouvintes esperados</div>
                            <div style="padding:1rem;background:rgba(0,229,255,0.08);border-radius:8px">
                                <div style="font-size:12px;color:var(--text-3)">Baseado em média de</div>
                                <div style="font-size:24px;font-weight:700;color:var(--accent)"><?= $previsao['media_diaria'] ?? 0 ?></div>
                                <div style="font-size:12px;color:var(--text-3)">cadastros/dia</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card">
                        <h3 style="margin-bottom:1rem">🎯 Metas vs Realizado</h3>
                        <div style="padding:1rem">
                            <div style="margin-bottom:1.5rem">
                                <div style="display:flex;justify-content:space-between;margin-bottom:0.5rem">
                                    <span style="font-size:13px;font-weight:600">Meta Mensal: 100</span>
                                    <span style="font-size:13px;color:var(--accent);font-weight:700"><?= $kpis['novos_7d'] ?? 0 ?> (<?= round(($kpis['novos_7d'] ?? 0) / 100 * 100) ?>%)</span>
                                </div>
                                <div style="height:8px;background:rgba(255,255,255,0.1);border-radius:4px;overflow:hidden">
                                    <div style="height:100%;background:linear-gradient(90deg,var(--accent),var(--accent2));width:<?= min(round(($kpis['novos_7d'] ?? 0) / 100 * 100), 100) ?>%;transition:width 1s"></div>
                                </div>
                            </div>
                            
                            <div style="font-size:11px;color:var(--text-3);text-align:center;padding:1rem;background:rgba(0,0,0,0.2);border-radius:8px">
                                💡 Mantendo este ritmo, meta será atingida em <?= round(100 / max($previsao['media_diaria'] ?? 1, 1)) ?> dias
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ABA 2: GEOGRAFIA AVANÇADA -->
        <div class="tab-content" x-show="activeTab === 'geografia'" x-transition>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:2rem">
                <div class="card">
                    <h3 style="margin-bottom:1rem">🗺️ Distribuição por País</h3>
                    <canvas id="chartPaises" style="height:300px"></canvas>
                </div>
                
                <div class="card">
                    <h3 style="margin-bottom:1rem">🏙️ Top 15 Municípios</h3>
                    <div style="max-height:300px;overflow-y:auto">
                        <?php foreach ($municipios as $i => $mun): ?>
                        <div style="display:flex;align-items:center;padding:0.75rem;border-bottom:1px solid var(--border);cursor:pointer;transition:all 0.2s" 
                             onmouseover="this.style.background='rgba(0,229,255,0.05)'" 
                             onmouseout="this.style.background='transparent'">
                            <div style="font-size:18px;margin-right:0.75rem">
                                <?= $i < 3 ? ['🥇','🥈','🥉'][$i] : '📍' ?>
                            </div>
                            <div style="flex:1">
                                <div style="font-weight:600;font-size:14px"><?= htmlspecialchars($mun['municipio']) ?></div>
                                <div style="font-size:11px;color:var(--text-3)"><?= $mun['percentagem'] ?>% da audiência</div>
                            </div>
                            <div style="font-size:20px;font-weight:800;color:var(--accent)"><?= $mun['total'] ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <div class="card" style="margin-top:2rem;text-align:center;padding:3rem;background:linear-gradient(135deg,rgba(0,229,255,0.08),rgba(124,58,237,0.08))">
                <div style="font-size:48px;margin-bottom:1rem">🗺️</div>
                <h3 style="margin-bottom:0.5rem">Mapa Interactivo de Angola</h3>
                <p style="color:var(--text-3);margin-bottom:1.5rem">Visualização geográfica com densidade por província</p>
                <div style="font-size:12px;color:var(--text-3);padding:1rem;background:rgba(0,0,0,0.2);border-radius:8px;display:inline-block">
                    🚀 Em desenvolvimento - Integração com Leaflet.js
                </div>
            </div>
        </div>

        <!-- ABA 3: AUDIÊNCIA PROFUNDA -->
        <div class="tab-content" x-show="activeTab === 'audiencia'" x-transition>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:2rem">
                <div class="card">
                    <h3 style="margin-bottom:1rem">📊 Pirâmide Etária</h3>
                    <canvas id="chartIdade" style="height:300px"></canvas>
                </div>
                
                <div class="card">
                    <h3 style="margin-bottom:1rem">⚧️ Distribuição por Género</h3>
                    <canvas id="chartGenero" style="height:300px"></canvas>
                </div>
            </div>
        </div>

        <!-- ABA 4: GOSTOS + CORRELAÇÕES -->
        <div class="tab-content" x-show="activeTab === 'gostos'" x-transition>
            <div class="card" style="margin-bottom:2rem">
                <h3 style="margin-bottom:1rem">🎵 Top Géneros Musicais</h3>
                <canvas id="chartGenerosMusicais" style="height:350px"></canvas>
            </div>
            
            <?php if (!empty($correlacoes)): ?>
            <div class="card">
                <h3 style="margin-bottom:1rem">🔗 Correlações: Quem gosta de X também gosta de Y</h3>
                <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:1rem">
                    <?php foreach (array_slice($correlacoes, 0, 6, true) as $combo => $count): ?>
                    <div style="padding:1rem;background:rgba(0,0,0,0.2);border:1px solid var(--border);border-radius:10px">
                        <div style="font-weight:700;margin-bottom:0.5rem;font-size:14px"><?= htmlspecialchars($combo) ?></div>
                        <div style="display:flex;align-items:center;gap:0.5rem">
                            <div style="flex:1;height:6px;background:rgba(255,255,255,0.1);border-radius:3px;overflow:hidden">
                                <div style="height:100%;background:var(--accent);width:<?= min($count * 10, 100) ?>%"></div>
                            </div>
                            <span style="font-size:12px;font-weight:700;color:var(--accent)"><?= $count ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- ABA 5: ANÁLISE TEMPORAL -->
        <div class="tab-content" x-show="activeTab === 'temporal'" x-transition>
            <div class="card">
                <h3 style="margin-bottom:1rem">📅 Comparação de Períodos</h3>
                <p style="color:var(--text-3);padding:2rem;text-align:center">
                    🚀 Funcionalidade avançada em desenvolvimento<br>
                    Compare qualquer período: Esta semana vs anterior, Este mês vs mês passado, etc.
                </p>
            </div>
        </div>

        <!-- ABA 6: COMPARADOR -->
        <div class="tab-content" x-show="activeTab === 'comparador'" x-transition>
            <div class="card">
                <h3 style="margin-bottom:1rem">⚡ Comparador Inteligente</h3>
                <p style="color:var(--text-3);padding:2rem;text-align:center">
                    🚀 A/B Testing de campanhas<br>
                    ROI de promoções<br>
                    Análise de efectividade
                </p>
            </div>
        </div>

    </div>
</div>

        <!-- ABA 1: PAINEL EXECUTIVO -->
        <div class="tab-content" x-show="activeTab === 'executivo'" x-transition>
            <div style="display:grid;grid-template-columns:2fr 1fr;gap:2rem">
                <div>
                    <div class="card" style="margin-bottom:1.5rem">
                        <h3 style="margin-bottom:1rem">📈 Evolução Últimos 90 Dias</h3>
                        <canvas id="chartEvolucao" style="height:300px"></canvas>
                    </div>
                    
                    <div class="card">
                        <h3 style="margin-bottom:1rem">⏰ Actividade por Hora do Dia</h3>
                        <canvas id="chartHoraria" style="height:250px"></canvas>
                    </div>
                </div>
                
                <div>
                    <div class="card" style="margin-bottom:1.5rem">
                        <h3 style="margin-bottom:1rem">🔮 Previsão Próximo Mês</h3>
                        <div style="text-align:center;padding:2rem">
                            <div style="font-size:64px;font-weight:900;color:var(--accent);margin-bottom:0.5rem">
                                +<?= $previsao['previsao_proximo_mes'] ?? 0 ?>
                            </div>
                            <div style="font-size:14px;color:var(--text-3);margin-bottom:1rem">novos ouvintes esperados</div>
                            <div style="padding:1rem;background:rgba(0,229,255,0.08);border-radius:8px">
                                <div style="font-size:12px;color:var(--text-3)">Baseado em média de</div>
                                <div style="font-size:24px;font-weight:700;color:var(--accent)"><?= $previsao['media_diaria'] ?? 0 ?></div>
                                <div style="font-size:12px;color:var(--text-3)">cadastros/dia</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card">
                        <h3 style="margin-bottom:1rem">🎯 Metas vs Realizado</h3>
                        <div style="padding:1rem">
                            <div style="margin-bottom:1.5rem">
                                <div style="display:flex;justify-content:space-between;margin-bottom:0.5rem">
                                    <span style="font-size:13px;font-weight:600">Meta Mensal: 100</span>
                                    <span style="font-size:13px;color:var(--accent);font-weight:700"><?= $kpis['novos_7d'] ?? 0 ?> (<?= round(($kpis['novos_7d'] ?? 0) / 100 * 100) ?>%)</span>
                                </div>
                                <div style="height:8px;background:rgba(255,255,255,0.1);border-radius:4px;overflow:hidden">
                                    <div style="height:100%;background:linear-gradient(90deg,var(--accent),var(--accent2));width:<?= min(round(($kpis['novos_7d'] ?? 0) / 100 * 100), 100) ?>%;transition:width 1s"></div>
                                </div>
                            </div>
                            
                            <div style="font-size:11px;color:var(--text-3);text-align:center;padding:1rem;background:rgba(0,0,0,0.2);border-radius:8px">
                                💡 Mantendo este ritmo, meta será atingida em <?= round(100 / max($previsao['media_diaria'] ?? 1, 1)) ?> dias
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ABA 2: GEOGRAFIA AVANÇADA -->
        <div class="tab-content" x-show="activeTab === 'geografia'" x-transition>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:2rem">
                <div class="card">
                    <h3 style="margin-bottom:1rem">🗺️ Distribuição por País</h3>
                    <canvas id="chartPaises" style="height:300px"></canvas>
                </div>
                
                <div class="card">
                    <h3 style="margin-bottom:1rem">🏙️ Top 15 Municípios</h3>
                    <div style="max-height:300px;overflow-y:auto">
                        <?php foreach ($municipios as $i => $mun): ?>
                        <div style="display:flex;align-items:center;padding:0.75rem;border-bottom:1px solid var(--border);cursor:pointer;transition:all 0.2s" 
                             onmouseover="this.style.background='rgba(0,229,255,0.05)'" 
                             onmouseout="this.style.background='transparent'">
                            <div style="font-size:18px;margin-right:0.75rem">
                                <?= $i < 3 ? ['🥇','🥈','🥉'][$i] : '📍' ?>
                            </div>
                            <div style="flex:1">
                                <div style="font-weight:600;font-size:14px"><?= htmlspecialchars($mun['municipio']) ?></div>
                                <div style="font-size:11px;color:var(--text-3)"><?= $mun['percentagem'] ?>% da audiência</div>
                            </div>
                            <div style="font-size:20px;font-weight:800;color:var(--accent)"><?= $mun['total'] ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <div class="card" style="margin-top:2rem;text-align:center;padding:3rem;background:linear-gradient(135deg,rgba(0,229,255,0.08),rgba(124,58,237,0.08))">
                <div style="font-size:48px;margin-bottom:1rem">🗺️</div>
                <h3 style="margin-bottom:0.5rem">Mapa Interactivo de Angola</h3>
                <p style="color:var(--text-3);margin-bottom:1.5rem">Visualização geográfica com densidade por província</p>
                <div style="font-size:12px;color:var(--text-3);padding:1rem;background:rgba(0,0,0,0.2);border-radius:8px;display:inline-block">
                    🚀 Em desenvolvimento - Integração com Leaflet.js
                </div>
            </div>
        </div>

        <!-- ABA 3: AUDIÊNCIA PROFUNDA -->
        <div class="tab-content" x-show="activeTab === 'audiencia'" x-transition>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:2rem">
                <div class="card">
                    <h3 style="margin-bottom:1rem">📊 Pirâmide Etária</h3>
                    <canvas id="chartIdade" style="height:300px"></canvas>
                </div>
                
                <div class="card">
                    <h3 style="margin-bottom:1rem">⚧️ Distribuição por Género</h3>
                    <canvas id="chartGenero" style="height:300px"></canvas>
                </div>
            </div>
        </div>

        <!-- ABA 4: GOSTOS + CORRELAÇÕES -->
        <div class="tab-content" x-show="activeTab === 'gostos'" x-transition>
            <div class="card" style="margin-bottom:2rem">
                <h3 style="margin-bottom:1rem">🎵 Top Géneros Musicais</h3>
                <canvas id="chartGenerosMusicais" style="height:350px"></canvas>
            </div>
            
            <?php if (!empty($correlacoes)): ?>
            <div class="card">
                <h3 style="margin-bottom:1rem">🔗 Correlações: Quem gosta de X também gosta de Y</h3>
                <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:1rem">
                    <?php foreach (array_slice($correlacoes, 0, 6, true) as $combo => $count): ?>
                    <div style="padding:1rem;background:rgba(0,0,0,0.2);border:1px solid var(--border);border-radius:10px">
                        <div style="font-weight:700;margin-bottom:0.5rem;font-size:14px"><?= htmlspecialchars($combo) ?></div>
                        <div style="display:flex;align-items:center;gap:0.5rem">
                            <div style="flex:1;height:6px;background:rgba(255,255,255,0.1);border-radius:3px;overflow:hidden">
                                <div style="height:100%;background:var(--accent);width:<?= min($count * 10, 100) ?>%"></div>
                            </div>
                            <span style="font-size:12px;font-weight:700;color:var(--accent)"><?= $count ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- ABA 5: ANÁLISE TEMPORAL -->
        <div class="tab-content" x-show="activeTab === 'temporal'" x-transition>
            <div class="card">
                <h3 style="margin-bottom:1rem">📅 Comparação de Períodos</h3>
                <p style="color:var(--text-3);padding:2rem;text-align:center">
                    🚀 Funcionalidade avançada em desenvolvimento<br>
                    Compare qualquer período: Esta semana vs anterior, Este mês vs mês passado, etc.
                </p>
            </div>
        </div>

        <!-- ABA 6: COMPARADOR -->
        <div class="tab-content" x-show="activeTab === 'comparador'" x-transition>
            <div class="card">
                <h3 style="margin-bottom:1rem">⚡ Comparador Inteligente</h3>
                <p style="color:var(--text-3);padding:2rem;text-align:center">
                    🚀 A/B Testing de campanhas<br>
                    ROI de promoções<br>
                    Análise de efectividade
                </p>
            </div>
        </div>

    </div>
</div>

<!-- MODAL DRILL-DOWN -->
<div id="modalOuvintes" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.85);backdrop-filter:blur(10px);z-index:9999;align-items:center;justify-content:center">
    <div style="background:linear-gradient(145deg,rgba(26,26,46,0.98),rgba(21,21,32,0.98));border:2px solid var(--accent);border-radius:20px;max-width:900px;max-height:80vh;overflow-y:auto;padding:2.5rem;position:relative;box-shadow:0 20px 60px rgba(0,0,0,0.5)">
        <button onclick="fecharModal()" style="position:absolute;top:1.5rem;right:1.5rem;background:rgba(255,255,255,0.1);border:none;width:40px;height:40px;border-radius:50%;font-size:20px;cursor:pointer;color:#fff;transition:all 0.3s" onmouseover="this.style.background='var(--accent)';this.style.color='#000'" onmouseout="this.style.background='rgba(255,255,255,0.1)';this.style.color='#fff'">✕</button>
        <h3 id="modalTitulo" style="margin-bottom:2rem;font-size:24px"></h3>
        <div id="modalConteudo"></div>
    </div>
</div>

<style>
.demograficos-pro-v2 .tabs-container{background:var(--bg);border-radius:20px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.3)}
.demograficos-pro-v2 .tabs-nav{display:flex;background:rgba(0,0,0,0.4);border-bottom:1px solid var(--border);overflow-x:auto}
.demograficos-pro-v2 .tab-button{padding:1.25rem 2rem;border:none;background:transparent;color:var(--text-3);font-weight:700;cursor:pointer;transition:all 0.3s;position:relative;white-space:nowrap;font-size:14px;letter-spacing:0.3px}
.demograficos-pro-v2 .tab-button:hover{color:var(--accent);background:rgba(0,229,255,0.05)}
.demograficos-pro-v2 .tab-button.active{color:var(--accent)}
.demograficos-pro-v2 .tab-button.active::after{content:'';position:absolute;bottom:0;left:0;right:0;height:3px;background:linear-gradient(90deg,var(--accent),var(--accent2))}
.demograficos-pro-v2 .tab-content{padding:2.5rem}
.demograficos-pro-v2 .card{background:linear-gradient(145deg,rgba(26,26,46,0.95),rgba(21,21,32,0.95));border:1px solid var(--border);border-radius:16px;padding:2rem;box-shadow:0 8px 24px rgba(0,0,0,0.3);transition:all 0.3s}
.demograficos-pro-v2 .card:hover{transform:translateY(-2px);border-color:rgba(0,229,255,0.3)}

@media(max-width:768px){
    .demograficos-pro-v2 [style*="grid-template-columns"]{grid-template-columns:1fr !important}
    .demograficos-pro-v2 .tabs-nav{flex-wrap:nowrap;overflow-x:scroll}
    .demograficos-pro-v2 .tab-button{padding:1rem 1.5rem;font-size:12px}
}
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0"></script>
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>

<script>
// Configuração global dos gráficos
Chart.defaults.color = '#94a3b8';
Chart.defaults.borderColor = 'rgba(255,255,255,0.06)';
Chart.defaults.font.family = 'Inter';

// Dados
const paisesLabels = <?= $paisesJson ?>;
const paisesData = <?= $paisesTotalJson ?>;
const idadesLabels = <?= $idadesLabels ?>;
const idadesData = <?= $idadesValues ?>;
const generosLabels = <?= $generosLabels ?>;
const generosData = <?= $generosValues ?>;
const generosMusicaisLabels = <?= $generosMusicaisLabels ?>;
const generosMusicaisData = <?= $generosMusicaisValues ?>;
const crescimentoLabels = <?= $crescimentoLabels ?>;
const crescimentoData = <?= $crescimentoValues ?>;

// Gráfico Evolução (linha área)
const ctxEvolucao = document.getElementById('chartEvolucao');
if(ctxEvolucao){
    new Chart(ctxEvolucao, {
        type: 'line',
        data: {
            labels: crescimentoLabels,
            datasets: [{
                label: 'Novos Cadastros',
                data: crescimentoData,
                borderColor: '#00e5ff',
                backgroundColor: 'rgba(0,229,255,0.1)',
                fill: true,
                tension: 0.4,
                borderWidth: 3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {legend: {display: false}},
            scales: {y: {beginAtZero: true, grid: {color: 'rgba(255,255,255,0.03)'}}}
        }
    });
}

// Gráfico Horária
const ctxHoraria = document.getElementById('chartHoraria');
if(ctxHoraria){
    const horariaData = <?= json_encode(array_column($horaria, 'total')) ?>;
    const horariaLabels = <?= json_encode(array_map(fn($h) => $h['hora'].'h', $horaria)) ?>;
    new Chart(ctxHoraria, {
        type: 'bar',
        data: {
            labels: horariaLabels,
            datasets: [{
                label: 'Participações',
                data: horariaData,
                backgroundColor: 'rgba(139,92,246,0.8)',
                borderRadius: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {legend: {display: false}},
            scales: {y: {beginAtZero: true}}
        }
    });
}

// Gráfico Países
const ctxPaises = document.getElementById('chartPaises');
if(ctxPaises){
    new Chart(ctxPaises, {
        type: 'doughnut',
        data: {
            labels: paisesLabels,
            datasets: [{
                data: paisesData,
                backgroundColor: ['#00e5ff','#4facfe','#8b5cf6','#ec4899','#f59e0b','#10b981'],
                borderWidth: 0,
                borderRadius: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {legend: {position: 'bottom'}}
        }
    });
}

// Gráfico Idade
const ctxIdade = document.getElementById('chartIdade');
if(ctxIdade){
    new Chart(ctxIdade, {
        type: 'bar',
        data: {
            labels: idadesLabels,
            datasets: [{
                label: 'Ouvintes',
                data: idadesData,
                backgroundColor: '#00e5ff',
                borderRadius: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {legend: {display: false}}
        }
    });
}

// Gráfico Género
const ctxGenero = document.getElementById('chartGenero');
if(ctxGenero){
    new Chart(ctxGenero, {
        type: 'pie',
        data: {
            labels: generosLabels.map(g => g === 'masculino' ? '👨 Masculino' : g === 'feminino' ? '👩 Feminino' : '⚧ Outro'),
            datasets: [{
                data: generosData,
                backgroundColor: ['#4facfe','#ec4899','#8b5cf6']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {legend: {position: 'bottom'}}
        }
    });
}

// Gráfico Géneros Musicais
const ctxGenerosMusicais = document.getElementById('chartGenerosMusicais');
if(ctxGenerosMusicais){
    new Chart(ctxGenerosMusicais, {
        type: 'bar',
        data: {
            labels: generosMusicaisLabels,
            datasets: [{
                label: 'Ouvintes',
                data: generosMusicaisData,
                backgroundColor: 'rgba(139,92,246,0.8)',
                borderRadius: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: 'y',
            plugins: {legend: {display: false}}
        }
    });
}

// Funções auxiliares
function fecharModal() {
    document.getElementById('modalOuvintes').style.display = 'none';
}

// Animação dos números ao carregar
document.addEventListener('DOMContentLoaded', () => {
    const animateValue = (element, start, end, duration) => {
        let startTimestamp = null;
        const step = (timestamp) => {
            if (!startTimestamp) startTimestamp = timestamp;
            const progress = Math.min((timestamp - startTimestamp) / duration, 1);
            element.textContent = Math.floor(progress * (end - start) + start);
            if (progress < 1) window.requestAnimationFrame(step);
        };
        window.requestAnimationFrame(step);
    };
    
    // Animar KPIs
    document.querySelectorAll('[style*="font-size:48px"]').forEach(el => {
        const finalValue = parseInt(el.textContent.replace(/\D/g, '')) || 0;
        animateValue(el, 0, finalValue, 1500);
    });
});
</script>
