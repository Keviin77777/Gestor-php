import api from './api'
import type { Server } from '@/types'

export const serverService = {
  async getAll() {
    const { data } = await api.get<{ success: boolean; servers: Server[] }>('/api-servers.php')
    return data
  },

  async create(server: Partial<Server>) {
    const { data } = await api.post<{ success: boolean; id: number; message: string }>('/api-servers.php', server)
    return data
  },

  async update(id: number, server: Partial<Server>) {
    const { data } = await api.put<{ success: boolean; message: string }>(`/api-servers.php?id=${id}`, server)
    return data
  },

  async delete(id: number) {
    const { data } = await api.delete<{ success: boolean; message: string }>(`/api-servers.php?id=${id}`)
    return data
  },
}
