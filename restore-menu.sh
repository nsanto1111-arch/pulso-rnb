#!/bin/bash
# Restaura o menu de Programação no AzuraCast

MENU_FILE="/var/azuracast/www/frontend/components/Stations/menu.ts"

# Verifica se o menu já existe
if ! grep -q "programacao" "$MENU_FILE" 2>/dev/null; then
    echo "Restaurando menu de Programação..."
    
    sed -i "/key: 'logs',/i\\
        {\\
            key: 'programacao',\\
            label: 'Programação',\\
            icon: () => IconIcMic,\\
            items: [\\
                {\\
                    key: 'programacao_dashboard',\\
                    label: 'Dashboard',\\
                    url: '/public/programacao/' + station.value.id,\\
                    external: true,\\
                },\\
                {\\
                    key: 'programacao_programas',\\
                    label: 'Programas',\\
                    url: '/public/programacao/' + station.value.id + '/programas',\\
                    external: true,\\
                },\\
                {\\
                    key: 'programacao_locutores',\\
                    label: 'Locutores',\\
                    url: '/public/programacao/' + station.value.id + '/locutores',\\
                    external: true,\\
                },\\
                {\\
                    key: 'programacao_carrossel',\\
                    label: 'Carrossel',\\
                    url: '/public/programacao/' + station.value.id + '/carrossel',\\
                    external: true,\\
                },\\
                {\\
                    key: 'programacao_player',\\
                    label: 'Player Demo',\\
                    url: '/public/programacao/' + station.value.id + '/player',\\
                    external: true,\\
                },\\
            ]\\
        }," "$MENU_FILE"
    
    # Remover { duplicada
    sed -i '/^        {$/{n;/^        {$/d}' "$MENU_FILE"
    
    # Adicionar { antes de logs se faltar
    LINE_NUM=$(grep -n "key: 'logs'," "$MENU_FILE" | head -1 | cut -d: -f1)
    PREV_LINE=$((LINE_NUM - 1))
    PREV_CONTENT=$(sed -n "${PREV_LINE}p" "$MENU_FILE")
    if [[ "$PREV_CONTENT" != *"{"* ]]; then
        sed -i "${LINE_NUM}i\\        {" "$MENU_FILE"
    fi
    
    # Recompilar frontend
    cd /var/azuracast/www
    npm install 2>/dev/null
    npm run build
    
    # Limpar cache de rotas
    rm -f /var/azuracast/www_tmp/app_routes.cache.php
    
    echo "Menu de Programação restaurado e frontend recompilado!"
else
    echo "Menu de Programação já existe."
fi
