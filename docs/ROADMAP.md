# Roadmap do Proposta Fácil

## Fase 1 — MVP base ✅

- [x] Estrutura Laravel 13 com Vue 3, TypeScript, Tailwind e Vite.
- [x] Entidades principais: usuários, planos, clientes, serviços, propostas, itens, tokens públicos e configurações.
- [x] Seeders para admin, planos iniciais e configurações.
- [x] Services para criação de propostas, limites de plano e aprovação pública.
- [x] Policies e middleware admin.
- [x] Páginas públicas com SEO básico, robots.txt e sitemap.xml.
- [x] Testes mínimos de proposta, limite gratuito, aprovação pública e admin protegido.

## Fase 2 — Experiência do usuário

- [x] Telas Inertia completas para CRUDs autenticados.
- [x] Upload de logo com validação por plano.
- [x] Editor visual de proposta e pré-visualização.
- [x] E-mails transacionais para envio e aprovação.

## Fase 3 — Documentos e automações

- [x] Gerador PDF real com template personalizável.
- [x] Histórico de eventos da proposta.
- [x] Notificações quando cliente visualizar, aprovar ou recusar.

## Fase 4 — Cobrança futura

- [ ] Integração Pix/checkout.
- [ ] Portal de assinatura.
- [ ] Webhooks de pagamento.
- [ ] Métricas SaaS avançadas.

## Outras Melhorias
- [ ] Adicionar Aprovada / Recusada em um badge, ou melhorar layout de itens da lista de proposta cadastradas
- [ ] Aparentemente "Serviços" não está sendo usado, verificar
- [ ] Filtros no Dashboard
- [ ] Filtros e paginação em listagens (Propostas, Clientes)
- [ ] Input de pesquisa em selects (Clientes em Proposta)
- [ ] Validar se usuário Admin do sistema deve ter menus de propostas, clientes, serviços e perfil da marca
- [ ] Gerar conteudos, pagina Inicial, termos e privacidade,
- [ ] melhorar recursos e preços (está sem valor)
- [ ] Verificar / Adicionar envio de email reais, verificar se permite personalizar email tanto no admin quanto para usuário
