import api from './api'
import type { Invoice } from '@/types'

export const invoiceService = {
  async getAll(clientId?: string) {
    const url = clientId ? `/api-invoices.php?client_id=${clientId}` : '/api-invoices.php'
    const { data } = await api.get<{ success: boolean; invoices: Invoice[] }>(url)
    return data
  },

  async markAsPaid(id: string) {
    const { data } = await api.put<{ success: boolean; message: string }>(`/api-invoices.php?id=${id}&action=mark-paid`)
    return data
  },

  async delete(id: string) {
    const { data } = await api.delete<{ success: boolean; message: string }>(`/api-invoices.php?id=${id}`)
    return data
  },
}
