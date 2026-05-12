# Proposta Fácil

Proposta Fácil é um MVP SaaS em Laravel 13 para freelancers, prestadores de serviço e pequenos negócios criarem propostas comerciais, orçamentos e links públicos de aprovação.

## Stack técnica

- Laravel 13, PHP 8.3 e MySQL
- Vue.js 3 com TypeScript, Vite e Tailwind CSS
- Arquitetura com Models, Migrations, Form Requests, Policies, Services, Enums, Seeders, Factories e testes
- SEO público básico com páginas institucionais, Open Graph, Twitter Card, Schema.org, robots.txt e sitemap.xml

## Funcionalidades do MVP

- Dashboard autenticado com indicadores de propostas e plano
- CRUD JSON de clientes, serviços/produtos e propostas
- Propostas com itens, desconto, validade, condições, status e token público seguro
- Aprovação/recusa de proposta via link público sem login
- Preparação de PDF por rota protegida e validação por plano
- Painel admin protegido por role para planos, usuários, configurações e relatórios
- Planos iniciais: Gratuito, Pro e Plus

## Instalação local

```bash
composer install
cp .env.example .env
php artisan key:generate
npm install
php artisan migrate --seed
npm run build
php artisan serve
```

Configure o banco no `.env`. Para desenvolvimento rápido é possível usar SQLite ajustando `DB_CONNECTION=sqlite`.

## Comandos principais

```bash
composer test
npm run build
php artisan migrate:fresh --seed
php artisan route:list
```

## Usuário admin seed

- E-mail: `admin@propostafacil.test`
- Senha: `password`

## Observações

Pagamentos online não fazem parte do MVP. A estrutura de planos permite alteração manual pelo admin e deixa espaço para futura integração com Pix/checkout.
