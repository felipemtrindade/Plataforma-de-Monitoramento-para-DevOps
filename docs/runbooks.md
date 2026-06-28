# Runbooks

## Web Server DOWN

1. Confirmar se o alerta é RED e se o status está DOWN.
2. Testar o host manualmente pelo navegador ou `curl`.
3. Verificar DNS e porta configurada no cadastro do serviço.
4. Consultar logs da aplicação web.
5. Reiniciar o serviço web se for um ambiente controlado.
6. Registrar o horário de início, causa provável e horário de normalização.

## DNS com latência alta

1. Confirmar se a latência ficou entre 200 ms e 500 ms ou acima de 500 ms.
2. Testar resolução com outro resolvedor DNS.
3. Verificar alterações recentes de zona.
4. Validar se o domínio cadastrado está correto.
5. Se persistir, acionar responsável por DNS ou provedor.

## Brute force

1. Identificar o IP em `security_events`.
2. Bloquear temporariamente o IP no firewall ou proxy.
3. Revisar logs de autenticação.
4. Verificar se alguma conta teve acesso indevido.
5. Recomendar troca de senha e habilitar MFA quando aplicável.

## Alteração de configuração

1. Abrir `storage/app/monitor_config.txt`.
2. Comparar conteúdo atual com a versão esperada.
3. Identificar quem realizou a alteração.
4. Reverter se a mudança não foi autorizada.
5. Registrar o incidente e atualizar o runbook se necessário.

