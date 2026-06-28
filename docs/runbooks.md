# Runbooks

## Web Server DOWN

1. Confirmar se o alerta e RED e se o status esta DOWN.
2. Testar o host manualmente pelo navegador ou `curl`.
3. Verificar DNS e porta configurada no cadastro do servico.
4. Consultar logs da aplicacao web.
5. Reiniciar o servico web se for um ambiente controlado.
6. Registrar o horario de inicio, causa provavel e horario de normalizacao.

## DNS com latencia alta

1. Confirmar se a latencia ficou entre 200 ms e 500 ms ou acima de 500 ms.
2. Testar resolucao com outro resolvedor DNS.
3. Verificar alteracoes recentes de zona.
4. Validar se o dominio cadastrado esta correto.
5. Se persistir, acionar responsavel por DNS ou provedor.

## Brute force

1. Identificar o IP em `security_events`.
2. Bloquear temporariamente o IP no firewall ou proxy.
3. Revisar logs de autenticacao.
4. Verificar se alguma conta teve acesso indevido.
5. Recomendar troca de senha e habilitar MFA quando aplicavel.

## Alteracao de configuracao

1. Abrir `storage/app/monitor_config.txt`.
2. Comparar conteudo atual com a versao esperada.
3. Identificar quem realizou a alteracao.
4. Reverter se a mudanca nao foi autorizada.
5. Registrar o incidente e atualizar o runbook se necessario.
