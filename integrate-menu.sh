#!/bin/bash
set -e

echo "🎯 Integrando PULSO no menu do AzuraCast..."

MENU_FILE="/var/azuracast/www/frontend/components/Stations/menu.ts"

# Backup
cp "$MENU_FILE" "$MENU_FILE.backup-$(date +%Y%m%d-%H%M%S)"

# 1. Adicionar import do ícone (se não existir)
if ! grep -q "IconIcCalendarMonth" "$MENU_FILE"; then
    echo "Adicionando import do ícone..."
    sed -i '/import IconIcPodcasts/a import IconIcCalendarMonth from "~icons/ic/baseline-calendar-month";' "$MENU_FILE"
fi

# 2. Verificar se PULSO já existe
if grep -q "key: 'pulso'" "$MENU_FILE"; then
    echo "✅ PULSO já está no menu!"
    exit 0
fi

# 3. Adicionar categoria PULSO após playlists (linha ~169)
echo "Adicionando categoria PULSO..."

# Criar o código da categoria PULSO
PULSO_CATEGORY='        },
        {
            key: '\''pulso'\'',
            label: $gettext('\''PULSO - Programação'\''),
            icon: () => IconIcCalendarMonth,
            url: '\''/public/pulso/'\'' + station.value.id,
            external: true,
            visible: () => userAllowedForStation(StationPermissions.Media)
        },'

# Inserir após a categoria playlists (procurar pelo fechamento dela)
# Linha ~168 tem o fechamento de playlists
sed -i "/key: 'playlists'/,/},/{
    /^        },$/a\\
$PULSO_CATEGORY
}" "$MENU_FILE"

echo "✅ PULSO adicionado ao menu!"

# 4. Compilar frontend
echo "Compilando frontend..."
cd /var/azuracast/www
npm run build 2>&1 | tail -20

echo ""
echo "🎉 PULSO integrado com sucesso!"
echo ""
echo "Acede: https://rnb.radionewband.ao/station/1"
echo "O menu PULSO deve aparecer entre Playlists e Podcasts"
