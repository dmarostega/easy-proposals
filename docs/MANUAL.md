# Manual do Proposta Fácil

## Visão geral

O Proposta Fácil ajuda profissionais autônomos e pequenos negócios a criar propostas comerciais, controlar status e enviar um link público para o cliente aprovar ou recusar.

## Como cadastrar clientes

1. Acesse a área autenticada.
2. Abra `Clientes`.
3. Informe nome, e-mail, telefone, documento, endereço e observações.
4. Salve. Clientes ficam isolados por usuário.

## Como criar propostas

1. Abra `Propostas` e selecione um cliente.
2. Defina título, descrição, validade, observações e condições comerciais.
3. Adicione itens com descrição, quantidade e valor unitário.
4. Opcionalmente informe desconto.
5. O sistema calcula subtotal e total e valida o limite do plano.

## Como enviar link público

Ao criar a proposta, o sistema gera um token público seguro. Envie a URL `/p/{token}` ao cliente. O cliente não precisa de login para visualizar, aprovar ou recusar.

## Como funcionam os planos

- **Gratuito**: até 3 propostas por mês, sem logo personalizada.
- **Pro**: até 50 propostas por mês e PDF.
- **Plus**: propostas ilimitadas, PDF e logo personalizada.

Pagamentos não estão implementados no MVP. O plano é alterado manualmente pelo admin.

## Como o admin gerencia usuários e planos

A área `/admin` exige role `admin`. O admin pode:

- Criar, editar e desativar planos.
- Listar usuários, ativar/desativar e alterar plano manualmente.
- Atualizar configurações globais do aplicativo.
- Consultar relatório básico de usuários, usuários por plano, propostas criadas e aprovadas.
