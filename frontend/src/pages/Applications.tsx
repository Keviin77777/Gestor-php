import { useEffect, useState } from 'react'
import { Plus, Search, Edit, Trash2, Smartphone } from 'lucide-react'
import toast from 'react-hot-toast'
import LoadingSpinner from '../components/LoadingSpinner'

interface Application {
  id: number
  name: string
  created_at: string
}

export default function Applications() {
  const [applications, setApplications] = useState<Application[]>([])
  const [loading, setLoading] = useState(true)
  const [searchTerm, setSearchTerm] = useState('')

  useEffect(() => {
    loadApplications()
  }, [])

  const loadApplications = async () => {
    try {
      const response = await fetch('/api-applications.php')
      const data = await response.json()
      if (data.success) {
        setApplications(data.applications || [])
      }
    } catch (error) {
      toast.error('Erro ao carregar aplicativos')
    } finally {
      setLoading(false)
    }
  }

  const filteredApplications = applications.filter((app) =>
    app.name.toLowerCase().includes(searchTerm.toLowerCase())
  )

  const handleDelete = async (id: number, name: string) => {
    if (window.confirm(`Tem certeza que deseja excluir o aplicativo "${name}"?`)) {
      try {
        const response = await fetch(`/api-applications.php?id=${id}`, { method: 'DELETE' })
        const data = await response.json()
        if (data.success) {
          toast.success('Aplicativo excluído!')
          loadApplications()
        }
      } catch (error) {
        toast.error('Erro ao excluir')
      }
    }
  }

  return (
    <div className="space-y-6">
      <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
          <h1 className="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white">Aplicativos</h1>
          <p className="text-sm md:text-base text-gray-600 dark:text-gray-400 mt-1">{filteredApplications.length} aplicativos</p>
        </div>
        <button className="flex items-center gap-2 px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg text-sm md:text-base w-full sm:w-auto justify-center">
          <Plus className="w-5 h-5" />
          Novo Aplicativo
        </button>
      </div>

      <div className="bg-white/80 dark:bg-gray-800/50 backdrop-blur-sm border border-gray-200 dark:border-gray-700/50 rounded-xl p-4">
        <div className="relative">
          <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5" />
          <input type="text" placeholder="Pesquisar aplicativos..." value={searchTerm} onChange={(e) => setSearchTerm(e.target.value)} className="w-full pl-10 pr-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500" />
        </div>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        {loading ? (
          <div className="col-span-full"><LoadingSpinner /></div>
        ) : filteredApplications.length === 0 ? (
          <div className="col-span-full text-center py-12 text-gray-500">Nenhum aplicativo encontrado</div>
        ) : (
          filteredApplications.map((app) => (
            <div key={app.id} className="bg-white/80 dark:bg-gray-800/50 backdrop-blur-sm rounded-xl shadow-sm border border-gray-200 dark:border-gray-700/50 p-6 hover:shadow-md transition-all">
              <div className="flex items-start justify-between mb-4">
                <div className="p-3 bg-primary-100 dark:bg-primary-900/20 rounded-lg">
                  <Smartphone className="w-6 h-6 text-primary-600 dark:text-primary-400" />
                </div>
              </div>
              <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-2">{app.name}</h3>
              <p className="text-sm text-gray-600 dark:text-gray-400 mb-4">
                Criado em {new Date(app.created_at).toLocaleDateString('pt-BR')}
              </p>
              <div className="flex items-center gap-2">
                <button className="flex-1 flex items-center justify-center gap-2 px-3 py-2 bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/30">
                  <Edit className="w-4 h-4" />
                  Editar
                </button>
                <button onClick={() => handleDelete(app.id, app.name)} className="flex-1 flex items-center justify-center gap-2 px-3 py-2 bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 rounded-lg hover:bg-red-100 dark:hover:bg-red-900/30">
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
