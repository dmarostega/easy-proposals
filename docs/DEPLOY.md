# Deploy em VPS Hostinger KVM 1

Passos para Nginx, PHP-FPM, MySQL e Node.

## 1. Preparar `.env`

```bash
cp .env.example .env
php artisan key:generate
```

Configure `APP_URL`, `DB_CONNECTION=mysql`, `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, mail e cache conforme a VPS.

## 2. Instalar dependências

```bash
composer install --no-dev --optimize-autoloader
npm install
npm run build
```

## 3. Banco, storage e permissões

```bash
php artisan migrate --seed --force
php artisan storage:link
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R ug+rw storage bootstrap/cache
```

## 4. Cache de produção

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## 5. Nginx

Aponte o `root` do virtual host para `public/`, habilite PHP-FPM e proteja arquivos ocultos. Exemplo de bloco principal:

```nginx
root /var/www/proposta-facil/public;
index index.php;
location / { try_files $uri $uri/ /index.php?$query_string; }
location ~ \.php$ { include snippets/fastcgi-php.conf; fastcgi_pass unix:/run/php/php8.3-fpm.sock; }
location ~ /\.(?!well-known).* { deny all; }
```

## 6. Atualização de release

```bash
git pull
composer install --no-dev --optimize-autoloader
npm install
npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```
