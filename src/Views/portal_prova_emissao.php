<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prova de Emissão — Portal do Anunciante RNB</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --ink: #08090C; --ink-2: #0F1117; --ink-3: #161A22; --ink-4: #1D2129;
            --wire: #272D38; --wire-2: #353C4A; --smoke: #60697A; --fog: #B8C0CE; --white: #EEF0F5;
            --gold: #D4A84B; --gold-light: #E8C870; --gold-dark: #9A7620; --gold-glow: rgba(212, 168, 75, 0.15);
            --jade: #27C47A; --jade-light: #3DDA8E; --jade-tint: rgba(39, 196, 122, 0.1);
            --ember: #E05A38; --ember-tint: rgba(224, 90, 56, 0.1);
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: var(--ink); color: var(--white); min-height: 100vh; -webkit-font-smoothing: antialiased; }
        
        /* SIDEBAR */
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
        
        /* TABLE */
        .table-container { background: var(--ink-2); border: 1px solid var(--wire); border-radius: 16px; overflow: hidden; }
        table { width: 100%; border-collapse: collapse; }
        thead { background: linear-gradient(135deg, var(--ink-3), var(--ink-4)); }
        th { padding: 20px 24px; text-align: left; font-weight: 700; font-size: 13px; text-transform: uppercase; letter-spacing: 1px; color: var(--gold); border-bottom: 2px solid var(--gold-dark); }
        td { padding: 20px 24px; border-bottom: 1px solid var(--wire); font-size: 14px; }
        tr:last-child td { border-bottom: none; }
        tbody tr { transition: background 0.2s; }
        tbody tr:hover { background: var(--ink-3); }
        
        .timestamp { font-family: 'JetBrains Mono', monospace; color: var(--fog); font-weight: 500; }
        .spot-title { font-weight: 600; color: var(--white); }
        .duration { font-family: 'JetBrains Mono', monospace; background: var(--jade-tint); color: var(--jade); padding: 4px 12px; border-radius: 6px; font-size: 13px; font-weight: 600; display: inline-block; }
        .type-badge { padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; background: var(--ink-4); color: var(--smoke); }
        
        .empty-state { text-align: center; padding: 80px 20px; }
        .empty-icon { font-size: 72px; color: var(--wire-2); margin-bottom: 20px; }
        .empty-text { color: var(--smoke); font-size: 16px; }
        
        .btn-download-pdf {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 14px 28px;
            background: linear-gradient(135deg, var(--gold-dark), var(--gold));
            color: var(--ink);
            text-decoration: none;
            border-radius: 10px;
            font-weight: 700;
            font-size: 15px;
            transition: all 0.3s;
            box-shadow: 0 4px 12px var(--gold-glow);
        }
        
        .btn-download-pdf:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px var(--gold-glow);
        }
        
        .btn-download-pdf i {
            font-size: 20px;
        }
        
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .main-content { margin-left: 0; padding: 24px 20px; }
            .table-container { overflow-x: auto; }
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
            <a href="/public/portal/1/campanhas" class="nav-item">
                <i class="bi bi-megaphone-fill"></i><span>Campanhas</span>
            </a>
            <a href="/public/portal/1/prova-emissao" class="nav-item active">
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
            <h1 class="page-title">Prova de Emissão</h1>
            <p class="page-subtitle">Registo completo de todos os spots emitidos</p>
        </div>
        
        <?php if (!empty($provas)): ?>
        <div style="margin-bottom: 24px; text-align: right; padding: 20px 0;">
            <a href="/public/portal/1/prova-emissao/pdf" 
               style="display: inline-flex !important; 
                      align-items: center; 
                      gap: 10px; 
                      padding: 14px 28px; 
                      background: linear-gradient(135deg, #9A7620, #D4A84B); 
                      color: #08090C; 
                      text-decoration: none; 
                      border-radius: 10px; 
                      font-weight: 700; 
                      font-size: 15px; 
                      box-shadow: 0 4px 12px rgba(212, 168, 75, 0.3);
                      transition: all 0.3s;">
                <i class="bi bi-file-earmark-pdf-fill" style="font-size: 20px;"></i>
                <span>Baixar Prova de Emissão em PDF</span>
            </a>
        </div>
        <?php endif; ?>
        
        <?php if (empty($provas)): ?>
            <div class="table-container">
                <div class="empty-state">
                    <div class="empty-icon"><i class="bi bi-broadcast-pin"></i></div>
                    <div class="empty-text">Nenhuma emissão registada no sistema</div>
                </div>
            </div>
        <?php else: ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th><i class="bi bi-calendar-event"></i> Data & Hora</th>
                            <th><i class="bi bi-mic-fill"></i> Spot Emitido</th>
                            <th><i class="bi bi-stopwatch"></i> Duração</th>
                            <th><i class="bi bi-tag-fill"></i> Tipo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($provas as $p): ?>
                        <tr>
                            <td>
                                <div class="timestamp">
                                    <?= date('d/m/Y', strtotime($p['data_emissao'])) ?>
                                    <span style="color: var(--gold); margin: 0 8px;">•</span>
                                    <?= date('H:i', strtotime($p['data_emissao'])) ?>
                                </div>
                            </td>
                            <td>
                                <div class="spot-title"><?= htmlspecialchars($p['titulo']) ?></div>
                            </td>
                            <td>
                                <span class="duration"><?= $p['duracao_seg'] ?>s</span>
                            </td>
                            <td>
                                <span class="type-badge"><?= htmlspecialchars($p['tipo']) ?></span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>
