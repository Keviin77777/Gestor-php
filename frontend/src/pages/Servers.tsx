import { useEffect, useState } from 'react'
import { Plus, Edit, Trash2, Server as ServerIcon } from 'lucide-react'
import { serverService } from '@/services/serverService'
import toast from 'react-hot-toast'
import type { Server } from '@/types'
import LoadingSpinner from '../components/LoadingSpinner'

export default function Servers() {
  const [servers, setServers] = useState<Server[]>([])
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    loadServers()
  }, [])

  const loadServers = async () => {
    try {
      const result = await serverService.getAll()
      if (result.success) {
        setServers(result.servers)
      }
    } catch (error) {
      toast.error('Erro ao carregar servidores')
    } finally {
      setLoading(false)
    }
  }

  const handleDelete = async (id: number) => {
    if (window.confirm('Tem certeza que deseja excluir este servidor?')) {
      try {
        await serverService.delete(id)
        toast.success('Servidor excluído!')
        loadServers()
      } catch (error) {
        toast.error('Erro ao excluir servidor')
      }
    }
  }

  return (
    <div className="space-y-6">
      <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
          <h1 className="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white">Servidores</h1>
          <p className="text-sm md:text-base text-gray-600 dark:text-gray-400 mt-1">Gerencie seus servidores</p>
        </div>
        <button className="flex items-center gap-2 px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg text-sm md:text-base w-full sm:w-auto justify-center">
          <Plus className="w-5 h-5" />
          Novo Servidor
        </button>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        {loading ? (
          <div className="col-span-full"><LoadingSpinner /></div>
        ) : servers.length === 0 ? (
          <div className="col-span-full text-center py-12 text-gray-500">Nenhum servidor cadastrado</div>
        ) : (
          servers.map((server) => (
            <div key={server.id} className="bg-white/80 dark:bg-gray-800/50 backdrop-blur-sm rounded-xl shadow-sm border border-gray-200 dark:border-gray-700/50 p-6">
              <div className="flex items-start justify-between mb-4">
                <div className="p-3 bg-primary-100 dark:bg-primary-900/20 rounded-lg">
                  <ServerIcon className="w-6 h-6 text-primary-600 dark:text-primary-400" />
                </div>
                <span className={`px-2 py-1 text-xs font-medium rounded-full ${
                  server.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                }`}>
                  {server.status === 'active' ? 'Ativo' : 'Inativo'}
                </span>
              </div>
              <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-2">{server.name}</h3>
              <p className="text-sm text-gray-600 dark:text-gray-400 mb-4">{server.url}</p>
              <div className="flex items-center gap-2">
                <button className="flex-1 flex items-center justify-center gap-2 px-3 py-2 bg-blue-50 text-blue-600 rounded-lg hover:bg-blue-100">
                  <Edit className="w-4 h-4" />
                  Editar
                </button>
                <button onClick={() => handleDelete(server.id)} className="flex-1 flex items-center justify-center gap-2 px-3 py-2 bg-red-50 text-red-600 rounded-lg hover:bg-red-100">
                  <Trash2 className="w-4 h-4" />
                  Excluir
                </button>
              </div>
            </div>
          ))
        )}
      </div>
    </div>
  )
}
