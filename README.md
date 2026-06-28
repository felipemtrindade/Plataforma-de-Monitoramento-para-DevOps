# Plataforma de Monitoramento para DevOps

Projeto web acadêmico para a disciplina de Redes de Computadores. A aplicação simula uma plataforma simples de monitoramento de serviços de rede, com dashboard, métricas, alertas por e-mail e eventos básicos de segurança.

## Tecnologias

- Backend: Laravel
- Banco de dados: MySQL
- Frontend: React com Vite
- Gráficos: Chart.js
- E-mail: SMTP simples, recomendado Mailtrap para testes

## Arquitetura

A aplicação usa arquitetura monolítica. O Laravel serve a API REST, executa o comando de coleta `monitor:check`, acessa o MySQL via Eloquent e entrega a SPA React via Vite. O React consome os endpoints `/api/*` para renderizar dashboard, serviços, alertas, segurança e runbooks.

## Instalação

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
MAIL_USERNAME=seu_usuário
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

Os seeders criam serviços iniciais, métricas, alertas e eventos de segurança:

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

Build de produção:

```bash
npm run build
```

## Acesso administrativo

O painel de monitoramento e as APIs principais são protegidos por login de administrador. O seeder cria um usuário inicial:

```txt
E-mail: admin@monitor.local
Senha: admin123
```

Depois do login, o frontend guarda um token local e envia esse token nas chamadas para `/api/dashboard`, `/api/services`, `/api/alerts`, `/api/security-events`, `/api/notifications` e `/api/monitor/check`.

## API principal

Rotas públicas:

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

O comando busca todos os serviços, testa disponibilidade, mede latência aproximada, salva métricas, atualiza status e gera alertas amarelos/vermelhos.

Também é possível executar a coleta pelo botão **Executar coleta agora** no dashboard administrativo.

## Métricas coletadas e simuladas

A plataforma faz verificações reais de disponibilidade:

- WEB: requisição HTTP/HTTPS com status code.
- DATABASE: conexão com o banco configurado ou teste TCP para hosts externos.
- DNS: resolução de domínio.
- SMTP: conexão TCP na porta configurada.

Algumas métricas de infraestrutura, como CPU, memória, I/O wait, tamanho do banco, queries lentas, taxa de entrega SMTP e volume de e-mails, são simuladas para fins didáticos. Isso evita instalar agentes externos e mantém o projeto simples, mas ainda permite apresentar o comportamento esperado de uma plataforma real de monitoramento.

## Testando alertas e segurança

- Execute `php artisan monitor:check` para coletar métricas reais.
- Use a tela Segurança para simular falhas de login, anomalia de tráfego e alteração de configuração.
- O painel consulta notificações automaticamente a cada 10 segundos e exibe popups quando surgem novos alertas ou eventos críticos.
- A API também aceita:
  - `POST /api/simulate-login-failure`
  - `POST /api/simulate-traffic-anomaly`
  - `POST /api/simulate-config-change`

## Telas

- Dashboard geral: cards de serviços, alertas e segurança, além de gráficos de latência, disponibilidade, alertas e eventos.
- Serviços monitorados: cadastro, listagem, exclusão e acesso aos detalhes.
- Detalhes do serviço: informações, status, últimas métricas, gráfico de latência e alertas.
- Alertas: histórico de alertas e indicação de envio por e-mail.
- Segurança: eventos simulados e vulnerabilidades conhecidas.
- Documentação/Runbooks: procedimentos simples para incidentes comuns.

## Documentação adicional

- [Arquitetura](docs/arquitetura.md)
- [Runbooks](docs/runbooks.md)
- [Playbooks de Incidentes](docs/playbooks-incidentes.md)
- [Guia de Instalação](docs/guia-instalacao.md)

