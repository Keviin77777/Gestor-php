const express = require('express');
const cors = require('cors');
const bodyParser = require('body-parser');
require('dotenv').config();

const instanceManager = require('./src/instanceManager');
const routes = require('./src/routes');

const app = express();
const PORT = process.env.PORT || 3001;

// Middlewares
app.use(cors());
app.use(bodyParser.json({ limit: '10mb' }));
app.use(bodyParser.urlencoded({ extended: true, limit: '10mb' }));

// Middleware de autenticação
app.use((req, res, next) => {
    if (req.path === '/health') return next();
    
    const apiKey = req.headers['x-api-key'] || req.headers['apikey'];
    const expectedKey = process.env.API_KEY;
    
    if (!apiKey || apiKey !== expectedKey) {
        return res.status(401).json({ success: false, error: 'API Key inválida' });
    }
    
    next();
});

// Rotas
app.use('/api', routes);

// Health check com mais informações
app.get('/health', (req, res) => {
    const memUsage = process.memoryUsage();
    res.json({ 
        success: true, 
        status: 'online',
        provider: 'native',
        instances: instanceManager.getInstancesCount(),
        memory: {
            heapUsed: Math.round(memUsage.heapUsed / 1024 / 1024) + 'MB',
            heapTotal: Math.round(memUsage.heapTotal / 1024 / 1024) + 'MB',
            rss: Math.round(memUsage.rss / 1024 / 1024) + 'MB'
        },
        uptime: Math.round(process.uptime()) + 's'
    });
});

// Iniciar servidor
app.listen(PORT, () => {
    console.log(`🚀 UltraGestor WhatsApp API rodando na porta ${PORT}`);
    console.log(`📱 Pronto para gerenciar multi-instâncias`);
    console.log(`💾 Memória inicial: ${Math.round(process.memoryUsage().heapUsed / 1024 / 1024)}MB`);
});

// Graceful shutdown
process.on('SIGINT', async () => {
    console.log('\n🛑 Encerrando servidor...');
    await instanceManager.destroyAll();
    process.exit(0);
});

process.on('SIGTERM', async () => {
    console.log('\n🛑 Recebido SIGTERM, encerrando...');
    await instanceManager.destroyAll();
    process.exit(0);
});

// Capturar erros não tratados
process.on('uncaughtException', (err) => {
    console.error('❌ Erro não capturado:', err.message);
    // Não encerrar o processo, apenas logar
});

process.on('unhandledRejection', (reason, promise) => {
    console.error('❌ Promise rejeitada não tratada:', reason);
    // Não encerrar o processo, apenas logar
});
