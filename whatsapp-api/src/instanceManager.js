const { Client, LocalAuth } = require('whatsapp-web.js');
const qrcode = require('qrcode');
const db = require('./database');
const fs = require('fs');
const path = require('path');

class InstanceManager {
    constructor() {
        this.instances = new Map();
        this.qrCodes = new Map();
        this.cleaningInProgress = new Set(); // Evitar limpeza simultânea
    }

    /**
     * Sanitizar resellerId para usar como clientId
     * Remove caracteres não permitidos (apenas alfanuméricos, underscore e hífen)
     */
    sanitizeResellerId(resellerId) {
        return resellerId.toString().replace(/[^a-zA-Z0-9_-]/g, '_');
    }

    /**
     * Limpar arquivo com retry logic (Windows-safe)
     */
    async safeUnlink(filePath, retries = 5, delay = 500) {
        for (let i = 0; i < retries; i++) {
            try {
                if (fs.existsSync(filePath)) {
                    fs.unlinkSync(filePath);
                    return true;
                }
                return true; // Arquivo não existe, considerado limpo
            } catch (err) {
                if (i === retries - 1) {
                    // Última tentativa falhou
                    return false;
                }
                // Aguardar antes de tentar novamente
                await new Promise(resolve => setTimeout(resolve, delay));
            }
        }
        return false;
    }

    /**
     * Limpar apenas locks problemáticos (Windows-safe)
     * NÃO remove arquivos críticos como Local Storage, Session Storage, IndexedDB
     */
    async cleanSessionLocks(sessionPath) {
        if (!fs.existsSync(sessionPath)) {
            return true;
        }

        const isWindows = process.platform === 'win32';
        const baseDelay = isWindows ? 1500 : 800;

        // Apenas limpar locks que causam travamento, NÃO arquivos críticos
        const lockFiles = [
            path.join(sessionPath, 'SingletonLock'),
            path.join(sessionPath, 'SingletonSocket'),
            path.join(sessionPath, 'lockfile'),
            path.join(sessionPath, 'Default', 'chrome_debug.log') // Log não é crítico
        ];

        // Limpar apenas locks problemáticos
        for (const file of lockFiles) {
            await this.safeUnlink(file, 3, 300);
        }

        // Aguardar liberação de recursos
        await new Promise(resolve => setTimeout(resolve, baseDelay));

        return true;
    }

    /**
     * Limpar diretório de sessão completamente (apenas quando necessário)
     * Usado após desconexão completa ou em caso de erro crítico
     */
    async cleanSessionDirectory(sessionPath, maxRetries = 3) {
        if (!fs.existsSync(sessionPath)) {
            return true;
        }

        const isWindows = process.platform === 'win32';
        const baseDelay = isWindows ? 2000 : 1000;

        // Primeiro, limpar locks
        await this.cleanSessionLocks(sessionPath);

        // Aguardar liberação de recursos
        await new Promise(resolve => setTimeout(resolve, baseDelay));

        // Tentar remover diretório completamente apenas se não houver arquivos críticos
        // Mas na prática, é melhor deixar o LocalAuth gerenciar isso
        return true;
    }

