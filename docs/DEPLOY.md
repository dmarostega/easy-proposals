# Deploy — Easy Proposals

Guia de deploy do projeto **Easy Proposals** em uma VPS Hostinger KVM 1 usando CloudPanel, Nginx, PHP-FPM, MySQL, Node.js, Composer e Cloudflare.

---

## 1. Visão geral

O **Easy Proposals** é um SaaS desenvolvido com:

- Laravel 13
- Vue.js 3
- TypeScript
- MySQL
- Tailwind CSS
- Vite
- Inertia.js
- Nginx
- PHP-FPM
- Cloudflare

Este documento descreve o processo básico para publicar o projeto em produção.

---

## 2. Ambiente previsto

Infraestrutura esperada:

- VPS Hostinger KVM 1
- CloudPanel
- Nginx
- PHP-FPM
- MySQL/MariaDB
- Composer
- Node.js
- NPM
- Git
- Certificado SSL válido
- Cloudflare gerenciando DNS

---

## 3. Requisitos mínimos

Verificar se a VPS possui:

```bash
php -v
composer -V
node -v
npm -v
mysql --version
git --version

## Cloudflare

Este projeto pode ser publicado atrás do Cloudflare.

Configuração recomendada:

- DNS apontando para o IP da VPS.
- Proxy ativado, se desejado.
- SSL/TLS em modo Full ou Full (strict), preferencialmente Full (strict) quando houver certificado válido na VPS.
- Não usar Flexible SSL em produção Laravel, para evitar problemas de redirect HTTPS, mixed content e loop de redirecionamento.
- Garantir que `APP_URL` esteja configurado com `https://seudominio.com.br`.
- Após alterações de DNS, SSL ou build front-end, limpar cache do Cloudflare se necessário.

## Atenção com cache

Evitar cache agressivo em rotas autenticadas, painel admin, dashboard e APIs.

Rotas que não devem ser cacheadas:

- `/login`
- `/register`
- `/dashboard`
- `/admin/*`
- `/api/*`
- rotas autenticadas em geral

Páginas públicas como Home, Preços, Recursos, Termos e Política de Privacidade podem usar cache com cuidado.