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
app.use(bodyParser.json());
app.use(bodyParser.urlencoded({ extended: true }));

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

// Health check
app.get('/health', (req, res) => {
    res.json({ 
        success: true, 
        status: 'online',
        instances: instanceManager.getInstancesCount()
    });
});

// Iniciar servidor
app.listen(PORT, () => {
    console.log(`🚀 UltraGestor WhatsApp API rodando na porta ${PORT}`);
    console.log(`📱 Pronto para gerenciar multi-instâncias`);
});

// Graceful shutdown
process.on('SIGINT', async () => {
    console.log('\n🛑 Encerrando servidor...');
    await instanceManager.destroyAll();
    process.exit(0);
});
