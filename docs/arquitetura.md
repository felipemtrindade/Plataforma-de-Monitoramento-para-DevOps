# Arquitetura

## Visão geral

A Plataforma de Monitoramento para DevOps é um monolito Laravel com frontend React embutido pelo Vite. Essa escolha deixa o projeto simples de explicar e executar em ambiente acadêmico.

## Camadas

- React: interface navegável com dashboard, serviços, alertas, segurança e runbooks.
- API Laravel: rotas REST em `routes/api.php`.
- Domínio: models `Service`, `Metric`, `Alert` e `SecurityEvent`.
- Coleta: comando `php artisan monitor:check` usando `NetworkMonitor`.
- Alertas: `AlertService` aplica regras GREEN, YELLOW e RED e tenta enviar e-mail SMTP.
- Banco: MySQL com migrations e seeders.

## Fluxo de monitoramento

1. O usuário cadastra serviços pela tela ou API.
2. O comando `monitor:check` percorre todos os serviços.
3. Cada serviço é testado conforme seu tipo: HTTP, conexão de banco, DNS ou TCP SMTP.
4. Uma métrica é salva com campos comuns e campos específicos do protocolo.
5. O status atual do serviço é atualizado.
6. Se a regra indicar YELLOW ou RED, um alerta é salvo.
7. Se não houver alerta semelhante nos últimos 5 minutos, a aplicação tenta enviar e-mail.

## Métricas por tipo

- WEB: disponibilidade, HTTP status code, RPS, latência, taxa/contagem de erro e conexões ativas.
- DATABASE: disponibilidade, QPS, taxa/contagem de erro, conexões, CPU/memória/I/O simulados, tamanho do banco e queries lentas.
- DNS: disponibilidade, tempo de resolução, QPS e falhas de resolução.
- SMTP: disponibilidade, latência, fila SMTP, taxa de entrega e volume de e-mails.

As verificações de disponibilidade são reais. As métricas que normalmente exigiriam agente no servidor são simuladas para manter a arquitetura monolítica e acadêmica.

## Fluxo do frontend

O React consome `/api/dashboard`, `/api/services`, `/api/alerts` e `/api/security-events`. Os gráficos usam Chart.js a partir dos dados agregados pela API.

## Atualização em tempo quase real

Alertas e eventos de segurança usam atualização leve a cada 30 segundos, além de atualização ao voltar o foco da aba e após ações críticas. O projeto também possui suporte opcional a SSE (Server-Sent Events) no endpoint `/api/notifications/stream`, mas ele fica desabilitado por padrão no ambiente local porque o servidor de desenvolvimento do PHP pode bloquear outras requisições enquanto mantém uma conexão longa aberta.

Em um ambiente com servidor web concorrente, como Nginx/Apache ou Laravel Octane, o SSE pode ser habilitado com `VITE_ENABLE_SSE=true`.

A coleta de métricas continua centralizada no backend pelo comando `php artisan monitor:check`, que pode ser executado manualmente, pelo botão do dashboard ou por agendamento do Laravel Scheduler.

## Histórico e retenção

As tabelas `metrics`, `alerts`, `security_events` e `login_failures` preservam o histórico operacional e de segurança. Serviços removidos pela interface usam soft delete, mantendo os relacionamentos históricos disponíveis para auditoria.

O projeto não apaga dados automaticamente. Uma melhoria futura natural seria criar uma rotina de retenção para compactar métricas antigas e evitar crescimento indefinido do banco.

