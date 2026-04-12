<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Campanhas — Portal do Anunciante RNB</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --ink: #08090C; --ink-2: #0F1117; --ink-3: #161A22; --ink-4: #1D2129;
            --wire: #272D38; --wire-2: #353C4A; --smoke: #60697A; --fog: #B8C0CE; --white: #EEF0F5;
            --gold: #D4A84B; --gold-light: #E8C870; --gold-dark: #9A7620; --gold-glow: rgba(212, 168, 75, 0.15);
            --jade: #27C47A; --jade-light: #3DDA8E; --jade-tint: rgba(39, 196, 122, 0.1);
            --ember: #E05A38; --ember-tint: rgba(224, 90, 56, 0.1);
            --amber: #F59E0B; --amber-tint: rgba(245, 158, 11, 0.1);
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: var(--ink); color: var(--white); min-height: 100vh; -webkit-font-smoothing: antialiased; }
        
        /* SIDEBAR - mesmo código */
        .sidebar { position: fixed; left: 0; top: 0; bottom: 0; width: 280px; background: var(--ink-2); border-right: 1px solid var(--wire); display: flex; flex-direction: column; z-index: 100; }
        .sidebar-header { padding: 32px 24px; border-bottom: 1px solid var(--wire); }
        .logo { display: flex; align-items: center; gap: 14px; }
        .logo-mark { width: 46px; height: 46px; border-radius: 12px; background: linear-gradient(135deg, var(--gold-dark), var(--gold)); display: flex; align-items: center; justify-content: center; font-family: 'Playfair Display', serif; font-size: 18px; font-weight: 800; color: var(--ink); box-shadow: 0 0 24px var(--gold-glow); }
        .logo-text { font-family: 'Playfair Display', serif; font-size: 20px; font-weight: 700; color: var(--white); }
        .logo-text em { color: var(--gold); font-style: normal; }
        .sidebar-nav { flex: 1; padding: 24px 16px; overflow-y: auto; }
        .nav-item { display: flex; align-items: center; gap: 14px; padding: 14px 16px; color: var(--smoke); text-decoration: none; border-radius: 10px; margin-bottom: 6px; transition: all 0.2s; font-weight: 500; font-size: 15px; }
        .nav-item:hover { background: var(--ink-3); color: var(--white); }
        .nav-item.active { background: var(--gold-glow); color: var(--gold-light); border: 1px solid rgba(212, 168, 75, 0.2); }
        .nav-item i { font-size: 20px; width: 24px; text-align: center; }
        .sidebar-footer { padding: 24px; border-top: 1px solid var(--wire); }
        .user-info { display: flex; align-items: center; gap: 12px; margin-bottom: 16px; }
        .user-avatar { width: 42px; height: 42px; border-radius: 50%; background: linear-gradient(135deg, var(--gold-dark), var(--gold)); display: flex; align-items: center; justify-content: center; font-weight: 700; color: var(--ink); }
        .user-details { flex: 1; }
        .user-name { font-weight: 600; font-size: 14px; color: var(--white); }
        .user-role { font-size: 12px; color: var(--smoke); }
        .btn-logout { width: 100%; padding: 12px; background: var(--ink-3); border: 1px solid var(--wire); border-radius: 8px; color: var(--smoke); font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; justify-content: center; gap: 8px; text-decoration: none; }
        .btn-logout:hover { background: var(--ember-tint); border-color: var(--ember); color: var(--ember); }
        
        /* MAIN */
        .main-content { margin-left: 280px; padding: 40px 48px; }
        .page-header { margin-bottom: 36px; }
        .page-title { font-family: 'Playfair Display', serif; font-size: 38px; font-weight: 700; margin-bottom: 8px; background: linear-gradient(135deg, var(--white), var(--fog)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
        .page-subtitle { color: var(--smoke); font-size: 16px; }
        
        /* CAMPAIGN CARDS */
        .campaigns-grid { display: grid; gap: 24px; }
        .campaign-card { background: linear-gradient(135deg, var(--ink-2), var(--ink-3)); border: 1px solid var(--wire); border-radius: 16px; padding: 32px; position: relative; overflow: hidden; transition: all 0.3s; }
        .campaign-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px; background: linear-gradient(90deg, var(--gold-dark), var(--gold-light)); }
        .campaign-card:hover { transform: translateY(-4px); border-color: var(--wire-2); box-shadow: 0 16px 48px rgba(0, 0, 0, 0.5); }
        
        .campaign-header { display: flex; justify-content: space-between; align-items: start; margin-bottom: 24px; }
        .campaign-title { font-size: 24px; font-weight: 700; font-family: 'Playfair Display', serif; color: var(--white); margin-bottom: 8px; }
        .campaign-dates { font-size: 14px; color: var(--smoke); }
        
        .badge { padding: 8px 16px; border-radius: 100px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
        .badge-success { background: var(--jade-tint); color: var(--jade); }
        .badge-warning { background: var(--amber-tint); color: var(--amber); }
        .badge-danger { background: var(--ember-tint); color: var(--ember); }
        
        .stats-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 24px; }
        .stat-item { text-align: center; padding: 16px; background: var(--ink-4); border-radius: 12px; border: 1px solid var(--wire); }
        .stat-label { font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: var(--smoke); margin-bottom: 8px; }
        .stat-value { font-size: 28px; font-weight: 800; font-family: 'Playfair Display', serif; background: linear-gradient(135deg, var(--white), var(--fog)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        
        .progress-section { }
        .progress-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
        .progress-label { font-size: 14px; font-weight: 600; color: var(--fog); }
        .progress-percent { font-size: 18px; font-weight: 800; color: var(--gold); }
        .progress-bar-bg { height: 10px; background: var(--ink-4); border-radius: 100px; overflow: hidden; box-shadow: inset 0 2px 4px rgba(0,0,0,0.3); }
        .progress-bar-fill { height: 100%; background: linear-gradient(90deg, var(--gold-dark), var(--gold)); border-radius: 100px; box-shadow: 0 0 12px var(--gold-glow); transition: width 0.5s ease; }
        
        .empty-state { text-align: center; padding: 80px 20px; background: var(--ink-2); border: 1px solid var(--wire); border-radius: 16px; }
        .empty-icon { font-size: 72px; color: var(--wire-2); margin-bottom: 20px; }
        .empty-text { color: var(--smoke); font-size: 16px; }
        
        .btn-download-mini {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: var(--ink-4);
            border: 1px solid var(--wire-2);
            border-radius: 8px;
            color: var(--gold);
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.2s;
        }
        
        .btn-download-mini:hover {
            background: var(--gold-glow);
            border-color: var(--gold);
            transform: translateY(-1px);
        }
        
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .main-content { margin-left: 0; padding: 24px 20px; }
            .stats-row { grid-template-columns: repeat(2, 1fr); }
        }
    </style>
</head>
<body>
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="logo">
                <div class="logo-mark">RNB</div>
                <div class="logo-text">Radio <em>New Band</em></div>
            </div>
        </div>
        <nav class="sidebar-nav">
            <a href="/public/portal/1" class="nav-item">
                <i class="bi bi-grid-fill"></i><span>Dashboard</span>
            </a>
            <a href="/public/portal/1/campanhas" class="nav-item active">
                <i class="bi bi-megaphone-fill"></i><span>Campanhas</span>
            </a>
            <a href="/public/portal/1/prova-emissao" class="nav-item">
                <i class="bi bi-broadcast"></i><span>Prova de Emissão</span>
            </a>
            <a href="/public/portal/1/facturas" class="nav-item">
                <i class="bi bi-file-earmark-text-fill"></i><span>Facturas</span>
            </a>
        </nav>
        <div class="sidebar-footer">
            <div class="user-info">
                <div class="user-avatar"><?= strtoupper(substr($user['nome_contacto'], 0, 2)) ?></div>
                <div class="user-details">
                    <div class="user-name"><?= htmlspecialchars($user['nome_contacto']) ?></div>
                    <div class="user-role">Anunciante</div>
                </div>
            </div>
            <a href="/public/portal/logout" class="btn-logout">
                <i class="bi bi-box-arrow-right"></i><span>Terminar Sessão</span>
            </a>
        </div>
    </aside>
    
    <main class="main-content">
        <div class="page-header">
            <h1 class="page-title">Campanhas Publicitárias</h1>
            <p class="page-subtitle">Acompanhe todas as suas campanhas activas e históricas</p>
        </div>
        
        <?php if (empty($campanhas)): ?>
            <div class="empty-state">
                <div class="empty-icon"><i class="bi bi-megaphone"></i></div>
                <div class="empty-text">Nenhuma campanha encontrada</div>
            </div>
        <?php else: ?>
            <div class="campaigns-grid">
                <?php foreach ($campanhas as $c): ?>
                <div class="campaign-card">
                    <div class="campaign-header">
                        <div>
                            <h3 class="campaign-title"><?= htmlspecialchars($c['titulo']) ?></h3>
                            <div class="campaign-dates">
                                <i class="bi bi-calendar3"></i>
                                <?= date('d/m/Y', strtotime($c['data_inicio'])) ?> — <?= date('d/m/Y', strtotime($c['data_fim'])) ?>
                            </div>
                        </div>
                        <span class="badge badge-<?= $c['estado'] === 'activa' ? 'success' : ($c['estado'] === 'pausada' ? 'warning' : 'danger') ?>">
                            <?= ucfirst($c['estado']) ?>
                        </span>
                    </div>
                    
                    <div class="stats-row">
                        <div class="stat-item">
                            <div class="stat-label">Contratados</div>
                            <div class="stat-value"><?= number_format($c['spots_contratados']) ?></div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-label">Emitidos</div>
                            <div class="stat-value"><?= number_format($c['spots_emitidos']) ?></div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-label">Restantes</div>
                            <div class="stat-value"><?= number_format($c['spots_contratados'] - $c['spots_emitidos']) ?></div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-label">Conclusão</div>
                            <div class="stat-value"><?= $c['progresso'] ?>%</div>
                        </div>
                    </div>
                    
                    <div class="progress-section">
                        <div class="progress-header">
                            <span class="progress-label">Progresso da Campanha</span>
                            <span class="progress-percent"><?= $c['progresso'] ?>%</span>
                        </div>
                        <div class="progress-bar-bg">
                            <div class="progress-bar-fill" style="width: <?= $c['progresso'] ?>%"></div>
                        </div>
                    </div>
                    
                    <?php if ($c['spots_emitidos'] > 0): ?>
                    <div style="margin-top: 20px; text-align: right;">
                        <a href="/public/portal/1/prova-emissao/pdf?campanha_id=<?= $c['id'] ?>" 
                           target="_blank"
                           style="display: inline-flex; 
                                  align-items: center; 
                                  gap: 8px; 
                                  padding: 10px 20px; 
                                  background: var(--ink-4); 
                                  border: 1px solid var(--wire-2); 
                                  border-radius: 8px; 
                                  color: var(--gold); 
                                  text-decoration: none; 
                                  font-size: 13px; 
                                  font-weight: 600;">
                            <i class="bi bi-file-pdf"></i>
                            <span>Baixar Prova de Emissão</span>
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>
