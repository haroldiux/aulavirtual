#!/bin/bash
set -e

cd /var/www

# 1. Asegurar .env
if [ ! -f .env ]; then
  cp .env.example .env
fi

# 2. Composer install si falta vendor (el volumen anonimo puede crear dir vacio)
if [ ! -f vendor/autoload.php ]; then
  echo "[entrypoint] Instalando dependencias composer..."
  composer install --no-interaction --no-progress
fi

# 3. APP_KEY si esta vacio
if ! grep -q "^APP_KEY=." .env; then
  echo "[entrypoint] Generando APP_KEY..."
  php artisan key:generate --no-interaction
fi

# 4. Permisos storage/bootstrap
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
chmod -R 775 storage bootstrap/cache 2>/dev/null || true

# 5. Migraciones con reintentos (esperar a que db este lista)
echo "[entrypoint] Esperando base de datos..."
for i in $(seq 1 30); do
  if php artisan migrate --force >/dev/null 2>&1; then
    echo "[entrypoint] Migraciones OK"
    break
  fi
  echo "[entrypoint] DB no lista, reintentando ($i/30)..."
  sleep 2
done

# 6. Seeders solo si RUN_SEED=true (dev)
if [ "${RUN_SEED:-false}" = "true" ]; then
  echo "[entrypoint] Ejecutando seeders..."
  php artisan db:seed --force
fi

# 7. Storage link
php artisan storage:link >/dev/null 2>&1 || true

# 8. Crear bucket MinIO si no existe
echo "[entrypoint] Configurando MinIO..."
mc alias set local http://minio:9000 minioadmin minioadmin 2>/dev/null || true
mc mb -p local/aula-virtual 2>/dev/null || true
mc anonymous set download local/aula-virtual 2>/dev/null || true

echo "[entrypoint] Arrancando supervisor (nginx + php-fpm)..."
exec "$@"
