# Importação de Clientes - Frontend React

## Resumo das Alterações

A página de importação de clientes foi completamente refatorada do PHP para React, mantendo todas as funcionalidades originais e melhorando a experiência do usuário.

## Funcionalidades Implementadas

### ✅ Upload de Arquivos
- Suporte para arquivos `.xlsx` e `.csv`
- Validação de tamanho (máximo 10MB)
- Limite de 1000 clientes por importação
- Detecção automática de formato Sigma

### ✅ Validação Inteligente
- Validação em tempo real de todos os campos obrigatórios
- Detecção automática de campos faltantes
- Mapeamento inteligente de colunas (suporta nomes em português e inglês)
- Suporte para formato de exportação do painel Sigma

### ✅ Preview e Edição
- Tabela interativa com todos os dados importados
- Edição inline de todos os campos
- Indicadores visuais de status (válido/erro)
- Estatísticas em tempo real (total, válidos, com erros)

### ✅ Ações em Massa
- Aplicar servidor para todos os clientes
- Aplicar plano para todos os clientes
- Aplicar aplicativo para todos os clientes
- Criar planos automaticamente da planilha
- Remover clientes vencidos
- Remover clientes de teste

### ✅ Campos Obrigatórios
1. **Nome** - Nome completo do cliente
2. **Usuário IPTV** - Login de acesso
3. **Senha IPTV** - Senha de acesso
4. **WhatsApp** - Número com DDD
5. **Vencimento** - Data de vencimento
6. **Servidor** - Nome do servidor
7. **Aplicativo** - Nome do aplicativo
8. **Plano** - Nome do plano

### ✅ Campos Opcionais
- Email
- MAC Address
- Valor
- Telas
- Observações

## Mapeamento de Colunas

### Formato Padrão do Sistema
```
nome, email, whatsapp, usuario_iptv, senha_iptv, vencimento, 
valor, servidor, mac, telas, plano, aplicativo
```

### Formato Sigma (Detectado Automaticamente)
```
username, password, expiry_date, connections, name, whatsapp, 
telegram, email, note, plan_price, server, package
```

**Exemplo de linha Sigma:**
```csv
alexfibra2,102030,"2026-08-09 23:59:59",1,,,,,,300.00,"CINE PULSE","COMPLETO C/ADULTOS 12 Meses"
```

**Mapeamento Sigma → Sistema:**
- `username` → Usuário IPTV
- `password` → Senha IPTV
- `expiry_date` → Vencimento (formato: "YYYY-MM-DD HH:MM:SS" → "YYYY-MM-DD")
- `connections` → Telas
- `name` → Nome (se vazio, usa `note`)
- `whatsapp` → WhatsApp
- `telegram` → WhatsApp (fallback)
- `email` → Email
- `note` → Observações
- `plan_price` → Valor
- `server` → Servidor
- `package` → Plano

## Formatos de Data Suportados

O sistema aceita múltiplos formatos de data e converte automaticamente para o formato padrão (YYYY-MM-DD):

1. **ISO 8601**: `2026-08-09` ✅
2. **Sigma**: `2026-08-09 23:59:59` → `2026-08-09` ✅
3. **Brasileiro**: `09/08/2026` → `2026-08-09` ✅
4. **Hífen**: `09-08-2026` → `2026-08-09` ✅
5. **Serial Excel**: `44789` → `2026-08-09` ✅

**Nota:** O sistema remove automaticamente a parte de hora (HH:MM:SS) das datas do formato Sigma.

## Tecnologias Utilizadas

- **React** - Framework principal
- **TypeScript** - Tipagem estática
- **XLSX** - Leitura de arquivos Excel
- **Lucide React** - Ícones
- **React Hot Toast** - Notificações
- **Tailwind CSS** - Estilização

## Backend (Mantido)

O backend PHP permanece inalterado:
- `public/api-clients-import.php` - API de importação
- Validação de dados no servidor
- Integração com automações (WhatsApp, Faturas, Sigma)
- Transações de banco de dados

## Como Usar

### 1. Instalar Dependências
```bash
cd frontend
npm install
```

### 2. Executar em Desenvolvimento
```bash
npm run dev
```

### 3. Build para Produção
```bash
npm run build
```

## Fluxo de Importação

1. **Upload** - Usuário faz upload do arquivo Excel ou CSV
2. **Processamento** - Sistema lê e valida os dados
3. **Preview** - Usuário revisa e edita os dados na tabela
4. **Ações em Massa** - Usuário pode aplicar valores em massa ou criar planos
5. **Importação** - Sistema envia dados válidos para o backend
6. **Redirecionamento** - Após sucesso, redireciona para lista de clientes

## Melhorias em Relação ao PHP

### Interface
- Design moderno e responsivo
- Feedback visual imediato
- Animações suaves
- Dark mode completo

### Usabilidade
- Edição inline de campos
- Validação em tempo real
- Ações em massa simplificadas
- Estatísticas visuais

### Performance
- Processamento no cliente
- Validação instantânea
- Sem recarregamento de página

### Manutenibilidade
- Código TypeScript tipado
- Componentes reutilizáveis
- Lógica separada da apresentação

## Compatibilidade

- ✅ Chrome/Edge (recomendado)
- ✅ Firefox
- ✅ Safari
- ✅ Mobile (responsivo)

## Notas Importantes

1. O backend PHP **não foi alterado** - toda a lógica de importação permanece a mesma
2. A validação ocorre tanto no frontend quanto no backend
3. O formato de data aceito é `YYYY-MM-DD` (ISO 8601)
4. Planos que não existem podem ser criados automaticamente
5. Clientes vencidos e de teste podem ser filtrados antes da importação

## Próximos Passos

- [ ] Adicionar suporte para importação de múltiplos arquivos
- [ ] Implementar histórico de importações
- [ ] Adicionar exportação de erros em CSV
- [ ] Implementar preview de duplicados
- [ ] Adicionar validação de CPF/CNPJ
