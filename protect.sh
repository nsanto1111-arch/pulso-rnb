#!/bin/bash
# RNB Plugin Protection Script
# Executa após cada actualização do AzuraCast

PLUGIN_DIR="/var/azuracast/www/plugins/programacao-plugin"
BACKUP_DIR="/var/azuracast/backups/rnb-plugin"
DATE=$(date +%Y%m%d_%H%M%S)

# Criar backup
mkdir -p $BACKUP_DIR
tar -czf "$BACKUP_DIR/plugin_backup_$DATE.tar.gz" -C /var/azuracast/www/plugins programacao-plugin

# Manter apenas últimos 10 backups
ls -t $BACKUP_DIR/plugin_backup_*.tar.gz | tail -n +11 | xargs -r rm

# Limpar cache
rm -f /var/azuracast/www_tmp/app_routes.cache.php
php /var/azuracast/www/backend/bin/console cache:clear

echo "[$DATE] Plugin protegido e cache limpo" >> $BACKUP_DIR/protection.log
