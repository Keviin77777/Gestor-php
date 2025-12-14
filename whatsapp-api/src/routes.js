const express = require('express');
const router = express.Router();
const instanceManager = require('./instanceManager');
const db = require('./database');

/**
 * GET /health
 * Health check endpoint
 */
router.get('/health', (req, res) => {
    const stats = instanceManager.getInstancesCount();
    res.json({ 
        success: true, 
        status: 'running',
        provider: 'native',
        instances: stats
    });
});

/**
 * POST /api/instance/connect
 * Conectar instância do reseller
 */
router.post('/instance/connect', async (req, res) => {
    try {
        const { reseller_id } = req.body;
        
        if (!reseller_id) {
            return res.status(400).json({ success: false, error: 'reseller_id é obrigatório' });
        }

        // IMPORTANTE: SEMPRE desconectar e limpar instância antiga antes de criar nova
        console.log(`🔄 Verificando instância existente para ${reseller_id}...`);
        
        // Verificar se já existe instância ativa
        if (instanceManager.isConnected(reseller_id)) {
            console.log(`⚠️ Instância ainda ativa, desconectando primeiro...`);
            await instanceManager.disconnect(reseller_id);
        }
        
        try {
            // Forçar desconexão completa (ignora erros)
            await instanceManager.disconnect(reseller_id);
            console.log(`✅ Instância antiga removida para ${reseller_id}`);
            
            // Aguardar liberação de recursos (Windows precisa de mais tempo)
            const isWindows = process.platform === 'win32';
            const delay = isWindows ? 5000 : 2500; // Aumentado para garantir liberação completa de processos Chrome
            console.log(`⏳ Aguardando ${delay}ms para liberação completa de recursos...`);
            await new Promise(resolve => setTimeout(resolve, delay));
        } catch (disconnectError) {
            console.log(`⚠️ Erro ao desconectar (continuando): ${disconnectError.message}`);
            // Aguardar mesmo com erro para garantir que recursos sejam liberados
            const isWindows = process.platform === 'win32';
            const delay = isWindows ? 4000 : 2000;
            await new Promise(resolve => setTimeout(resolve, delay));
        }

        // Criar sessão no banco
        const instanceName = `reseller_${reseller_id}`;
        await db.createSession(reseller_id, instanceName);

        // Pequeno delay adicional para garantir que processos foram finalizados
        await new Promise(resolve => setTimeout(resolve, 1000));

        // Criar nova instância no gerenciador
        await instanceManager.getInstance(reseller_id);
        
        res.json({ 
            success: true, 
            message: 'Instância iniciada. Aguarde o QR Code.',
            reseller_id
        });
    } catch (error) {
        console.error('Erro ao conectar:', error);
        res.status(500).json({ success: false, error: error.message });
    }
});

/**
 * GET /api/instance/qrcode/:reseller_id
 * Obter QR Code
 */
router.get('/instance/qrcode/:reseller_id', async (req, res) => {
    try {
        const { reseller_id } = req.params;
        
        // Verificar se já está conectado
        if (instanceManager.isConnected(reseller_id)) {
            const session = await db.getSession(reseller_id);
            if (session) {
                return res.json({
                    success: true,
                    connected: true,
                    profile_name: session.profile_name,
                    phone_number: session.phone_number
                });
            }
        }

        // Buscar QR Code apenas se houver instância ativa
        const qrCode = instanceManager.getQRCode(reseller_id);
        
        if (qrCode) {
            res.json({ 
                success: true, 
                connected: false,
                qr_code: qrCode 
            });
        } else {
            res.json({ 
                success: true, 
                connected: false,
                qr_code: null,
                message: 'Nenhuma instância ativa'
            });
        }
    } catch (error) {
        console.error('Erro ao buscar QR Code:', error);
        res.status(500).json({ success: false, error: error.message });
    }
});

/**
 * GET /api/instance/status/:reseller_id
 * Verificar status da instância
 */
router.get('/instance/status/:reseller_id', async (req, res) => {
    try {
        const { reseller_id } = req.params;
        const session = await db.getSession(reseller_id);
        const connected = instanceManager.isConnected(reseller_id);
        
        res.json({
            success: true,
            connected,
            has_session: session !== null,
            status: session.status,
            profile_name: session.profile_name,
            phone_number: session.phone_number
        });
    } catch (error) {
        console.error('Erro ao verificar status:', error);
        res.status(500).json({ success: false, error: error.message });
    }
});

/**
 * POST /api/instance/disconnect
 * Desconectar instância
 */
