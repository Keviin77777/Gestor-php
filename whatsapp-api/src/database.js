const mysql = require('mysql2/promise');

class Database {
    constructor() {
        this.pool = null;
    }

    async connect() {
        if (!this.pool) {
            this.pool = mysql.createPool({
                host: process.env.DB_HOST || 'localhost',
                port: process.env.DB_PORT || 3306,
                user: process.env.DB_USER || 'root',
                password: process.env.DB_PASS || '',
                database: process.env.DB_NAME || 'ultragestor_php',
                waitForConnections: true,
                connectionLimit: 10,
                queueLimit: 0
            });
            console.log('✅ Conectado ao banco de dados');
        }
        return this.pool;
    }

    async query(sql, params = []) {
        const pool = await this.connect();
        const [rows] = await pool.execute(sql, params);
        return rows;
    }

    /**
     * Buscar ou criar sessão
     */
    async getOrCreateSession(resellerId) {
        const sessions = await this.query(
            'SELECT * FROM whatsapp_sessions WHERE reseller_id = ? ORDER BY created_at DESC LIMIT 1',
            [resellerId]
        );

        if (sessions.length > 0) {
            return sessions[0];
        }

        // Criar nova sessão
        const sessionId = `ws-${Date.now()}-${resellerId}`;
        const instanceName = `reseller_${resellerId}`;
        
        await this.query(
            `INSERT INTO whatsapp_sessions 
            (id, reseller_id, session_name, instance_name, status) 
            VALUES (?, ?, ?, ?, 'connecting')`,
            [sessionId, resellerId, instanceName, instanceName]
        );

        return {
            id: sessionId,
            reseller_id: resellerId,
            instance_name: instanceName,
            status: 'connecting'
        };
    }

    /**
     * Atualizar sessão
     */
    async updateSession(resellerId, data) {
        const session = await this.getOrCreateSession(resellerId);
        
        const fields = [];
        const values = [];
        
        for (const [key, value] of Object.entries(data)) {
            fields.push(`${key} = ?`);
            values.push(value);
        }
        
        values.push(session.id);
        
        await this.query(
            `UPDATE whatsapp_sessions SET ${fields.join(', ')}, updated_at = NOW() WHERE id = ?`,
            values
        );
    }

    /**
     * Criar registro de mensagem
     */
    async createMessage(resellerId, data) {
        const messageId = `msg-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;
        const session = await this.getOrCreateSession(resellerId);
        
        await this.query(
            `INSERT INTO whatsapp_messages 
            (id, reseller_id, session_id, phone_number, message, template_id, client_id, invoice_id, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')`,
            [
                messageId,
                resellerId,
                session.id,
                data.phone_number,
                data.message,
                data.template_id || null,
                data.client_id || null,
                data.invoice_id || null
            ]
        );

        return messageId;
    }

    /**
     * Atualizar status da mensagem
     */
    async updateMessageStatus(evolutionMessageId, status) {
        const statusMap = {
            'sent': 'sent_at',
            'delivered': 'delivered_at',
            'read': 'read_at'
        };

        const timestampField = statusMap[status];
        const updates = [`status = ?`];
        const values = [status];

        if (timestampField) {
            updates.push(`${timestampField} = NOW()`);
        }

        await this.query(
            `UPDATE whatsapp_messages SET ${updates.join(', ')} WHERE evolution_message_id = ?`,
            [...values, evolutionMessageId]
        );
    }

    /**
     * Atualizar mensagem com ID da Evolution
     */
    async updateMessageWithEvolutionId(messageId, evolutionMessageId) {
        await this.query(
            `UPDATE whatsapp_messages SET evolution_message_id = ?, status = 'sent', sent_at = NOW() WHERE id = ?`,
            [evolutionMessageId, messageId]
        );
    }

    /**
     * Marcar mensagem como falha
     */
    async markMessageAsFailed(messageId, error) {
        await this.query(
            `UPDATE whatsapp_messages SET status = 'failed', error_message = ? WHERE id = ?`,
            [error, messageId]
        );
    }

    /**
     * Buscar fila de mensagens pendentes
     */
    async getPendingMessages(resellerId, limit = 10) {
        return await this.query(
            `SELECT * FROM whatsapp_messages 
            WHERE reseller_id = ? AND status = 'pending' 
            ORDER BY created_at ASC LIMIT ?`,
            [resellerId, limit]
        );
    }
}

module.exports = new Database();
