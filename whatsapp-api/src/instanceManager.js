const { Client, LocalAuth } = require('whatsapp-web.js');
const qrcode = require('qrcode');
const db = require('./database');
const fs = require('fs');
const path = require('path');
const { exec } = require('child_process');

class InstanceManager {
    constructor() {
        this.instances = new Map();
        this.qrCodes = new Map();
        this.initializingInstances = new Set(); // Evitar inicialização simultânea
        this.lastCleanup = Date.now();
        
        // Limpar processos Chrome órfãos periodicamente
        this.startCleanupInterval();
    }

    /**
     * Iniciar limpeza periódica de recursos
     */
    startCleanupInterval() {
        // A cada 30 minutos, verificar e limpar recursos
        setInterval(() => {
            this.cleanupOrphanedResources();
        }, 30 * 60 * 1000);
        
        console.log('🧹 Limpeza automática de recursos configurada (a cada 30 min)');
    }

    /**
     * Limpar processos Chrome órfãos e instâncias mortas
     */
    async cleanupOrphanedResources() {
        console.log('🧹 Executando limpeza de recursos órfãos...');
        
        // 1. Verificar instâncias mortas na memória
        for (const [key, client] of this.instances) {
            try {
                const hasBrowser = client.pupBrowser && client.pupBrowser.isConnected();
                if (!hasBrowser) {
                    console.log(`🗑️ Removendo instância morta: ${key}`);
                    this.instances.delete(key);
                    this.qrCodes.delete(key);
                }
            } catch (err) {
                console.log(`🗑️ Removendo instância com erro: ${key}`);
                this.instances.delete(key);
                this.qrCodes.delete(key);
            }
        }
        
        // 2. Limpar processos Chrome órfãos (Linux)
        if (process.platform === 'linux') {
            try {
                // Matar processos Chrome que estão rodando há mais de 2 horas sem parent
                exec('pkill -f "chrome.*--disable-gpu" -o 7200 2>/dev/null || true');
            } catch (err) {
                // Ignorar erros
            }
        }
        
        console.log(`✅ Limpeza concluída. Instâncias ativas: ${this.instances.size}`);
    }

    /**
     * Sanitizar resellerId para usar como clientId
     */
    sanitizeResellerId(resellerId) {
        return resellerId.toString().replace(/[^a-zA-Z0-9_-]/g, '_');
    }

    /**
     * Matar processos Chrome específicos de uma sessão
     */
    async killChromeProcesses(sessionKey) {
        return new Promise((resolve) => {
            if (process.platform === 'linux') {
                exec(`pkill -f "chrome.*${sessionKey}" 2>/dev/null || true`, () => resolve());
            } else if (process.platform === 'win32') {
                exec(`taskkill /F /IM chrome.exe /FI "WINDOWTITLE eq *${sessionKey}*" 2>nul || exit 0`, () => resolve());
            } else {
                resolve();
            }
            
            // Timeout de segurança
            setTimeout(resolve, 2000);
        });
    }

    /**
     * Limpar diretório de sessão com retry
     */
    async cleanSessionDirectory(sessionPath, maxRetries = 3) {
        if (!fs.existsSync(sessionPath)) {
            return true;
        }

        // Arquivos de lock que podem travar
        const lockFiles = [
            'SingletonLock',
            'SingletonSocket', 
            'SingletonCookie',
            'lockfile'
        ];

        for (const lockFile of lockFiles) {
            const lockPath = path.join(sessionPath, lockFile);
            for (let i = 0; i < maxRetries; i++) {
                try {
                    if (fs.existsSync(lockPath)) {
                        fs.unlinkSync(lockPath);
                    }
                    break;
                } catch (err) {
                    await new Promise(r => setTimeout(r, 500));
                }
            }
        }

        return true;
    }

