<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Enviar Dedicatória - PULSO</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, sans-serif; background: linear-gradient(135deg, #0a0e27 0%, #1a1f3a 100%); padding: 20px; }
        .container { max-width: 600px; margin: 40px auto; background: rgba(255,255,255,0.05); border-radius: 20px; padding: 32px; border: 1px solid rgba(255,255,255,0.1); }
        h1 { color: #D4AF37; margin-bottom: 24px; font-size: 28px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; color: #aaa; margin-bottom: 8px; font-size: 13px; font-weight: 600; }
        input, textarea, select { width: 100%; padding: 12px; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15); border-radius: 8px; color: #fff; font-size: 14px; }
        input:focus, textarea:focus, select:focus { outline: none; border-color: #D4AF37; }
        textarea { min-height: 100px; resize: vertical; }
        button { width: 100%; padding: 14px; background: linear-gradient(135deg, #D4AF37 0%, #FFD700 100%); border: none; border-radius: 8px; color: #000; font-weight: 700; font-size: 16px; cursor: pointer; }
        button:hover { opacity: 0.9; }
        .success { background: rgba(0,230,118,0.1); border: 1px solid #00e676; color: #00e676; padding: 16px; border-radius: 8px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <?php if (isset($_GET['success'])): ?>
            <div class="success">✅ Dedicatória enviada com sucesso!</div>
        <?php endif; ?>
        
        <h1>📻 Enviar Dedicatória</h1>
        
        <form method="POST" action="/public/pulso/<?= $stationId ?>/participacao/enviar">
            <div class="form-group">
                <label>Seu Nome *</label>
                <input type="text" name="nome" required>
            </div>
            
            <div class="form-group">
                <label>Telefone (WhatsApp) *</label>
                <input type="text" name="telefone" required placeholder="+244 923 456 789">
            </div>
            
            <div class="form-group">
                <label>Música Pedida</label>
                <input type="text" name="musica" placeholder="Artista - Nome da Música">
            </div>
            
            <div class="form-group">
                <label>Mensagem/Dedicatória</label>
                <textarea name="mensagem" placeholder="Escreve a tua dedicatória..."></textarea>
            </div>
            
            <div class="form-group">
                <label>Para Quem?</label>
                <input type="text" name="para_quem" placeholder="Nome da pessoa">
            </div>
            
            <button type="submit">🎵 Enviar Dedicatória</button>
        </form>
    </div>
</body>
</html>
