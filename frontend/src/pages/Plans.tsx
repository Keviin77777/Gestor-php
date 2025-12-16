import { useEffect, useState } from 'react'
import { Plus, Search, Edit, Trash2, Calendar, DollarSign, Clock } from 'lucide-react'
import toast from 'react-hot-toast'
import LoadingSpinner from '../components/LoadingSpinner'

interface Plan {
  id: number
  name: string
  price: number
  duration_days: number
  status: 'active' | 'inactive'
  server_id?: number
  server_name?: string
  created_at: string
}

export default function Plans() {
  const [plans, setPlans] = useState<Plan[]>([])
  const [loading, setLoading] = useState(true)
  const [searchTerm, setSearchTerm] = useState('')
  const [serverFilter, setServerFilter] = useState<string>('all')

  useEffect(() => {
    loadPlans()
  }, [])

  const loadPlans = async () => {
    try {
      const response = await fetch('/api-plans.php')
      const data = await response.json()
      if (data.success) {
        setPlans(data.plans || [])
      }
    } catch (error) {
      toast.error('Erro ao carregar planos')
    } finally {
      setLoading(false)
    }
  }

  const filteredPlans = plans.filter((plan) => {
    const matchesSearch = plan.name.toLowerCase().includes(searchTerm.toLowerCase())
    const matchesServer = serverFilter === 'all' || plan.server_name === serverFilter
    return matchesSearch && matchesServer
  })

  const uniqueServers = Array.from(new Set(plans.map(p => p.server_name).filter(Boolean)))

  const handleDelete = async (id: number, name: string) => {
    if (window.confirm(`Tem certeza que deseja excluir o plano "${name}"?`)) {
      try {
        const response = await fetch(`/api-plans.php?id=${id}`, { method: 'DELETE' })
        const data = await response.json()
        if (data.success) {
          toast.success('Plano excluído com sucesso!')
          loadPlans()
        } else {
          toast.error('Erro ao excluir plano')
        }
      } catch (error) {
        toast.error('Erro ao excluir plano')
      }
    }
  }

  const formatMoney = (value: number) => {
    return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value)
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
          <h1 className="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white">Planos</h1>
          <p className="text-sm md:text-base text-gray-600 dark:text-gray-400 mt-1">
            {filteredPlans.length} {filteredPlans.length === 1 ? 'plano' : 'planos'}
          </p>
        </div>
        <button className="flex items-center gap-2 px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-colors text-sm md:text-base w-full sm:w-auto justify-center">
          <Plus className="w-5 h-5" />
          Novo Plano
        </button>
      </div>

      {/* Filters */}
      <div className="bg-white/80 dark:bg-gray-800/50 backdrop-blur-sm border border-gray-200 dark:border-gray-700/50 rounded-xl p-4">
        <div className="flex flex-col lg:flex-row gap-4">
          <div className="flex-1 relative">
            <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5" />
            <input type="text" placeholder="Pesquisar planos..." value={searchTerm} onChange={(e) => setSearchTerm(e.target.value)} className="w-full pl-10 pr-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent" />
          </div>
          <select value={serverFilter} onChange={(e) => setServerFilter(e.target.value)} className="px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500">
            <option value="all">Todos os servidores</option>
            {uniqueServers.map(server => <option key={server} value={server}>{server}</option>)}
          </select>
          {(searchTerm || serverFilter !== 'all') && (
            <button onClick={() => { setSearchTerm(''); setServerFilter('all') }} className="px-4 py-2.5 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">
              Limpar
            </button>
          )}
        </div>
      </div>

      {/* Plans Grid */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        {loading ? (
          <div className="col-span-full"><LoadingSpinner /></div>
        ) : filteredPlans.length === 0 ? (
          <div className="col-span-full text-center py-12 text-gray-500">Nenhum plano encontrado</div>
        ) : (
          filteredPlans.map((plan) => (
            <div key={plan.id} className="bg-white/80 dark:bg-gray-800/50 backdrop-blur-sm rounded-xl shadow-sm border border-gray-200 dark:border-gray-700/50 p-6 hover:shadow-md transition-all">
              <div className="flex items-start justify-between mb-4">
                <div className="flex-1">
                  <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-1">{plan.name}</h3>
                  {plan.server_name && (
                    <span className="inline-block px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400">
                      {plan.server_name}
                    </span>
                  )}
                </div>
                <span className={`px-2 py-1 text-xs font-medium rounded-full ${plan.status === 'active' ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400' : 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400'}`}>
                  {plan.status === 'active' ? 'Ativo' : 'Inativo'}
                </span>
              </div>

              <div className="space-y-3 mb-4">
                <div className="flex items-center gap-2 text-gray-600 dark:text-gray-400">
                  <DollarSign className="w-4 h-4" />
                  <span className="text-sm">Valor:</span>
                  <span className="font-semibold text-gray-900 dark:text-white">{formatMoney(plan.price)}</span>
                </div>
                <div className="flex items-center gap-2 text-gray-600 dark:text-gray-400">
                  <Clock className="w-4 h-4" />
                  <span className="text-sm">Duração:</span>
                  <span className="font-semibold text-gray-900 dark:text-white">{plan.duration_days} dias</span>
                </div>
                <div className="flex items-center gap-2 text-gray-600 dark:text-gray-400">
                  <Calendar className="w-4 h-4" />
                  <span className="text-sm">Criado:</span>
                  <span className="text-sm text-gray-900 dark:text-white">{new Date(plan.created_at).toLocaleDateString('pt-BR')}</span>
                </div>
              </div>

              <div className="flex items-center gap-2 pt-4 border-t border-gray-200 dark:border-gray-700">
                <button className="flex-1 flex items-center justify-center gap-2 px-3 py-2 bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/30 transition-colors">
                  <Edit className="w-4 h-4" />
                  Editar
                </button>
                <button onClick={() => handleDelete(plan.id, plan.name)} className="flex-1 flex items-center justify-center gap-2 px-3 py-2 bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 rounded-lg hover:bg-red-100 dark:hover:bg-red-900/30 transition-colors">
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
