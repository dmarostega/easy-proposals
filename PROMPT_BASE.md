# Prompt Codex - Proposta Fácil (Easy Proposal)

Você é um arquiteto de software sênior especialista em Laravel, Vue.js, TypeScript, MySQL, SaaS e boas práticas como SOLID, Clean Code, Service Layer, Form Requests, DTOs quando fizer sentido, Policies e separação clara de responsabilidades.

Crie um projeto SaaS chamado "Proposta Fácil", focado em freelancers, prestadores de serviço, técnicos, designers, desenvolvedores, pequenos negócios e profissionais autônomos que precisam criar propostas comerciais e orçamentos profissionais.

Stack obrigatória:
- Laravel 13
- Vue.js 3
- TypeScript
- MySQL
- Tailwind CSS
- Vite
- Preferencialmente Inertia.js com Vue 3, salvo se houver justificativa melhor para API REST
- Autenticação com starter kit oficial ou estrutura compatível com Laravel 13

Objetivo do produto:
Permitir que usuários criem propostas comerciais, orçamentos e links públicos para aprovação do cliente.

Personas principais:
1. Freelancer de desenvolvimento ou design que envia propostas para clientes.
2. Prestador de serviços local que precisa formalizar orçamento.
3. Pequena agência que quer registrar propostas enviadas e aprovadas.

Funcionalidades obrigatórias do usuário:
- Cadastro e login.
- Dashboard com resumo:
  - propostas criadas no mês;
  - propostas aprovadas;
  - propostas pendentes;
  - valor total aprovado;
  - limite do plano atual.
- CRUD de clientes.
- CRUD de serviços/produtos reutilizáveis.
- CRUD de propostas.
- Proposta deve conter:
  - cliente;
  - título;
  - descrição;
  - validade;
  - itens com descrição, quantidade, valor unitário e total;
  - desconto opcional;
  - observações;
  - condições comerciais;
  - status: rascunho, enviada, visualizada, aprovada, recusada, expirada.
- Gerar link público seguro para visualização da proposta sem login.
- Cliente pode aprovar ou recusar a proposta pelo link público.
- Registrar data/hora de aprovação ou recusa.
- Gerar PDF da proposta.
- Permitir personalização simples:
  - nome comercial;
  - logo;
  - cor principal;
  - cor secundária;
  - texto padrão de rodapé;
  - dados de contato.
- Usar variáveis CSS/Tailwind para cores configuráveis no front-end.
- Validar limites conforme plano.

Painel admin obrigatório:
- Área administrativa protegida por role admin.
- CRUD de planos.
- Plano deve ter:
  - nome;
  - slug;
  - preço mensal sugerido;
  - limite de propostas por mês;
  - limite de clientes;
  - permite PDF;
  - permite logo personalizada;
  - ativo/inativo.
- Deve existir no mínimo:
  - Plano Gratuito;
  - Plano Pro;
  - Plano Plus.
- CRUD de usuários.
- Admin pode alterar plano do usuário manualmente.
- Admin pode ativar/desativar usuário.
- Tela de configurações globais:
  - nome do aplicativo;
  - domínio base;
  - e-mail de contato;
  - cores padrão;
  - metatags padrão;
  - texto padrão de SEO.
- Relatório básico:
  - usuários cadastrados;
  - usuários por plano;
  - propostas criadas;
  - propostas aprovadas.

Planos iniciais:
- Gratuito: até 3 propostas por mês, sem logo personalizada.
- Pro: até 50 propostas por mês, com PDF.
- Plus: propostas ilimitadas, PDF e logo personalizada.

SEO obrigatório:
- Criar páginas públicas:
  - Home;
  - Preços;
  - Recursos;
  - Termos de Uso;
  - Política de Privacidade.
- Implementar SEO básico:
  - title dinâmico;
  - meta description;
  - canonical;
  - Open Graph;
  - Twitter Card;
  - Schema.org básico para SoftwareApplication.
- Criar public/robots.txt.
- Criar public/sitemap.xml com URLs públicas.
- Não incluir páginas autenticadas no sitemap.
- robots.txt deve permitir indexação apenas das páginas públicas e evitar rotas administrativas/autenticadas.

Arquitetura obrigatória:
- Models bem definidos.
- Migrations com índices e foreign keys.
- Form Requests para validação.
- Policies para autorização.
- Services para regras de negócio.
- Actions ou UseCases quando fizer sentido.
- Evitar lógica de negócio em Controllers.
- Enums para status de proposta, tipos de planos e roles.
- Seeders para:
  - usuário admin;
  - planos iniciais;
  - configurações iniciais.
- Factories para testes.
- Testes mínimos:
  - criação de proposta;
  - limite do plano gratuito;
  - aprovação via link público;
  - acesso admin protegido.

