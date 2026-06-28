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

Para operação contínua em ambiente local, rode o scheduler em um terminal separado:

```bash
php artisan schedule:work
```

O projeto agenda `monitor:check` a cada minuto em `routes/console.php`.

## Métricas coletadas e simuladas

A plataforma faz verificações reais de disponibilidade:

- WEB: requisição HTTP/HTTPS com status code.
- DATABASE: conexão com o banco configurado ou teste TCP para hosts externos.
- DNS: resolução de domínio.
- SMTP: conexão TCP na porta configurada.

Algumas métricas de infraestrutura, como CPU, memória, I/O wait, tamanho do banco, queries lentas, taxa de entrega SMTP e volume de e-mails, são simuladas para fins didáticos. Isso evita instalar agentes externos e mantém o projeto simples, mas ainda permite apresentar o comportamento esperado de uma plataforma real de monitoramento.

## Histórico e auditoria

O sistema mantém histórico no banco para:

- métricas coletadas em `metrics`;
- alertas gerados em `alerts`;
- eventos de segurança em `security_events`;
- falhas de login simuladas em `login_failures`.

Ao remover um serviço pela interface, ele é arquivado com soft delete. Isso evita perder métricas e alertas antigos, comportamento mais próximo de sistemas reais de monitoramento.

Não há limpeza automática de retenção configurada. Em produção, seria comum definir uma política, por exemplo manter métricas detalhadas por 30 ou 90 dias e consolidar dados antigos.

Alertas iguais para o mesmo serviço respeitam uma janela de cooldown configurável por `ALERT_COOLDOWN_MINUTES`, evitando spam visual e excesso de e-mails durante incidentes contínuos.

## Testando alertas e segurança

- Execute `php artisan monitor:check` para coletar métricas reais.
- Use a tela Segurança para simular falhas de login, anomalia de tráfego e alteração de configuração.
- O painel usa atualização leve a cada 30 segundos para notificações e também atualiza ao voltar o foco da aba ou após ações críticas.
- Existe suporte opcional a SSE (Server-Sent Events) em `/api/notifications/stream`. Para habilitar, configure `VITE_ENABLE_SSE=true`.
- No ambiente local com `php artisan serve`, o SSE fica desabilitado por padrão porque conexões longas podem segurar o servidor de desenvolvimento e atrasar outras chamadas após F5.
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

