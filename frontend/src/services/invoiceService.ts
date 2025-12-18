import api from './api'
import { Invoice } from '@/types'

export const invoiceService = {
  async getAll(): Promise<Invoice[]> {
    const response = await api.get('/api-invoices.php')
    return response.data.invoices || []
  },

  async getById(id: string): Promise<Invoice> {
    const response = await api.get(`/api-invoices.php?id=${id}`)
    return response.data
  },

  async create(invoice: Partial<Invoice>): Promise<Invoice> {
    const response = await api.post('/api-invoices.php', invoice)
    return response.data
  },

  async update(id: string, invoice: Partial<Invoice>): Promise<Invoice> {
    const response = await api.put(`/api-invoices.php?id=${id}`, invoice)
    return response.data
  },

  async delete(id: string): Promise<void> {
    await api.delete(`/api-invoices.php?id=${id}`)
  },

  async markAsPaid(id: string): Promise<Invoice> {
    const response = await api.put(`/api-invoices.php?id=${id}&action=mark-paid`)
    return response.data
  }
}
