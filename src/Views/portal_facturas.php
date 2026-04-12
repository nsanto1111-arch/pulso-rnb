<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facturas — Portal do Anunciante RNB</title>
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
            --amber: #F59E0B; --amber-tint: rgba(245, 158, 11, 0.1);
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
        
        /* STATS */
        .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; margin-bottom: 40px; }
        .stat-card { background: linear-gradient(135deg, var(--ink-2), var(--ink-3)); border: 1px solid var(--wire); border-radius: 16px; padding: 28px; position: relative; overflow: hidden; }
        .stat-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px; background: linear-gradient(90deg, var(--gold-dark), var(--gold-light)); }
        .stat-label { font-size: 13px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; color: var(--smoke); margin-bottom: 12px; }
        .stat-value { font-size: 36px; font-weight: 800; font-family: 'Playfair Display', serif; background: linear-gradient(135deg, var(--white), var(--fog)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .stat-currency { font-size: 18px; color: var(--smoke); margin-left: 8px; }
        
        /* TABLE */
        .table-container { background: var(--ink-2); border: 1px solid var(--wire); border-radius: 16px; overflow: hidden; }
        table { width: 100%; border-collapse: collapse; }
        thead { background: linear-gradient(135deg, var(--ink-3), var(--ink-4)); }
        th { padding: 20px 24px; text-align: left; font-weight: 700; font-size: 13px; text-transform: uppercase; letter-spacing: 1px; color: var(--gold); border-bottom: 2px solid var(--gold-dark); }
        td { padding: 20px 24px; border-bottom: 1px solid var(--wire); font-size: 14px; }
        tr:last-child td { border-bottom: none; }
        tbody tr { transition: background 0.2s; }
        tbody tr:hover { background: var(--ink-3); }
        
        .factura-numero { font-family: 'JetBrains Mono', monospace; font-weight: 600; color: var(--gold); font-size: 15px; }
        .factura-data { color: var(--fog); }
        .factura-valor { font-family: 'JetBrains Mono', monospace; font-weight: 700; font-size: 16px; }
        
        .status-badge { padding: 6px 14px; border-radius: 100px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
        .status-paga { background: var(--jade-tint); color: var(--jade); }
        .status-pendente { background: var(--amber-tint); color: var(--amber); }
        .status-vencida { background: var(--ember-tint); color: var(--ember); }
        
        .btn-download { padding: 8px 16px; background: var(--ink-4); border: 1px solid var(--wire-2); border-radius: 8px; color: var(--gold); font-size: 13px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s; }
        .btn-download:hover { background: var(--gold-glow); border-color: var(--gold); }
        
        /* EMPTY STATES */
        .empty-state { text-align: center; padding: 80px 20px; }
        .empty-icon { font-size: 72px; color: var(--wire-2); margin-bottom: 20px; }
        .empty-text { color: var(--smoke); font-size: 16px; }
        
        .info-box { background: var(--ink-3); border: 1px solid var(--wire-2); border-left: 4px solid var(--gold); border-radius: 12px; padding: 24px; margin-bottom: 32px; }
        .info-box-title { font-weight: 700; color: var(--gold); margin-bottom: 8px; display: flex; align-items: center; gap: 10px; }
        .info-box-text { color: var(--fog); font-size: 14px; line-height: 1.6; }
        
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .main-content { margin-left: 0; padding: 24px 20px; }
            .stats-grid { grid-template-columns: 1fr; }
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
            <a href="/public/portal/1/prova-emissao" class="nav-item">
                <i class="bi bi-broadcast"></i><span>Prova de Emissão</span>
            </a>
            <a href="/public/portal/1/facturas" class="nav-item active">
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
            <h1 class="page-title">Facturas & Pagamentos</h1>
            <p class="page-subtitle">Gestão financeira e documentos de facturação</p>
        </div>
        
        <?php if (!$cliente): ?>
            <!-- SEM CLIENTE VINCULADO -->
            <div class="info-box">
                <div class="info-box-title">
                    <i class="bi bi-info-circle-fill"></i>
                    Conta Financeira em Configuração
                </div>
                <div class="info-box-text">
                    A sua conta financeira está a ser configurada pela nossa equipa comercial. 
                    As facturas estarão disponíveis em breve. Para mais informações, contacte o departamento financeiro.
                </div>
            </div>
            
            <div class="table-container">
                <div class="empty-state">
                    <div class="empty-icon"><i class="bi bi-file-earmark-lock2"></i></div>
                    <div class="empty-text">Aguardando configuração do sistema financeiro</div>
                </div>
            </div>
            
        <?php elseif (empty($facturas)): ?>
            <!-- CLIENTE VINCULADO MAS SEM FACTURAS -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-label">Total Emitido</div>
                    <div class="stat-value">0<span class="stat-currency">AOA</span></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Total Pago</div>
                    <div class="stat-value">0<span class="stat-currency">AOA</span></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Saldo Pendente</div>
                    <div class="stat-value">0<span class="stat-currency">AOA</span></div>
                </div>
            </div>
            
            <div class="table-container">
                <div class="empty-state">
                    <div class="empty-icon"><i class="bi bi-receipt"></i></div>
                    <div class="empty-text">Nenhuma factura emitida até ao momento</div>
                </div>
            </div>
            
        <?php else: ?>
            <!-- COM FACTURAS -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-label">Total Emitido</div>
                    <div class="stat-value">
                        <?= number_format($totais['total_emitido'], 2, ',', '.') ?>
                        <span class="stat-currency">AOA</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Total Pago</div>
                    <div class="stat-value">
                        <?= number_format($totais['total_pago'], 2, ',', '.') ?>
                        <span class="stat-currency">AOA</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Saldo Pendente</div>
                    <div class="stat-value">
                        <?= number_format($totais['total_pendente'], 2, ',', '.') ?>
                        <span class="stat-currency">AOA</span>
                    </div>
                </div>
            </div>
            
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th><i class="bi bi-hash"></i> Número</th>
                            <th><i class="bi bi-calendar3"></i> Data Emissão</th>
                            <th><i class="bi bi-calendar-check"></i> Vencimento</th>
                            <th><i class="bi bi-cash-coin"></i> Valor Total</th>
                            <th><i class="bi bi-check-circle"></i> Pago</th>
                            <th><i class="bi bi-exclamation-circle"></i> Saldo</th>
                            <th><i class="bi bi-tag"></i> Estado</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($facturas as $f): ?>
                        <?php
                            $hoje = new DateTime();
                            $vencimento = new DateTime($f['data_vencimento']);
                            $status_class = 'status-pendente';
                            $status_text = 'Pendente';
                            
                            if ($f['status'] === 'paga' || $f['saldo'] <= 0) {
                                $status_class = 'status-paga';
                                $status_text = 'Paga';
                            } elseif ($vencimento < $hoje && $f['saldo'] > 0) {
                                $status_class = 'status-vencida';
                                $status_text = 'Vencida';
                            }
                        ?>
                        <tr>
                            <td>
                                <div class="factura-numero"><?= htmlspecialchars($f['numero']) ?></div>
                            </td>
                            <td>
                                <div class="factura-data"><?= date('d/m/Y', strtotime($f['data_emissao'])) ?></div>
                            </td>
                            <td>
                                <div class="factura-data"><?= date('d/m/Y', strtotime($f['data_vencimento'])) ?></div>
                            </td>
                            <td>
                                <div class="factura-valor"><?= number_format($f['total'], 2, ',', '.') ?> <?= $f['moeda'] ?></div>
                            </td>
                            <td>
                                <div style="color: var(--jade);"><?= number_format($f['valor_pago'], 2, ',', '.') ?> <?= $f['moeda'] ?></div>
                            </td>
                            <td>
                                <div style="color: var(--amber);"><?= number_format($f['saldo'], 2, ',', '.') ?> <?= $f['moeda'] ?></div>
                            </td>
                            <td>
                                <span class="status-badge <?= $status_class ?>"><?= $status_text ?></span>
                            </td>
                            <td>
                                <a href="#" class="btn-download" onclick="alert('Download PDF em desenvolvimento'); return false;">
                                    <i class="bi bi-file-pdf"></i> PDF
                                </a>
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
