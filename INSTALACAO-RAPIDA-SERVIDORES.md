# 🚀 INSTALAÇÃO RÁPIDA - Tabela de Servidores

## Opção 1: Via Script PHP (RECOMENDADO - Mais Fácil)

1. **Certifique-se que o servidor está rodando:**
   ```bash
   # Se não estiver rodando, execute:
   php -S localhost:8000 -t public
   ```

2. **Acesse o instalador no navegador:**
   ```
   http://localhost:8000/install-servers-table.php
   ```

3. **Pronto!** A tabela será criada automaticamente e você verá uma confirmação.

4. **Após a instalação, delete o arquivo:**
   - Apague o arquivo `install-servers-table.php` da raiz do projeto

---

## Opção 2: Via phpMyAdmin (Manual)

1. Abra o phpMyAdmin: `http://localhost/phpmyadmin`

2. Selecione o banco `ultragestor_php`

3. Clique na aba **SQL**

4. Copie e cole este código:

```sql
CREATE TABLE IF NOT EXISTS servers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(36) NOT NULL,
    name VARCHAR(255) NOT NULL,
    billing_type ENUM('fixed', 'per_active') NOT NULL DEFAULT 'fixed',
    cost DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    panel_type VARCHAR(50) NULL,
    panel_url VARCHAR(255) NULL,
    reseller_user VARCHAR(100) NULL,
    sigma_token VARCHAR(500) NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

5. Clique em **Executar**

---

## ✅ Verificar Instalação

Após executar qualquer uma das opções acima, teste:

1. Acesse: `http://localhost:8000/servidores`
2. Faça login se necessário
3. Clique em "Adicionar Servidor"
4. Preencha os dados e salve
5. O servidor deve aparecer na lista!

---

## 🔍 Solução de Problemas

### Erro: "Tabela já existe"
- Tudo bem! A tabela já foi criada anteriormente.

### Erro: "FOREIGN KEY constraint fails"
- Certifique-se que a tabela `users` existe no banco

### Erro: "Access denied"
- Verifique as credenciais no arquivo `.env`

### Erro: "Database not found"
- Crie o banco `ultragestor_php` primeiro
- Execute o arquivo `database/schema.sql` completo

---

## 📝 Arquivos Criados

- ✅ `app/views/servers/index.php` - Página de servidores
- ✅ `app/api/endpoints/servers.php` - API REST
- ✅ `database/servers.sql` - SQL da tabela
- ✅ `database/schema.sql` - Atualizado com servers
- ✅ `public/index.php` - Rotas adicionadas
- ✅ `install-servers-table.php` - Script de instalação

---

## 🎯 Próximos Passos

Após instalar a tabela:

1. ✅ Teste adicionar servidor
2. ✅ Verifique se os dados aparecem
3. ✅ Delete o arquivo `install-servers-table.php`
4. 🔜 Implemente edição de servidor
5. 🔜 Implemente exclusão de servidor