    /**
     * Criar ou recuperar instância com proteção contra race conditions
     */
    async getInstance(resellerId) {
        const sanitizedId = this.sanitizeResellerId(resellerId);
        const key = `reseller_${sanitizedId}`;
        
        // Verificar se já está inicializando (evitar race condition)
        if (this.initializingInstances.has(key)) {
            console.log(`⏳ Instância ${resellerId} já está sendo inicializada, aguardando...`);
            // Aguardar até 30 segundos pela inicialização
            for (let i = 0; i < 60; i++) {
                await new Promise(r => setTimeout(r, 500));
                if (!this.initializingInstances.has(key)) {
                    if (this.instances.has(key)) {
                        return this.instances.get(key);
                    }
                    break;
                }
            }
        }
        
        // Se já existe e está funcional, reutilizar
        if (this.instances.has(key)) {
            const client = this.instances.get(key);
            try {
                const hasBrowser = client.pupBrowser && client.pupBrowser.isConnected();
                const isReady = client.info !== undefined;
                
                if (hasBrowser && isReady) {
                    console.log(`♻️ Reutilizando instância existente para ${resellerId}`);
                    return client;
                }
                
                // Instância existe mas não está funcional
                console.log(`⚠️ Instância existente não funcional, recriando...`);
                await this.forceCleanup(resellerId);
            } catch (err) {
                console.log(`⚠️ Erro ao verificar instância: ${err.message}`);
                await this.forceCleanup(resellerId);
            }
        }

        return await this.createInstance(resellerId);
    }

    /**
     * Forçar limpeza completa de uma instância
     */
    async forceCleanup(resellerId) {
        const sanitizedId = this.sanitizeResellerId(resellerId);
        const key = `reseller_${sanitizedId}`;
        
        console.log(`🧹 Forçando limpeza completa para ${resellerId}...`);
        
        // Remover da memória
        const client = this.instances.get(key);
        if (client) {
            try {
                client.removeAllListeners();
                if (client.pupBrowser) {
                    await client.pupBrowser.close().catch(() => {});
                }
            } catch (err) {
                // Ignorar
            }
        }
        
        this.instances.delete(key);
        this.qrCodes.delete(key);
        this.initializingInstances.delete(key);
        
        // Matar processos Chrome órfãos
        await this.killChromeProcesses(key);
        
        // Limpar locks da sessão
        const sessionPath = path.join(process.env.SESSION_PATH || './sessions', `session-${key}`);
        await this.cleanSessionDirectory(sessionPath);
        
        // Aguardar liberação de recursos
        await new Promise(r => setTimeout(r, 2000));
    }

