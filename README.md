# Plugin de Programação para AzuraCast

Gestor de Programas, Locutores e Grade de Programação para AzuraCast.

## 🚀 Instalação Rápida

### Passo 1: Fazer backup (sempre!)

```bash
cd /var/azuracast
./docker.sh backup
```

### Passo 2: Copiar os arquivos do plugin

```bash
# Remover versão antiga (se existir)
rm -rf /var/azuracast/www/plugins/programacao-plugin

# Copiar nova versão
cp -r /caminho/para/programacao-plugin /var/azuracast/www/plugins/programacao-plugin

# Ajustar permissões
chown -R 1000:1000 /var/azuracast/www/plugins/programacao-plugin
chmod -R 755 /var/azuracast/www/plugins/programacao-plugin
```

### Passo 3: Configurar docker-compose.override.yml

```bash
cat > /var/azuracast/docker-compose.override.yml << 'EOF'
services:
  web:
    environment:
      AZURACAST_PLUGIN_MODE: "true"
    volumes:
      - /var/azuracast/www/plugins/programacao-plugin:/var/azuracast/www/plugins/programacao-plugin
EOF
```

### Passo 4: Criar tabelas no banco de dados

```bash
# Copiar SQL para o container
docker cp /var/azuracast/www/plugins/programacao-plugin/migrations/001_create_tables.sql azuracast:/tmp/

# Executar o SQL
docker exec azuracast bash -c "mysql -u azuracast -pazuracast azuracast < /tmp/001_create_tables.sql"
```

### Passo 5: Reiniciar o AzuraCast

```bash
cd /var/azuracast
./docker.sh restart
```

### Passo 6: Testar

Acesse no navegador:
```
https://SEU-DOMINIO/station/1/programacao
```

API pública:
```
https://SEU-DOMINIO/api/station/1/programacao/no-ar
```

---

## 📁 Estrutura do Plugin

```
programacao-plugin/
├── composer.json           # Metadados e autoload PSR-4
├── events.php              # Registro de rotas, views, menu
├── services.php            # Registro de serviços (DI)
├── migrations/
│   └── 001_create_tables.sql
└── src/
    ├── Controller/
    │   ├── ProgramacaoController.php     # Admin
    │   └── ProgramacaoApiController.php  # API pública
    └── Service/
        └── ProgramacaoService.php        # Lógica de negócio
```

---

## 🔗 Rotas Disponíveis

### Admin (requer autenticação)

| Rota | Descrição |
|------|-----------|
| `/station/{id}/programacao` | Dashboard do plugin |
| `/station/{id}/programacao/programas` | Lista de programas |
| `/station/{id}/programacao/locutores` | Lista de locutores |
| `/station/{id}/programacao/grade` | Grade semanal |
| `/station/{id}/programacao/config` | Configurações |

### API Pública

| Rota | Descrição |
|------|-----------|
| `/api/station/{id}/programacao/no-ar` | Programa atual |
| `/api/station/{id}/programacao/programas` | Lista de programas |
| `/api/station/{id}/programacao/programas/{id}` | Detalhes de um programa |
| `/api/station/{id}/programacao/locutores` | Lista de locutores |
| `/api/station/{id}/programacao/grade` | Grade semanal |

---

## 🛠️ Troubleshooting

### Erro 404 nas rotas

1. Verificar se o plugin está montado:
```bash
docker exec azuracast ls -la /var/azuracast/www/plugins/programacao-plugin/
```

2. Verificar logs:
```bash
docker logs azuracast 2>&1 | grep -i "plugin\|programacao" | tail -20
```

### Erro 500 (erro interno)

1. Verificar logs PHP:
```bash
docker logs azuracast 2>&1 | grep -i "error\|fatal\|exception" | tail -50
```

2. Verificar sintaxe PHP:
```bash
docker exec azuracast php -l /var/azuracast/www/plugins/programacao-plugin/events.php
docker exec azuracast php -l /var/azuracast/www/plugins/programacao-plugin/services.php
```

### Tabelas não criadas

1. Verificar se as tabelas existem:
```bash
docker exec azuracast mysql -u azuracast -pazuracast azuracast -e "SHOW TABLES LIKE 'plugin_prog%';"
```

2. Executar migration novamente:
```bash
docker exec azuracast bash -c "mysql -u azuracast -pazuracast azuracast < /var/azuracast/www/plugins/programacao-plugin/migrations/001_create_tables.sql"
```

---

## 📋 Changelog

### v1.0.0
- Dashboard com estatísticas
- CRUD de programas
- CRUD de locutores
- Grade semanal
- API pública para integração
- Configurações por estação
