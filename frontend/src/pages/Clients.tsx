import { useEffect, useState } from 'react'
import { Plus, Search, Edit, Trash2, FileText, CreditCard, MessageSquare } from 'lucide-react'
import { useClientStore } from '@/stores/useClientStore'
import toast from 'react-hot-toast'
import LoadingSpinner from '../components/LoadingSpinner'
import type { Client } from '@/types'

export default function Clients() {
  const { clients, loading, fetchClients, deleteClient, addClient, updateClient } = useClientStore()
  const [searchTerm, setSearchTerm] = useState('')
  const [statusFilter, setStatusFilter] = useState<string>('all')
  const [showForm, setShowForm] = useState(false)
  const [editingClient, setEditingClient] = useState<Client | null>(null)
  const [servers, setServers] = useState<any[]>([])
  const [plans, setPlans] = useState<any[]>([])
  const [filteredPlans, setFilteredPlans] = useState<any[]>([])
  const [viewMode, setViewMode] = useState<'list' | 'payment-history'>('list')
  const [selectedClient, setSelectedClient] = useState<Client | null>(null)
  const [paymentHistory, setPaymentHistory] = useState<any[]>([])
  const [loadingHistory, setLoadingHistory] = useState(false)
  const [formData, setFormData] = useState<Partial<Client>>({
    name: '',
    email: '',
    phone: '',
    username: '',
    password: '',
    plan: 'Personalizado',
    value: 0,
    renewal_date: '',
    server: '',
    mac: '',
    screens: 1,
    notifications: 'sim',
    notes: '',
  })
  const [saving, setSaving] = useState(false)

  useEffect(() => {
    fetchClients()
    loadServers()
    loadPlans()
  }, [fetchClients])

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
      console.error('Erro ao carregar servidores')
    }
  }

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
      console.error('Erro ao carregar planos')
    }
  }

  useEffect(() => {
    if (formData.server && servers.length > 0) {
      const selectedServer = servers.find(s => s.name === formData.server)
      if (selectedServer) {
        const filtered = plans.filter(p => p.server_id === selectedServer.id)
        setFilteredPlans(filtered)
      }
    } else {
      setFilteredPlans([])
    }
  }, [formData.server, servers, plans])

  useEffect(() => {
    if (editingClient) {
      setFormData(editingClient)
      setShowForm(true)
    } else if (!showForm) {
      setFormData({
        name: '',
        email: '',
        phone: '',
        username: '',
        password: '',
        plan: 'Personalizado',
        value: 0,
        renewal_date: '',
        server: '',
        mac: '',
        screens: 1,
        notifications: 'sim',
        notes: '',
      })
    }
  }, [editingClient, showForm])

  const handleSave = async (e: React.FormEvent) => {
    e.preventDefault()
    setSaving(true)
    try {
      if (editingClient) {
        await updateClient(editingClient.id, formData)
        toast.success('Cliente atualizado com sucesso!')
      } else {
        await addClient(formData)
        toast.success('Cliente adicionado com sucesso!')
      }
      setShowForm(false)
      setEditingClient(null)
      fetchClients()
    } catch (error) {
      toast.error('Erro ao salvar cliente')
    } finally {
      setSaving(false)
    }
  }

  const handleCancel = () => {
    setShowForm(false)
    setEditingClient(null)
  }

  const filteredClients = clients.filter((client) => {
    const matchesSearch = client.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
      client.email?.toLowerCase().includes(searchTerm.toLowerCase()) ||
      client.phone?.includes(searchTerm)
    
    const matchesStatus = statusFilter === 'all' || client.status === statusFilter

    return matchesSearch && matchesStatus
  })

  const handleDelete = async (id: string) => {
    if (window.confirm('Tem certeza que deseja excluir este cliente?')) {
      try {
        await deleteClient(id)
        toast.success('Cliente excluído com sucesso!')
      } catch (error) {
        toast.error('Erro ao excluir cliente')
      }
    }
  }

  const handleGenerateInvoice = async (clientId: string) => {
    const client = clients.find(c => c.id === clientId)
    if (!client) {
      toast.error('Cliente não encontrado')
      return
    }

    if (window.confirm(`Gerar fatura para ${client.name}?`)) {
      try {
        const response = await fetch('/api-invoices.php', {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({
            client_id: clientId,
            description: `Mensalidade ${client.plan}`,
            value: client.value,
            due_date: client.renewal_date
          })
        })

        const data = await response.json()
        if (data.success) {
          toast.success('Fatura gerada com sucesso!')
        } else {
          toast.error(data.error || 'Erro ao gerar fatura')
        }
      } catch (error) {
        toast.error('Erro ao gerar fatura')
      }
    }
  }

  const handlePaymentHistory = async (clientId: string) => {
    const client = clients.find(c => c.id === clientId)
    if (!client) return

    setSelectedClient(client)
    setViewMode('payment-history')
    setLoadingHistory(true)

    try {
      const response = await fetch('/api-invoices.php', {
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('token')}`
        }
      })
      const data = await response.json()
      
      if (data.success && data.invoices) {
        // Filtrar faturas do cliente específico
        const clientInvoices = data.invoices.filter((inv: any) => inv.client_id === clientId)
        setPaymentHistory(clientInvoices)
      } else {
        setPaymentHistory([])
      }
    } catch (error) {
      console.error('Erro ao carregar histórico:', error)
      toast.error('Erro ao carregar histórico')
      setPaymentHistory([])
    } finally {
      setLoadingHistory(false)
    }
  }

  const handleBackToList = () => {
    setViewMode('list')
    setSelectedClient(null)
    setPaymentHistory([])
  }

  const handleMarkAsPaid = async (invoiceId: string) => {
    if (!selectedClient) return

    if (window.confirm('Marcar esta fatura como paga? Isso irá renovar o cliente automaticamente.')) {
      // Mostrar loading imediatamente
      const loadingToast = toast.loading('Processando pagamento...')
      
      try {
        // Atualizar estado local imediatamente para feedback visual
        setPaymentHistory(prev => prev.map(p => 
          p.id === invoiceId 
            ? { ...p, status: 'paid', payment_date: new Date().toISOString().split('T')[0] }
            : p
        ))

        const response = await fetch(`/api-invoices.php?id=${invoiceId}&action=mark-paid`, {
          method: 'PUT',
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({
            client_id: selectedClient.id
          })
        })

        const data = await response.json()
        toast.dismiss(loadingToast)
        
        if (data.success) {
          toast.success('Fatura marcada como paga e cliente renovado!')
          // Recarregar dados do servidor em background
          handlePaymentHistory(selectedClient.id)
          fetchClients()
        } else {
          toast.error(data.error || 'Erro ao marcar como paga')
          await handlePaymentHistory(selectedClient.id)
        }
      } catch (error) {
        toast.dismiss(loadingToast)
        toast.error('Erro ao marcar como paga')
        await handlePaymentHistory(selectedClient.id)
      }
    }
  }

  const handleUnmarkAsPaid = async (invoiceId: string) => {
    if (!selectedClient) return

    if (window.confirm('Desmarcar esta fatura como paga? Isso irá reverter a renovação do cliente.')) {
      // Mostrar loading imediatamente
      const loadingToast = toast.loading('Revertendo pagamento...')
      
      try {
        // Atualizar estado local imediatamente para feedback visual
        setPaymentHistory(prev => prev.map(p => 
          p.id === invoiceId 
            ? { ...p, status: 'pending', payment_date: null }
            : p
        ))

        const response = await fetch(`/api-invoices.php?id=${invoiceId}&action=unmark-paid`, {
          method: 'PUT',
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({
            client_id: selectedClient.id
          })
        })

        const data = await response.json()
        toast.dismiss(loadingToast)
        
        if (data.success) {
          toast.success('Fatura desmarcada e renovação revertida!')
          // Recarregar dados do servidor em background
          handlePaymentHistory(selectedClient.id)
          fetchClients()
        } else {
          toast.error(data.error || 'Erro ao desmarcar')
          await handlePaymentHistory(selectedClient.id)
        }
      } catch (error) {
        toast.dismiss(loadingToast)
        toast.error('Erro ao desmarcar')
        await handlePaymentHistory(selectedClient.id)
      }
    }
  }

  const handleWhatsApp = (name: string, phone: string) => {
    toast.success(`Enviar WhatsApp para ${name} (${phone}) - Em desenvolvimento`, {
      duration: 3000,
    })
    // TODO: Implementar modal de WhatsApp
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

  return (
    <div className="space-y-6">
      {viewMode === 'list' ? (
        <>
          <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
              <h1 className="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white">
                Clientes
              </h1>
              <p className="text-sm md:text-base text-gray-600 dark:text-gray-400 mt-1">
                Gerencie seus clientes
              </p>
            </div>
            <button 
              onClick={() => setShowForm(true)}
              className="flex items-center gap-2 px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-colors text-sm md:text-base w-full sm:w-auto justify-center"
            >
              <Plus className="w-5 h-5" />
              Novo Cliente
            </button>
          </div>

      {showForm && (
        <div className="bg-white/60 dark:bg-gray-800/30 backdrop-blur-md rounded-xl border border-gray-200/50 dark:border-gray-700/50 p-6 mb-6 animate-in slide-in-from-top duration-300">
          <h3 className="text-xl font-semibold text-gray-900 dark:text-white mb-6">
            {editingClient ? 'Editar Cliente' : 'Novo Cliente'}
          </h3>
          <form onSubmit={handleSave}>
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
              <div>
                <label className="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">Nome *</label>
                <input type="text" required value={formData.name} onChange={(e) => setFormData({ ...formData, name: e.target.value })} className="w-full px-4 py-2.5 rounded-lg border border-gray-200 dark:border-gray-700/50 bg-white/80 dark:bg-gray-900/30 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all" />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">Email</label>
                <input type="email" value={formData.email} onChange={(e) => setFormData({ ...formData, email: e.target.value })} className="w-full px-4 py-2.5 rounded-lg border border-gray-200 dark:border-gray-700/50 bg-white/80 dark:bg-gray-900/30 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all" />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">WhatsApp</label>
                <input type="tel" value={formData.phone} onChange={(e) => setFormData({ ...formData, phone: e.target.value })} className="w-full px-4 py-2.5 rounded-lg border border-gray-200 dark:border-gray-700/50 bg-white/80 dark:bg-gray-900/30 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all" />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">Usuário IPTV</label>
                <input type="text" value={formData.username} onChange={(e) => setFormData({ ...formData, username: e.target.value })} className="w-full px-4 py-2.5 rounded-lg border border-gray-200 dark:border-gray-700/50 bg-white/80 dark:bg-gray-900/30 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all" />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">Senha IPTV</label>
                <input type="text" value={formData.password} onChange={(e) => setFormData({ ...formData, password: e.target.value })} className="w-full px-4 py-2.5 rounded-lg border border-gray-200 dark:border-gray-700/50 bg-white/80 dark:bg-gray-900/30 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all" />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">Servidor *</label>
                <select 
                  value={formData.server} 
                  onChange={(e) => {
                    setFormData({ ...formData, server: e.target.value, plan: '' })
                  }} 
                  className="w-full px-4 py-2.5 rounded-lg border border-gray-200 dark:border-gray-700/50 bg-white/80 dark:bg-gray-900/30 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all"
                  required
                >
                  <option value="">Selecione um servidor</option>
                  {servers.map(server => (
                    <option key={server.id} value={server.name}>{server.name}</option>
                  ))}
                </select>
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">Plano *</label>
                <select 
                  value={formData.plan} 
                  onChange={(e) => {
                    const selectedPlan = filteredPlans.find(p => p.name === e.target.value)
                    setFormData({ 
                      ...formData, 
                      plan: e.target.value,
                      value: selectedPlan ? selectedPlan.price : formData.value
                    })
                  }} 
                  className="w-full px-4 py-2.5 rounded-lg border border-gray-200 dark:border-gray-700/50 bg-white/80 dark:bg-gray-900/30 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all"
                  required
                  disabled={!formData.server}
                >
                  <option value="">{!formData.server ? 'Selecione um servidor primeiro' : filteredPlans.length === 0 ? 'Este servidor não possui planos' : 'Selecione um plano'}</option>
                  {filteredPlans.map(plan => (
                    <option key={plan.id} value={plan.name}>{plan.name} - R$ {plan.price.toFixed(2)}</option>
                  ))}
                </select>
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">Valor *</label>
                <input type="number" required step="0.01" value={formData.value} onChange={(e) => setFormData({ ...formData, value: parseFloat(e.target.value) })} className="w-full px-4 py-2.5 rounded-lg border border-gray-200 dark:border-gray-700/50 bg-white/80 dark:bg-gray-900/30 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all" />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">Vencimento *</label>
                <input type="date" required value={formData.renewal_date} onChange={(e) => setFormData({ ...formData, renewal_date: e.target.value })} className="w-full px-4 py-2.5 rounded-lg border border-gray-200 dark:border-gray-700/50 bg-white/80 dark:bg-gray-900/30 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all" />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">MAC</label>
                <input type="text" value={formData.mac} onChange={(e) => setFormData({ ...formData, mac: e.target.value })} className="w-full px-4 py-2.5 rounded-lg border border-gray-200 dark:border-gray-700/50 bg-white/80 dark:bg-gray-900/30 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all" />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">Telas</label>
                <input type="number" min="1" value={formData.screens} onChange={(e) => setFormData({ ...formData, screens: parseInt(e.target.value) })} className="w-full px-4 py-2.5 rounded-lg border border-gray-200 dark:border-gray-700/50 bg-white/80 dark:bg-gray-900/30 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all" />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">Notificações</label>
                <select value={formData.notifications} onChange={(e) => setFormData({ ...formData, notifications: e.target.value as 'sim' | 'nao' })} className="w-full px-4 py-2.5 rounded-lg border border-gray-200 dark:border-gray-700/50 bg-white/80 dark:bg-gray-900/30 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all">
                  <option value="sim">Sim</option>
                  <option value="nao">Não</option>
                </select>
              </div>
            </div>
            <div className="mb-6">
              <label className="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">Observações</label>
              <textarea rows={3} value={formData.notes} onChange={(e) => setFormData({ ...formData, notes: e.target.value })} className="w-full px-4 py-2.5 rounded-lg border border-gray-200 dark:border-gray-700/50 bg-white/80 dark:bg-gray-900/30 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all" />
            </div>
            <div className="flex justify-end gap-3">
              <button type="button" onClick={handleCancel} className="px-6 py-2.5 text-gray-700 dark:text-gray-200 bg-white/80 dark:bg-gray-900/30 hover:bg-gray-100 dark:hover:bg-gray-700/50 rounded-lg transition-colors border border-gray-200 dark:border-gray-700/50">
                Cancelar
              </button>
              <button type="submit" disabled={saving} className="px-6 py-2.5 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-colors disabled:opacity-50 shadow-sm">
                {saving ? 'Salvando...' : 'Salvar'}
              </button>
            </div>
          </form>
        </div>
      )}

      <div className="bg-white/60 dark:bg-gray-800/30 backdrop-blur-md rounded-xl border border-gray-200/50 dark:border-gray-700/50 p-6 mb-6">
        <div className="flex flex-col sm:flex-row gap-4">
          <div className="flex-1 relative">
            <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5" />
            <input
              type="text"
              placeholder="Buscar clientes..."
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
              className="w-full pl-10 pr-4 py-2.5 rounded-lg border border-gray-200 dark:border-gray-700/50 bg-white/80 dark:bg-gray-900/30 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all"
            />
          </div>
          <select
            value={statusFilter}
            onChange={(e) => setStatusFilter(e.target.value)}
            className="px-4 py-2.5 rounded-lg border border-gray-200 dark:border-gray-700/50 bg-white/80 dark:bg-gray-900/30 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all"
          >
            <option value="all" className="bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100">Todos os status</option>
            <option value="active" className="bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100">Ativos</option>
            <option value="inactive" className="bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100">Inativos</option>
            <option value="suspended" className="bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100">Suspensos</option>
          </select>
        </div>
      </div>

        <div className="bg-white/60 dark:bg-gray-800/30 backdrop-blur-md rounded-xl border border-gray-200/50 dark:border-gray-700/50 overflow-hidden">
          <div className="overflow-x-auto">
            <table className="w-full">
              <thead className="bg-gray-50/50 dark:bg-gray-900/20 border-b border-gray-200/50 dark:border-gray-700/30">
                <tr>
                  <th className="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                    Nome
                  </th>
                  <th className="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                    Usuário
                  </th>
                  <th className="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                    WhatsApp
                  </th>
                  <th className="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                    Plano
                  </th>
                  <th className="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                    Valor
                  </th>
                  <th className="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                    Vencimento
                  </th>
                  <th className="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                    Aplicativos
                  </th>
                  <th className="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                    Notificações
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
                  <td colSpan={10} className="px-6 py-4">
                    <LoadingSpinner />
                  </td>
                </tr>
              ) : filteredClients.length === 0 ? (
                <tr>
                  <td colSpan={10} className="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                    Nenhum cliente encontrado
                  </td>
                </tr>
              ) : (
                filteredClients.map((client) => (
                  <tr key={client.id} className="hover:bg-white/40 dark:hover:bg-gray-700/20 transition-colors">
                    <td className="px-6 py-4 whitespace-nowrap">
                      <div className="text-sm font-medium text-gray-900 dark:text-white">
                        {client.name}
                      </div>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap">
                      <div className="text-sm text-gray-600 dark:text-gray-400 font-mono">
                        {client.username || 'N/A'}
                      </div>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap">
                      {client.phone ? (
                        <div className="text-sm text-gray-900 dark:text-white font-medium">
                          {client.phone}
                        </div>
                      ) : (
                        <div className="text-sm text-gray-400 dark:text-gray-500 italic">
                          Sem WhatsApp
                        </div>
                      )}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap">
                      <span className="px-2 py-1 text-xs font-medium rounded bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400">
                        {client.plan}
                      </span>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                      {formatMoney(client.value)}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                      {formatDate(client.renewal_date)}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap">
                      <div className="flex flex-wrap gap-1">
                        {client.applications && client.applications.length > 0 ? (
                          client.applications.map((app: string, index: number) => (
                            <span
                              key={index}
                              className="px-2 py-0.5 text-xs font-medium rounded bg-purple-100 text-purple-800 dark:bg-purple-900/20 dark:text-purple-400"
                            >
                              {app}
                            </span>
                          ))
                        ) : (
                          <span className="text-xs text-gray-400 dark:text-gray-500 italic">
                            Nenhum
                          </span>
                        )}
                      </div>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap">
                      <span
                        className={`px-2 py-1 text-xs font-medium rounded ${
                          client.notifications === 'sim'
                            ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400'
                            : 'bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400'
                        }`}
                      >
                        {client.notifications === 'sim' ? 'Ativado' : 'Desativado'}
                      </span>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap">
                      <span
                        className={`px-2 py-1 text-xs font-medium rounded ${
                          client.status === 'active'
                            ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400'
                            : client.status === 'suspended'
                            ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400'
                            : 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400'
                        }`}
                      >
                        {client.status === 'active' ? 'Ativo' : client.status === 'suspended' ? 'Suspenso' : 'Inativo'}
                      </span>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                      <div className="flex items-center justify-end gap-1.5">
                        <button
                          onClick={() => setEditingClient(client)}
                          className="p-2 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-all hover:scale-110"
                          title="Editar Cliente"
                        >
                          <Edit className="w-4 h-4" />
                        </button>
                        <button
                          onClick={() => handleGenerateInvoice(client.id)}
                          className="p-2 text-purple-600 hover:bg-purple-50 dark:hover:bg-purple-900/20 rounded-lg transition-all hover:scale-110"
                          title="Gerar Fatura"
                        >
                          <FileText className="w-4 h-4" />
                        </button>
                        <button
                          onClick={() => handlePaymentHistory(client.id)}
                          className="p-2 text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 rounded-lg transition-all hover:scale-110"
                          title="Histórico de Pagamentos"
                        >
                          <CreditCard className="w-4 h-4" />
                        </button>
                        <button
                          onClick={() => client.phone && handleWhatsApp(client.name, client.phone)}
                          disabled={!client.phone}
                          className={`p-2 rounded-lg transition-all hover:scale-110 ${
                            client.phone 
                              ? 'text-green-600 hover:bg-green-50 dark:hover:bg-green-900/20' 
                              : 'text-gray-400 cursor-not-allowed opacity-50'
                          }`}
                          title={client.phone ? 'Enviar WhatsApp' : 'Número não cadastrado'}
                        >
                          <MessageSquare className="w-4 h-4" />
                        </button>
                        <button
                          onClick={() => handleDelete(client.id)}
                          className="p-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-all hover:scale-110"
                          title="Excluir Cliente"
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
        </>
      ) : (
        <div className="space-y-6">
          {/* Header do Histórico */}
          <div className="flex items-center gap-4">
            <button
              onClick={handleBackToList}
              className="p-2 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition-colors"
            >
              <svg className="w-6 h-6 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
              </svg>
            </button>
            <div className="flex-1">
              <h1 className="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white">
                Histórico de Pagamentos
              </h1>
              <p className="text-sm md:text-base text-gray-600 dark:text-gray-400 mt-1">
                {selectedClient?.name}
              </p>
            </div>
          </div>

          {/* Estatísticas */}
          <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div className="bg-white/80 dark:bg-gray-800/50 backdrop-blur-sm rounded-xl p-4 border border-gray-200 dark:border-gray-700">
              <p className="text-sm text-gray-600 dark:text-gray-400">Total Pago</p>
              <p className="text-2xl font-bold text-green-600 dark:text-green-400 mt-1">
                R$ {(paymentHistory.filter(p => p.status === 'paid').reduce((sum, p) => sum + Number(p.final_value || p.value || 0), 0) || 0).toFixed(2)}
              </p>
            </div>
            <div className="bg-white/80 dark:bg-gray-800/50 backdrop-blur-sm rounded-xl p-4 border border-gray-200 dark:border-gray-700">
              <p className="text-sm text-gray-600 dark:text-gray-400">Pendente</p>
              <p className="text-2xl font-bold text-yellow-600 dark:text-yellow-400 mt-1">
                R$ {(paymentHistory.filter(p => p.status === 'pending').reduce((sum, p) => sum + Number(p.final_value || p.value || 0), 0) || 0).toFixed(2)}
              </p>
            </div>
            <div className="bg-white/80 dark:bg-gray-800/50 backdrop-blur-sm rounded-xl p-4 border border-gray-200 dark:border-gray-700">
              <p className="text-sm text-gray-600 dark:text-gray-400">Vencido</p>
              <p className="text-2xl font-bold text-red-600 dark:text-red-400 mt-1">
                R$ {(paymentHistory.filter(p => p.status === 'overdue').reduce((sum, p) => sum + Number(p.final_value || p.value || 0), 0) || 0).toFixed(2)}
              </p>
            </div>
            <div className="bg-white/80 dark:bg-gray-800/50 backdrop-blur-sm rounded-xl p-4 border border-gray-200 dark:border-gray-700">
              <p className="text-sm text-gray-600 dark:text-gray-400">Total de Faturas</p>
              <p className="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                {paymentHistory.length}
              </p>
            </div>
          </div>

          {/* Tabela de Histórico */}
          <div className="bg-white/60 dark:bg-gray-800/30 backdrop-blur-md rounded-xl border border-gray-200/50 dark:border-gray-700/50 overflow-hidden">
            <div className="overflow-x-auto">
              <table className="w-full">
                <thead className="bg-gray-50/50 dark:bg-gray-900/20 border-b border-gray-200/50 dark:border-gray-700/30">
                  <tr>
                    <th className="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                      Data
                    </th>
                    <th className="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                      Descrição
                    </th>
                    <th className="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                      Valor
                    </th>
                    <th className="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                      Vencimento
                    </th>
                    <th className="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                      Status
                    </th>
                    <th className="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                      Pagamento
                    </th>
                    <th className="px-6 py-4 text-right text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                      Ações
                    </th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-gray-200 dark:divide-gray-700/30">
                  {loadingHistory ? (
                    <tr>
                      <td colSpan={7} className="px-6 py-4">
                        <LoadingSpinner />
                      </td>
                    </tr>
                  ) : paymentHistory.length === 0 ? (
                    <tr>
                      <td colSpan={7} className="px-6 py-12 text-center">
                        <div className="flex flex-col items-center gap-2">
                          <FileText className="w-12 h-12 text-gray-400" />
                          <p className="text-gray-500 dark:text-gray-400">Nenhum pagamento registrado</p>
                        </div>
                      </td>
                    </tr>
                  ) : (
                    paymentHistory.map((payment) => (
                      <tr key={payment.id} className="hover:bg-white/40 dark:hover:bg-gray-700/20 transition-colors">
                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                          {payment.created_at ? formatDate(payment.created_at) : '-'}
                        </td>
                        <td className="px-6 py-4 text-sm text-gray-900 dark:text-white">
                          {payment.description || 'Sem descrição'}
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                          {formatMoney(payment.final_value || payment.value || 0)}
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                          {payment.due_date ? formatDate(payment.due_date) : '-'}
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap">
                          <span
                            className={`px-2 py-1 text-xs font-medium rounded ${
                              payment.status === 'paid'
                                ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400'
                                : payment.status === 'overdue'
                                ? 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400'
                                : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400'
                            }`}
                          >
                            {payment.status === 'paid' ? 'Pago' : payment.status === 'overdue' ? 'Vencido' : 'Pendente'}
                          </span>
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                          {payment.payment_date ? formatDate(payment.payment_date) : '-'}
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap text-right">
                          {payment.status === 'paid' ? (
                            <button
                              onClick={() => handleUnmarkAsPaid(payment.id)}
                              className="px-3 py-1.5 text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400 hover:bg-red-200 dark:hover:bg-red-900/30 rounded transition-all"
                              title="Desmarcar como paga"
                            >
                              Desmarcar
                            </button>
                          ) : (
                            <button
                              onClick={() => handleMarkAsPaid(payment.id)}
                              className="px-3 py-1.5 text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400 hover:bg-green-200 dark:hover:bg-green-900/30 rounded transition-all"
                              title="Marcar como paga"
                            >
                              Marcar Paga
                            </button>
                          )}
                        </td>
                      </tr>
                    ))
                  )}
                </tbody>
              </table>
            </div>
          </div>
        </div>
      )}
    </div>
  )
}
