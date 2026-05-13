#!/bin/bash
# Mega Hotspot SaaS — Build Release Script
# Gera um pacote ZIP pronto para upload na Hostinger

echo "=== Gerando Pacote de Release (Hostinger) ==="

# Verifica se está na raiz do projeto
if [ ! -d "api" ] || [ ! -d "admin" ]; then
    echo "Erro: Execute este script na raiz do projeto."
    exit 1
fi

RELEASE_DIR="mega_hotspot_release"
RELEASE_ZIP="mega_hotspot_v1.0.zip"

echo "Limpando releases anteriores..."
rm -rf "$RELEASE_DIR" "$RELEASE_ZIP"

echo "Criando estrutura do pacote..."
mkdir "$RELEASE_DIR"

# Pastas essenciais
cp -r admin api config hotspot logs sql superadmin "$RELEASE_DIR/"

# Arquivos da raiz
cp .htaccess "$RELEASE_DIR/" 2>/dev/null || true
cp index.php "$RELEASE_DIR/" 2>/dev/null || true

# Limpeza de arquivos não necessários no servidor dentro dessas pastas
echo "Removendo arquivos desnecessários..."
find "$RELEASE_DIR" -name "*.log" -delete

echo "Compactando $RELEASE_ZIP..."
cd "$RELEASE_DIR" || exit
zip -r "../$RELEASE_ZIP" ./* .htaccess > /dev/null
cd ..

echo "Limpando diretório temporário..."
rm -rf "$RELEASE_DIR"

echo "✅ Sucesso! O arquivo '$RELEASE_ZIP' está pronto para upload na Hostinger."
