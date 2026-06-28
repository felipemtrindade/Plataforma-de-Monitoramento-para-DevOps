# Playbooks de Incidentes

## Incidente RED de disponibilidade

Objetivo: restaurar o serviço o mais rápido possível.

1. Confirmar alerta RED na tela Alertas.
2. Abrir detalhes do serviço e verificar últimas métricas.
3. Testar host e porta manualmente.
4. Verificar se houve alteração de configuração no mesmo período.
5. Aplicar correção operacional.
6. Executar `php artisan monitor:check` novamente.
7. Encerrar incidente quando o serviço voltar para UP.

## Incidente YELLOW de latência

Objetivo: impedir que degradação vire indisponibilidade.

1. Identificar serviço com latência entre 200 ms e 500 ms.
2. Comparar com histórico no gráfico.
3. Verificar conexões ativas, QPS e taxa de erro.
4. Reduzir carga, otimizar consulta ou escalar recurso quando aplicável.
5. Acompanhar novas coletas.

## Incidente de segurança

Objetivo: conter a origem e preservar evidências.

1. Abrir tela Segurança.
2. Classificar evento: TRAFFIC_ANOMALY, BRUTE_FORCE, CONFIG_CHANGE ou VULNERABILITY.
3. Registrar IP de origem quando existir.
4. Aplicar bloqueio, reversão ou correção.
5. Documentar a causa e a ação tomada.

