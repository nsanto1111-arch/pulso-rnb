<?php
try {
    // No Docker do AzuraCast, essas são as variáveis padrão injetadas
    $host = getenv('MYSQL_HOST') ?: 'mariadb';
    $dbname = getenv('MYSQL_DATABASE') ?: 'azuracast';
    $user = getenv('MYSQL_USER') ?: 'azuracast';
    $pass = getenv('MYSQL_PASSWORD') ?: 'azuracast';
    $port = getenv('MYSQL_PORT') ?: '3306';

    // Forçamos o uso do HOST e PORT via TCP para evitar erro de socket
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    $queries = [
        "CREATE TABLE IF NOT EXISTS pulso_ouvintes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            station_id INT NOT NULL,
            telefone VARCHAR(20) UNIQUE,
            nome VARCHAR(100),
            data_nascimento DATE NULL,
            last_birthday_celebrated YEAR NULL,
            score_fidelidade FLOAT DEFAULT 0.0,
            sentimento_score FLOAT DEFAULT 0.0,
            win_count INT DEFAULT 0,
            last_win_at DATETIME NULL,
            status_lealdade ENUM('novo', 'regular', 'veterano', 'embaixador', 'lenda') DEFAULT 'novo',
            risco_churn TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX (telefone),
            INDEX (station_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

        "CREATE TABLE IF NOT EXISTS pulso_interacoes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ouvinte_id INT NOT NULL,
            station_id INT NOT NULL,
            tipo ENUM('mensagem', 'pedido_musica', 'participacao_promo', 'clique_app', 'tempo_escuta') NOT NULL,
            conteudo TEXT,
            humor_detectado VARCHAR(50),
            contexto_tag VARCHAR(50),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (ouvinte_id) REFERENCES pulso_ouvintes(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

        "CREATE TABLE IF NOT EXISTS pulso_promocoes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            station_id INT NOT NULL,
            nome VARCHAR(100) NOT NULL,
            premio VARCHAR(255),
            meta_participantes INT DEFAULT 150,
            status ENUM('rascunho', 'activa', 'encerrada') DEFAULT 'rascunho',
            data_inicio DATETIME,
            data_fim DATETIME,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

        "CREATE TABLE IF NOT EXISTS pulso_participacoes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            promocao_id INT NOT NULL,
            ouvinte_id INT NOT NULL,
            peso_atribuido FLOAT DEFAULT 1.0,
            venceu TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (promocao_id) REFERENCES pulso_promocoes(id) ON DELETE CASCADE,
FOREIGN KEY (ouvinte_id) REFERENCES pulso_ouvintes(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
        "CREATE TABLE IF NOT EXISTS pulso_notificacoes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            station_id INT NOT NULL,
            tipo VARCHAR(50) NOT NULL,
            titulo VARCHAR(255) NOT NULL,
            mensagem TEXT,
            dados JSON,
            lida TINYINT DEFAULT 0,
            data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_station_lida (station_id, lida)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
    
    ];

    foreach ($queries as $sql) {
        $pdo->exec($sql);
    }

    echo "\n✅ [PULSO] Estrutura de Inteligência instalada com sucesso!\n";

} catch (Exception $e) {
    echo "\n❌ Erro: " . $e->getMessage() . "\n";
}
