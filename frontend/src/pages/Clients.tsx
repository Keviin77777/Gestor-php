import { useEffect, useState, useRef } from 'react'
import { Plus, Search, Edit, Trash2, FileText, CreditCard, MessageSquare } from 'lucide-react'
import { useClientStore } from '@/stores/useClientStore'
import toast from 'react-hot-toast'
import LoadingSpinner from '../components/LoadingSpinner'
import WhatsAppModal from '../components/WhatsAppModal'
import ConfirmModal from '../components/ConfirmModal'
import type { Client } from '@/types'
import { usePageTitle } from '@/hooks/usePageTitle'

export default function Clients() {
  usePageTitle('Clientes')
  const formRef = useRef<HTMLDivElement>(null)
  const { clients, loading, fetchClients, deleteClient, addClient, updateClient } = useClientStore()
  const [searchTerm, setSearchTerm] = useState('')
  const [statusFilter, setStatusFilter] = useState<string>('all')
  const [serverFilter, setServerFilter] = useState<string>('all')
  const [planFilter, setPlanFilter] = useState<string>('all')
  const [itemsPerPage, setItemsPerPage] = useState(10)
  const [currentPage, setCurrentPage] = useState(1)
  const [showForm, setShowForm] = useState(false)
  const [editingClient, setEditingClient] = useState<Client | null>(null)
  const [servers, setServers] = useState<any[]>([])
  const [plans, setPlans] = useState<any[]>([])
  const [filteredPlans, setFilteredPlans] = useState<any[]>([])
  const [applications, setApplications] = useState<any[]>([])
  const [viewMode, setViewMode] = useState<'list' | 'payment-history'>('list')
  const [selectedClient, setSelectedClient] = useState<Client | null>(null)
  const [paymentHistory, setPaymentHistory] = useState<any[]>([])
  const [loadingHistory, setLoadingHistory] = useState(false)
  const [whatsappClient, setWhatsappClient] = useState<Client | null>(null)
  const [confirmModal, setConfirmModal] = useState<{
    isOpen: boolean
    title: string
    message: string
    onConfirm: () => void
    type?: 'danger' | 'warning' | 'info'
  }>({
    isOpen: false,
    title: '',
    message: '',
    onConfirm: () => {},
    type: 'warning'
  })
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
  const [macFormat, setMacFormat] = useState<':' | '-'>(':') // Formato do MAC

  useEffect(() => {
    fetchClients()
    loadServers()
    loadPlans()
    loadApplications()
  }, [fetchClients])

  // Scroll para o formulário quando editar um cliente
  useEffect(() => {
    if (editingClient && formRef.current) {
      formRef.current.scrollIntoView({ behavior: 'smooth', block: 'start' })
    }
  }, [editingClient])

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
      // Erro ao carregar planos
    }
  }

  const loadApplications = async () => {
    try {
      const response = await fetch('/api-applications.php', {
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('token')}`
        }
      })
      const data = await response.json()
      if (data.success) {
        setApplications(data.applications || [])
      }
    } catch (error) {
      // Erro ao carregar aplicativos
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
      client.phone?.includes(searchTerm) ||
      client.username?.toLowerCase().includes(searchTerm.toLowerCase())
    
    const matchesServer = serverFilter === 'all' || client.server === serverFilter
    const matchesPlan = planFilter === 'all' || client.plan === planFilter
    
    // Detectar se o cliente está vencido pela data de renovação
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
      // Mostrar clientes vencidos pela data OU com status 'expired'
      matchesStatus = isExpired || client.status === 'expired'
    } else {
      // Para outros filtros, verificar o status E que não esteja vencido pela data
      matchesStatus = client.status === statusFilter && !isExpired
    }

    return matchesSearch && matchesServer && matchesPlan && matchesStatus
  })

  // Paginação
  const totalPages = Math.ceil(filteredClients.length / itemsPerPage)
  const startIndex = (currentPage - 1) * itemsPerPage
  const endIndex = startIndex + itemsPerPage
  const paginatedClients = filteredClients.slice(startIndex, endIndex)

  // Reset para página 1 quando filtros mudarem
  useEffect(() => {
    setCurrentPage(1)
  }, [searchTerm, serverFilter, planFilter, statusFilter, itemsPerPage])

  const uniqueServers = Array.from(new Set(clients.map(c => c.server).filter(Boolean)))
  const uniquePlans = Array.from(new Set(clients.map(c => c.plan).filter(Boolean)))

  const handleDelete = async (id: string) => {
    setConfirmModal({
      isOpen: true,
      title: 'Excluir Cliente',
      message: 'Tem certeza que deseja excluir este cliente? Esta ação não pode ser desfeita.',
      type: 'danger',
      onConfirm: async () => {
        try {
          await deleteClient(id)
          toast.success('Cliente excluído com sucesso!')
          fetchClients()
        } catch (error) {
          toast.error('Erro ao excluir cliente')
        }
        setConfirmModal({ ...confirmModal, isOpen: false })
      }
    })
  }

  const handleGenerateInvoice = async (clientId: string) => {
    const client = clients.find(c => c.id === clientId)
    if (!client) {
      toast.error('Cliente não encontrado')
      return
    }

    // Buscar faturas existentes do cliente
    try {
      const response = await fetch('/api-invoices.php', {
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('token')}`
        }
      })
      const data = await response.json()
      
      if (!data.success) {
        toast.error('Erro ao verificar faturas')
        return
      }

      // Filtrar faturas do cliente
      const clientInvoices = data.invoices?.filter((inv: any) => inv.client_id === clientId) || []
      
      // Contar faturas pendentes/vencidas (não pagas)
      const unpaidInvoices = clientInvoices.filter((inv: any) => inv.status !== 'paid')
      const unpaidCount = unpaidInvoices.length

      // Usar a data de vencimento do cliente como base
      const clientRenewalDate = new Date(client.renewal_date + 'T00:00:00')
      
      // Lógica:
      // - 0 faturas pendentes = gera fatura do mês do vencimento (mês atual do cliente)
      // - 1 fatura pendente = gera fatura do próximo mês (+1 mês)
      // - 2 faturas pendentes = gera fatura de 2 meses à frente (+2 meses)
      const nextDueDate = new Date(clientRenewalDate)
      
      // Se há faturas pendentes, adicionar meses
      if (unpaidCount > 0) {
        nextDueDate.setMonth(nextDueDate.getMonth() + unpaidCount)
      }
      
      const nextMonth = nextDueDate.toLocaleDateString('pt-BR', { month: 'long', year: 'numeric' })

      let message = `Deseja gerar uma fatura para ${client.name}?\n\n`
      
      if (unpaidCount === 0) {
        message += `📅 Mês de referência: ${nextMonth}\n`
        message += `💰 Valor: R$ ${client.value.toFixed(2)}\n`
        message += `📆 Vencimento: ${nextDueDate.toLocaleDateString('pt-BR')}\n\n`
        message += `ℹ️ Fatura do mês atual (vencimento do cliente)`
      } else if (unpaidCount === 1) {
        message += `⚠️ Existe 1 fatura pendente\n\n`
        message += `📅 Mês de referência: ${nextMonth}\n`
        message += `💰 Valor: R$ ${client.value.toFixed(2)}\n`
        message += `📆 Vencimento: ${nextDueDate.toLocaleDateString('pt-BR')}\n\n`
        message += `ℹ️ Fatura do próximo mês (após a pendente)`
      } else {
        message += `⚠️ Existem ${unpaidCount} faturas pendentes\n\n`
        message += `📅 Mês de referência: ${nextMonth}\n`
        message += `💰 Valor: R$ ${client.value.toFixed(2)}\n`
        message += `📆 Vencimento: ${nextDueDate.toLocaleDateString('pt-BR')}\n\n`
        message += `ℹ️ Fatura de ${unpaidCount} meses à frente (após as pendentes)`
      }

      setConfirmModal({
        isOpen: true,
        title: 'Gerar Fatura',
        message: message,
        type: 'info',
        onConfirm: async () => {
          setConfirmModal({ ...confirmModal, isOpen: false })
          try {
            const response = await fetch('/api-generate-invoice.php', {
              method: 'POST',
              headers: {
                'Authorization': `Bearer ${localStorage.getItem('token')}`,
                'Content-Type': 'application/json'
              },
              body: JSON.stringify({
                client_id: clientId
              })
            })

            const data = await response.json()
            if (data.success) {
              toast.success('Fatura gerada com sucesso!')
              // Recarregar histórico se estiver visualizando
              if (selectedClient?.id === clientId) {
                handlePaymentHistory(clientId)
              }
            } else {
              toast.error(data.error || 'Erro ao gerar fatura')
            }
          } catch (error) {
            toast.error('Erro ao gerar fatura')
          }
        }
      })
    } catch (error) {
      toast.error('Erro ao verificar faturas')
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

    setConfirmModal({
      isOpen: true,
      title: 'Marcar como Paga',
      message: 'Marcar esta fatura como paga? Isso irá renovar o cliente automaticamente.',
      type: 'info',
      onConfirm: async () => {
        setConfirmModal({ ...confirmModal, isOpen: false })
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
    })
  }

  const handleDeleteInvoice = async (invoiceId: string) => {
    if (!selectedClient) return

    setConfirmModal({
      isOpen: true,
      title: 'Excluir Fatura',
      message: 'Tem certeza que deseja excluir esta fatura? Esta ação não pode ser desfeita.',
      type: 'danger',
      onConfirm: async () => {
        setConfirmModal({ ...confirmModal, isOpen: false })
        const loadingToast = toast.loading('Excluindo fatura...')
        
        try {
          // Atualizar estado local imediatamente para feedback visual
          setPaymentHistory(prev => prev.filter(p => p.id !== invoiceId))

          const response = await fetch(`/api-invoices.php?id=${invoiceId}`, {
            method: 'DELETE',
            headers: {
              'Authorization': `Bearer ${localStorage.getItem('token')}`
            }
          })

          const data = await response.json()
          toast.dismiss(loadingToast)
          
          if (data.success) {
            toast.success('Fatura excluída com sucesso!')
            // Recarregar dados do servidor em background
            handlePaymentHistory(selectedClient.id)
            fetchClients()
          } else {
            toast.error(data.error || 'Erro ao excluir fatura')
            await handlePaymentHistory(selectedClient.id)
          }
        } catch (error) {
          toast.dismiss(loadingToast)
          toast.error('Erro ao excluir fatura')
          await handlePaymentHistory(selectedClient.id)
        }
      }
    })
  }

  const handleUnmarkAsPaid = async (invoiceId: string) => {
    if (!selectedClient) return

    setConfirmModal({
      isOpen: true,
      title: 'Desmarcar como Paga',
      message: 'Desmarcar esta fatura como paga? Isso irá reverter a renovação do cliente.',
      type: 'warning',
      onConfirm: async () => {
        setConfirmModal({ ...confirmModal, isOpen: false })
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
    })
  }

  const handleWhatsApp = (client: Client) => {
    setWhatsappClient(client)
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

  const getRenewalBadge = (renewalDate: string) => {
    const today = new Date()
    today.setHours(0, 0, 0, 0)
    
    const renewal = new Date(renewalDate)
    renewal.setHours(0, 0, 0, 0)
    
    const diffTime = renewal.getTime() - today.getTime()
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
    
    // Formatar data e hora
    const dateObj = new Date(renewalDate)
    const formattedDate = dateObj.toLocaleDateString('pt-BR')
    const formattedTime = '23:59'
    
    let bgColor = ''
    let textColor = ''
    
    if (diffDays < 0) {
      // Vencido
      bgColor = 'bg-red-100 dark:bg-red-900/30'
      textColor = 'text-red-700 dark:text-red-400'
    } else if (diffDays === 0) {
      // Vence hoje
      bgColor = 'bg-orange-100 dark:bg-orange-900/30'
      textColor = 'text-orange-700 dark:text-orange-400'
    } else if (diffDays <= 3) {
      // Vence em 1-3 dias
      bgColor = 'bg-yellow-100 dark:bg-yellow-900/30'
      textColor = 'text-yellow-700 dark:text-yellow-400'
    } else if (diffDays <= 7) {
      // Vence em 4-7 dias
      bgColor = 'bg-blue-100 dark:bg-blue-900/30'
      textColor = 'text-blue-700 dark:text-blue-400'
    } else {
      // Mais de 7 dias
      bgColor = 'bg-green-100 dark:bg-green-900/30'
      textColor = 'text-green-700 dark:text-green-400'
    }
    
    return (
      <span className={`inline-flex items-center gap-1.5 px-2 py-1 rounded text-xs font-medium ${bgColor} ${textColor}`}>
        <svg className="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
        </svg>
        {formattedDate} {formattedTime}
      </span>
    )
  }

  return (
    <div className="space-y-6">
      {viewMode === 'list' ? (
        <>
          <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
              <h1 className="text-2xl md:text-3xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">
                Clientes
              </h1>
              <p className="text-sm md:text-base text-gray-600 dark:text-gray-400 mt-1">
                {filteredClients.length} {filteredClients.length === 1 ? 'cliente' : 'clientes'}
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
        <div ref={formRef} className="bg-white/60 dark:bg-gray-800/30 backdrop-blur-md rounded-xl border border-gray-200/50 dark:border-gray-700/50 p-6 mb-6 animate-in slide-in-from-top duration-300">
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
                <input 
                  type="date" 
                  required 
                  value={formData.renewal_date} 
                  onChange={(e) => {
                    // Corrigir timezone - garantir que a data seja salva corretamente
                    const selectedDate = e.target.value
                    setFormData({ ...formData, renewal_date: selectedDate })
                  }} 
                  className="w-full px-4 py-2.5 rounded-lg border border-gray-200 dark:border-gray-700/50 bg-white/80 dark:bg-gray-900/30 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all" 
                />
              </div>
              <div className="md:col-span-2">
                <label className="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">MAC Address</label>
                <div className="flex gap-2">
                  <select
                    value={macFormat}
                    onChange={(e) => {
                      const newFormat = e.target.value as ':' | '-'
                      setMacFormat(newFormat)
                      // Reformatar MAC existente com novo separador
                      if (formData.mac) {
                        const cleanMac = formData.mac.replace(/[^0-9A-F]/g, '')
                        const formatted = cleanMac.match(/.{1,2}/g)?.join(newFormat) || cleanMac
                        setFormData({ ...formData, mac: formatted })
                      }
                    }}
                    className="w-32 px-4 py-2.5 rounded-lg border border-gray-200 dark:border-gray-700/50 bg-white/80 dark:bg-gray-900/30 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all"
                  >
                    <option value=":">: (dois-pontos)</option>
                    <option value="-">- (traço)</option>
                  </select>
                  <input 
                    type="text" 
                    value={formData.mac} 
                    onChange={(e) => {
                      let value = e.target.value.toUpperCase().replace(/[^0-9A-F]/g, '')
                      if (value.length > 12) value = value.slice(0, 12)
                      const formatted = value.match(/.{1,2}/g)?.join(macFormat) || value
                      setFormData({ ...formData, mac: formatted })
                    }}
                    placeholder={macFormat === ':' ? '00:1A:2B:3C:4D:5E' : '00-1A-2B-3C-4D-5E'}
                    maxLength={17}
                    className="flex-1 px-4 py-2.5 rounded-lg border border-gray-200 dark:border-gray-700/50 bg-white/80 dark:bg-gray-900/30 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all font-mono" 
                  />
                </div>
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
              <div>
                <label className="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">Aplicativo</label>
                <select 
                  value={formData.application_id || ''} 
                  onChange={(e) => setFormData({ ...formData, application_id: e.target.value ? Number(e.target.value) : undefined })} 
                  className="w-full px-4 py-2.5 rounded-lg border border-gray-200 dark:border-gray-700/50 bg-white/80 dark:bg-gray-900/30 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all"
                >
                  <option value="">Não informado</option>
                  {applications.map(app => (
                    <option key={app.id} value={app.id}>{app.name}</option>
                  ))}
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
        <div className="flex flex-col gap-4">
          {/* Linha 1: Busca e Filtros */}
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
              value={serverFilter}
              onChange={(e) => setServerFilter(e.target.value)}
              className="px-4 py-2.5 rounded-lg border border-gray-200 dark:border-gray-700/50 bg-white/80 dark:bg-gray-900/30 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all"
            >
              <option value="all" className="bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100">Todos os servidores</option>
              {uniqueServers.map(server => (
                <option key={server} value={server} className="bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100">
                  {server}
                </option>
              ))}
            </select>
            <select
              value={planFilter}
              onChange={(e) => setPlanFilter(e.target.value)}
              className="px-4 py-2.5 rounded-lg border border-gray-200 dark:border-gray-700/50 bg-white/80 dark:bg-gray-900/30 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all"
            >
              <option value="all" className="bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100">Todos os planos</option>
              {uniquePlans.map(plan => (
                <option key={plan} value={plan} className="bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100">
                  {plan}
                </option>
              ))}
            </select>
            <select
              value={statusFilter}
              onChange={(e) => setStatusFilter(e.target.value)}
              className="px-4 py-2.5 rounded-lg border border-gray-200 dark:border-gray-700/50 bg-white/80 dark:bg-gray-900/30 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all"
            >
              <option value="all" className="bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100">Todos os status</option>
              <option value="active" className="bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100">Ativos</option>
              <option value="expired" className="bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100">Vencidos</option>
              <option value="suspended" className="bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100">Suspensos</option>
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

            {(searchTerm || serverFilter !== 'all' || planFilter !== 'all' || statusFilter !== 'all') && (
              <button 
                onClick={() => { 
                  setSearchTerm(''); 
                  setServerFilter('all'); 
                  setPlanFilter('all');
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
                    Aplicativo
                  </th>
                  <th className="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                    MAC
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
                  <td colSpan={11} className="px-6 py-4">
                    <LoadingSpinner />
                  </td>
                </tr>
              ) : paginatedClients.length === 0 ? (
                <tr>
                  <td colSpan={11} className="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                    Nenhum cliente encontrado
                  </td>
                </tr>
              ) : (
                paginatedClients.map((client) => (
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
                        <div className="flex items-center gap-2 text-sm font-medium">
                          <svg className="w-4 h-4 text-green-600 dark:text-green-400 flex-shrink-0" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                          </svg>
                          <span className="text-gray-700 dark:text-gray-300">
                            {client.phone}
                          </span>
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
                    <td className="px-6 py-4 whitespace-nowrap">
                      {getRenewalBadge(client.renewal_date)}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap">
                      {client.application_name ? (
                        <span className="px-2 py-1 text-xs font-medium rounded bg-purple-100 text-purple-800 dark:bg-purple-900/20 dark:text-purple-400">
                          {client.application_name}
                        </span>
                      ) : (
                        <span className="text-xs text-gray-400 dark:text-gray-500 italic">
                          Não informado
                        </span>
                      )}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap">
                      {client.mac ? (
                        <span className="px-2 py-1 text-xs font-medium rounded bg-cyan-100 text-cyan-800 dark:bg-cyan-900/20 dark:text-cyan-400 font-mono">
                          {client.mac}
                        </span>
                      ) : (
                        <span className="text-xs text-gray-400 dark:text-gray-500 italic">
                          Não informado
                        </span>
                      )}
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
                        }`}
                      >
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
                        })()}
                      </span>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                      <div className="flex items-center justify-end gap-1.5">
                        <button
                          onClick={() => {
                            setEditingClient(client)
                            setShowForm(true)
                            setFormData({
                              name: client.name,
                              email: client.email || '',
                              phone: client.phone,
                              username: client.username,
                              password: client.password,
                              plan: client.plan,
                              value: client.value,
                              renewal_date: client.renewal_date,
                              server: client.server,
                              mac: client.mac || '',
                              screens: client.screens || 1,
                              notifications: client.notifications || 'sim',
                              notes: client.notes || '',
                            })
                          }}
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
                          onClick={() => client.phone && handleWhatsApp(client)}
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

        {/* Paginação */}
        {totalPages > 1 && (
          <div className="px-6 py-4 bg-gray-50/50 dark:bg-gray-900/20 border-t border-gray-200/50 dark:border-gray-700/30">
            <div className="flex items-center justify-between">
              <div className="text-sm text-gray-600 dark:text-gray-400">
                Mostrando {startIndex + 1} a {Math.min(endIndex, filteredClients.length)} de {filteredClients.length} clientes
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
              <h1 className="text-2xl md:text-3xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">
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
                          <div className="flex items-center justify-end gap-2">
                            {payment.status === 'paid' ? (
                              <button
                                onClick={() => handleUnmarkAsPaid(payment.id)}
                                className="px-3 py-1.5 text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400 hover:bg-yellow-200 dark:hover:bg-yellow-900/30 rounded transition-all"
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
                            <button
                              onClick={() => handleDeleteInvoice(payment.id)}
                              className="px-3 py-1.5 text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400 hover:bg-red-200 dark:hover:bg-red-900/30 rounded transition-all"
                              title="Excluir fatura"
                            >
                              Excluir
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
      )}

      {/* WhatsApp Modal */}
      {whatsappClient && (
        <WhatsAppModal
          client={whatsappClient}
          onClose={() => setWhatsappClient(null)}
        />
      )}

      {/* Confirm Modal */}
      <ConfirmModal
        isOpen={confirmModal.isOpen}
        title={confirmModal.title}
        message={confirmModal.message}
        type={confirmModal.type}
        onConfirm={confirmModal.onConfirm}
        onCancel={() => setConfirmModal({ ...confirmModal, isOpen: false })}
      />
    </div>
  )
}
