import { useState, useEffect } from 'react'
import { UserCog, Plus, Search, Edit, Trash2, Mail, Phone, Calendar, CheckCircle, XCircle, Loader2 } from 'lucide-react'
import api from '../../services/api'
import toast from 'react-hot-toast'

interface Reseller {
  id: number
  name: string
  email: string
  phone?: string
  whatsapp?: string
  current_plan_id?: string
  plan_name?: string
  plan_expires_at?: string
  current_status: 'active' | 'expired' | 'suspended' | 'no_plan'
  days_remaining?: number
  created_at: string
}

import { usePageTitle } from '@/hooks/usePageTitle'

export default function Resellers() {
  usePageTitle('Revendedores')
  const [resellers, setResellers] = useState<Reseller[]>([])
  const [loading, setLoading] = useState(true)
  const [searchTerm, setSearchTerm] = useState('')
  const [showForm, setShowForm] = useState(false)
  const [editingReseller, setEditingReseller] = useState<Reseller | null>(null)
  const [plans, setPlans] = useState<any[]>([])
  const [saving, setSaving] = useState(false)
  const [formData, setFormData] = useState({
    name: '',
    email: '',
    phone: '',
    password: '',
    current_plan_id: '',
    plan_duration_days: 30
  })

  useEffect(() => {
    loadResellers()
    loadPlans()
  }, [])

  const loadPlans = async () => {
    try {
      const response = await api.get('/api-reseller-plans.php')
      if (response.data.success) {
        setPlans(response.data.plans || [])
      }
    } catch (error) {
      // Erro ao carregar planos
    }
  }

  const loadResellers = async () => {
    try {
      setLoading(true)
      const response = await api.get('/api-resellers.php')
      if (response.data.success) {
        setResellers(response.data.resellers || [])
      }
    } catch (error: any) {
      toast.error(error.response?.data?.error || 'Erro ao carregar revendedores')
    } finally {
      setLoading(false)
    }
  }

  const handleEdit = (reseller: Reseller) => {
    setEditingReseller(reseller)
    setFormData({
      name: reseller.name,
      email: reseller.email,
      phone: reseller.phone || reseller.whatsapp || '',
      password: '',
      current_plan_id: reseller.current_plan_id || '',
      plan_duration_days: 30
    })
    setShowForm(true)
  }

  const handleNew = () => {
    setEditingReseller(null)
    setFormData({
      name: '',
      email: '',
      phone: '',
      password: '',
      current_plan_id: '',
      plan_duration_days: 30
    })
    setShowForm(true)
  }

  const handleCancel = () => {
    setShowForm(false)
    setEditingReseller(null)
    setFormData({
      name: '',
      email: '',
      phone: '',
      password: '',
      current_plan_id: '',
      plan_duration_days: 30
    })
  }

  const handleSave = async () => {
    if (!formData.name || !formData.email) {
      toast.error('Nome e email são obrigatórios')
      return
    }

    if (!editingReseller && !formData.password) {
      toast.error('Senha é obrigatória para novos revendedores')
      return
    }

    try {
      setSaving(true)

      if (editingReseller) {
        // Atualizar revendedor existente
        const response = await api.post(`/api-resellers.php`, {
          id: editingReseller.id,
          name: formData.name,
          email: formData.email,
          phone: formData.phone,
          ...(formData.password && { password: formData.password })
        })

        if (response.data.success) {
          // Se mudou o plano, atualizar também
          if (formData.current_plan_id && formData.current_plan_id !== editingReseller.current_plan_id) {
            await api.put(`/api-resellers.php?id=${editingReseller.id}&action=change-plan`, {
              plan_id: formData.current_plan_id
            })
          }

          toast.success('Revendedor atualizado com sucesso')
          handleCancel()
          loadResellers()
        }
      } else {
        // Criar novo revendedor
        const response = await api.post('/api-resellers.php', {
          name: formData.name,
          email: formData.email,
          phone: formData.phone,
          password: formData.password,
          current_plan_id: formData.current_plan_id
        })

        if (response.data.success) {
          toast.success('Revendedor criado com sucesso')
          handleCancel()
          loadResellers()
        }
      }
    } catch (error: any) {
      toast.error(error.response?.data?.error || 'Erro ao salvar revendedor')
    } finally {
      setSaving(false)
    }
  }

  const handleDelete = async (id: number, name: string) => {
    if (!confirm(`Tem certeza que deseja excluir o revendedor "${name}"?\n\nEsta ação não pode ser desfeita.`)) {
      return
    }

    try {
      const response = await api.delete(`/api-resellers.php?id=${id}`)
      
      if (response.data.success) {
        toast.success('Revendedor excluído com sucesso')
        loadResellers() // Recarregar lista
      } else {
        toast.error(response.data.error || 'Erro ao excluir revendedor')
      }
    } catch (error: any) {
      toast.error(error.response?.data?.error || 'Erro ao excluir revendedor')
    }
  }

  const filteredResellers = resellers.filter(reseller =>
    (reseller.name || '').toLowerCase().includes(searchTerm.toLowerCase()) ||
    (reseller.email || '').toLowerCase().includes(searchTerm.toLowerCase())
  )

  const getStatusBadge = (status: string) => {
    const badges = {
      active: { bg: 'bg-green-100 dark:bg-green-900/30', text: 'text-green-700 dark:text-green-400', label: 'Ativo' },
      expired: { bg: 'bg-red-100 dark:bg-red-900/30', text: 'text-red-700 dark:text-red-400', label: 'Expirado' },
      suspended: { bg: 'bg-gray-100 dark:bg-gray-900/30', text: 'text-gray-700 dark:text-gray-400', label: 'Suspenso' },
      no_plan: { bg: 'bg-yellow-100 dark:bg-yellow-900/30', text: 'text-yellow-700 dark:text-yellow-400', label: 'Sem Plano' },
    }
    const badge = badges[status as keyof typeof badges] || badges.suspended
    return (
      <span className={`px-2 py-1 rounded-full text-xs font-semibold ${badge.bg} ${badge.text}`}>
        {badge.label}
      </span>
    )
  }

  const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('pt-BR')
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
            <UserCog className="w-7 h-7 text-purple-600" />
            Revendedores
          </h1>
          <p className="text-sm text-gray-600 dark:text-gray-400 mt-1">
            Gerencie todos os revendedores do sistema
          </p>
        </div>
        <button 
          onClick={handleNew}
          className="flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-purple-600 to-blue-600 text-white rounded-lg hover:shadow-lg transition-all"
        >
          <Plus className="w-5 h-5" />
          Novo Revendedor
        </button>
      </div>

      {/* Search */}
      <div className="relative">
        <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
        <input
          type="text"
          placeholder="Buscar por nome ou email..."
          value={searchTerm}
          onChange={(e) => setSearchTerm(e.target.value)}
          className="w-full pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent"
        />
      </div>

      {/* Stats Cards */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div className="bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20 rounded-xl p-6 border border-green-200 dark:border-green-800">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm font-medium text-green-700 dark:text-green-400">Ativos</p>
              <p className="text-3xl font-bold text-green-900 dark:text-green-300 mt-1">
                {resellers.filter(r => r.current_status === 'active').length}
              </p>
            </div>
            <CheckCircle className="w-12 h-12 text-green-600 dark:text-green-400 opacity-50" />
          </div>
        </div>

        <div className="bg-gradient-to-br from-red-50 to-red-100 dark:from-red-900/20 dark:to-red-800/20 rounded-xl p-6 border border-red-200 dark:border-red-800">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm font-medium text-red-700 dark:text-red-400">Expirados</p>
              <p className="text-3xl font-bold text-red-900 dark:text-red-300 mt-1">
                {resellers.filter(r => r.current_status === 'expired').length}
              </p>
            </div>
            <XCircle className="w-12 h-12 text-red-600 dark:text-red-400 opacity-50" />
          </div>
        </div>

        <div className="bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900/20 dark:to-purple-800/20 rounded-xl p-6 border border-purple-200 dark:border-purple-800">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm font-medium text-purple-700 dark:text-purple-400">Total</p>
              <p className="text-3xl font-bold text-purple-900 dark:text-purple-300 mt-1">
                {resellers.length}
              </p>
            </div>
            <UserCog className="w-12 h-12 text-purple-600 dark:text-purple-400 opacity-50" />
          </div>
        </div>
      </div>

      {/* Formulário Inline */}
      {showForm && (
        <div className="bg-white dark:bg-gray-800 rounded-xl shadow-sm border-2 border-purple-200 dark:border-purple-800 p-6">
          <div className="flex items-center justify-between mb-6">
            <h3 className="text-lg font-semibold text-gray-900 dark:text-white">
              {editingReseller ? 'Editar Revendedor' : 'Novo Revendedor'}
            </h3>
            <button
              onClick={handleCancel}
              className="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
            >
              ✕
            </button>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            {/* Nome */}
            <div>
              <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Nome *
              </label>
              <input
                type="text"
                value={formData.name}
                onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                className="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                placeholder="Nome do revendedor"
              />
            </div>

            {/* Email */}
            <div>
              <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Email *
              </label>
              <input
                type="email"
                value={formData.email}
                onChange={(e) => setFormData({ ...formData, email: e.target.value })}
                className="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                placeholder="email@exemplo.com"
              />
            </div>

            {/* Telefone */}
            <div>
              <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Telefone
              </label>
              <input
                type="text"
                value={formData.phone}
                onChange={(e) => setFormData({ ...formData, phone: e.target.value })}
                className="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                placeholder="(00) 00000-0000"
              />
            </div>

            {/* Senha */}
            <div>
              <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Senha {!editingReseller && '*'}
              </label>
              <input
                type="password"
                value={formData.password}
                onChange={(e) => setFormData({ ...formData, password: e.target.value })}
                className="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                placeholder={editingReseller ? 'Deixe em branco para não alterar' : 'Senha de acesso'}
              />
            </div>

            {/* Plano */}
            <div>
              <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Plano
              </label>
              <select
                value={formData.current_plan_id}
                onChange={(e) => setFormData({ ...formData, current_plan_id: e.target.value })}
                className="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent"
              >
                <option value="">Sem plano</option>
                {plans.filter(p => p.is_active).map(plan => (
                  <option key={plan.id} value={plan.id}>
                    {plan.name} - R$ {plan.price} ({plan.duration_days} dias)
                  </option>
                ))}
              </select>
            </div>
          </div>

          {/* Botões */}
          <div className="flex gap-3 mt-6">
            <button
              onClick={handleSave}
              disabled={saving}
              className="flex-1 px-4 py-2 bg-gradient-to-r from-purple-600 to-blue-600 text-white rounded-lg hover:shadow-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {saving ? 'Salvando...' : editingReseller ? 'Atualizar' : 'Criar'}
            </button>
            <button
              onClick={handleCancel}
              disabled={saving}
              className="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
            >
              Cancelar
            </button>
          </div>
        </div>
      )}

      {/* Table with horizontal scroll on mobile */}
      <div className="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full">
            <thead className="bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-700">
              <tr>
                <th className="px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap">
                  Revendedor
                </th>
                <th className="px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap">
                  Plano
                </th>
                <th className="px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap">
                  Vencimento
                </th>
                <th className="px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap">
                  Status
                </th>
                <th className="px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap">
                  Cadastro
                </th>
                <th className="px-6 py-3 text-right text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap">
                  Ações
                </th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-200 dark:divide-gray-700">
              {filteredResellers.length === 0 ? (
                <tr>
                  <td colSpan={6} className="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                    Nenhum revendedor encontrado
                  </td>
                </tr>
              ) : (
                filteredResellers.map((reseller) => (
                  <tr key={reseller.id} className="hover:bg-gray-50 dark:hover:bg-gray-900/50 transition-colors">
                    <td className="px-6 py-4 whitespace-nowrap">
                      <div>
                        <div className="font-medium text-gray-900 dark:text-white">{reseller.name}</div>
                        <div className="flex items-center gap-3 mt-1 text-sm text-gray-500 dark:text-gray-400">
                          <span className="flex items-center gap-1">
                            <Mail className="w-3 h-3" />
                            {reseller.email}
                          </span>
                          {reseller.phone && (
                            <span className="flex items-center gap-1">
                              <Phone className="w-3 h-3" />
                              {reseller.phone}
                            </span>
                          )}
                        </div>
                      </div>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap">
                      <span className="text-sm text-gray-900 dark:text-white font-medium">
                        {reseller.plan_name || 'Sem plano'}
                      </span>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap">
                      {reseller.plan_expires_at ? (
                        <div className="flex items-center gap-1 text-sm text-gray-600 dark:text-gray-400">
                          <Calendar className="w-4 h-4" />
                          {formatDate(reseller.plan_expires_at)}
                        </div>
                      ) : (
                        <span className="text-sm text-gray-400">-</span>
                      )}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap">
                      {getStatusBadge(reseller.current_status)}
                    </td>
                    <td className="px-6 py-4 text-sm text-gray-600 dark:text-gray-400 whitespace-nowrap">
                      {formatDate(reseller.created_at)}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap">
                      <div className="flex items-center justify-end gap-2">
                        <button 
                          onClick={() => handleEdit(reseller)}
                          className="p-2 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors"
                          title="Editar"
                        >
                          <Edit className="w-4 h-4" />
                        </button>
                        <button 
                          onClick={() => handleDelete(reseller.id, reseller.name)}
                          className="p-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors"
                          title="Excluir"
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
      </div>
    </div>
  )
}
