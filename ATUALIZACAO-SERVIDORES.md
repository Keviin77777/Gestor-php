# Atualiza\u00e7\u00e3o - Tabela de Servidores

## Como Aplicar a Atualiza\u00e7\u00e3o

### Op\u00e7\u00e3o 1: Executar apenas a nova tabela (para quem j\u00e1 tem o banco configurado)

1. Acesse o phpMyAdmin
2. Selecione o banco de dados `ultragestor_php`
3. Clique em "SQL"
4. Execute o conte\u00fado do arquivo `database/servers.sql`

### Op\u00e7\u00e3o 2: Recriar todo o banco (para instala\u00e7\u00e3o limpa)

1. Acesse o phpMyAdmin
2. **ATEN\u00c7\u00c3O**: Isso vai apagar todos os dados existentes!
3. Clique em "SQL"
4. Execute o conte\u00fado do arquivo `database/schema.sql`

## Verifica\u00e7\u00e3o

Ap\u00f3s executar, verifique se a tabela `servers` foi criada com sucesso:

```sql
SHOW TABLES LIKE 'servers';
DESCRIBE servers;
```

## Estrutura da Tabela Servers

- **id**: ID \u00fanico do servidor (auto-increment)
- **user_id**: ID do usu\u00e1rio (reseller) dono do servidor
- **name**: Nome do servidor
- **billing_type**: Tipo de cobran\u00e7a (fixed ou per_active)
- **cost**: Valor mensal do servidor
- **panel_type**: Tipo de painel (qpanel_sigma, etc.) - **OPCIONAL**
- **panel_url**: URL do painel - **OPCIONAL**
- **reseller_user**: Usu\u00e1rio de revenda - **OPCIONAL**
- **sigma_token**: Token do Sigma - **OPCIONAL**
- **status**: Status do servidor (active/inactive)
- **created_at**: Data de cria\u00e7\u00e3o
- **updated_at**: Data de atualiza\u00e7\u00e3o

## Funcionalidades Implementadas

### Frontend
- ✅ P\u00e1gina de gerenciamento de servidores
- ✅ Modal profissional para adicionar servidor
- ✅ Campos opcionais para integra\u00e7\u00e3o com painel
- ✅ Valida\u00e7\u00e3o de formul\u00e1rio
- ✅ Loading centralizado
- ✅ Design responsivo

### Backend
- ✅ API REST completa para servidores
- ✅ Autentica\u00e7\u00e3o JWT
- ✅ Valida\u00e7\u00e3o de dados
- ✅ Relacionamento com usu\u00e1rios
- ✅ CRUD completo (Create, Read, Update, Delete)

### Endpoints da API

```
GET    /api/servers           - Lista todos os servidores do usu\u00e1rio
POST   /api/servers           - Cria novo servidor
PUT    /api/servers/{id}      - Atualiza servidor
DELETE /api/servers/{id}      - Exclui servidor
```

## Testes

1. Acesse: `http://localhost:8000/servidores`
2. Clique em "Adicionar Servidor"
3. Preencha os dados:
   - Nome do Servidor
   - Tipo de Cobran\u00e7a
   - Valor Mensal
4. (Opcional) Selecione o tipo de painel e preencha os dados de integra\u00e7\u00e3o
5. Clique em "Salvar"

## Pr\u00f3ximos Passos

- [ ] Implementar edi\u00e7\u00e3o de servidor
- [ ] Implementar exclus\u00e3o de servidor
- [ ] Adicionar estat\u00edsticas de uso
- [ ] Implementar teste de conex\u00e3o real com o painel
- [ ] Adicionar mais tipos de pain\u00e9is
