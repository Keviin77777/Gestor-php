/**
 * Script para criar arquivo XLSX de exemplo para importação de clientes
 * Execute: node scripts/create-xlsx-example.js
 */

const XLSX = require('xlsx');

// Dados de exemplo
const data = [
    {
        nome: 'João Silva',
        usuario_iptv: 'joao123',
        senha_iptv: 'senha123',
        whatsapp: '11987654321',
        vencimento: '2024-12-31',
        servidor: 'Servidor Principal',
        aplicativo: 'NextApp',
        mac: '00:1A:2B:3C:4D:5E',
        plano: 'Premium',
        email: 'joao.silva@email.com'
    },
    {
        nome: 'Maria Santos',
        usuario_iptv: 'maria456',
        senha_iptv: 'senha456',
        whatsapp: '11976543210',
        vencimento: '2024-12-25',
        servidor: 'Servidor Principal',
        aplicativo: 'SmartIPTV',
        mac: '00:1A:2B:3C:4D:5F',
        plano: 'Básico',
        email: 'maria.santos@email.com'
    },
    {
        nome: 'Pedro Oliveira',
        usuario_iptv: 'pedro789',
        senha_iptv: 'senha789',
        whatsapp: '11965432109',
        vencimento: '2024-12-20',
        servidor: 'Servidor Secundário',
        aplicativo: 'IPTV Smarters',
        mac: '00:1A:2B:3C:4D:60',
        plano: 'VIP',
        email: 'pedro.oliveira@email.com'
    }
];

// Criar workbook
const wb = XLSX.utils.book_new();

// Criar worksheet a partir dos dados
const ws = XLSX.utils.json_to_sheet(data);

// Adicionar worksheet ao workbook
XLSX.utils.book_append_sheet(wb, ws, 'Clientes');

// Salvar arquivo
XLSX.writeFile(wb, 'exemplo-importacao-clientes.xlsx');

console.log('✅ Arquivo exemplo-importacao-clientes.xlsx criado com sucesso!');
console.log('📋 Contém 3 clientes de exemplo');
console.log('📁 Localização: exemplo-importacao-clientes.xlsx');
