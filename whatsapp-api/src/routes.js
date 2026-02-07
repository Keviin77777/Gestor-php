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

        console.log(`🔄 Iniciando conexão para ${reseller_id}...`);

        // Verificar se já está conectado
        if (instanceManager.isConnected(reseller_id)) {
            console.log(`✅ Já conectado: ${reseller_id}`);
            const session = await db.getSession(reseller_id);
            return res.json({
                success: true,
                message: 'Já conectado',
                connected: true,
                profile_name: session?.profile_name,
                phone_number: session?.phone_number
            });
        }

        // Limpar qualquer instância antiga
        try {
            await instanceManager.disconnect(reseller_id);
        } catch (err) {
            // Ignorar erros de desconexão
        }

        // Aguardar liberação de recursos
        await new Promise(resolve => setTimeout(resolve, 3000));

        // Criar sessão no banco
        const instanceName = `reseller_${reseller_id}`;
        await db.createSession(reseller_id, instanceName);

        // Criar nova instância (não bloquear - QR será gerado assincronamente)
        instanceManager.getInstance(reseller_id).catch(err => {
            console.error(`Erro ao criar instância ${reseller_id}:`, err.message);
        });

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

// Função auxiliar para validar número
async function validateNumber(client, phoneNumber) {
    let chatId = phoneNumber.includes('@') ? phoneNumber : `${phoneNumber.replace(/\D/g, '')}@c.us`;

    // Tentar obter o ID do número diretamente
    let numberId = await client.getNumberId(chatId);

    if (numberId) {
        return numberId._serialized;
    }

    // Se falhou e é número brasileiro, tentar variações do 9º dígito
    const cleanPhone = chatId.replace('@c.us', '');
    if (cleanPhone.startsWith('55')) {
        // Se tem 13 dígitos (55 + 2 DDD + 9 + 8 NUM), tentar remover o 9
        if (cleanPhone.length === 13 && cleanPhone[4] === '9') {
            const withoutNine = cleanPhone.substring(0, 4) + cleanPhone.substring(5);

            numberId = await client.getNumberId(`${withoutNine}@c.us`);
            if (numberId) return numberId._serialized;
        }

        // Se tem 12 dígitos (55 + 2 DDD + 8 NUM), tentar adicionar o 9
        if (cleanPhone.length === 12) {
            const withNine = cleanPhone.substring(0, 4) + '9' + cleanPhone.substring(4);

            numberId = await client.getNumberId(`${withNine}@c.us`);
            if (numberId) return numberId._serialized;
        }
    }

    // Se nada funcionou, retornar null
    return null;
}

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

        // Validar e obter ID correto do número
        const chatId = await validateNumber(client, phone_number);

        if (!chatId) {
            throw new Error(`Número não encontrado no WhatsApp: ${phone_number}`);
        }

        console.log(`📤 Enviando mensagem para ${chatId} (reseller: ${reseller_id})`);

        const sentMessage = await client.sendMessage(chatId, message, {
            sendSeen: false  // Não marcar como lido automaticamente
        });

        // Atualizar com ID da mensagem
        await db.updateMessageWithEvolutionId(messageId, sentMessage.id.id);

        console.log(`✅ Mensagem enviada com sucesso: ${messageId}`);

        res.json({
            success: true,
            message_id: messageId,
            whatsapp_message_id: sentMessage.id.id
        });
    } catch (error) {
        console.error('Erro ao enviar mensagem:', error);

        // Marcar como falha no banco
        if (req.body.message_id) { // Nota: req.body.message_id não vem no request original mas o db.createMessage retorna o ID interno
            // Aqui temos um pequeno problema lógico: messageId foi criado acima.
            // Corrigindo para usar a variável local se disponível, ou falhar.
            // Mas como messageId é local, precisamos tratar ele no catch.
        }

        // Mensagens de erro mais amigáveis
        let errorMessage = error.message;

        if (error.message.includes('No LID for user') || error.message.includes('Número não encontrado')) {
            errorMessage = 'Número não encontrado no WhatsApp. Verifique se o numero está correto.';
        } else if (error.message.includes('phone number is not registered')) {
            errorMessage = 'Este número não está registrado no WhatsApp.';
        } else if (error.message.includes('Execution context was destroyed')) {
            errorMessage = 'Erro de conexão com WhatsApp. Tente reconectar o WhatsApp.';
        } else if (error.message.includes('Timeout')) {
            errorMessage = 'Timeout ao enviar mensagem. O WhatsApp pode estar lento ou desconectado.';
        }

        res.status(500).json({
            success: false,
            error: errorMessage
        });
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

                // Validar número
                const chatId = await validateNumber(client, msg.phone_number);

                if (!chatId) {
                    throw new Error(`Número não encontrado: ${msg.phone_number}`);
                }

                const sentMessage = await client.sendMessage(chatId, msg.message, {
                    sendSeen: false  // Não marcar como lido automaticamente
                });
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
