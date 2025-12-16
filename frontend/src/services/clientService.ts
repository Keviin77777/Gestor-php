import api from './api'
import type { Client } from '@/types'

export const clientService = {
  async getAll() {
    const { data } = await api.get<{ success: boolean; clients: Client[]; total: number }>('/api-clients.php')
    return data
  },

  async create(client: Partial<Client>) {
    const { data } = await api.post<{ success: boolean; id: string; message: string }>('/api-clients.php', client)
    return data
  },

  async update(id: string, client: Partial<Client>) {
    const { data } = await api.put<{ success: boolean; message: string }>(`/api-clients.php?id=${id}`, client)
    return data
  },

  async delete(id: string) {
    const { data} = await api.delete<{ success: boolean; message: string }>(`/api-clients.php?id=${id}`)
    return data
  },
}