    /**
     * Criar nova instância com proteções
     */
    async createInstance(resellerId) {
        const sanitizedId = this.sanitizeResellerId(resellerId);
        const key = `reseller_${sanitizedId}`;
        
        // Marcar como inicializando
        this.initializingInstances.add(key);
        
        console.log(`📱 Criando instância para reseller: ${resellerId}`);

        try {
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
                        '--disable-gpu',
                        '--disable-extensions',
                        '--disable-default-apps',
                        '--disable-translate',
                        '--disable-sync',
                        '--hide-scrollbars',
                        '--metrics-recording-only',
                        '--mute-audio',
                        '--no-default-browser-check',
                        '--safebrowsing-disable-auto-update',
                        // Limitar uso de memória
                        '--js-flags=--max-old-space-size=256',
                        '--single-process' // Importante para estabilidade em servidores
                    ],
                    timeout: 60000 // Timeout de 60 segundos para inicialização
                },
                qrMaxRetries: 3,
                takeoverOnConflict: true,
                takeoverTimeoutMs: 10000
            });

            // Configurar eventos
            this.setupClientEvents(client, resellerId, key);

            // Armazenar antes de inicializar
            this.instances.set(key, client);
            
            // Inicializar com timeout
            const initPromise = client.initialize();
            const timeoutPromise = new Promise((_, reject) => 
                setTimeout(() => reject(new Error('Timeout ao inicializar WhatsApp')), 90000)
            );
            
            await Promise.race([initPromise, timeoutPromise]);
            
            console.log(`✅ Instância inicializada: ${resellerId}`);
            return client;
            
        } catch (err) {
            console.error(`❌ Erro ao criar instância ${resellerId}:`, err.message);
            
            // Limpar tudo em caso de erro
            await this.forceCleanup(resellerId);
            
            // Atualizar status no banco
            try {
                await db.updateSession(resellerId, { status: 'error' });
            } catch (dbErr) {
                // Ignorar
            }
            
            throw err;
        } finally {
            // Sempre remover flag de inicialização
            this.initializingInstances.delete(key);
        }
    }

    /**
     * Configurar eventos do cliente
     */
    setupClientEvents(client, resellerId, key) {
        // QR Code gerado
        client.on('qr', async (qr) => {
            console.log(`📷 QR Code gerado para ${resellerId}`);
            try {
                const qrBase64 = await qrcode.toDataURL(qr);
                this.qrCodes.set(key, qrBase64);
                
                await db.updateSession(resellerId, {
                    status: 'connecting',
                    qr_code: qrBase64
                });
            } catch (err) {
                console.error('Erro ao gerar QR Code:', err.message);
            }
        });

        // Cliente pronto
        client.on('ready', async () => {
            console.log(`✅ Cliente conectado: ${resellerId}`);
            this.qrCodes.delete(key);
            
            try {
                const info = client.info;
                await db.updateSession(resellerId, {
                    status: 'connected',
                    qr_code: null,
                    phone_number: info?.wid?.user || null,
                    profile_name: info?.pushname || null,
                    connected_at: new Date()
                });
            } catch (err) {
                console.error('Erro ao atualizar sessão:', err.message);
            }
        });

        // Autenticado
        client.on('authenticated', () => {
            console.log(`🔐 Autenticado: ${resellerId}`);
        });

        // Falha na autenticação
        client.on('auth_failure', async (msg) => {
            console.error(`❌ Falha na autenticação ${resellerId}:`, msg);
            
            // Limpar sessão corrompida
            await this.forceCleanup(resellerId);
            
            await db.updateSession(resellerId, { status: 'error' });
        });

        // Desconectado
        client.on('disconnected', async (reason) => {
            console.log(`🔌 Desconectado ${resellerId}:`, reason);
            
            // Limpar recursos
            this.instances.delete(key);
            this.qrCodes.delete(key);
            
            // Fechar browser se ainda existir
            if (client.pupBrowser) {
                try {
                    await client.pupBrowser.close();
                } catch (err) {
                    // Ignorar
                }
            }
            
            await db.updateSession(resellerId, {
                status: 'disconnected',
                qr_code: null
            });
        });

        // Atualização de status de mensagem
        client.on('message_ack', async (msg, ack) => {
            try {
                const status = ack === 3 ? 'read' : ack === 2 ? 'delivered' : 'sent';
                await db.updateMessageStatus(msg.id.id, status);
            } catch (err) {
                // Ignorar erros de atualização de status
            }
        });

        // Erro no cliente
        client.on('error', (err) => {
            console.error(`⚠️ Erro no cliente ${resellerId}:`, err.message);
        });
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
        
        if (!client) return false;
        
        try {
            return client.info !== undefined && 
                   client.pupBrowser && 
                   client.pupBrowser.isConnected();
        } catch (err) {
            return false;
        }
    }

    /**
     * Desconectar instância
     */
    async disconnect(resellerId) {
        const sanitizedId = this.sanitizeResellerId(resellerId);
        const key = `reseller_${sanitizedId}`;
        
        console.log(`🔌 Desconectando instância: ${resellerId}`);
        
        await this.forceCleanup(resellerId);
        
        // Deletar sessão do banco
        try {
            await db.deleteSession(resellerId);
            console.log(`✅ Sessão deletada do banco: ${resellerId}`);
        } catch (err) {
            console.error(`⚠️ Erro ao deletar sessão:`, err.message);
        }
    }

    /**
     * Destruir todas as instâncias
     */
    async destroyAll() {
        console.log('🧹 Destruindo todas as instâncias...');
        
        const promises = [];
        for (const [key] of this.instances) {
            const resellerId = key.replace('reseller_', '');
            promises.push(this.forceCleanup(resellerId));
        }
        
        await Promise.allSettled(promises);
        
        this.instances.clear();
        this.qrCodes.clear();
        this.initializingInstances.clear();
        
        console.log('✅ Todas as instâncias destruídas');
    }

    /**
     * Obter contagem de instâncias
     */
    getInstancesCount() {
        let connected = 0;
        
        for (const [, client] of this.instances) {
            try {
                if (client.info && client.pupBrowser?.isConnected()) {
                    connected++;
                }
            } catch (err) {
                // Ignorar
            }
        }
        
        return {
            total: this.instances.size,
            connected,
            initializing: this.initializingInstances.size
        };
    }
}

module.exports = new InstanceManager();
