-- ============================================================================
-- Plugin de Programação - Criação das Tabelas
-- Executar no banco de dados do AzuraCast (azuracast)
-- ============================================================================

-- Tabela de Programas
CREATE TABLE IF NOT EXISTS plugin_prog_programas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    station_id INT NOT NULL,
    nome VARCHAR(255) NOT NULL,
    descricao TEXT,
    banner VARCHAR(500),
    hora_inicio TIME NOT NULL DEFAULT '00:00:00',
    hora_fim TIME NOT NULL DEFAULT '00:00:00',
    dias_semana JSON,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_station_id (station_id),
    INDEX idx_ativo (ativo),
    INDEX idx_horario (hora_inicio, hora_fim)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Locutores
CREATE TABLE IF NOT EXISTS plugin_prog_locutores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    station_id INT NOT NULL,
    nome VARCHAR(255) NOT NULL,
    bio TEXT,
    foto VARCHAR(500),
    email VARCHAR(255),
    instagram VARCHAR(100),
    twitter VARCHAR(100),
    facebook VARCHAR(100),
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_station_id (station_id),
    INDEX idx_ativo (ativo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Relacionamento Programa-Locutor
CREATE TABLE IF NOT EXISTS plugin_prog_programa_locutor (
    id INT AUTO_INCREMENT PRIMARY KEY,
    programa_id INT NOT NULL,
    locutor_id INT NOT NULL,
    is_principal TINYINT(1) NOT NULL DEFAULT 0,
    funcao VARCHAR(100),
    UNIQUE KEY uk_programa_locutor (programa_id, locutor_id),
    INDEX idx_programa_id (programa_id),
    INDEX idx_locutor_id (locutor_id),
    FOREIGN KEY (programa_id) REFERENCES plugin_prog_programas(id) ON DELETE CASCADE,
    FOREIGN KEY (locutor_id) REFERENCES plugin_prog_locutores(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Configurações
CREATE TABLE IF NOT EXISTS plugin_prog_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    station_id INT NOT NULL UNIQUE,
    exibir_api_publica TINYINT(1) NOT NULL DEFAULT 1,
    exibir_locutor_metadata TINYINT(1) NOT NULL DEFAULT 0,
    formato_metadata VARCHAR(255) DEFAULT '{programa} - {locutor}',
    programa_padrao_nome VARCHAR(255) DEFAULT 'Programação Musical',
    programa_padrao_descricao TEXT,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_station_id (station_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- Dados de exemplo (opcional - remova se não quiser dados de teste)
-- ============================================================================

-- Inserir programa de exemplo para station_id = 1
INSERT IGNORE INTO plugin_prog_programas (station_id, nome, descricao, hora_inicio, hora_fim, dias_semana, ativo) VALUES
(1, 'Manhã Total', 'O melhor da música para começar o seu dia!', '06:00:00', '10:00:00', '["segunda","terca","quarta","quinta","sexta"]', 1),
(1, 'Tarde Hits', 'Os maiores sucessos do momento.', '14:00:00', '18:00:00', '["segunda","terca","quarta","quinta","sexta"]', 1),
(1, 'Noite Especial', 'Programação especial para a sua noite.', '20:00:00', '00:00:00', '["segunda","terca","quarta","quinta","sexta","sabado"]', 1),
(1, 'Final de Semana', 'Música boa o dia todo!', '08:00:00', '22:00:00', '["sabado","domingo"]', 1);

-- Inserir locutor de exemplo
INSERT IGNORE INTO plugin_prog_locutores (station_id, nome, bio, ativo) VALUES
(1, 'DJ Newton', 'Locutor e DJ com anos de experiência no rádio.', 1);

-- Inserir configuração padrão
INSERT IGNORE INTO plugin_prog_config (station_id, exibir_api_publica, programa_padrao_nome, programa_padrao_descricao) VALUES
(1, 1, 'Rádio New Band', 'A melhor programação musical para você.');

-- ============================================================================
-- FIM
-- ============================================================================