    /**
     * Criar ou recuperar instância
     */
    async getInstance(resellerId) {
        const sanitizedId = this.sanitizeResellerId(resellerId);
        const key = `reseller_${sanitizedId}`;
        
        // Se já existe, verificar se está em bom estado
        if (this.instances.has(key)) {
            const client = this.instances.get(key);
            try {
                // Verificar se o cliente está funcional
                const isReady = client.info !== undefined;
                let hasBrowser = false;
                
                try {
                    hasBrowser = client.pupBrowser && client.pupBrowser.isConnected();
                } catch (e) {
                    hasBrowser = false;
                }
                
                if (isReady && hasBrowser) {
                    console.log(`♻️ Reutilizando instância existente para ${resellerId}`);
                    return client;
                } else {
                    console.log(`⚠️ Instância existente não está pronta (ready: ${isReady}, browser: ${hasBrowser}), removendo...`);
                    // Remover instância inválida
                    this.instances.delete(key);
                    this.qrCodes.delete(key);
                }
            } catch (err) {
                console.log(`⚠️ Instância existente está travada, removendo: ${err.message}`);
                // Remover instância travada
                this.instances.delete(key);
                this.qrCodes.delete(key);
            }
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

        // NÃO limpar sessão antes de criar instância
        // O LocalAuth do whatsapp-web.js gerencia a sessão automaticamente
        // Limpar antes pode causar "Execution context was destroyed"
        // Apenas limpar locks após desconexão completa

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
            
            // Fechar browser se ainda estiver aberto
            if (client.pupBrowser) {
                try {
                    await client.pupBrowser.close();
                } catch (err) {
                    // Ignorar erro
                }
            }
            
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
        
        // Inicializar cliente com tratamento de erros robusto
        try {
            await client.initialize();
        } catch (err) {
            console.error(`❌ Erro ao inicializar ${resellerId}:`, err.message);
            
            // Limpar instância da memória
            this.instances.delete(key);
            this.qrCodes.delete(key);
            
            // Se o erro for relacionado a contexto destruído, pode ser que a sessão esteja corrompida
            // Tentar limpar locks e aguardar mais tempo antes de retentar
            if (err.message.includes('Execution context was destroyed') || 
                err.message.includes('Protocol error')) {
                console.log(`⚠️ Erro de contexto destruído detectado, limpando locks e aguardando...`);
                try {
                    const sessionPath = path.join(process.env.SESSION_PATH || './sessions', `session-${key}`);
                    await this.cleanSessionLocks(sessionPath);
                    // Aguardar mais tempo para garantir liberação
                    await new Promise(resolve => setTimeout(resolve, 2000));
                } catch (cleanErr) {
                    console.log(`⚠️ Erro ao limpar locks após falha: ${cleanErr.message}`);
                }
            }
            
            // Atualizar status no banco
            try {
                await db.updateSession(resellerId, {
                    status: 'error'
                });
            } catch (dbErr) {
                console.log(`⚠️ Erro ao atualizar status no banco: ${dbErr.message}`);
            }
            
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
     * Desconectar instância de forma segura (Windows-safe)
     */
    async disconnect(resellerId) {
        const sanitizedId = this.sanitizeResellerId(resellerId);
        const key = `reseller_${sanitizedId}`;
        const client = this.instances.get(key);
        
        if (!client) {
            console.log(`ℹ️ Nenhuma instância ativa para ${resellerId}`);
            return;
        }

        // Evitar limpeza simultânea
        if (this.cleaningInProgress.has(key)) {
            console.log(`⏳ Limpeza já em progresso para ${resellerId}, aguardando...`);
            // Aguardar limpeza anterior terminar
            while (this.cleaningInProgress.has(key)) {
                await new Promise(resolve => setTimeout(resolve, 500));
            }
            return;
        }

        this.cleaningInProgress.add(key);

        try {
            console.log(`🔌 Desconectando instância: ${resellerId}`);
            
            // 1. Remover listeners para evitar eventos durante desconexão
            client.removeAllListeners();
            
            // 2. Fechar todas as páginas primeiro
            try {
                const pages = await client.pupBrowser?.pages();
                if (pages && pages.length > 0) {
                    await Promise.all(pages.map(page => page.close().catch(() => {})));
                }
            } catch (e) {
                // Ignorar erro
            }

            // 3. Fechar browser completamente (mais seguro no Windows)
            if (client.pupBrowser) {
                try {
                    await client.pupBrowser.close();
                    console.log(`🌐 Browser fechado: ${resellerId}`);
                    
                    // Aguardar browser liberar arquivos (Windows precisa de mais tempo)
                    const isWindows = process.platform === 'win32';
                    const waitTime = isWindows ? 2500 : 1000;
                    await new Promise(resolve => setTimeout(resolve, waitTime));
                } catch (browserErr) {
                    console.log(`⚠️ Erro ao fechar browser: ${browserErr.message}`);
                }
            }
            
            // 4. Tentar destroy (pode falhar no Windows devido a EBUSY, mas tentamos)
            // NOTA: destroy() pode chamar logout() internamente, causando EBUSY no Windows
            // Por isso, fechamos o browser primeiro e tratamos o erro graciosamente
            try {
                if (client.destroy) {
                    // Tentar destroy com timeout para evitar travar
                    const destroyPromise = client.destroy();
                    const timeoutPromise = new Promise((_, reject) => 
                        setTimeout(() => reject(new Error('Destroy timeout')), 5000)
                    );
                    
                    await Promise.race([destroyPromise, timeoutPromise]);
                    console.log(`✅ Cliente destruído: ${resellerId}`);
                }
            } catch (destroyErr) {
                // Se der erro EBUSY, timeout ou qualquer outro, apenas avisar e continuar
                // O importante é que o browser já foi fechado e a instância será removida da memória
                if (destroyErr.message.includes('EBUSY') || 
                    destroyErr.message.includes('resource busy') ||
                    destroyErr.message.includes('unlink') ||
                    destroyErr.message.includes('timeout')) {
                    console.log(`📁 Destroy não pôde completar (arquivos travados/timeout), mas browser foi fechado. Continuando...`);
                } else {
                    console.log(`⚠️ Erro ao destruir: ${destroyErr.message}`);
                }
            }
            
        } catch (err) {
            console.error(`⚠️ Erro ao desconectar ${resellerId}:`, err.message);
            // Continuar mesmo com erro - forçar limpeza
        } finally {
            // SEMPRE remover da memória, mesmo se destroy falhar
            this.instances.delete(key);
            this.qrCodes.delete(key);
            console.log(`✅ Instância removida da memória: ${resellerId}`);
            
            // Limpar apenas locks problemáticos (não arquivos críticos)
            const sessionPath = path.join(process.env.SESSION_PATH || './sessions', `session-${key}`);
            try {
                await this.cleanSessionLocks(sessionPath);
                console.log(`🧹 Locks limpos do sistema de arquivos: ${resellerId}`);
            } catch (cleanErr) {
                console.log(`⚠️ Não foi possível limpar locks: ${cleanErr.message}`);
            }
            
            // Deletar sessão do banco completamente
            try {
                await db.deleteSession(resellerId);
                console.log(`✅ Sessão deletada do banco: ${resellerId}`);
            } catch (err) {
                console.error(`⚠️ Erro ao deletar sessão do banco:`, err.message);
            }

            // Remover flag de limpeza em progresso
            this.cleaningInProgress.delete(key);
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
