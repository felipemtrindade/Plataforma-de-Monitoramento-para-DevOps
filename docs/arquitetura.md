# Arquitetura

## Visao geral

A Plataforma de Monitoramento para DevOps e um monolito Laravel com frontend React embutido pelo Vite. Essa escolha deixa o projeto simples de explicar e executar em ambiente academico.

## Camadas

- React: interface navegavel com dashboard, servicos, alertas, seguranca e runbooks.
- API Laravel: rotas REST em `routes/api.php`.
- Dominio: models `Service`, `Metric`, `Alert` e `SecurityEvent`.
- Coleta: comando `php artisan monitor:check` usando `NetworkMonitor`.
- Alertas: `AlertService` aplica regras GREEN, YELLOW e RED e tenta enviar e-mail SMTP.
- Banco: MySQL com migrations e seeders.

## Fluxo de monitoramento

1. O usuario cadastra servicos pela tela ou API.
2. O comando `monitor:check` percorre todos os servicos.
3. Cada servico e testado conforme seu tipo: HTTP, conexao de banco, DNS ou TCP SMTP.
4. Uma metrica e salva com campos comuns e campos especificos do protocolo.
5. O status atual do servico e atualizado.
6. Se a regra indicar YELLOW ou RED, um alerta e salvo.
7. Se nao houver alerta semelhante nos ultimos 5 minutos, a aplicacao tenta enviar e-mail.

## Metricas por tipo

- WEB: disponibilidade, HTTP status code, RPS, latencia, taxa/contagem de erro e conexoes ativas.
- DATABASE: disponibilidade, QPS, taxa/contagem de erro, conexoes, CPU/memoria/I/O simulados, tamanho do banco e queries lentas.
- DNS: disponibilidade, tempo de resolucao, QPS e falhas de resolucao.
- SMTP: disponibilidade, latencia, fila SMTP, taxa de entrega e volume de e-mails.

As verificacoes de disponibilidade sao reais. As metricas que normalmente exigiriam agente no servidor sao simuladas para manter a arquitetura monolitica e academica.

## Fluxo do frontend

O React consome `/api/dashboard`, `/api/services`, `/api/alerts` e `/api/security-events`. Os graficos usam Chart.js a partir dos dados agregados pela API.
