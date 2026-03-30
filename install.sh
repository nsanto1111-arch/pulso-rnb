#!/bin/bash
# =============================================================================
# Script de Instalação - Plugin de Programação para AzuraCast
# =============================================================================

set -e

# Cores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}"
echo "=============================================="
echo " Plugin de Programação - Instalação"
echo "=============================================="
echo -e "${NC}"

# Verificar se está rodando como root
if [ "$EUID" -ne 0 ]; then
    echo -e "${RED}❌ Execute como root: sudo ./install.sh${NC}"
    exit 1
fi

# Diretórios
AZURACAST_DIR="/var/azuracast"
PLUGIN_DIR="${AZURACAST_DIR}/www/plugins/programacao-plugin"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# Verificar se o AzuraCast existe
if [ ! -d "$AZURACAST_DIR" ]; then
    echo -e "${RED}❌ Diretório do AzuraCast não encontrado: ${AZURACAST_DIR}${NC}"
    exit 1
fi

echo -e "${YELLOW}📦 Passo 1/5: Removendo versão anterior (se existir)...${NC}"
if [ -d "$PLUGIN_DIR" ]; then
    rm -rf "$PLUGIN_DIR"
    echo -e "${GREEN}   ✓ Versão anterior removida${NC}"
else
    echo -e "${GREEN}   ✓ Nenhuma versão anterior encontrada${NC}"
fi

echo -e "${YELLOW}📁 Passo 2/5: Copiando arquivos do plugin...${NC}"
cp -r "$SCRIPT_DIR" "$PLUGIN_DIR"
rm -f "${PLUGIN_DIR}/install.sh"  # Remover script de instalação da pasta do plugin
chown -R 1000:1000 "$PLUGIN_DIR"
chmod -R 755 "$PLUGIN_DIR"
echo -e "${GREEN}   ✓ Arquivos copiados para: ${PLUGIN_DIR}${NC}"

echo -e "${YELLOW}⚙️  Passo 3/5: Configurando docker-compose.override.yml...${NC}"
OVERRIDE_FILE="${AZURACAST_DIR}/docker-compose.override.yml"

# Verificar se já existe e fazer backup
if [ -f "$OVERRIDE_FILE" ]; then
    cp "$OVERRIDE_FILE" "${OVERRIDE_FILE}.bak"
    echo -e "${BLUE}   ℹ Backup criado: ${OVERRIDE_FILE}.bak${NC}"
fi

cat > "$OVERRIDE_FILE" << 'EOF'
services:
  web:
    environment:
      AZURACAST_PLUGIN_MODE: "true"
    volumes:
      - /var/azuracast/www/plugins/programacao-plugin:/var/azuracast/www/plugins/programacao-plugin
EOF
echo -e "${GREEN}   ✓ docker-compose.override.yml configurado${NC}"

echo -e "${YELLOW}🗃️  Passo 4/5: Criando tabelas no banco de dados...${NC}"
# Copiar SQL para dentro do container
docker cp "${PLUGIN_DIR}/migrations/001_create_tables.sql" azuracast:/tmp/plugin_tables.sql

# Executar SQL
docker exec azuracast bash -c "mysql -u azuracast -pazuracast azuracast < /tmp/plugin_tables.sql" 2>/dev/null || {
    echo -e "${YELLOW}   ⚠ Algumas tabelas podem já existir (ignorando erros)${NC}"
}

# Verificar se as tabelas foram criadas
TABLES=$(docker exec azuracast mysql -u azuracast -pazuracast azuracast -N -e "SHOW TABLES LIKE 'plugin_prog%';" 2>/dev/null | wc -l)
if [ "$TABLES" -ge 4 ]; then
    echo -e "${GREEN}   ✓ ${TABLES} tabelas criadas/verificadas${NC}"
else
    echo -e "${RED}   ❌ Erro ao criar tabelas. Verifique os logs.${NC}"
fi

echo -e "${YELLOW}🔄 Passo 5/5: Reiniciando o AzuraCast...${NC}"
cd "$AZURACAST_DIR"
./docker.sh restart

echo ""
echo -e "${GREEN}=============================================="
echo " ✅ Instalação concluída!"
echo "=============================================="
echo -e "${NC}"
echo ""
echo "Acesse o plugin em:"
echo -e "${BLUE}  → https://SEU-DOMINIO/station/1/programacao${NC}"
echo ""
echo "API pública:"
echo -e "${BLUE}  → https://SEU-DOMINIO/api/station/1/programacao/no-ar${NC}"
echo ""
echo -e "${YELLOW}⚠️  Aguarde ~60 segundos para o AzuraCast reiniciar completamente.${NC}"
