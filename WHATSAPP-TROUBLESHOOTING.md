# Troubleshooting - WhatsApp Evolution API

## Problema: QR Code não aparece

### Sintomas
- A conexão fica em "Conectando..." indefinidamente
- Logs mostram "Instância ainda inicializando (sem state)"
- O endpoint `/instance/connect` dá timeout

### Causa
O container Docker da Evolution API pode ficar em um estado inconsistente após múltiplas tentativas de criação/deleção de instâncias.

### Solução

**1. Reiniciar o container Docker:**
```bash
docker restart evolution-api
```

**2. Aguardar 10 segundos para o container inicializar**

**3. Deletar instâncias travadas:**
```bash
# Windows PowerShell
Invoke-WebRequest -Uri "http://localhost:8081/instance/delete/ultragestor-admin-001" -Headers @{"apikey"="gestplay-whatsapp-2024"} -Method DELETE

# Linux/Mac
curl -X DELETE "http://localhost:8081/instance/delete/ultragestor-admin-001" -H "apikey: gestplay-whatsapp-2024"
```

**4. Tentar conectar novamente pelo navegador**

## Verificação de Saúde da API

Execute este comando para verificar se a Evolution API está funcionando:

```bash
# Windows PowerShell
Invoke-WebRequest -Uri "http://localhost:8081/instance/fetchInstances" -Headers @{"apikey"="gestplay-whatsapp-2024"}

# Linux/Mac
curl "http://localhost:8081/instance/fetchInstances" -H "apikey: gestplay-whatsapp-2024"
```

Se retornar HTTP 200, a API está funcionando.

## Logs do Docker

Para ver os logs da Evolution API:

```bash
docker logs evolution-api --tail 50
```

## Instâncias Fantasma

Se uma instância aparece no `/connectionState` mas não no `/fetchInstances`, ela está "travada":

```bash
# Verificar se existe
curl "http://localhost:8081/instance/connectionState/ultragestor-admin-001" -H "apikey: gestplay-whatsapp-2024"

# Se retornar sem "state", deletar
curl -X DELETE "http://localhost:8081/instance/delete/ultragestor-admin-001" -H "apikey: gestplay-whatsapp-2024"
```

## Prevenção

- Evite criar/deletar instâncias rapidamente em sequência
- Aguarde pelo menos 5 segundos entre operações
- Reinicie o Docker periodicamente se houver muitos testes

## Teste Rápido

Execute o script de teste:

```bash
php test-new-instance.php
```

Se o QR Code for gerado, a API está funcionando corretamente.
