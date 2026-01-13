#!/usr/bin/env node

/**
 * Script para limpar sessões travadas do WhatsApp
 * Execute: node clean-sessions.js
 * 
 * Útil quando:
 * - Novos revendedores não conseguem conectar
 * - QR Code não é gerado
 * - Erro "Target closed" ou "Execution context was destroyed"
 */

const fs = require('fs');
const path = require('path');
const { exec } = require('child_process');

const sessionsPath = path.join(__dirname, 'sessions');

console.log('🧹 Limpando sessões e processos travados...\n');

// 1. Matar processos Chrome órfãos
console.log('1️⃣ Matando processos Chrome órfãos...');

const killChrome = () => {
    return new Promise((resolve) => {
        if (process.platform === 'linux') {
            exec('pkill -9 -f "chrome.*--disable-gpu" 2>/dev/null; pkill -9 -f "chromium.*--disable-gpu" 2>/dev/null', () => {
                console.log('   ✅ Processos Chrome/Chromium finalizados (Linux)');
                resolve();
            });
        } else if (process.platform === 'win32') {
            exec('taskkill /F /IM chrome.exe /T 2>nul & taskkill /F /IM chromium.exe /T 2>nul', () => {
                console.log('   ✅ Processos Chrome finalizados (Windows)');
                resolve();
            });
        } else {
            console.log('   ℹ️ Sistema não suportado para kill automático');
            resolve();
        }
    });
};

// 2. Limpar arquivos de lock
const cleanLocks = () => {
    console.log('\n2️⃣ Limpando arquivos de lock...');
    
    if (!fs.existsSync(sessionsPath)) {
        console.log('   ℹ️ Nenhuma sessão encontrada');
        return { cleaned: 0, errors: 0 };
    }

    const sessions = fs.readdirSync(sessionsPath);
    let cleaned = 0;
    let errors = 0;

    for (const session of sessions) {
        const sessionPath = path.join(sessionsPath, session);
        
        if (!fs.statSync(sessionPath).isDirectory()) {
            continue;
        }
        
        console.log(`   📁 ${session}`);
        
        // Arquivos de lock que causam travamento
        const lockFiles = [
            'SingletonLock',
            'SingletonSocket', 
            'SingletonCookie',
            'lockfile',
            path.join('Default', 'chrome_debug.log'),
            path.join('Default', 'Network Persistent State')
        ];
        
        for (const lockFile of lockFiles) {
            const filePath = path.join(sessionPath, lockFile);
            if (fs.existsSync(filePath)) {
                try {
                    fs.unlinkSync(filePath);
                    console.log(`      ✅ ${lockFile}`);
                    cleaned++;
                } catch (err) {
                    console.log(`      ⚠️ ${lockFile} (em uso)`);
                    errors++;
                }
            }
        }
    }
    
    return { cleaned, errors };
};

// 3. Limpar cache do wwebjs se muito grande
const cleanCache = () => {
    console.log('\n3️⃣ Verificando cache...');
    
    const cachePath = path.join(__dirname, '.wwebjs_cache');
    if (fs.existsSync(cachePath)) {
        try {
            const stats = fs.statSync(cachePath);
            const sizeMB = Math.round(stats.size / 1024 / 1024);
            console.log(`   📦 Cache: ${sizeMB}MB`);
            
            // Se cache > 500MB, avisar
            if (sizeMB > 500) {
                console.log('   ⚠️ Cache muito grande! Considere limpar manualmente.');
            }
        } catch (err) {
            // Ignorar
        }
    } else {
        console.log('   ℹ️ Sem cache');
    }
};

// Executar limpeza
(async () => {
    await killChrome();
    
    // Aguardar processos morrerem
    await new Promise(r => setTimeout(r, 2000));
    
    const { cleaned, errors } = cleanLocks();
    cleanCache();
    
    console.log('\n' + '='.repeat(40));
    console.log('📊 RESUMO:');
    console.log(`   ✅ Arquivos limpos: ${cleaned}`);
    console.log(`   ⚠️ Arquivos em uso: ${errors}`);
    console.log('='.repeat(40));
    
    if (errors > 0) {
        console.log('\n💡 DICA: Se ainda houver erros:');
        console.log('   1. Pare a API WhatsApp (pm2 stop whatsapp)');
        console.log('   2. Execute este script novamente');
        console.log('   3. Inicie a API (pm2 start whatsapp)');
    } else {
        console.log('\n✅ Limpeza concluída! Pode reiniciar a API.');
    }
})();
