import { useState, useEffect } from 'react'
import { Package, Plus, Search, Edit, Trash2, DollarSign, Calendar, CheckCircle, XCircle, Loader2, Star } from 'lucide-react'
import api from '../../services/api'
import toast from 'react-hot-toast'

interface ResellerPlan {
  id: number
  name: string
  description?: string
  price: number
  duration_days: number
  is_trial: boolean
  is_active: boolean
  created_at: string
}

import { usePageTitle } from '@/hooks/usePageTitle'

export default function ResellerPlans() {
  usePageTitle('Planos de Revendedores')
  const [plans, setPlans] = useState<ResellerPlan[]>([])
  const [loading, setLoading] = useState(true)
  const [searchTerm, setSearchTerm] = useState('')

  useEffect(() => {
    loadPlans()
  }, [])

  const loadPlans = async () => {
    try {
      setLoading(true)
      const response = await api.get('/api-reseller-plans.php')
      if (response.data.success) {
        setPlans(response.data.plans || [])
      }
    } catch (error: any) {
      toast.error(error.response?.data?.error || 'Erro ao carregar planos')
    } finally {
      setLoading(false)
    }
  }

  const handleEdit = (_plan: ResellerPlan) => {
    // TODO: Implementar modal de edição
    toast('Funcionalidade de edição em desenvolvimento', {
      icon: 'ℹ️',
    })
  }

  const handleDelete = async (id: number, name: string) => {
    if (!confirm(`Tem certeza que deseja excluir o plano "${name}"?\n\nEsta ação não pode ser desfeita.`)) {
      return
    }

    try {
      const response = await api.delete(`/api-reseller-plans.php/${id}`)
      
      if (response.data.success) {
        toast.success(response.data.message || 'Plano excluído com sucesso')
        loadPlans() // Recarregar lista
      } else {
        toast.error(response.data.error || 'Erro ao excluir plano')
      }
    } catch (error: any) {
      toast.error(error.response?.data?.error || 'Erro ao excluir plano')
    }
  }

  const filteredPlans = plans.filter(plan =>
    (plan.name || '').toLowerCase().includes(searchTerm.toLowerCase())
  )

  const formatPrice = (price: number) => {
    return new Intl.NumberFormat('pt-BR', {
      style: 'currency',
      currency: 'BRL'
    }).format(price)
  }

  if (loading) {
    return (
      <div className="flex items-center justify-center h-96">
        <Loader2 className="w-8 h-8 text-primary-600 animate-spin" />
      </div>
    )
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
            <Package className="w-7 h-7 text-blue-600" />
            Planos de Revendedores
          </h1>
          <p className="text-sm text-gray-600 dark:text-gray-400 mt-1">
            Configure os planos disponíveis para revendedores
          </p>
        </div>
        <button className="flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-lg hover:shadow-lg transition-all">
          <Plus className="w-5 h-5" />
          Novo Plano
        </button>
      </div>

      {/* Search */}
      <div className="relative">
        <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
        <input
          type="text"
          placeholder="Buscar planos..."
          value={searchTerm}
          onChange={(e) => setSearchTerm(e.target.value)}
          className="w-full pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
        />
      </div>

      {/* Stats Cards */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div className="bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 rounded-xl p-6 border border-blue-200 dark:border-blue-800">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm font-medium text-blue-700 dark:text-blue-400">Total</p>
              <p className="text-3xl font-bold text-blue-900 dark:text-blue-300 mt-1">
                {plans.length}
              </p>
            </div>
            <Package className="w-12 h-12 text-blue-600 dark:text-blue-400 opacity-50" />
          </div>
        </div>

        <div className="bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20 rounded-xl p-6 border border-green-200 dark:border-green-800">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm font-medium text-green-700 dark:text-green-400">Ativos</p>
              <p className="text-3xl font-bold text-green-900 dark:text-green-300 mt-1">
                {plans.filter(p => p.is_active).length}
              </p>
            </div>
            <CheckCircle className="w-12 h-12 text-green-600 dark:text-green-400 opacity-50" />
          </div>
        </div>

        <div className="bg-gradient-to-br from-yellow-50 to-yellow-100 dark:from-yellow-900/20 dark:to-yellow-800/20 rounded-xl p-6 border border-yellow-200 dark:border-yellow-800">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm font-medium text-yellow-700 dark:text-yellow-400">Gratuitos</p>
              <p className="text-3xl font-bold text-yellow-900 dark:text-yellow-300 mt-1">
                {plans.filter(p => p.is_trial).length}
              </p>
            </div>
            <Star className="w-12 h-12 text-yellow-600 dark:text-yellow-400 opacity-50" />
          </div>
        </div>

        <div className="bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900/20 dark:to-purple-800/20 rounded-xl p-6 border border-purple-200 dark:border-purple-800">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm font-medium text-purple-700 dark:text-purple-400">Pagos</p>
              <p className="text-3xl font-bold text-purple-900 dark:text-purple-300 mt-1">
                {plans.filter(p => !p.is_trial).length}
              </p>
            </div>
            <DollarSign className="w-12 h-12 text-purple-600 dark:text-purple-400 opacity-50" />
          </div>
        </div>
      </div>

      {/* Plans Grid */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        {filteredPlans.length === 0 ? (
          <div className="col-span-full text-center py-12 text-gray-500 dark:text-gray-400">
            Nenhum plano encontrado
          </div>
        ) : (
          filteredPlans.map((plan) => (
            <div
              key={plan.id}
              className={`relative bg-white dark:bg-gray-800 rounded-xl shadow-sm border-2 transition-all hover:shadow-lg ${
                plan.is_active
                  ? 'border-blue-200 dark:border-blue-800'
                  : 'border-gray-200 dark:border-gray-700 opacity-60'
              }`}
            >
              {/* Badge */}
              <div className="absolute -top-3 left-1/2 -translate-x-1/2">
                {plan.is_trial ? (
                  <span className="px-3 py-1 bg-gradient-to-r from-green-500 to-emerald-500 text-white text-xs font-bold rounded-full shadow-lg">
                    GRATUITO
                  </span>
                ) : (
                  <span className="px-3 py-1 bg-gradient-to-r from-blue-500 to-purple-500 text-white text-xs font-bold rounded-full shadow-lg">
                    PREMIUM
                  </span>
                )}
              </div>

              <div className="p-6 pt-8">
                {/* Header */}
                <div className="text-center mb-6">
                  <h3 className="text-xl font-bold text-gray-900 dark:text-white mb-2">
                    {plan.name}
                  </h3>
                  {plan.description && (
                    <p className="text-sm text-gray-600 dark:text-gray-400">
                      {plan.description}
                    </p>
                  )}
                </div>

                {/* Price */}
                <div className="text-center mb-6">
                  <div className="flex items-baseline justify-center gap-1">
                    <span className="text-3xl font-bold text-gray-900 dark:text-white">
                      {formatPrice(plan.price)}
                    </span>
                  </div>
                  <div className="flex items-center justify-center gap-1 mt-2 text-sm text-gray-600 dark:text-gray-400">
                    <Calendar className="w-4 h-4" />
                    <span>{plan.duration_days} dias</span>
                  </div>
                </div>

                {/* Status */}
                <div className="flex items-center justify-center gap-2 mb-6">
                  {plan.is_active ? (
                    <span className="flex items-center gap-1 px-3 py-1 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 rounded-full text-xs font-semibold">
                      <CheckCircle className="w-3 h-3" />
                      Ativo
                    </span>
                  ) : (
                    <span className="flex items-center gap-1 px-3 py-1 bg-gray-100 dark:bg-gray-900/30 text-gray-700 dark:text-gray-400 rounded-full text-xs font-semibold">
                      <XCircle className="w-3 h-3" />
                      Inativo
                    </span>
                  )}
                </div>

                {/* Actions */}
                <div className="flex gap-2">
                  <button 
                    onClick={() => handleEdit(plan)}
                    className="flex-1 flex items-center justify-center gap-2 px-4 py-2 bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/30 transition-colors"
                  >
                    <Edit className="w-4 h-4" />
                    Editar
                  </button>
                  <button 
                    onClick={() => handleDelete(plan.id, plan.name)}
                    className="flex-1 flex items-center justify-center gap-2 px-4 py-2 bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 rounded-lg hover:bg-red-100 dark:hover:bg-red-900/30 transition-colors"
                  >
                    <Trash2 className="w-4 h-4" />
                    Excluir
                  </button>
                </div>
              </div>
            </div>
          ))
        )}
      </div>
    </div>
  )
}
