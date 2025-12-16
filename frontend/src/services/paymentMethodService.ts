import api from './api'

export const paymentMethodService = {
  async getConfig(method: string) {
    const { data } = await api.get(`/api-payment-methods.php?method=${method}`)
    return data
  },

  async saveConfig(method: string, config: any) {
    const { data } = await api.post('/api-payment-methods.php', { method, config })
    return data
  },

  async testConnection(testData: any) {
    const { data } = await api.post('/api-payment-methods.php?action=test', testData)
    return data
  },
}
