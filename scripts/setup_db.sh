#!/bin/bash
# Mega Hotspot SaaS — Script de Setup do Banco de Dados

echo "=== Setup Mega Hotspot DB ==="
read -p "Host MySQL [localhost]: " HOST
HOST=${HOST:-localhost}
read -p "Usuário MySQL: " DBUSER
read -sp "Senha MySQL: " DBPASS
echo ""

# Gerar hashes reais via PHP
echo "Gerando hashes de senha..."
HASH_SUPER=$(php -r "echo password_hash('superadmin123', PASSWORD_DEFAULT);")
HASH_ADMIN=$(php -r "echo password_hash('admin123', PASSWORD_DEFAULT);")

# Criar banco se não existir
echo "Criando banco de dados (se não existir)..."
mysql -h "$HOST" -u "$DBUSER" -p"$DBPASS" -e "CREATE DATABASE IF NOT EXISTS mega_hotspot CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Executar Schema
echo "Aplicando schema.sql..."
mysql -h "$HOST" -u "$DBUSER" -p"$DBPASS" mega_hotspot < "$(dirname "$0")/../sql/schema.sql"

# Executar Seed (com substituição dinâmica dos hashes)
echo "Aplicando seed.sql com hashes gerados..."
SEED=$(cat "$(dirname "$0")/../sql/seed.sql")
SEED="${SEED//GERAR_HASH_DE_superadmin123/$HASH_SUPER}"
SEED="${SEED//GERAR_HASH_DE_admin123/$HASH_ADMIN}"
echo "$SEED" | mysql -h "$HOST" -u "$DBUSER" -p"$DBPASS" mega_hotspot

echo ""
echo "✅ Banco configurado com sucesso!"
echo "--------------------------------------------------------"
echo "Credenciais de Teste:"
echo "   Superadmin: admin@megatecnologias.com / superadmin123"
echo "   Admin Estab: barteste@megatecnologias.com / admin123"
echo "   Token Estab: d2a23ebf-8f8d-4a11-82f5-db981f3b0e12"
echo "--------------------------------------------------------"
