# 🔧 GUIA DE RECUPERAÇÃO RNB

## Após Actualização do AzuraCast

```bash
# Entrar no container
docker exec -it azuracast bash

# Opção 1: Apenas limpar cache (tenta primeiro)
rm -f /var/azuracast/www_tmp/app_routes.cache.php
php /var/azuracast/www/backend/bin/console cache:clear

# Testar se funciona
curl -s "http://localhost/pulso/widget" | head -5

# Se não funcionar, restaurar backup completo
/var/azuracast/www/plugins/programacao-plugin/restore.sh
```

## Após Actualização do WordPress/Tema

1. Aceder ao site: https://radionewband.ao/wp-admin/
2. Aparência → Editor de Temas → proradio-child → functions.php
3. Verificar se existe a secção "RNB PLAYER INTEGRATION"
4. Se não existir, copiar de: /var/azuracast/backups/rnb-plugin/wp-functions-backup.txt

## Testar Player

- API: https://rnb.radionewband.ao/pulso/widget
- Site: https://radionewband.ao (ver rotação no player)

## Contacto Suporte

Se nada funcionar, contactar equipa técnica com:
- Screenshot do erro
- Qual actualização foi feita
- Data/hora do problema

