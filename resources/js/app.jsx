import React, { useEffect, useMemo, useRef, useState } from 'react';
import { createRoot } from 'react-dom/client';
import {
  ArcElement,
  BarElement,
  CategoryScale,
  Chart as ChartJS,
  Legend,
  LinearScale,
  LineElement,
  PointElement,
  Tooltip,
} from 'chart.js';
import { Bar, Doughnut, Line } from 'react-chartjs-2';
import '../css/app.css';

ChartJS.register(CategoryScale, LinearScale, BarElement, ArcElement, PointElement, LineElement, Tooltip, Legend);

const storedToken = () => localStorage.getItem('monitor_token');
const sseEnabled = import.meta.env.VITE_ENABLE_SSE === 'true';

const api = async (path, options = {}) => {
  const token = storedToken();
  const response = await fetch(`/api${path}`, {
    headers: {
      'Content-Type': 'application/json',
      Accept: 'application/json',
      ...(token ? { Authorization: `Bearer ${token}` } : {}),
      ...(options.headers ?? {}),
    },
    ...options,
  });

  if (!response.ok) {
    throw new Error(`Erro ${response.status} ao acessar ${path}`);
  }

  return response.json();
};

const navItems = [
  ['dashboard', 'Dashboard'],
  ['services', 'Serviços'],
  ['alerts', 'Alertas'],
  ['security', 'Segurança'],
  ['docs', 'Runbooks'],
];

