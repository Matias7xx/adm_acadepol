#!/bin/bash
set -e

echo "🚀 Iniciando aplicação Laravel..."

# Aguardar banco de dados estar disponível
echo "⏳ Aguardando banco de dados..."
until pg_isready -h db -p 5432 -U postgres >/dev/null 2>&1; do
    echo "💤 Banco não disponível, aguardando 3 segundos..."
    sleep 3
done

echo "✅ Banco de dados disponível!"

# Aguardar mais um pouco para garantir
sleep 2

# Executar migrações
echo "🔄 Executando migrações..."
php artisan migrate --force

# Verificar se há dados nas tabelas
TABLES_COUNT=$(php artisan tinker --execute="echo \App\Models\User::count();" 2>/dev/null || echo "0")

if [ "$TABLES_COUNT" = "0" ]; then
    echo "📊 Executando seeders (primeira execução)..."
    php artisan db:seed --class=AdminCoreSeeder --force
else
    echo "📈 Banco já possui dados, pulando seeders..."
fi

# Limpar e otimizar caches
echo "🧹 Limpando caches..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# Otimizar para produção (se não for ambiente local)
if [ "$APP_ENV" != "local" ]; then
    echo "⚡ Otimizando para produção..."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
fi

# Garantir que o storage link existe
echo "🔗 Verificando storage link..."
if [ ! -L public/storage ]; then
    echo "Criando storage link..."
    php artisan storage:link --no-interaction
else
    echo "Storage link já existe, pulando..."
fi

# Ajustar permissões finais
echo "🔒 Ajustando permissões..."
chown -R www-data:www-data storage bootstrap/cache
chmod -R 755 storage bootstrap/cache

# Garantir que public/storage tenha as permissões corretas se existir
if [ -L public/storage ]; then
    chown -h www-data:www-data public/storage
fi

#Composer install para garantir o funcionamento da API
# Garantir que composer está atualizado
echo "📦 Verificando dependências..."
composer install --no-interaction --prefer-dist --optimize-autoloader

echo "🎉 Aplicação pronta! Iniciando Apache..."

# Iniciar Apache
exec apache2-foreground