router.post('/instance/disconnect', async (req, res) => {
    try {
        const { reseller_id } = req.body;
        
        if (!reseller_id) {
            return res.status(400).json({ success: false, error: 'reseller_id é obrigatório' });
        }

        await instanceManager.disconnect(reseller_id);
        
        res.json({ 
            success: true, 
            message: 'Instância desconectada com sucesso' 
        });
    } catch (error) {
        console.error('Erro ao desconectar:', error);
        res.status(500).json({ success: false, error: error.message });
    }
});

/**
 * POST /api/message/send
 * Enviar mensagem
 */
router.post('/message/send', async (req, res) => {
    try {
        const { reseller_id, phone_number, message, template_id, client_id, invoice_id } = req.body;
        
        if (!reseller_id || !phone_number || !message) {
            return res.status(400).json({ 
                success: false, 
                error: 'reseller_id, phone_number e message são obrigatórios' 
            });
        }

        // Verificar se está conectado
        if (!instanceManager.isConnected(reseller_id)) {
            return res.status(400).json({ 
                success: false, 
                error: 'WhatsApp não está conectado. Conecte primeiro.' 
            });
        }

        // Criar registro no banco
        const messageId = await db.createMessage(reseller_id, {
            phone_number,
            message,
            template_id,
            client_id,
            invoice_id
        });

        // Enviar mensagem
        const client = await instanceManager.getInstance(reseller_id);
        const chatId = phone_number.includes('@') ? phone_number : `${phone_number}@c.us`;
        
        const sentMessage = await client.sendMessage(chatId, message);
        
        // Atualizar com ID da mensagem
        await db.updateMessageWithEvolutionId(messageId, sentMessage.id.id);
        
        res.json({ 
            success: true, 
            message_id: messageId,
            whatsapp_message_id: sentMessage.id.id
        });
    } catch (error) {
        console.error('Erro ao enviar mensagem:', error);
        
        // Marcar como falha no banco se tiver messageId
        if (req.body.message_id) {
            await db.markMessageAsFailed(req.body.message_id, error.message);
        }
        
        res.status(500).json({ success: false, error: error.message });
    }
});

/**
 * POST /api/message/send-bulk
 * Enviar mensagens em massa (fila)
 */
router.post('/message/send-bulk', async (req, res) => {
    try {
        const { reseller_id, messages } = req.body;
        
        if (!reseller_id || !Array.isArray(messages) || messages.length === 0) {
            return res.status(400).json({ 
                success: false, 
                error: 'reseller_id e messages (array) são obrigatórios' 
            });
        }

        // Verificar se está conectado
        if (!instanceManager.isConnected(reseller_id)) {
            return res.status(400).json({ 
                success: false, 
                error: 'WhatsApp não está conectado' 
            });
        }

        const results = [];
        
        // Processar fila com delay
        for (const msg of messages) {
            try {
                const messageId = await db.createMessage(reseller_id, {
                    phone_number: msg.phone_number,
                    message: msg.message,
                    template_id: msg.template_id,
                    client_id: msg.client_id,
                    invoice_id: msg.invoice_id
                });

                const client = await instanceManager.getInstance(reseller_id);
                const chatId = msg.phone_number.includes('@') ? msg.phone_number : `${msg.phone_number}@c.us`;
                
                const sentMessage = await client.sendMessage(chatId, msg.message);
                await db.updateMessageWithEvolutionId(messageId, sentMessage.id.id);
                
                results.push({ 
                    success: true, 
                    phone_number: msg.phone_number,
                    message_id: messageId 
                });
                
                // Delay de 2 segundos entre mensagens
                await new Promise(resolve => setTimeout(resolve, 2000));
            } catch (error) {
                results.push({ 
                    success: false, 
                    phone_number: msg.phone_number,
                    error: error.message 
                });
            }
        }
        
        res.json({ 
            success: true, 
            total: messages.length,
            sent: results.filter(r => r.success).length,
            failed: results.filter(r => !r.success).length,
            results 
        });
    } catch (error) {
        console.error('Erro ao enviar mensagens em massa:', error);
        res.status(500).json({ success: false, error: error.message });
    }
});

/**
 * GET /api/queue/pending/:reseller_id
 * Buscar fila de mensagens pendentes
 */
router.get('/queue/pending/:reseller_id', async (req, res) => {
    try {
        const { reseller_id } = req.params;
        const limit = parseInt(req.query.limit) || 10;
        
        const messages = await db.getPendingMessages(reseller_id, limit);
        
        res.json({ 
            success: true, 
            count: messages.length,
            messages 
        });
    } catch (error) {
        console.error('Erro ao buscar fila:', error);
        res.status(500).json({ success: false, error: error.message });
    }
});

module.exports = router;