const parseHashRoute = () => {
  const hash = window.location.hash.replace(/^#\/?/, '');
  const [route = 'dashboard', id] = hash.split('/');

  if (route === 'services' && id) {
    return { page: 'service-detail', selectedServiceId: Number(id) };
  }

  if (navItems.some(([item]) => item === route)) {
    return { page: route, selectedServiceId: null };
  }

  return { page: 'dashboard', selectedServiceId: null };
};

const badgeClass = (value) => {
  if (['UP', 'GREEN', 'LOW'].includes(value)) return 'badge success';
  if (['YELLOW', 'MEDIUM'].includes(value)) return 'badge warning';
  return 'badge danger';
};

function App() {
  const initialRoute = useMemo(parseHashRoute, []);
  const [page, setPage] = useState(initialRoute.page);
  const [selectedServiceId, setSelectedServiceId] = useState(initialRoute.selectedServiceId);
  const [user, setUser] = useState(null);
  const [booting, setBooting] = useState(true);
  const [notifications, setNotifications] = useState([]);
  const [notificationOpen, setNotificationOpen] = useState(false);
  const [toasts, setToasts] = useState([]);
  const [unreadCount, setUnreadCount] = useState(0);
  const [liveMode, setLiveMode] = useState('conectando');
  const knownNotificationIds = useRef(new Set());
  const firstNotificationLoad = useRef(true);

  const pushToast = (notification) => {
    const toast = { ...notification, toastId: `${notification.id}-${Date.now()}` };
    setToasts((current) => [toast, ...current].slice(0, 4));
    window.setTimeout(() => {
      setToasts((current) => current.filter((item) => item.toastId !== toast.toastId));
    }, 7000);
  };

  const logout = async () => {
    try {
      await api('/logout', { method: 'POST', body: JSON.stringify({}) });
    } catch (_) {
      // Local logout still matters if the token is already invalid.
    }
    localStorage.removeItem('monitor_token');
    setUser(null);
    setNotifications([]);
    setToasts([]);
    setUnreadCount(0);
    knownNotificationIds.current = new Set();
    firstNotificationLoad.current = true;
    setPage('dashboard');
    window.location.hash = '/dashboard';
  };

  const applyNotifications = (nextNotifications) => {
      const incoming = nextNotifications.filter((item) => !knownNotificationIds.current.has(item.id));

      if (firstNotificationLoad.current) {
        firstNotificationLoad.current = false;
      } else if (incoming.length > 0) {
        incoming.slice(0, 3).forEach(pushToast);
        setUnreadCount((count) => count + incoming.length);
      }

      knownNotificationIds.current = new Set(nextNotifications.map((item) => item.id));
      setNotifications(nextNotifications);
  };

  const loadNotifications = async () => {
    if (!storedToken()) return;
    try {
      applyNotifications(await api('/notifications'));
    } catch (error) {
      if (String(error.message).includes('401') || String(error.message).includes('403')) {
        await logout();
      }
    }
  };

  useEffect(() => {
    const token = storedToken();
    if (!token) {
      setBooting(false);
      return;
    }

    api('/me')
      .then((data) => {
        setUser(data.user);
        return loadNotifications();
      })
      .catch(() => localStorage.removeItem('monitor_token'))
      .finally(() => setBooting(false));
  }, []);

  useEffect(() => {
    if (!user) return undefined;
    const token = storedToken();

    if (!sseEnabled || !window.EventSource || !token) {
      setLiveMode('polling');
      loadNotifications();
      const fallbackInterval = setInterval(loadNotifications, 30000);
      const refreshOnFocus = () => {
        if (document.visibilityState === 'visible') {
          loadNotifications();
        }
      };

      document.addEventListener('visibilitychange', refreshOnFocus);

      return () => {
        clearInterval(fallbackInterval);
        document.removeEventListener('visibilitychange', refreshOnFocus);
      };
    }

    setLiveMode('conectando');
    const stream = new EventSource(`/api/notifications/stream?token=${encodeURIComponent(token)}`);
    let fallbackInterval = null;

    stream.addEventListener('open', () => setLiveMode('tempo real'));
    stream.addEventListener('notifications', (event) => {
      setLiveMode('tempo real');
      applyNotifications(JSON.parse(event.data));
    });
    stream.addEventListener('error', () => {
      stream.close();
      setLiveMode('polling');
      loadNotifications();
      fallbackInterval = setInterval(loadNotifications, 30000);
    });

    return () => {
      stream.close();
      if (fallbackInterval) {
        clearInterval(fallbackInterval);
      }
    };
  }, [user]);

  const openService = (id) => {
    setSelectedServiceId(id);
    setPage('service-detail');
    window.location.hash = `/services/${id}`;
  };

  const navigate = (nextPage) => {
    setSelectedServiceId(null);
    setPage(nextPage);
    window.location.hash = `/${nextPage}`;
  };

  useEffect(() => {
    const syncRoute = () => {
      const route = parseHashRoute();
      setPage(route.page);
      setSelectedServiceId(route.selectedServiceId);
    };

    window.addEventListener('hashchange', syncRoute);
    return () => window.removeEventListener('hashchange', syncRoute);
  }, []);

  if (booting) {
    return <State title="Carregando" message="Validando sessão administrativa..." />;
  }

  if (!user) {
    return <Login onLogin={(data) => { localStorage.setItem('monitor_token', data.token); setUser(data.user); }} />;
  }

  return (
    <div className="app-shell">
      <aside className="sidebar">
        <div>
          <p className="eyebrow">Redes de Computadores</p>
          <h1>Plataforma de Monitoramento para DevOps</h1>
        </div>
        <nav>
          {navItems.map(([id, label]) => (
            <button key={id} className={page === id || (page === 'service-detail' && id === 'services') ? 'active' : ''} onClick={() => navigate(id)}>
              {label}
            </button>
          ))}
        </nav>
        <button
          className={`sidebar-alert ${unreadCount > 0 ? 'has-unread' : ''}`}
          onClick={() => {
            setNotificationOpen(true);
            setUnreadCount(0);
          }}
        >
          <span>Avisos em tempo real</span>
          <strong>{unreadCount > 0 ? `${unreadCount} novo(s)` : `${notifications.length} recentes`}</strong>
        </button>
        <div className="admin-card">
          <span>Admin conectado</span>
          <strong>{user.name}</strong>
          <button className="ghost logout" onClick={logout}>Sair</button>
        </div>
      </aside>

      <main className="content">
        <NotificationBar
          notifications={notifications}
          unreadCount={unreadCount}
          liveMode={liveMode}
          isOpen={notificationOpen}
          onToggle={() => {
            setNotificationOpen((open) => !open);
            setUnreadCount(0);
          }}
          onRefresh={async () => {
            await loadNotifications();
            setUnreadCount(0);
          }}
        />
        {page === 'dashboard' && <Dashboard onMonitorFinished={loadNotifications} />}
        {page === 'services' && <Services onOpen={openService} />}
        {page === 'service-detail' && <ServiceDetail id={selectedServiceId} onBack={() => navigate('services')} />}
        {page === 'alerts' && <Alerts />}
        {page === 'security' && <Security onChanged={loadNotifications} />}
        {page === 'docs' && <Docs />}
      </main>
      <ToastStack toasts={toasts} onDismiss={(id) => setToasts((current) => current.filter((item) => item.toastId !== id))} />
    </div>
  );
}

function Login({ onLogin }) {
  const [form, setForm] = useState({ email: 'admin@monitor.local', password: 'admin123' });
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);

  const submit = async (event) => {
    event.preventDefault();
    setLoading(true);
    setError('');
    try {
      onLogin(await api('/login', { method: 'POST', body: JSON.stringify(form) }));
    } catch (err) {
      setError('Não foi possível entrar. Confira e-mail e senha do administrador.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <main className="login-page">
      <form className="login-card" onSubmit={submit}>
        <p className="eyebrow">Acesso administrativo</p>
        <h1>Plataforma de Monitoramento para DevOps</h1>
        <p>Entre como administrador para visualizar métricas, alertas e controles de segurança.</p>
        {error && <div className="inline-error">{error}</div>}
        <label>
          E-mail
          <input type="email" value={form.email} onChange={(e) => setForm({ ...form, email: e.target.value })} required />
        </label>
        <label>
          Senha
          <input type="password" value={form.password} onChange={(e) => setForm({ ...form, password: e.target.value })} required />
        </label>
        <button type="submit" disabled={loading}>{loading ? 'Entrando...' : 'Entrar no painel'}</button>
        <small>Usuário inicial: admin@monitor.local / admin123</small>
      </form>
    </main>
  );
}

function Dashboard({ onMonitorFinished }) {
  const [data, setData] = useState(null);
  const [error, setError] = useState('');
  const [monitorOutput, setMonitorOutput] = useState('');
  const [running, setRunning] = useState(false);

  const loadDashboard = () => api('/dashboard').then(setData).catch((err) => setError(err.message));

  useEffect(() => { loadDashboard(); }, []);

  const runMonitor = async () => {
    setRunning(true);
    setMonitorOutput('');
    try {
      const result = await api('/monitor/check', { method: 'POST', body: JSON.stringify({}) });
      setMonitorOutput(result.output || result.message);
      await loadDashboard();
      await onMonitorFinished();
    } catch (err) {
      setMonitorOutput(err.message);
    } finally {
      setRunning(false);
    }
  };

  if (error) return <State title="Falha ao carregar dashboard" message={error} />;
  if (!data) return <State title="Carregando dashboard" message="Buscando métricas e alertas..." />;

  const summary = data.summary;
  const latency = data.latency_by_service ?? [];
  const alertsByLevel = data.alerts_by_level ?? {};
  const eventsByType = data.security_events_by_type ?? {};
  const traffic = data.traffic_by_service ?? [];
  const errors = data.error_rate_by_service ?? [];

  return (
    <section>
      <PageHeader title="Dashboard geral" subtitle="Visão consolidada dos serviços, métricas, alertas e segurança." />
      <div className="toolbar">
        <button onClick={runMonitor} disabled={running}>{running ? 'Coletando...' : 'Executar coleta agora'}</button>
        {monitorOutput && <pre>{monitorOutput}</pre>}
      </div>
      <div className="stats-grid">
        <Stat label="Total de serviços" value={summary.total_services} />
        <Stat label="Serviços UP" value={summary.services_up} tone="success" />
        <Stat label="Serviços DOWN" value={summary.services_down} tone="danger" />
        <Stat label="Alertas amarelos" value={summary.yellow_alerts} tone="warning" />
        <Stat label="Alertas vermelhos" value={summary.red_alerts} tone="danger" />
        <Stat label="Eventos de segurança" value={summary.security_events} />
        <Stat label="Vulnerabilidades" value={summary.known_vulnerabilities} tone="danger" />
        <Stat label="Taxa média de erro" value={`${summary.average_error_rate}%`} tone="warning" />
      </div>

      <div className="charts-grid">
        <ChartPanel title="Latência por serviço">
          <Bar
            data={{
              labels: latency.map((item) => item.service),
              datasets: [{ label: 'ms', data: latency.map((item) => item.latency_ms || null), backgroundColor: '#2563eb' }],
            }}
          />
        </ChartPanel>
        <ChartPanel title="Disponibilidade">
          <Doughnut
            data={{
              labels: ['UP', 'DOWN'],
              datasets: [{ data: [data.availability.UP ?? 0, data.availability.DOWN ?? 0], backgroundColor: ['#16a34a', '#dc2626'] }],
            }}
          />
        </ChartPanel>
        <ChartPanel title="Alertas por nivel">
          <Bar
            data={{
              labels: ['GREEN', 'YELLOW', 'RED'],
              datasets: [{ label: 'Alertas', data: ['GREEN', 'YELLOW', 'RED'].map((key) => alertsByLevel[key] ?? 0), backgroundColor: ['#16a34a', '#f59e0b', '#dc2626'] }],
            }}
          />
        </ChartPanel>
        <ChartPanel title="Eventos por tipo">
          <Bar
            data={{
              labels: Object.keys(eventsByType),
              datasets: [{ label: 'Eventos', data: Object.values(eventsByType), backgroundColor: '#0891b2' }],
            }}
          />
        </ChartPanel>
        <ChartPanel title="Tráfego e consultas">
          <Bar
            data={{
              labels: traffic.map((item) => item.service),
              datasets: [
                { label: 'RPS', data: traffic.map((item) => item.requests_per_second), backgroundColor: '#2563eb' },
                { label: 'QPS', data: traffic.map((item) => item.qps), backgroundColor: '#0891b2' },
                { label: 'E-mails', data: traffic.map((item) => item.email_volume), backgroundColor: '#7c3aed' },
              ],
            }}
          />
        </ChartPanel>
        <ChartPanel title="Taxa e contagem de erros">
          <Bar
            data={{
              labels: errors.map((item) => item.service),
              datasets: [
                { label: 'Taxa de erro (%)', data: errors.map((item) => item.error_rate), backgroundColor: '#f59e0b' },
                { label: 'Erros', data: errors.map((item) => item.error_count), backgroundColor: '#dc2626' },
              ],
            }}
          />
        </ChartPanel>
      </div>
    </section>
  );
}

function Services({ onOpen }) {
  const emptyForm = { name: '', type: 'WEB', host: '', port: '', description: '', current_status: 'DOWN' };
  const [services, setServices] = useState([]);
  const [form, setForm] = useState(emptyForm);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  const load = () => api('/services').then(setServices).finally(() => setLoading(false));

  useEffect(() => {
    load().catch((err) => setError(err.message));
  }, []);

  const submit = async (event) => {
    event.preventDefault();
    await api('/services', { method: 'POST', body: JSON.stringify({ ...form, port: form.port ? Number(form.port) : null }) });
    setForm(emptyForm);
    await load();
  };

  const remove = async (id) => {
    await api(`/services/${id}`, { method: 'DELETE' });
    await load();
  };

  return (
    <section>
      <PageHeader title="Serviços monitorados" subtitle="Cadastro e listagem dos alvos de monitoramento." />
      {error && <State title="Erro" message={error} />}
      <form className="service-form" onSubmit={submit}>
        <input placeholder="Nome" value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} required />
        <select value={form.type} onChange={(e) => setForm({ ...form, type: e.target.value })}>
          <option>WEB</option>
          <option>DATABASE</option>
          <option>DNS</option>
          <option>SMTP</option>
        </select>
        <input placeholder="Host" value={form.host} onChange={(e) => setForm({ ...form, host: e.target.value })} required />
        <input placeholder="Porta" type="number" value={form.port} onChange={(e) => setForm({ ...form, port: e.target.value })} />
        <input className="wide" placeholder="Descrição" value={form.description} onChange={(e) => setForm({ ...form, description: e.target.value })} />
        <button type="submit">Adicionar serviço</button>
      </form>

      {loading ? <State title="Carregando" message="Buscando serviços..." /> : (
        <div className="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Nome</th>
                <th>Tipo</th>
                <th>Host</th>
                <th>Status</th>
                <th>Métricas</th>
                <th>Alertas</th>
                <th>Ações</th>
              </tr>
            </thead>
            <tbody>
              {services.map((service) => (
                <tr key={service.id}>
                  <td>{service.name}</td>
                  <td>{service.type}</td>
                  <td>{service.host}:{service.port}</td>
                  <td><span className={badgeClass(service.current_status)}>{service.current_status}</span></td>
                  <td>{service.metrics_count}</td>
                  <td>{service.alerts_count}</td>
                  <td className="actions">
                    <button onClick={() => onOpen(service.id)}>Detalhes</button>
                    <button className="ghost danger-text" onClick={() => remove(service.id)}>Arquivar</button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </section>
  );
}

function ServiceDetail({ id, onBack }) {
  const [service, setService] = useState(null);
  const [metrics, setMetrics] = useState([]);

  useEffect(() => {
    if (!id) return;
    api(`/services/${id}`).then(setService);
    api(`/services/${id}/metrics`).then(setMetrics);
  }, [id]);

  if (!id) return <State title="Nenhum serviço selecionado" message="Volte para a listagem e escolha um serviço." />;
  if (!service) return <State title="Carregando serviço" message="Buscando detalhes..." />;

  const orderedMetrics = [...metrics].reverse();
  const latestMetric = metrics[0];

  return (
    <section>
      <button className="ghost back" onClick={onBack}>Voltar</button>
      <PageHeader title={service.name} subtitle={`${service.type} em ${service.host}:${service.port}`} />
      <div className="stats-grid compact">
        <Stat label="Status atual" value={service.current_status} tone={service.current_status === 'UP' ? 'success' : 'danger'} />
        <Stat label="Métricas coletadas" value={service.metrics.length} />
        <Stat label="Alertas históricos" value={service.alerts.length} tone="warning" />
      </div>
      <ChartPanel title="Histórico de latência">
        <Line
          data={{
            labels: orderedMetrics.map((metric) => new Date(metric.created_at).toLocaleTimeString()),
            datasets: [{ label: 'Latência (ms)', data: orderedMetrics.map((metric) => metric.latency_ms), borderColor: '#2563eb', backgroundColor: '#bfdbfe', spanGaps: false }],
          }}
        />
      </ChartPanel>
      {latestMetric && (
        <Panel title="Métricas técnicas do protocolo">
          <ProtocolMetrics service={service} metric={latestMetric} />
        </Panel>
      )}
      <div className="two-columns">
        <Panel title="Últimas métricas">
          <MiniList items={metrics.slice(0, 8).map((metric) => `${metric.status} - ${metric.latency_ms ? `${metric.latency_ms} ms` : 'sem resposta'} - ${metric.requests_per_second} req/s`)} />
        </Panel>
        <Panel title="Alertas do serviço">
          <MiniList items={service.alerts.map((alert) => `${alert.level}: ${alert.message}`)} />
        </Panel>
      </div>
    </section>
  );
}

function ProtocolMetrics({ service, metric }) {
  const common = [
    ['Latência', metric.latency_ms ? `${metric.latency_ms} ms` : 'sem resposta'],
    ['Taxa de erro', `${metric.error_rate}%`],
    ['Erros registrados', metric.error_count],
    ['Conexões ativas', metric.active_connections],
  ];

  const byType = {
    WEB: [
      ['HTTP status', metric.http_status_code ?? 'sem resposta'],
      ['Requests por segundo', metric.requests_per_second],
    ],
    DATABASE: [
      ['Queries por segundo', metric.qps],
      ['CPU simulada', `${metric.cpu_usage ?? 0}%`],
      ['Memoria simulada', `${metric.memory_usage ?? 0}%`],
      ['I/O wait', `${metric.io_wait ?? 0}%`],
      ['Tamanho do banco', `${metric.db_size_mb ?? 0} MB`],
      ['Queries lentas', metric.slow_queries],
    ],
    DNS: [
      ['Tempo de resolução', metric.dns_response_time ? `${metric.dns_response_time} ms` : 'sem resposta'],
      ['Queries por segundo', metric.qps],
      ['Falhas de resolução', metric.failed_resolutions],
    ],
    SMTP: [
      ['Fila SMTP', metric.smtp_queue_size],
      ['Taxa de entrega', `${metric.smtp_delivery_rate ?? 0}%`],
      ['Volume de e-mails', metric.email_volume],
    ],
  };

  return (
    <dl className="metric-grid">
      {[...common, ...(byType[service.type] ?? [])].map(([label, value]) => (
        <div key={label}>
          <dt>{label}</dt>
          <dd>{value}</dd>
        </div>
      ))}
    </dl>
  );
}

function Alerts() {
  const [alerts, setAlerts] = useState([]);
  useEffect(() => { api('/alerts').then(setAlerts); }, []);

  return (
    <section>
      <PageHeader title="Alertas" subtitle="Histórico de alertas amarelos e vermelhos com envio de e-mail simulado." />
      <div className="table-wrap">
        <table>
          <thead><tr><th>Nível</th><th>Serviço</th><th>Título</th><th>Email</th><th>Data</th></tr></thead>
          <tbody>
            {alerts.map((alert) => (
              <tr key={alert.id}>
                <td><span className={badgeClass(alert.level)}>{alert.level}</span></td>
                <td>{alert.service?.name}</td>
                <td>{alert.title}</td>
                <td>{alert.sent_by_email ? 'Sim' : 'Não'}</td>
                <td>{new Date(alert.created_at).toLocaleString()}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </section>
  );
}

function Security({ onChanged }) {
  const [data, setData] = useState({ events: [], known_vulnerabilities: [], login_failures: [] });

  useEffect(() => { api('/security-events').then(setData); }, []);

  const simulate = async (path) => {
    await api(path, { method: 'POST', body: JSON.stringify({}) });
    setData(await api('/security-events'));
    await onChanged();
  };

  return (
    <section>
      <PageHeader title="Segurança" subtitle="Eventos simulados de tráfego, brute force, configuração e vulnerabilidades." />
      <div className="button-row">
        <button onClick={() => simulate('/simulate-login-failure')}>Simular falha de login</button>
        <button onClick={() => simulate('/simulate-traffic-anomaly')}>Simular anomalia de tráfego</button>
        <button onClick={() => simulate('/simulate-config-change')}>Simular alteração de config</button>
      </div>
      <div className="two-columns">
        <Panel title="Eventos recentes">
          <MiniList items={data.events.map((event) => `${event.type} (${event.level})${event.service ? ` em ${event.service.name}` : ''} - ${event.description}`)} />
        </Panel>
        <Panel title="Vulnerabilidades conhecidas">
          <MiniList items={data.known_vulnerabilities.map((event) => `${event.level}${event.service ? ` - ${event.service.name}` : ''} - ${event.description}`)} />
        </Panel>
        <Panel title="Histórico de falhas de login">
          <MiniList items={data.login_failures.map((failure) => `${failure.source_ip} tentou acessar ${failure.email ?? 'usuário desconhecido'} em ${new Date(failure.created_at).toLocaleString()}`)} />
        </Panel>
      </div>
    </section>
  );
}

function Docs() {
  const docs = [
    ['Web Server DOWN', 'Validar conectividade, DNS, porta 443 e logs da aplicação. Se o erro persistir, acionar responsável pelo serviço.'],
    ['DNS com latência alta', 'Comparar resolução local e externa, testar outro resolvedor e verificar alterações recentes de zona.'],
    ['Brute force', 'Bloquear IP ofensivo, revisar logs de autenticação e recomendar troca de senha quando houver risco.'],
    ['Alteração de configuração', 'Comparar hash, revisar diff do arquivo monitor_config.txt e registrar responsável pela mudança.'],
  ];

  return (
    <section>
      <PageHeader title="Documentação e runbooks" subtitle="Procedimentos curtos para responder incidentes comuns." />
      <div className="runbook-grid">
        {docs.map(([title, text]) => <Panel key={title} title={title}><p>{text}</p></Panel>)}
      </div>
    </section>
  );
}

function PageHeader({ title, subtitle }) {
  return (
    <header className="page-header">
      <p className="eyebrow">Monitoramento DevOps</p>
      <h2>{title}</h2>
      <p>{subtitle}</p>
    </header>
  );
}

function NotificationBar({ notifications, unreadCount, liveMode, isOpen, onToggle, onRefresh }) {
  const criticalCount = notifications.filter((item) => ['RED', 'HIGH', 'CRITICAL'].includes(item.level)).length;
  const unreadLabel = unreadCount === 1
    ? '1 notificação nova detectada automaticamente'
    : `${unreadCount} notificações novas detectadas automaticamente`;
  const recentLabel = notifications.length === 1
    ? '1 notificação recente'
    : `${notifications.length} notificações recentes`;

  return (
    <div className={`notification-bar ${isOpen ? 'expanded' : 'compact'}`}>
      <div>
        <strong>{criticalCount} avisos críticos</strong>
        <span>
          {unreadCount > 0
            ? unreadLabel
            : recentLabel}
        </span>
        <small>Modo: {liveMode === 'tempo real' ? 'SSE em tempo quase real' : liveMode === 'polling' ? 'atualização leve a cada 30s' : 'conectando stream'}</small>
      </div>
      <button className="ghost" onClick={onToggle}>{isOpen ? 'Ocultar avisos' : 'Ver avisos'}</button>
      <button className="ghost" onClick={onRefresh}>Atualizar avisos</button>
      {isOpen && notifications.length > 0 && (
        <div className="notification-list">
          {notifications.slice(0, 4).map((item) => (
            <article key={item.id}>
              <span className={badgeClass(item.level)}>{item.level}</span>
              <div>
                <strong>{item.title}</strong>
                <p>{item.message}</p>
              </div>
            </article>
          ))}
        </div>
      )}
    </div>
  );
}

function ToastStack({ toasts, onDismiss }) {
  if (!toasts.length) return null;

  return (
    <div className="toast-stack" aria-live="polite">
      {toasts.map((toast) => (
        <article key={toast.toastId} className={`toast ${toast.level?.toLowerCase()}`}>
          <span className={badgeClass(toast.level)}>{toast.level}</span>
          <div>
            <strong>{toast.type === 'SECURITY' ? 'Evento de segurança' : 'Alerta de serviço'}</strong>
            <p>{toast.title}: {toast.message}</p>
          </div>
          <button className="toast-close" onClick={() => onDismiss(toast.toastId)} aria-label="Fechar aviso">x</button>
        </article>
      ))}
    </div>
  );
}

function Stat({ label, value, tone = '' }) {
  return <div className={`stat ${tone}`}><span>{label}</span><strong>{value}</strong></div>;
}

function Panel({ title, children }) {
  return <div className="panel"><h3>{title}</h3>{children}</div>;
}

function ChartPanel({ title, children }) {
  const options = useMemo(() => ({ maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }), []);
  return <div className="panel chart-panel"><h3>{title}</h3><div className="chart-box">{React.cloneElement(children, { options })}</div></div>;
}

function MiniList({ items }) {
  if (!items.length) return <p className="muted">Nenhum registro encontrado.</p>;
  return <ul className="mini-list">{items.map((item, index) => <li key={`${item}-${index}`}>{item}</li>)}</ul>;
}

function State({ title, message }) {
  return <div className="state"><h3>{title}</h3><p>{message}</p></div>;
}

createRoot(document.getElementById('root')).render(<App />);

