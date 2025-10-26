-- Adicionar dados de exemplo para testar o dashboard

-- Inserir alguns clientes de exemplo para o admin
INSERT INTO clients (reseller_id, name, email, phone, username, password, iptv_password, plan, start_date, renewal_date, status, value, server, mac, notifications, screens, notes) VALUES
('admin-001', 'João Silva Santos', 'joao@email.com', '(11) 99999-1234', 'joao123', 'senha123', 'iptv123', 'Premium', '2024-10-01', '2025-10-28', 'active', 35.00, 'Servidor Principal', 'AA:BB:CC:DD:EE:FF', 'sim', 2, 'Cliente VIP'),
('admin-001', 'Maria Oliveira Costa', 'maria@email.com', '(11) 98888-5678', 'maria456', 'senha456', 'iptv456', 'Básico', '2024-09-15', '2025-10-30', 'active', 25.00, 'Servidor Backup', 'BB:CC:DD:EE:FF:AA', 'sim', 1, ''),
('admin-001', 'Pedro Souza Lima', 'pedro@email.com', '(11) 97777-9012', 'pedro789', 'senha789', 'iptv789', 'VIP', '2024-08-20', '2025-11-05', 'active', 50.00, 'Servidor Premium', 'CC:DD:EE:FF:AA:BB', 'sim', 3, 'Pagamento em dia'),
('admin-001', 'Ana Carolina Ferreira', 'ana@email.com', '(11) 96666-3456', 'ana321', 'senha321', 'iptv321', 'Premium', '2024-07-10', '2025-10-25', 'inactive', 35.00, 'Servidor Principal', 'DD:EE:FF:AA:BB:CC', 'nao', 1, 'Vencido há 2 dias'),
('admin-001', 'Carlos Eduardo Rocha', 'carlos@email.com', '(11) 95555-7890', 'carlos654', 'senha654', 'iptv654', 'Básico', '2024-06-05', '2025-11-10', 'suspended', 25.00, 'Servidor Backup', 'EE:FF:AA:BB:CC:DD', 'nao', 1, 'Suspenso por falta de pagamento');

-- Inserir algumas faturas de exemplo
INSERT INTO invoices (id, reseller_id, client_id, value, discount, final_value, issue_date, due_date, status, payment_date) VALUES
('invoice-001', 'admin-001', 'client-001', 35.00, 0.00, 35.00, '2024-09-28', '2024-10-28', 'paid', '2024-10-25 14:30:00'),
('invoice-002', 'admin-001', 'client-002', 25.00, 0.00, 25.00, '2024-09-30', '2024-10-30', 'paid', '2024-10-28 09:15:00'),
('invoice-003', 'admin-001', 'client-003', 50.00, 5.00, 45.00, '2024-10-05', '2024-11-05', 'pending', NULL),
('invoice-004', 'admin-001', 'client-001', 35.00, 0.00, 35.00, '2024-08-28', '2024-09-28', 'paid', '2024-09-25 16:45:00'),
('invoice-005', 'admin-001', 'client-002', 25.00, 0.00, 25.00, '2024-08-30', '2024-09-30', 'paid', '2024-09-29 11:20:00');