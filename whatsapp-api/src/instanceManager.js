const { Client, LocalAuth } = require('whatsapp-web.js');
const qrcode = require('qrcode');
const db = require('./database');

class InstanceManager {
    constructor() {
        this.instances = new Map();
        this.qrCodes = new Map();
    }

    /**
     * Sanitizar resellerId para usar como clientId
     * Remove caracteres não permitidos (apenas alfanuméricos, underscore e hífen)
     */
    sanitizeResellerId(resellerId) {
        return resellerId.toString().replace(/[^a-zA-Z0-9_-]/g, '_');
    }

    /**
     * Criar ou recuperar instância
     */
    async getInstance(resellerId) {
        const sanitizedId = this.sanitizeResellerId(resellerId);
        const key = `reseller_${sanitizedId}`;
        
        if (this.instances.has(key)) {
            return this.instances.get(key);
        }

        return await this.createInstance(resellerId);
    }

    /**
     * Criar nova instância
     */
    async createInstance(resellerId) {
        const sanitizedId = this.sanitizeResellerId(resellerId);
        const key = `reseller_${sanitizedId}`;
        
        console.log(`📱 Criando instância para reseller: ${resellerId} (sanitized: ${sanitizedId})`);

        const client = new Client({
            authStrategy: new LocalAuth({
                clientId: key,
                dataPath: process.env.SESSION_PATH || './sessions'
            }),
            puppeteer: {
                headless: true,
                args: [
                    '--no-sandbox',
                    '--disable-setuid-sandbox',
                    '--disable-dev-shm-usage',
                    '--disable-accelerated-2d-canvas',
                    '--no-first-run',
                    '--no-zygote',
                    '--disable-gpu'
                ]
            }
        });

        // Event: QR Code gerado
        client.on('qr', async (qr) => {
            console.log(`📷 QR Code gerado para ${resellerId}`);
            try {
                const qrBase64 = await qrcode.toDataURL(qr);
                this.qrCodes.set(key, qrBase64);
                
                // Atualizar no banco
                await db.updateSession(resellerId, {
                    status: 'connecting',
                    qr_code: qrBase64
                });
            } catch (err) {
                console.error('Erro ao gerar QR Code:', err);
            }
        });

        // Event: Cliente pronto
        client.on('ready', async () => {
            console.log(`✅ Cliente conectado: ${resellerId}`);
            this.qrCodes.delete(key);
            
            const info = client.info;
            await db.updateSession(resellerId, {
                status: 'connected',
                qr_code: null,
                phone_number: info.wid.user,
                profile_name: info.pushname,
                connected_at: new Date()
            });
        });

        // Event: Autenticação bem-sucedida
        client.on('authenticated', () => {
            console.log(`🔐 Autenticado: ${resellerId}`);
        });

        // Event: Falha na autenticação
        client.on('auth_failure', async (msg) => {
            console.error(`❌ Falha na autenticação ${resellerId}:`, msg);
            await db.updateSession(resellerId, {
                status: 'error'
            });
        });

        // Event: Desconectado
        client.on('disconnected', async (reason) => {
            console.log(`🔌 Desconectado ${resellerId}:`, reason);
            this.instances.delete(key);
            this.qrCodes.delete(key);
            
            await db.updateSession(resellerId, {
                status: 'disconnected',
                qr_code: null
            });
        });

        // Event: Mensagem recebida (para confirmação de leitura)
        client.on('message_ack', async (msg, ack) => {
            // 1 = enviada, 2 = recebida, 3 = lida
            const status = ack === 3 ? 'read' : ack === 2 ? 'delivered' : 'sent';
            await db.updateMessageStatus(msg.id.id, status);
        });

        this.instances.set(key, client);
        
        // Inicializar cliente
        try {
            await client.initialize();
        } catch (err) {
            console.error(`Erro ao inicializar ${resellerId}:`, err);
            this.instances.delete(key);
            throw err;
        }

        return client;
    }

    /**
     * Obter QR Code
     */
    getQRCode(resellerId) {
        const sanitizedId = this.sanitizeResellerId(resellerId);
        const key = `reseller_${sanitizedId}`;
        return this.qrCodes.get(key) || null;
    }

    /**
     * Verificar se está conectado
     */
    isConnected(resellerId) {
        const sanitizedId = this.sanitizeResellerId(resellerId);
        const key = `reseller_${sanitizedId}`;
        const client = this.instances.get(key);
        return client ? client.info !== undefined : false;
    }

    /**
     * Desconectar instância
     */
    async disconnect(resellerId) {
        const sanitizedId = this.sanitizeResellerId(resellerId);
        const key = `reseller_${sanitizedId}`;
        const client = this.instances.get(key);
        
        if (client) {
            await client.destroy();
            this.instances.delete(key);
            this.qrCodes.delete(key);
            
            await db.updateSession(resellerId, {
                status: 'disconnected',
                qr_code: null
            });
        }
    }

    /**
     * Destruir todas as instâncias
     */
    async destroyAll() {
        console.log('🧹 Destruindo todas as instâncias...');
        for (const [key, client] of this.instances) {
            try {
                await client.destroy();
            } catch (err) {
                console.error(`Erro ao destruir ${key}:`, err);
            }
        }
        this.instances.clear();
        this.qrCodes.clear();
    }

    /**
     * Obter contagem de instâncias
     */
    getInstancesCount() {
        return {
            total: this.instances.size,
            connected: Array.from(this.instances.values()).filter(c => c.info).length
        };
    }
}

module.exports = new InstanceManager();
