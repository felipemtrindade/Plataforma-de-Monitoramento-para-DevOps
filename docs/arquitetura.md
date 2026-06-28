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

