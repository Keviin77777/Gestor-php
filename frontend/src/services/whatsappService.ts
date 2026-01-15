import api from './api'
import type { WhatsAppTemplate } from '@/types'

export const whatsappService = {
  async getTemplates() {
    const { data } = await api.get<{ success: boolean; templates: WhatsAppTemplate[] }>('/api-whatsapp-templates.php')
    return data
  },

  async getQRCode() {
    const { data } = await api.get('/api-whatsapp-qr.php')
    return data
  },

  async getStatus() {
    const { data } = await api.get('/api-whatsapp-status.php')
    return data
  },

  async connect(instanceName: string, resellerId: string) {
    const { data } = await api.post('/api-whatsapp-native-connect.php', { instance_name: instanceName, reseller_id: resellerId })
    return data
  },

  async disconnect() {
    const { data } = await api.post('/api-whatsapp-disconnect.php')
    return data
  },
}
