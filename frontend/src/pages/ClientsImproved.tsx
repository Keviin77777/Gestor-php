import { useEffect, useState } from 'react'
import { Plus, Search, Edit, Trash2, Phone, FileText, Download } from 'lucide-react'
import { useClientStore } from '@/stores/useClientStore'
import ClientModal from '@/components/ClientModal'
import toast from 'react-hot-toast'
import type { Client } from '@/types'

export default function ClientsImproved() {
  const { clients, loading, fetchClients, addClient, updateClient, deleteClient } = useClientStore()
  const [searchTerm, setSearchTerm] = useState('')
  const [statusFilter, setStatusFilter] = useState<string>('all')
  const [planFilter, setPlanFilter] = useState<string>('all')
  const [isModalOpen, setIsModalOpen] = useState(false)
  const [selectedClient, setSelectedClient] = useState<Client | null>(null)

  useEffect(() => {
    fetchClients()
  }, [fetchClients])

  const filteredClients = clients.filter((client) => {
    const matchesSearch = client.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
      client.email?.toLowerCase().includes(searchTerm.toLowerCase()) ||
      client.phone?.includes(searchTerm) ||
      client.username?.toLowerCase().includes(searchTerm.toLowerCase())
    
    // Verificar se está vencido pela data de renovação
    const today = new Date()
    today.setHours(0, 0, 0, 0)
    const renewalDate = new Date(client.renewal_date)
    renewalDate.setHours(0, 0, 0, 0)
    const isExpired = renewalDate < today
    
    // Filtro de status considerando vencidos
    let matchesStatus = false
    if (statusFilter === 'all') {
      matchesStatus = true
    } else if (statusFilter === 'expired') {
      matchesStatus = isExpired || client.status === 'expired'
    } else {
      matchesStatus = client.status === statusFilter && !isExpired
    }
    
    const matchesPlan = planFilter === 'all' || client.plan === planFilter

    return matchesSearch && matchesStatus && matchesPlan
  })

  const uniquePlans = Array.from(new Set(clients.map(c => c.plan)))

  const handleOpenModal = (client?: Client) => {
    setSelectedClient(client || null)
    setIsModalOpen(true)
  }

  const handleSaveClient = async (clientData: Partial<Client>) => {
    try {
      if (selectedClient) {
        await updateClient(selectedClient.id, clientData)
        toast.success('Cliente atualizado com sucesso!')
      } else {
        await addClient(clientData)
        toast.success('Cliente adicionado com sucesso!')
      }
    } catch (error) {
      toast.error('Erro ao salvar cliente')
      throw error
    }
  }

  const handleDelete = async (id: string, name: string) => {
    if (window.confirm(`Tem certeza que deseja excluir o cliente "${name}"?`)) {
      try {
        await deleteClient(id)
        toast.success('Cliente excluído com sucesso!')
      } catch (error) {
        toast.error('Erro ao excluir cliente')
      }
    }
  }

  const formatDate = (dateString: string) => {
    const date = new Date(dateString)
    return date.toLocaleDateString('pt-BR')
  }

  const formatMoney = (value: number) => {
    return new Intl.NumberFormat('pt-BR', {
      style: 'currency',
      currency: 'BRL',
    }).format(value)
  }

  const calculateDaysUntil = (dateString: string) => {
    const today = new Date()
    today.setHours(0, 0, 0, 0)
    const targetDate = new Date(dateString)
    targetDate.setHours(0, 0, 0, 0)
    const diffTime = targetDate.getTime() - today.getTime()
    return Math.ceil(diffTime / (1000 * 60 * 60 * 24))
  }

  const getDaysUntilBadge = (days: number) => {
    if (days < 0) return <span className="px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400">Vencido há {Math.abs(days)} dias</span>
    if (days === 0) return <span className="px-2 py-1 text-xs font-medium rounded-full bg-orange-100 text-orange-800 dark:bg-orange-900/20 dark:text-orange-400">Vence hoje</span>
    if (days <= 3) return <span className="px-2 py-1 text-xs font-medium rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400">{days} dias</span>
    if (days <= 7) return <span className="px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400">{days} dias</span>
    return <span className="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400">{days} dias</span>
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold text-gray-900 dark:text-white">Clientes</h1>
          <p className="text-gray-600 dark:text-gray-400 mt-1">
            {filteredClients.length} {filteredClients.length === 1 ? 'cliente' : 'clientes'}
          </p>
        </div>
        <div className="flex gap-3">
          <button onClick={() => {}} className="flex items-center gap-2 px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg transition-colors">
            <Download className="w-5 h-5" />
            Exportar
          </button>
          <button onClick={() => handleOpenModal()} className="flex items-center gap-2 px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-colors">
            <Plus className="w-5 h-5" />
            Novo Cliente
          </button>
        </div>
      </div>

      {/* Filters */}
      <div className="bg-white/80 dark:bg-gray-800/50 backdrop-blur-sm border border-gray-200 dark:border-gray-700/50 rounded-xl p-4">
        <div className="flex flex-col lg:flex-row gap-4">
          <div className="flex-1 relative">
            <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5" />
            <input type="text" placeholder="Buscar por nome, email, telefone ou usuário..." value={searchTerm} onChange={(e) => setSearchTerm(e.target.value)} className="w-full pl-10 pr-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent" />
          </div>
          <div className="flex gap-3">
            <select value={statusFilter} onChange={(e) => setStatusFilter(e.target.value)} className="px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500">
              <option value="all">Todos os status</option>
              <option value="active">Ativos</option>
              <option value="expired">Vencidos</option>
              <option value="inactive">Inativos</option>
              <option value="suspended">Suspensos</option>
            </select>
            <select value={planFilter} onChange={(e) => setPlanFilter(e.target.value)} className="px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500">
              <option value="all">Todos os planos</option>
              {uniquePlans.map(plan => <option key={plan} value={plan}>{plan}</option>)}
            </select>
            {(searchTerm || statusFilter !== 'all' || planFilter !== 'all') && (
              <button onClick={() => { setSearchTerm(''); setStatusFilter('all'); setPlanFilter('all') }} className="px-4 py-2.5 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">
                Limpar
              </button>
            )}
          </div>
        </div>
      </div>

      {/* Table */}
      <div className="bg-white/80 dark:bg-gray-800/50 backdrop-blur-sm rounded-xl shadow-sm border border-gray-200 dark:border-gray-700/50 overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full">
            <thead className="bg-gray-50 dark:bg-gray-700/50">
              <tr>
                <th className="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Cliente</th>
                <th className="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Contato</th>
                <th className="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Plano</th>
                <th className="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Valor</th>
                <th className="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Vencimento</th>
                <th className="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Status</th>
                <th className="px-6 py-4 text-right text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Ações</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-200 dark:divide-gray-700">
              {loading ? (
                <tr><td colSpan={7} className="px-6 py-12 text-center text-gray-500 dark:text-gray-400">Carregando...</td></tr>
              ) : filteredClients.length === 0 ? (
                <tr><td colSpan={7} className="px-6 py-12 text-center text-gray-500 dark:text-gray-400">Nenhum cliente encontrado</td></tr>
              ) : (
                filteredClients.map((client) => {
                  const daysUntil = calculateDaysUntil(client.renewal_date)
                  return (
                    <tr key={client.id} className="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                      <td className="px-6 py-4">
                        <div>
                          <div className="text-sm font-semibold text-gray-900 dark:text-white">{client.name}</div>
                          <div className="text-xs text-gray-500 dark:text-gray-400">{client.username || 'Sem usuário'}</div>
                        </div>
                      </td>
                      <td className="px-6 py-4">
                        <div>
                          <div className="text-sm text-gray-900 dark:text-white">{client.email || 'Sem email'}</div>
                          <div className="text-xs text-gray-500 dark:text-gray-400">{client.phone || 'Sem telefone'}</div>
                        </div>
                      </td>
                      <td className="px-6 py-4">
                        <span className="px-3 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400">{client.plan}</span>
                      </td>
                      <td className="px-6 py-4 text-sm font-semibold text-gray-900 dark:text-white">{formatMoney(client.value)}</td>
                      <td className="px-6 py-4">
                        <div>
                          <div className="text-sm text-gray-900 dark:text-white">{formatDate(client.renewal_date)}</div>
                          <div className="mt-1">{getDaysUntilBadge(daysUntil)}</div>
                        </div>
                      </td>
                      <td className="px-6 py-4">
                        <span className={`px-3 py-1 text-xs font-medium rounded-full ${
                          (() => {
                            // Verificar se está vencido pela data de renovação
                            const today = new Date()
                            today.setHours(0, 0, 0, 0)
                            const renewalDate = new Date(client.renewal_date)
                            renewalDate.setHours(0, 0, 0, 0)
                            const isExpired = renewalDate < today
                            
                            if (isExpired || client.status === 'expired') {
                              return 'bg-orange-100 text-orange-800 dark:bg-orange-900/20 dark:text-orange-400'
                            } else if (client.status === 'active') {
                              return 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400'
                            } else if (client.status === 'suspended') {
                              return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400'
                            } else {
                              return 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400'
                            }
                          })()
                        }`}>
                          {(() => {
                            // Verificar se está vencido pela data de renovação
                            const today = new Date()
                            today.setHours(0, 0, 0, 0)
                            const renewalDate = new Date(client.renewal_date)
                            renewalDate.setHours(0, 0, 0, 0)
                            const isExpired = renewalDate < today
                            
                            if (isExpired || client.status === 'expired') {
                              return 'Vencido'
                            } else if (client.status === 'active') {
                              return 'Ativo'
                            } else if (client.status === 'suspended') {
                              return 'Suspenso'
                            } else {
                              return 'Inativo'
                            }
                          })()
                        }
                        </span>
                      </td>
                      <td className="px-6 py-4">
                        <div className="flex items-center justify-end gap-2">
                          {client.phone && (
                            <button className="p-2 text-green-600 hover:bg-green-50 dark:hover:bg-green-900/20 rounded-lg transition-colors" title="WhatsApp">
                              <Phone className="w-4 h-4" />
                            </button>
                          )}
                          <button className="p-2 text-purple-600 hover:bg-purple-50 dark:hover:bg-purple-900/20 rounded-lg transition-colors" title="Faturas">
                            <FileText className="w-4 h-4" />
                          </button>
                          <button onClick={() => handleOpenModal(client)} className="p-2 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors" title="Editar">
                            <Edit className="w-4 h-4" />
                          </button>
                          <button onClick={() => handleDelete(client.id, client.name)} className="p-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors" title="Excluir">
                            <Trash2 className="w-4 h-4" />
                          </button>
                        </div>
                      </td>
                    </tr>
                  )
                })
              )}
            </tbody>
          </table>
        </div>
      </div>

      <ClientModal isOpen={isModalOpen} onClose={() => setIsModalOpen(false)} onSave={handleSaveClient} client={selectedClient} />
    </div>
  )
}
