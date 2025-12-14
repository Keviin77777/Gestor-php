#!/usr/bin/env node

/**
 * Script para limpar sessões travadas do WhatsApp
 * Útil quando há arquivos travados no Windows
 */

const fs = require('fs');
const path = require('path');

const sessionsPath = path.join(__dirname, 'sessions');

console.log('🧹 Limpando sessões travadas...\n');

if (!fs.existsSync(sessionsPath)) {
    console.log('✅ Nenhuma sessão encontrada');
    process.exit(0);
}

const sessions = fs.readdirSync(sessionsPath);

if (sessions.length === 0) {
    console.log('✅ Nenhuma sessão encontrada');
    process.exit(0);
}

let cleaned = 0;
let errors = 0;

for (const session of sessions) {
    const sessionPath = path.join(sessionsPath, session);
    
    if (!fs.statSync(sessionPath).isDirectory()) {
        continue;
    }
    
    console.log(`📁 Processando: ${session}`);
    
    // Arquivos problemáticos do Chrome
    const filesToClean = [
        path.join(sessionPath, 'Default', 'chrome_debug.log'),
        path.join(sessionPath, 'Default', 'Preferences'),
        path.join(sessionPath, 'SingletonLock'),
        path.join(sessionPath, 'SingletonSocket'),
        path.join(sessionPath, 'Default', 'Cookies'),
        path.join(sessionPath, 'Default', 'Network Persistent State')
    ];
    
    for (const file of filesToClean) {
        if (fs.existsSync(file)) {
            try {
                fs.unlinkSync(file);
                console.log(`   ✅ Removido: ${path.basename(file)}`);
                cleaned++;
            } catch (err) {
                console.log(`   ⚠️ Erro ao remover ${path.basename(file)}: ${err.message}`);
                errors++;
            }
        }
    }
}

console.log(`\n📊 Resumo:`);
console.log(`   ✅ Arquivos limpos: ${cleaned}`);
console.log(`   ⚠️ Erros: ${errors}`);

if (errors > 0) {
    console.log(`\n💡 Dica: Se ainda houver erros, feche a API e tente novamente`);
}
