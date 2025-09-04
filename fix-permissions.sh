#!/bin/bash

# Script para corrigir permissões do Laravel no Docker

echo "Corrigindo permissões do Laravel..."

# Corrige ownership
chown -R sail:sail storage/
chown -R sail:sail bootstrap/cache/

# Corrige permissões dos diretórios
find storage -type d -exec chmod 775 {} +
find bootstrap/cache -type d -exec chmod 775 {} +

# Corrige permissões dos arquivos
find storage -type f -exec chmod 664 {} +
find bootstrap/cache -type f -exec chmod 664 {} +

echo "Permissões corrigidas com sucesso!"
