import { useEffect, useState } from 'react'
import { Plus, Search, Edit, Trash2 } from 'lucide-react'
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
  const [statusFilter, setStatusFilter] = useState<string>('all')
  const [itemsPerPage, setItemsPerPage] = useState(10)
  const [currentPage, setCurrentPage] = useState(1)
  const [showForm, setShowForm] = useState(false)
  const [editingPlan, setEditingPlan] = useState<Plan | null>(null)
  const [servers, setServers] = useState<any[]>([])
  const [saving, setSaving] = useState(false)
  const [formData, setFormData] = useState<Partial<Plan>>({
    name: '',
    price: 0,
    duration_days: 30,
    status: 'active',
    server_id: undefined
  })

  useEffect(() => {
    loadPlans()
    loadServers()
  }, [])

  useEffect(() => {
    if (editingPlan) {
      setFormData(editingPlan)
      setShowForm(true)
    } else if (!showForm) {
      setFormData({
        name: '',
        price: 0,
        duration_days: 30,
        status: 'active',
        server_id: undefined
      })
    }
  }, [editingPlan, showForm])

  const loadPlans = async () => {
    try {
      const response = await fetch('/api-plans.php', {
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('token')}`
        }
      })
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

  const loadServers = async () => {
    try {
      const response = await fetch('/api-servers.php', {
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('token')}`
        }
      })
      const data = await response.json()
      if (data.success) {
        setServers(data.servers || [])
      }
    } catch (error) {
      // Erro ao carregar servidores
    }
  }

  const filteredPlans = plans.filter((plan) => {
    const matchesSearch = plan.name.toLowerCase().includes(searchTerm.toLowerCase())
    const matchesServer = serverFilter === 'all' || plan.server_name === serverFilter
    const matchesStatus = statusFilter === 'all' || plan.status === statusFilter
    return matchesSearch && matchesServer && matchesStatus
  })

  // Paginação
  const totalPages = Math.ceil(filteredPlans.length / itemsPerPage)
  const startIndex = (currentPage - 1) * itemsPerPage
  const endIndex = startIndex + itemsPerPage
  const paginatedPlans = filteredPlans.slice(startIndex, endIndex)

  // Reset para página 1 quando filtros mudarem
  useEffect(() => {
    setCurrentPage(1)
  }, [searchTerm, serverFilter, statusFilter, itemsPerPage])

  const uniqueServers = Array.from(new Set(plans.map(p => p.server_name).filter(Boolean)))

  const handleSave = async (e: React.FormEvent) => {
    e.preventDefault()
    setSaving(true)
    try {
      const method = editingPlan ? 'PUT' : 'POST'
      const url = editingPlan ? `/api-plans.php?id=${editingPlan.id}` : '/api-plans.php'
      
      const response = await fetch(url, {
        method,
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('token')}`,
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(formData)
      })

      const data = await response.json()
      if (data.success) {
        toast.success(editingPlan ? 'Plano atualizado com sucesso!' : 'Plano criado com sucesso!')
        setShowForm(false)
        setEditingPlan(null)
        loadPlans()
      } else {
        toast.error(data.error || 'Erro ao salvar plano')
      }
    } catch (error) {
      toast.error('Erro ao salvar plano')
    } finally {
      setSaving(false)
    }
  }

  const handleCancel = () => {
    setShowForm(false)
    setEditingPlan(null)
  }

  const handleDelete = async (id: number, name: string) => {
    if (window.confirm(`Tem certeza que deseja excluir o plano "${name}"?`)) {
      try {
        const response = await fetch(`/api-plans.php?id=${id}`, {
          method: 'DELETE',
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`
          }
        })
        const data = await response.json()
        if (data.success) {
          toast.success('Plano excluído com sucesso!')
          loadPlans()
        } else {
          toast.error(data.error || 'Erro ao excluir plano')
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
        <button 
          onClick={() => setShowForm(true)}
          className="flex items-center gap-2 px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-colors text-sm md:text-base w-full sm:w-auto justify-center"
        >
          <Plus className="w-5 h-5" />
          Novo Plano
        </button>
      </div>

      {showForm && (
        <div className="bg-white/60 dark:bg-gray-800/30 backdrop-blur-md rounded-xl border border-gray-200/50 dark:border-gray-700/50 p-6 mb-6 animate-in slide-in-from-top duration-300">
          <h3 className="text-xl font-semibold text-gray-900 dark:text-white mb-6">
            {editingPlan ? 'Editar Plano' : 'Novo Plano'}
          </h3>
          <form onSubmit={handleSave}>
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
              <div>
                <label className="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">Nome *</label>
                <input 
                  type="text" 
                  required 
                  value={formData.name} 
                  onChange={(e) => setFormData({ ...formData, name: e.target.value })} 
                  className="w-full px-4 py-2.5 rounded-lg border border-gray-200 dark:border-gray-700/50 bg-white/80 dark:bg-gray-900/30 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all" 
                  placeholder="Ex: Plano Básico"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">Servidor *</label>
                <select 
                  value={formData.server_id || ''} 
                  onChange={(e) => setFormData({ ...formData, server_id: Number(e.target.value) })} 
                  className="w-full px-4 py-2.5 rounded-lg border border-gray-200 dark:border-gray-700/50 bg-white/80 dark:bg-gray-900/30 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all"
                  required
                >
                  <option value="">Selecione um servidor</option>
                  {servers.map(server => (
                    <option key={server.id} value={server.id}>{server.name}</option>
                  ))}
                </select>
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">Valor (R$) *</label>
                <input 
                  type="number" 
                  required 
                  step="0.01" 
                  min="0"
                  value={formData.price} 
                  onChange={(e) => setFormData({ ...formData, price: parseFloat(e.target.value) })} 
                  className="w-full px-4 py-2.5 rounded-lg border border-gray-200 dark:border-gray-700/50 bg-white/80 dark:bg-gray-900/30 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all" 
                  placeholder="0.00"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">Duração (dias) *</label>
                <input 
                  type="number" 
                  required 
                  min="1"
                  value={formData.duration_days} 
                  onChange={(e) => setFormData({ ...formData, duration_days: parseInt(e.target.value) })} 
                  className="w-full px-4 py-2.5 rounded-lg border border-gray-200 dark:border-gray-700/50 bg-white/80 dark:bg-gray-900/30 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all" 
                  placeholder="30"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">Status</label>
                <select 
                  value={formData.status} 
                  onChange={(e) => setFormData({ ...formData, status: e.target.value as 'active' | 'inactive' })} 
                  className="w-full px-4 py-2.5 rounded-lg border border-gray-200 dark:border-gray-700/50 bg-white/80 dark:bg-gray-900/30 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all"
                >
                  <option value="active">Ativo</option>
                  <option value="inactive">Inativo</option>
                </select>
              </div>
            </div>
            <div className="flex justify-end gap-3">
              <button 
                type="button" 
                onClick={handleCancel} 
                className="px-6 py-2.5 text-gray-700 dark:text-gray-200 bg-white/80 dark:bg-gray-900/30 hover:bg-gray-100 dark:hover:bg-gray-700/50 rounded-lg transition-colors border border-gray-200 dark:border-gray-700/50"
              >
                Cancelar
              </button>
              <button 
                type="submit" 
                disabled={saving} 
                className="px-6 py-2.5 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-colors disabled:opacity-50 shadow-sm"
              >
                {saving ? 'Salvando...' : 'Salvar'}
              </button>
            </div>
          </form>
        </div>
      )}

      {/* Filters */}
      <div className="bg-white/60 dark:bg-gray-800/30 backdrop-blur-md rounded-xl border border-gray-200/50 dark:border-gray-700/50 p-6 mb-6">
        <div className="flex flex-col gap-4">
          {/* Linha 1: Busca e Filtros */}
          <div className="flex flex-col sm:flex-row gap-4">
            <div className="flex-1 relative">
              <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5" />
              <input
                type="text"
                placeholder="Buscar planos..."
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                className="w-full pl-10 pr-4 py-2.5 rounded-lg border border-gray-200 dark:border-gray-700/50 bg-white/80 dark:bg-gray-900/30 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all"
              />
            </div>
            <select
              value={serverFilter}
              onChange={(e) => setServerFilter(e.target.value)}
              className="px-4 py-2.5 rounded-lg border border-gray-200 dark:border-gray-700/50 bg-white/80 dark:bg-gray-900/30 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all"
            >
              <option value="all" className="bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100">Todos os servidores</option>
              {uniqueServers.map(server => <option key={server} value={server} className="bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100">{server}</option>)}
            </select>
            <select
              value={statusFilter}
              onChange={(e) => setStatusFilter(e.target.value)}
              className="px-4 py-2.5 rounded-lg border border-gray-200 dark:border-gray-700/50 bg-white/80 dark:bg-gray-900/30 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all"
            >
              <option value="all" className="bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100">Todos os status</option>
              <option value="active" className="bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100">Ativos</option>
              <option value="inactive" className="bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100">Inativos</option>
            </select>
          </div>

          {/* Linha 2: Items por página e Limpar */}
          <div className="flex items-center justify-between">
            <div className="flex items-center gap-2">
              <span className="text-sm text-gray-600 dark:text-gray-400">Mostrar:</span>
              <select
                value={itemsPerPage}
                onChange={(e) => setItemsPerPage(Number(e.target.value))}
                className="px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700/50 bg-white/80 dark:bg-gray-900/30 text-gray-900 dark:text-gray-100 text-sm focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all"
              >
                <option value={10} className="bg-white dark:bg-gray-800">10</option>
                <option value={30} className="bg-white dark:bg-gray-800">30</option>
                <option value={50} className="bg-white dark:bg-gray-800">50</option>
                <option value={100} className="bg-white dark:bg-gray-800">100</option>
              </select>
              <span className="text-sm text-gray-600 dark:text-gray-400">por página</span>
            </div>

            {(searchTerm || serverFilter !== 'all' || statusFilter !== 'all') && (
              <button 
                onClick={() => { 
                  setSearchTerm(''); 
                  setServerFilter('all'); 
                  setStatusFilter('all');
                }} 
                className="px-4 py-2 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors"
              >
                Limpar Filtros
              </button>
            )}
          </div>
        </div>
      </div>

      {/* Plans Table */}
      <div className="bg-white/60 dark:bg-gray-800/30 backdrop-blur-md rounded-xl border border-gray-200/50 dark:border-gray-700/50 overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full">
            <thead className="bg-gray-50/50 dark:bg-gray-900/20 border-b border-gray-200/50 dark:border-gray-700/30">
              <tr>
                <th className="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                  Nome
                </th>
                <th className="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                  Servidor
                </th>
                <th className="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                  Valor
                </th>
                <th className="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                  Duração
                </th>
                <th className="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                  Criado em
                </th>
                <th className="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                  Status
                </th>
                <th className="px-6 py-4 text-right text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                  Ações
                </th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-200 dark:divide-gray-700/30">
              {loading ? (
                <tr>
                  <td colSpan={7} className="px-6 py-4">
                    <LoadingSpinner />
                  </td>
                </tr>
              ) : paginatedPlans.length === 0 ? (
                <tr>
                  <td colSpan={7} className="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                    Nenhum plano encontrado
                  </td>
                </tr>
              ) : (
                paginatedPlans.map((plan) => (
                  <tr key={plan.id} className="hover:bg-white/40 dark:hover:bg-gray-700/20 transition-colors">
                    <td className="px-6 py-4 whitespace-nowrap">
                      <span className="px-2 py-1 text-xs font-medium rounded bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-white">
                        {plan.name}
                      </span>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap">
                      {plan.server_name ? (
                        <span className="px-2 py-1 text-xs font-medium rounded bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400">
                          {plan.server_name}
                        </span>
                      ) : (
                        <span className="px-2 py-1 text-xs font-medium rounded bg-gray-100 text-gray-500 dark:bg-gray-900/20 dark:text-gray-500">
                          Sem servidor
                        </span>
                      )}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap">
                      <span className="px-2 py-1 text-xs font-medium rounded bg-emerald-100 text-emerald-800 dark:bg-emerald-900/20 dark:text-emerald-400">
                        {formatMoney(plan.price)}
                      </span>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap">
                      <span className="px-2 py-1 text-xs font-medium rounded bg-purple-100 text-purple-800 dark:bg-purple-900/20 dark:text-purple-400">
                        {plan.duration_days} dias
                      </span>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap">
                      <span className="px-2 py-1 text-xs font-medium rounded bg-orange-100 text-orange-800 dark:bg-orange-900/20 dark:text-orange-400">
                        {new Date(plan.created_at).toLocaleDateString('pt-BR')}
                      </span>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap">
                      <span className={`px-2 py-1 text-xs font-medium rounded ${plan.status === 'active' ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400' : 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400'}`}>
                        {plan.status === 'active' ? 'Ativo' : 'Inativo'}
                      </span>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                      <div className="flex items-center justify-end gap-1.5">
                        <button 
                          onClick={() => setEditingPlan(plan)}
                          className="p-2 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-all hover:scale-110" 
                          title="Editar Plano"
                        >
                          <Edit className="w-4 h-4" />
                        </button>
                        <button 
                          onClick={() => handleDelete(plan.id, plan.name)} 
                          className="p-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-all hover:scale-110" 
                          title="Excluir Plano"
                        >
                          <Trash2 className="w-4 h-4" />
                        </button>
                      </div>
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>

        {/* Paginação */}
        {totalPages > 1 && (
          <div className="px-6 py-4 bg-gray-50/50 dark:bg-gray-900/20 border-t border-gray-200/50 dark:border-gray-700/30">
            <div className="flex items-center justify-between">
              <div className="text-sm text-gray-600 dark:text-gray-400">
                Mostrando {startIndex + 1} a {Math.min(endIndex, filteredPlans.length)} de {filteredPlans.length} planos
              </div>
              <div className="flex items-center gap-2">
                <button
                  onClick={() => setCurrentPage(prev => Math.max(1, prev - 1))}
                  disabled={currentPage === 1}
                  className="px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 text-sm font-medium hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed transition-all"
                >
                  Anterior
                </button>
                
                <div className="flex items-center gap-1">
                  {Array.from({ length: Math.min(5, totalPages) }, (_, i) => {
                    let pageNum
                    if (totalPages <= 5) {
                      pageNum = i + 1
                    } else if (currentPage <= 3) {
                      pageNum = i + 1
                    } else if (currentPage >= totalPages - 2) {
                      pageNum = totalPages - 4 + i
                    } else {
                      pageNum = currentPage - 2 + i
                    }
                    
                    return (
                      <button
                        key={pageNum}
                        onClick={() => setCurrentPage(pageNum)}
                        className={`px-3 py-1.5 rounded-lg text-sm font-medium transition-all ${
                          currentPage === pageNum
                            ? 'bg-primary-600 text-white'
                            : 'border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700'
                        }`}
                      >
                        {pageNum}
                      </button>
                    )
                  })}
                </div>

                <button
                  onClick={() => setCurrentPage(prev => Math.min(totalPages, prev + 1))}
                  disabled={currentPage === totalPages}
                  className="px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 text-sm font-medium hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed transition-all"
                >
                  Próxima
                </button>
              </div>
            </div>
          </div>
        )}
      </div>
    </div>
  )
}
