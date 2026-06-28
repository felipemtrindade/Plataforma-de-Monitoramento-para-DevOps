# Plataforma de Monitoramento para DevOps

Projeto web academico para a disciplina de Redes de Computadores. A aplicacao simula uma plataforma simples de monitoramento de servicos de rede, com dashboard, metricas, alertas por e-mail e eventos basicos de seguranca.

## Tecnologias

- Backend: Laravel
- Banco de dados: MySQL
- Frontend: React com Vite
- Graficos: Chart.js
- E-mail: SMTP simples, recomendado Mailtrap para testes

## Arquitetura

A aplicacao usa arquitetura monolitica. O Laravel serve a API REST, executa o comando de coleta `monitor:check`, acessa o MySQL via Eloquent e entrega a SPA React via Vite. O React consome os endpoints `/api/*` para renderizar dashboard, servicos, alertas, seguranca e runbooks.

## Instalacao

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Configure o MySQL no `.env`:

```env
APP_NAME="Plataforma de Monitoramento para DevOps"
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=devops_monitor
DB_USERNAME=root
DB_PASSWORD=
```

Configure SMTP, por exemplo com Mailtrap:

```env
MAIL_MAILER=smtp
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=seu_usuario
MAIL_PASSWORD=sua_senha
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=monitoramento@example.com
MAIL_FROM_NAME="Monitoramento DevOps"
MONITORING_ALERT_EMAIL=destino@example.com
```

## Banco de dados

Crie o banco `devops_monitor` no MySQL e execute:

```bash
php artisan migrate:fresh --seed
```

Os seeders criam servicos iniciais, metricas, alertas e eventos de seguranca:

- Web Server - Google
- DNS - Cloudflare
- SMTP - Gmail SMTP
- Database - Local MySQL

## Executando o projeto

Backend:

```bash
php artisan serve
```

Frontend em desenvolvimento:

```bash
npm run dev
```

Build de producao:

```bash
npm run build
```

## Acesso administrativo

O painel de monitoramento e as APIs principais sao protegidos por login de administrador. O seeder cria um usuario inicial:

```txt
E-mail: admin@monitor.local
Senha: admin123
```

Depois do login, o frontend guarda um token local e envia esse token nas chamadas para `/api/dashboard`, `/api/services`, `/api/alerts`, `/api/security-events`, `/api/notifications` e `/api/monitor/check`.

## API principal

Rotas publicas:

- `POST /api/login`

Rotas protegidas por token de administrador:

- `GET /api/me`
- `POST /api/logout`
- `GET /api/dashboard`
- `GET /api/notifications`
- `POST /api/monitor/check`
- `GET /api/services`
- `POST /api/services`
- `GET /api/services/{id}`
- `PUT /api/services/{id}`
- `DELETE /api/services/{id}`
- `GET /api/services/{id}/metrics`
- `GET /api/alerts`
- `GET /api/security-events`
- `POST /api/simulate-login-failure`
- `POST /api/simulate-traffic-anomaly`
- `POST /api/simulate-config-change`

## Coleta manual

```bash
php artisan monitor:check
```

O comando busca todos os servicos, testa disponibilidade, mede latencia aproximada, salva metricas, atualiza status e gera alertas amarelos/vermelhos.

Tambem e possivel executar a coleta pelo botao **Executar coleta agora** no dashboard administrativo.

## Metricas coletadas e simuladas

A plataforma faz verificacoes reais de disponibilidade:

- WEB: requisicao HTTP/HTTPS com status code.
- DATABASE: conexao com o banco configurado ou teste TCP para hosts externos.
- DNS: resolucao de dominio.
- SMTP: conexao TCP na porta configurada.

Algumas metricas de infraestrutura, como CPU, memoria, I/O wait, tamanho do banco, queries lentas, taxa de entrega SMTP e volume de e-mails, sao simuladas para fins didaticos. Isso evita instalar agentes externos e mantem o projeto simples, mas ainda permite apresentar o comportamento esperado de uma plataforma real de monitoramento.

## Testando alertas e seguranca

- Execute `php artisan monitor:check` para coletar metricas reais.
- Use a tela Seguranca para simular falhas de login, anomalia de trafego e alteracao de configuracao.
- O painel consulta notificacoes automaticamente a cada 10 segundos e exibe popups quando surgem novos alertas ou eventos criticos.
- A API tambem aceita:
  - `POST /api/simulate-login-failure`
  - `POST /api/simulate-traffic-anomaly`
  - `POST /api/simulate-config-change`

## Telas

- Dashboard geral: cards de servicos, alertas e seguranca, alem de graficos de latencia, disponibilidade, alertas e eventos.
- Servicos monitorados: cadastro, listagem, exclusao e acesso aos detalhes.
- Detalhes do servico: informacoes, status, ultimas metricas, grafico de latencia e alertas.
- Alertas: historico de alertas e indicacao de envio por e-mail.
- Seguranca: eventos simulados e vulnerabilidades conhecidas.
- Documentacao/Runbooks: procedimentos simples para incidentes comuns.

## Documentacao adicional

- [Arquitetura](docs/arquitetura.md)
- [Runbooks](docs/runbooks.md)
- [Playbooks de Incidentes](docs/playbooks-incidentes.md)
- [Guia de Instalacao](docs/guia-instalacao.md)
