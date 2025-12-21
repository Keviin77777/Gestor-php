import { useState, useEffect } from 'react'
import { History, Search, Download, Filter, Calendar, CheckCircle, Clock, XCircle, Loader2, CreditCard, Trash2 } from 'lucide-react'
import api from '../../services/api'
import toast from 'react-hot-toast'

interface Payment {
  id: number
  user_id: number
  user_name: string
  user_email: string
  plan_id: string
  plan_name: string
  amount: number
  payment_method: string
  status: 'pending' | 'approved' | 'rejected' | 'cancelled'
  payment_id?: string
  created_at: string
  updated_at?: string
}

import { usePageTitle } from '@/hooks/usePageTitle'

export default function PaymentHistory() {
  usePageTitle('Histórico de Pagamentos')
  const [payments, setPayments] = useState<Payment[]>([])
  const [loading, setLoading] = useState(true)
  const [searchTerm, setSearchTerm] = useState('')
  const [statusFilter, setStatusFilter] = useState<string>('all')

  useEffect(() => {
    loadPayments()
  }, [])

  const loadPayments = async () => {
    try {
      setLoading(true)
      const response = await api.get('/api-payment-history.php')
      if (response.data.success) {
        setPayments(response.data.payments || [])
      }
    } catch (error: any) {
      toast.error(error.response?.data?.error || 'Erro ao carregar histórico')
    } finally {
      setLoading(false)
    }
  }

  const handleDelete = async (id: number) => {
    if (!confirm('Tem certeza que deseja excluir este pagamento?\n\nEsta ação não pode ser desfeita.')) {
      return
    }

    try {
      const response = await api.delete(`/api-payment-history.php?id=${id}`)
      
      if (response.data.success) {
        toast.success('Pagamento excluído com sucesso')
        loadPayments() // Recarregar lista
      } else {
        toast.error(response.data.error || 'Erro ao excluir pagamento')
      }
    } catch (error: any) {
      toast.error(error.response?.data?.error || 'Erro ao excluir pagamento')
    }
  }

  const filteredPayments = payments.filter(payment => {
    const matchesSearch = 
      (payment.user_name || '').toLowerCase().includes(searchTerm.toLowerCase()) ||
      (payment.user_email || '').toLowerCase().includes(searchTerm.toLowerCase()) ||
      (payment.plan_name || '').toLowerCase().includes(searchTerm.toLowerCase())
    
    const matchesStatus = statusFilter === 'all' || payment.status === statusFilter

    return matchesSearch && matchesStatus
  })

  const getStatusBadge = (status: string) => {
    const badges = {
      approved: { 
        bg: 'bg-green-100 dark:bg-green-900/30', 
        text: 'text-green-700 dark:text-green-400', 
        icon: CheckCircle,
        label: 'Aprovado' 
      },
      pending: { 
        bg: 'bg-yellow-100 dark:bg-yellow-900/30', 
        text: 'text-yellow-700 dark:text-yellow-400', 
        icon: Clock,
        label: 'Pendente' 
      },
      rejected: { 
        bg: 'bg-red-100 dark:bg-red-900/30', 
        text: 'text-red-700 dark:text-red-400', 
        icon: XCircle,
        label: 'Rejeitado' 
      },
      cancelled: { 
        bg: 'bg-gray-100 dark:bg-gray-900/30', 
        text: 'text-gray-700 dark:text-gray-400', 
        icon: XCircle,
        label: 'Cancelado' 
      },
    }
    const badge = badges[status as keyof typeof badges] || badges.pending
    const Icon = badge.icon
    
    return (
      <span className={`inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-semibold ${badge.bg} ${badge.text}`}>
        <Icon className="w-3 h-3" />
        {badge.label}
      </span>
    )
  }

  const formatPrice = (price: number) => {
    return new Intl.NumberFormat('pt-BR', {
      style: 'currency',
      currency: 'BRL'
    }).format(price)
  }

  const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('pt-BR', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    })
  }

  const totalRevenue = payments
    .filter(p => p.status === 'approved')
    .reduce((sum, p) => sum + (Number(p.amount) || 0), 0)

  const pendingRevenue = payments
    .filter(p => p.status === 'pending')
    .reduce((sum, p) => sum + (Number(p.amount) || 0), 0)

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
            <History className="w-7 h-7 text-blue-600" />
            Histórico de Pagamentos
          </h1>
          <p className="text-sm text-gray-600 dark:text-gray-400 mt-1">
            Acompanhe todos os pagamentos dos revendedores
          </p>
        </div>
        <button className="flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-lg hover:shadow-lg transition-all">
          <Download className="w-5 h-5" />
          Exportar
        </button>
      </div>

      {/* Stats Cards */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div className="bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20 rounded-xl p-6 border border-green-200 dark:border-green-800">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm font-medium text-green-700 dark:text-green-400">Recebido</p>
              <p className="text-2xl font-bold text-green-900 dark:text-green-300 mt-1">
                {formatPrice(totalRevenue)}
              </p>
            </div>
            <CheckCircle className="w-12 h-12 text-green-600 dark:text-green-400 opacity-50" />
          </div>
        </div>

        <div className="bg-gradient-to-br from-yellow-50 to-yellow-100 dark:from-yellow-900/20 dark:to-yellow-800/20 rounded-xl p-6 border border-yellow-200 dark:border-yellow-800">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm font-medium text-yellow-700 dark:text-yellow-400">Pendente</p>
              <p className="text-2xl font-bold text-yellow-900 dark:text-yellow-300 mt-1">
                {formatPrice(pendingRevenue)}
              </p>
            </div>
            <Clock className="w-12 h-12 text-yellow-600 dark:text-yellow-400 opacity-50" />
          </div>
        </div>

        <div className="bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 rounded-xl p-6 border border-blue-200 dark:border-blue-800">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm font-medium text-blue-700 dark:text-blue-400">Total</p>
              <p className="text-2xl font-bold text-blue-900 dark:text-blue-300 mt-1">
                {payments.length}
              </p>
            </div>
            <CreditCard className="w-12 h-12 text-blue-600 dark:text-blue-400 opacity-50" />
          </div>
        </div>

        <div className="bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900/20 dark:to-purple-800/20 rounded-xl p-6 border border-purple-200 dark:border-purple-800">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm font-medium text-purple-700 dark:text-purple-400">Este Mês</p>
              <p className="text-2xl font-bold text-purple-900 dark:text-purple-300 mt-1">
                {payments.filter(p => {
                  const paymentDate = new Date(p.created_at)
                  const now = new Date()
                  return paymentDate.getMonth() === now.getMonth() && 
                         paymentDate.getFullYear() === now.getFullYear()
                }).length}
              </p>
            </div>
            <Calendar className="w-12 h-12 text-purple-600 dark:text-purple-400 opacity-50" />
          </div>
        </div>
      </div>

      {/* Filters */}
      <div className="flex flex-col sm:flex-row gap-4">
        <div className="flex-1 relative">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
          <input
            type="text"
            placeholder="Buscar por revendedor, email ou plano..."
            value={searchTerm}
            onChange={(e) => setSearchTerm(e.target.value)}
            className="w-full pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          />
        </div>
        <div className="relative">
          <Filter className="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
          <select
            value={statusFilter}
            onChange={(e) => setStatusFilter(e.target.value)}
            className="pl-10 pr-8 py-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent appearance-none cursor-pointer"
          >
            <option value="all">Todos os Status</option>
            <option value="approved">Aprovados</option>
            <option value="pending">Pendentes</option>
            <option value="rejected">Rejeitados</option>
            <option value="cancelled">Cancelados</option>
          </select>
        </div>
      </div>

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
                  Valor
                </th>
                <th className="px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap">
                  Método
                </th>
                <th className="px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap">
                  Status
                </th>
                <th className="px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap">
                  Data
                </th>
                <th className="px-6 py-3 text-right text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap">
                  Ações
                </th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-200 dark:divide-gray-700">
              {filteredPayments.length === 0 ? (
                <tr>
                  <td colSpan={7} className="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                    Nenhum pagamento encontrado
                  </td>
                </tr>
              ) : (
                filteredPayments.map((payment) => (
                  <tr key={payment.id} className="hover:bg-gray-50 dark:hover:bg-gray-900/50 transition-colors">
                    <td className="px-6 py-4 whitespace-nowrap">
                      <div>
                        <div className="font-medium text-gray-900 dark:text-white">
                          {payment.user_name}
                        </div>
                        <div className="text-sm text-gray-500 dark:text-gray-400">
                          {payment.user_email}
                        </div>
                      </div>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap">
                      <span className="text-sm text-gray-900 dark:text-white font-medium">
                        {payment.plan_name}
                      </span>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap">
                      <span className="text-sm font-semibold text-gray-900 dark:text-white">
                        {formatPrice(payment.amount)}
                      </span>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap">
                      <span className="text-sm text-gray-600 dark:text-gray-400 capitalize">
                        {payment.payment_method}
                      </span>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap">
                      {getStatusBadge(payment.status)}
                    </td>
                    <td className="px-6 py-4 text-sm text-gray-600 dark:text-gray-400 whitespace-nowrap">
                      {formatDate(payment.updated_at || payment.created_at)}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap">
                      <div className="flex items-center justify-end gap-2">
                        <button 
                          onClick={() => handleDelete(payment.id)}
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
