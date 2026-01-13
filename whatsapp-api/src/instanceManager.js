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
        this.initializingInstances = new Set(); // Rastrear instâncias em inicialização
        this.initializationQueue = []; // Fila de inicialização
        this.lastCleanup = Date.now();
        
        // Configurações
        this.MAX_CONCURRENT_INITIALIZATIONS = 5; // Máximo de inicializações simultâneas
        
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
        
        // 2. Limpar processos Chrome órfãos (apenas Linux - no Windows não fazemos isso)
        if (process.platform === 'linux') {
            try {
                // Matar apenas processos Chrome HEADLESS que estão rodando há mais de 2 horas
                exec('pkill -f "chrome.*--headless.*--disable-gpu" -o 7200 2>/dev/null || true');
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
     * Matar processos Chrome específicos de uma sessão (apenas Puppeteer headless)
     * IMPORTANTE: Não mata o navegador do usuário!
     */
    async killChromeProcesses(sessionKey) {
        return new Promise((resolve) => {
            // No Linux, podemos ser mais específicos
            if (process.platform === 'linux') {
                const commands = [
                    // Matar apenas processos com --headless ou --disable-gpu (Puppeteer)
                    `pkill -9 -f "chrome.*--headless.*${sessionKey}" 2>/dev/null || true`,
                    `pkill -9 -f "chromium.*--headless.*${sessionKey}" 2>/dev/null || true`,
                    `pkill -9 -f "chrome.*--disable-gpu.*${sessionKey}" 2>/dev/null || true`
                ];
                
                let completed = 0;
                commands.forEach(cmd => {
                    exec(cmd, () => {
                        completed++;
                        if (completed === commands.length) {
                            resolve();
                        }
                    });
                });
                
                // Timeout de segurança
                setTimeout(resolve, 3000);
            } else {
                // No Windows, NÃO matar processos Chrome para não fechar o navegador do usuário
                // O browser do Puppeteer será fechado pelo client.pupBrowser.close()
                console.log(`   ℹ️ Windows: pulando kill de processos Chrome (evita fechar navegador do usuário)`);
                resolve();
            }
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
     * Criar ou recuperar instância
     * Permite múltiplos revendedores conectarem simultaneamente
     */
    async getInstance(resellerId) {
        const sanitizedId = this.sanitizeResellerId(resellerId);
        const key = `reseller_${sanitizedId}`;
        
        // Se ESTE revendedor já está inicializando, aguardar (evita cliques duplos)
        if (this.initializingInstances.has(key)) {
            console.log(`⏳ ${resellerId} já está inicializando, aguardando...`);
            for (let i = 0; i < 60; i++) {
                await new Promise(r => setTimeout(r, 500));
                if (!this.initializingInstances.has(key)) {
                    if (this.instances.has(key)) {
                        const client = this.instances.get(key);
                        // Verificar se está funcional
                        try {
                            if (client.pupBrowser && client.pupBrowser.isConnected()) {
                                return client;
                            }
                        } catch (e) {
                            // Instância não funcional
                        }
                    }
                    break;
                }
            }
            // Se ainda está inicializando após 30s, forçar limpeza
            if (this.initializingInstances.has(key)) {
                console.log(`⚠️ Timeout aguardando ${resellerId}, forçando limpeza...`);
                await this.forceCleanup(resellerId);
            }
        }
        
        // Se já existe, verificar se está funcional
        if (this.instances.has(key)) {
            const client = this.instances.get(key);
            try {
                const hasBrowser = client.pupBrowser && client.pupBrowser.isConnected();
                const isReady = client.info !== undefined;
                
                if (hasBrowser && isReady) {
                    console.log(`♻️ Reutilizando instância existente para ${resellerId}`);
                    return client;
                }
                
                // Instância existe mas não está funcional - LIMPAR
                console.log(`⚠️ Instância de ${resellerId} não funcional (browser: ${hasBrowser}, ready: ${isReady}), limpando...`);
                await this.forceCleanup(resellerId);
            } catch (err) {
                console.log(`⚠️ Erro ao verificar instância de ${resellerId}: ${err.message}`);
                await this.forceCleanup(resellerId);
            }
        }

        // Criar nova instância
        return await this.createInstance(resellerId);
    }

    /**
     * Forçar limpeza completa de uma instância
     * IMPORTANTE: Esta função garante que tudo seja limpo para permitir reconexão
     */
    async forceCleanup(resellerId) {
        const sanitizedId = this.sanitizeResellerId(resellerId);
        const key = `reseller_${sanitizedId}`;
        
        console.log(`🧹 Forçando limpeza completa para ${resellerId}...`);
        
        // 1. Remover flags primeiro (evita race conditions)
        this.initializingInstances.delete(key);
        this.qrCodes.delete(key);
        
        // 2. Fechar cliente e browser
        const client = this.instances.get(key);
        if (client) {
            try {
                // Remover todos os listeners para evitar eventos durante limpeza
                client.removeAllListeners();
                
                // Tentar logout primeiro (mais limpo)
                try {
                    await Promise.race([
                        client.logout(),
                        new Promise((_, reject) => setTimeout(() => reject(new Error('Logout timeout')), 5000))
                    ]);
                    console.log(`   ✅ Logout realizado: ${resellerId}`);
                } catch (logoutErr) {
                    // Logout falhou, continuar com fechamento forçado
                    console.log(`   ⚠️ Logout falhou, fechando forçadamente...`);
                }
                
                // Fechar browser
                if (client.pupBrowser) {
                    try {
                        // Fechar todas as páginas primeiro
                        const pages = await client.pupBrowser.pages().catch(() => []);
                        for (const page of pages) {
                            await page.close().catch(() => {});
                        }
                        
                        // Fechar browser
                        await client.pupBrowser.close().catch(() => {});
                        console.log(`   ✅ Browser fechado: ${resellerId}`);
                    } catch (browserErr) {
                        console.log(`   ⚠️ Erro ao fechar browser: ${browserErr.message}`);
                    }
                }
                
                // Tentar destroy
                try {
                    await Promise.race([
                        client.destroy(),
                        new Promise((_, reject) => setTimeout(() => reject(new Error('Destroy timeout')), 5000))
                    ]);
                } catch (destroyErr) {
                    // Ignorar erros de destroy
                }
            } catch (err) {
                console.log(`   ⚠️ Erro durante limpeza do cliente: ${err.message}`);
            }
        }
        
        // 3. Remover da memória
        this.instances.delete(key);
        
        // 4. Matar processos Chrome órfãos desta sessão
        await this.killChromeProcesses(key);
        
        // 5. Limpar arquivos de lock da sessão
        const sessionPath = path.join(process.env.SESSION_PATH || './sessions', `session-${key}`);
        await this.cleanSessionDirectory(sessionPath);
        
        // 6. Aguardar liberação completa de recursos
        await new Promise(r => setTimeout(r, 3000));
        
        console.log(`   ✅ Limpeza completa finalizada: ${resellerId}`);
    }

    /**
     * Criar nova instância
     */
    async createInstance(resellerId) {
        const sanitizedId = this.sanitizeResellerId(resellerId);
        const key = `reseller_${sanitizedId}`;
        
        // Marcar como inicializando
        this.initializingInstances.add(key);
        
        console.log(`📱 Criando nova instância para: ${resellerId}`);

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
                        '--js-flags=--max-old-space-size=256'
                    ],
                    timeout: 60000
                },
                qrMaxRetries: 5, // Mais tentativas de QR
                takeoverOnConflict: true,
                takeoverTimeoutMs: 10000
            });

            // Configurar eventos ANTES de inicializar
            this.setupClientEvents(client, resellerId, key);

            // Armazenar na memória
            this.instances.set(key, client);
            
            // Inicializar com timeout
            console.log(`   ⏳ Inicializando cliente...`);
            
            const initPromise = client.initialize();
            const timeoutPromise = new Promise((_, reject) => 
                setTimeout(() => reject(new Error('Timeout ao inicializar WhatsApp (90s)')), 90000)
            );
            
            await Promise.race([initPromise, timeoutPromise]);
            
            console.log(`   ✅ Instância inicializada com sucesso: ${resellerId}`);
            return client;
            
        } catch (err) {
            console.error(`   ❌ Erro ao criar instância ${resellerId}:`, err.message);
            
            // Limpar tudo em caso de erro
            this.instances.delete(key);
            this.qrCodes.delete(key);
            
            // Tentar matar processos órfãos
            await this.killChromeProcesses(key);
            
            // Limpar locks
            const sessionPath = path.join(process.env.SESSION_PATH || './sessions', `session-${key}`);
            await this.cleanSessionDirectory(sessionPath);
            
            // Atualizar status no banco
            try {
                await db.updateSession(resellerId, { status: 'error' });
            } catch (dbErr) {
                // Ignorar
            }
            
            throw err;
        } finally {
            // SEMPRE remover flag de inicialização
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
            // Verificar se tem info (está autenticado)
            const hasInfo = client.info !== undefined;
            
            // Verificar se o browser está conectado
            const hasBrowser = client.pupBrowser && client.pupBrowser.isConnected();
            
            // Verificar se a página principal existe
            let hasPage = false;
            try {
                const pages = client.pupBrowser?.pages();
                hasPage = pages && pages.length > 0;
            } catch (e) {
                hasPage = false;
            }
            
            return hasInfo && hasBrowser;
        } catch (err) {
            console.log(`⚠️ Erro ao verificar conexão de ${resellerId}: ${err.message}`);
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
