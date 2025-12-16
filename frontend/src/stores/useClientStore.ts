import { create } from 'zustand'
import { clientService } from '@/services/clientService'
import type { Client } from '@/types'

interface ClientState {
  clients: Client[]
  loading: boolean
  error: string | null
  fetchClients: () => Promise<void>
  addClient: (client: Partial<Client>) => Promise<void>
  updateClient: (id: string, client: Partial<Client>) => Promise<void>
  deleteClient: (id: string) => Promise<void>
}

export const useClientStore = create<ClientState>((set, get) => ({
  clients: [],
  loading: false,
  error: null,

  fetchClients: async () => {
    set({ loading: true, error: null })
    try {
      const result = await clientService.getAll()
      if (result.success) {
        set({ clients: result.clients, loading: false })
      } else {
        throw new Error('Erro ao carregar clientes')
      }
    } catch (error: any) {
      set({ error: error.message, loading: false })
    }
  },

  addClient: async (client) => {
    set({ loading: true, error: null })
    try {
      const result = await clientService.create(client)
      if (result.success) {
        await get().fetchClients()
      } else {
        throw new Error('Erro ao adicionar cliente')
      }
    } catch (error: any) {
      set({ error: error.message, loading: false })
      throw error
    }
  },

  updateClient: async (id, client) => {
    set({ loading: true, error: null })
    try {
      const result = await clientService.update(id, client)
      if (result.success) {
        await get().fetchClients()
      } else {
        throw new Error('Erro ao atualizar cliente')
      }
    } catch (error: any) {
      set({ error: error.message, loading: false })
      throw error
    }
  },

  deleteClient: async (id) => {
    set({ loading: true, error: null })
    try {
      const result = await clientService.delete(id)
      if (result.success) {
        await get().fetchClients()
      } else {
        throw new Error('Erro ao deletar cliente')
      }
    } catch (error: any) {
      set({ error: error.message, loading: false })
      throw error
    }
  },
}))
