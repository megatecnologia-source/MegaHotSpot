#!/bin/bash

# Endpoint de destino - O host pode variar (localhost/api/v1/sync.php ou porta específica)
API_URL="http://localhost/api/v1/sync.php"

# O Token deve corresponder ao cliente_token real no banco de dados para que o Insert funcione com sucesso (201)
TOKEN="d2a23ebf-8f8d-4a11-82f5-db981f3b0e12" 

echo "=============================================="
echo "    🧪 TESTE DE INGESTÃO MIKROTIK HOTSPOT"
echo "=============================================="

echo "Enviando um LEAD Fictício..."
echo "URL : $API_URL"
echo "AUTH: $TOKEN"
echo "..."

curl -i -X POST "$API_URL" \
     -H "Content-Type: application/json" \
     -H "X-Auth-Token: $TOKEN" \
     -d '{
         "nome": "João MikroTik Teste",
         "whatsapp": "5511999991234",
         "email": "joao.teste@mikrotik.com",
         "mac": "00:11:22:33:44:55"
     }'

echo -e "\n\n✔️ Processo finalizado."