Entidades sugeridas:
- User
- Role ou enum de role
- Plan
- Subscription ou UserPlan
- Customer
- ServiceItem
- Proposal
- ProposalItem
- ProposalPublicToken
- AppSetting

Deploy:
- Projeto deve estar preparado para VPS KVM 1 da Hostinger com Nginx, PHP-FPM, MySQL, Node e build Vite.
- Criar ou modificar arquivo docs/DEPLOY.md com passos:
  - composer install;
  - npm install;
  - npm run build;
  - php artisan migrate --seed;
  - php artisan storage:link;
  - permissões de storage/bootstrap/cache;
  - configuração de .env;
  - cache de config/routes/views.
- Não usar dependências pesadas desnecessárias.

Manual obrigatório:
- Criar ou modificar docs/MANUAL.md explicando:
  - visão geral;
  - como cadastrar clientes;
  - como criar propostas;
  - como enviar link público;
  - como funcionam os planos;
  - como o admin gerencia usuários e planos.
- Criar README.md com descrição técnica, instalação local e comandos principais.

Roadmap
- Criar o docs/ROADMAP.md
  - Para controlar o que já foi feito e o que vem depois.
  - Em formato markdown
  - Definir Fases do projeto
  - Atualizar itens que já foram concluídos

Importante:
- Não implementar gateway de pagamento agora.
- Plano pode ser alterado manualmente pelo admin.
- Preparar estrutura para futura integração com Pix/checkout, mas não implementar.
- O sistema deve funcionar como MVP real, simples, limpo e pronto para evolução.

## Objetivo

Criar um SaaS em Laravel 13, Vue.js, TypeScript e MySQL para propostas comerciais.

## Prompt 

Regras globais do projeto:

1. Stack obrigatória:
   - Laravel 13
   - Vue.js 3
   - TypeScript
   - MySQL
   - Tailwind CSS
   - Vite

2. Arquitetura:
   - Seguir SOLID, Clean Code e separação de responsabilidades.
   - Controllers devem ser finos.
   - Usar Form Requests para validação.
   - Usar Policies para autorização.
   - Usar Services/Actions para regras de negócio.
   - Usar Enums para status, roles e tipos fixos.
   - Usar migrations com foreign keys, índices e constraints.
   - Usar seeders para dados iniciais.
   - Usar factories e testes mínimos.

3. SaaS:
   - Todo projeto deve ter planos.
   - Deve existir no mínimo Plano Gratuito.
   - Planos devem ser configuráveis pelo painel admin.
   - Admin deve poder alterar plano do usuário manualmente.
   - Não implementar pagamento online no MVP.
   - Preparar estrutura para futura integração com cobrança.

4. Admin:
   - Criar role admin.
   - Criar painel admin protegido.
   - Admin deve gerenciar usuários, planos e configurações globais.
   - Admin deve visualizar relatórios básicos.

5. SEO:
   - Criar páginas públicas:
     - Home
     - Recursos
     - Preços
     - Termos de Uso
     - Política de Privacidade
   - Implementar title, meta description, canonical, Open Graph e Twitter Card.
   - Criar Schema.org SoftwareApplication quando aplicável.
   - Criar public/robots.txt.
   - Criar public/sitemap.xml.
   - Não indexar rotas autenticadas, admin, dashboard ou dados privados.

6. Front-end:
   - Usar Vue 3 com TypeScript.
   - Componentizar telas e elementos reutilizáveis.
   - Usar Tailwind CSS.
   - Cores principais devem ser configuráveis via variáveis CSS.
   - Evitar cores hardcoded espalhadas no projeto.
   - Criar layout público, layout autenticado e layout admin.

7. Deploy:
   - Preparar para VPS KVM 1 da Hostinger com Nginx, PHP-FPM, MySQL e Node.
   - Criar docs/DEPLOY.md.
   - Documentar:
     - composer install
     - npm install
     - npm run build
     - php artisan migrate --seed
     - php artisan storage:link
     - permissões
     - configuração .env
     - cache de config, routes e views

8. Documentação:
   - Criar README.md técnico.
   - Criar docs/MANUAL.md para uso do sistema.
   - Criar docs/ROADMAP.md com melhorias futuras.
   - Criar docs/DEPLOY.md.

9. Segurança:
   - Isolar dados por usuário.
   - Nunca permitir que um usuário acesse dados de outro.
   - Validar permissões via Policies.
   - Proteger rotas admin.
   - Não expor dados privados no sitemap.
   - Não indexar páginas privadas.

10. Qualidade:
   - Criar testes mínimos para regras principais.
   - Evitar overengineering.
   - Priorizar MVP funcional, limpo, simples e evolutivo.