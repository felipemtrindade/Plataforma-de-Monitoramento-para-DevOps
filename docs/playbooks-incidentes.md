# Playbooks de Incidentes

## Incidente RED de disponibilidade

Objetivo: restaurar o servico o mais rapido possivel.

1. Confirmar alerta RED na tela Alertas.
2. Abrir detalhes do servico e verificar ultimas metricas.
3. Testar host e porta manualmente.
4. Verificar se houve alteracao de configuracao no mesmo periodo.
5. Aplicar correcao operacional.
6. Executar `php artisan monitor:check` novamente.
7. Encerrar incidente quando o servico voltar para UP.

## Incidente YELLOW de latencia

Objetivo: impedir que degradacao vire indisponibilidade.

1. Identificar servico com latencia entre 200 ms e 500 ms.
2. Comparar com historico no grafico.
3. Verificar conexoes ativas, QPS e taxa de erro.
4. Reduzir carga, otimizar consulta ou escalar recurso quando aplicavel.
5. Acompanhar novas coletas.

## Incidente de seguranca

Objetivo: conter a origem e preservar evidencias.

1. Abrir tela Seguranca.
2. Classificar evento: TRAFFIC_ANOMALY, BRUTE_FORCE, CONFIG_CHANGE ou VULNERABILITY.
3. Registrar IP de origem quando existir.
4. Aplicar bloqueio, reversao ou correcao.
5. Documentar a causa e a acao tomada.
