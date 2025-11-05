# 🛡️ Sistema de Proteção do Código-Fonte

## Visão Geral

Sistema implementado para proteger o código-fonte da aplicação contra visualização e cópia não autorizada.

## Funcionalidades Implementadas

### 1. **Bloqueio de Atalhos de Teclado**

#### Windows/Linux:
- `Ctrl + U` - Visualizar código-fonte
- `Ctrl + Shift + I` - DevTools
- `Ctrl + Shift + J` - Console
- `Ctrl + Shift + C` - Inspecionar elemento
- `Ctrl + Shift + K` - Console Firefox
- `F12` - DevTools
- `Ctrl + S` - Salvar página
- `Ctrl + P` - Imprimir (opcional)

#### Mac:
- `Cmd + Option + I` - DevTools
- `Cmd + Option + J` - Console
- `Cmd + Option + C` - Inspecionar elemento

### 2. **Bloqueio de Clique Direito**

- Desabilita o menu de contexto
- Mostra notificação visual quando tentado

### 3. **Notificações Visuais**

Quando uma tentativa de acesso é detectada, uma notificação elegante aparece:
- Design moderno com gradiente vermelho
- Ícone de aviso
- Mensagem específica da ação bloqueada
- Desaparece automaticamente após 3 segundos

### 4. **Proteção Contra Iframe**

- Impede que a aplicação seja incorporada em outros sites
- Redireciona automaticamente para a página principal

### 5. **Aviso no Console**

Mensagem de segurança exibida no console alertando sobre:
- Uso indevido de ferramentas de desenvolvedor
- Riscos de fraude
- Proteção de dados

### 6. **Proteção de Imagens**

- Desabilita arrastar e soltar de imagens
- Previne download fácil de assets

## Páginas Protegidas

✅ Landing Page (`/`)
✅ Login (`/login`)
✅ Registro (`/register`)
✅ Dashboard (`/dashboard`)
✅ Clientes (`/clients`)
✅ Planos (`/plans`)
✅ Aplicativos (`/applications`)
✅ Faturas (`/invoices`)
✅ Servidores (`/servidores`)
✅ WhatsApp - Parear (`/whatsapp/parear`)
✅ WhatsApp - Templates (`/whatsapp/templates`)
✅ WhatsApp - Agendamento (`/whatsapp/scheduling`)
✅ Renovar Acesso (`/renew-access`)
✅ Métodos de Pagamento (`/payment-methods`)
✅ Meu Perfil (`/profile`)

## Arquivo de Proteção

**Localização:** `public/assets/js/protection.js`

## Como Adicionar em Novas Páginas

Para adicionar proteção em uma nova página, inclua o script antes do fechamento do `</body>`:

```html
    <!-- Protection Script -->
    <script src="/assets/js/protection.js"></script>
</body>
</html>
```

## Limitações Conhecidas

⚠️ **Importante:** Nenhum sistema de proteção client-side é 100% infalível.

### O que NÃO pode ser protegido:
1. Usuários avançados podem desabilitar JavaScript
2. Código-fonte pode ser acessado via ferramentas externas
3. Requisições de rede podem ser interceptadas
4. Screenshots ainda são possíveis

### Proteções Reais:
- ✅ Dificulta acesso casual ao código
- ✅ Previne cópia rápida de código
- ✅ Desencoraja usuários não técnicos
- ✅ Adiciona camada de segurança por obscuridade

## Melhores Práticas

### Para Máxima Segurança:

1. **Minificar e Ofuscar JavaScript**
   ```bash
   # Usar ferramentas como:
   - UglifyJS
   - Terser
   - JavaScript Obfuscator
   ```

2. **Proteger APIs**
   - Autenticação robusta
   - Rate limiting
   - Validação server-side

3. **Não Expor Dados Sensíveis**
   - Nunca colocar chaves de API no frontend
   - Usar variáveis de ambiente
   - Processar dados sensíveis no backend

4. **HTTPS Obrigatório**
   - Certificado SSL válido
   - Redirecionar HTTP para HTTPS

5. **Content Security Policy (CSP)**
   ```php
   header("Content-Security-Policy: default-src 'self'");
   ```

## Monitoramento

### Logs de Tentativas (Desenvolvimento)

Em ambiente de desenvolvimento (localhost), o sistema registra:
- Tentativas de acesso bloqueadas
- Atalhos interceptados
- Status da proteção

### Produção

Em produção, o sistema opera silenciosamente, apenas:
- Bloqueia ações
- Mostra notificações ao usuário
- Não gera logs no console

## Manutenção

### Atualizar Proteção

Para atualizar o sistema de proteção:

1. Editar `public/assets/js/protection.js`
2. Testar em ambiente de desenvolvimento
3. Limpar cache do navegador
4. Verificar em diferentes navegadores

### Desabilitar Temporariamente

Para desabilitar (desenvolvimento):

```javascript
// Comentar a linha no arquivo HTML
// <script src="/assets/js/protection.js"></script>
```

## Compatibilidade

### Navegadores Suportados:
- ✅ Chrome/Edge (Chromium) 90+
- ✅ Firefox 88+
- ✅ Safari 14+
- ✅ Opera 76+

### Dispositivos:
- ✅ Desktop (Windows, Mac, Linux)
- ✅ Mobile (iOS, Android)
- ✅ Tablets

## Troubleshooting

### Problema: Proteção não funciona

**Solução:**
1. Verificar se o script está carregando (Network tab)
2. Verificar erros no console
3. Limpar cache do navegador
4. Verificar se JavaScript está habilitado

### Problema: Notificações não aparecem

**Solução:**
1. Verificar z-index de outros elementos
2. Verificar se há conflitos de CSS
3. Testar em navegador limpo (modo anônimo)

### Problema: Afeta funcionalidade normal

**Solução:**
1. Revisar eventos bloqueados
2. Adicionar exceções específicas
3. Testar fluxos de usuário

## Conclusão

Este sistema de proteção oferece uma camada adicional de segurança contra acesso casual ao código-fonte. Deve ser usado em conjunto com outras medidas de segurança server-side para proteção completa da aplicação.

---

**Desenvolvido para:** UltraGestor  
**Versão:** 1.0  
**Data:** 2024  
**Autor:** Sistema de Proteção Integrado
