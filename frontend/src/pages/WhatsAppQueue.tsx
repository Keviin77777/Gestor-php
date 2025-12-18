import { useState, useEffect } from 'react'
import { Clock, CheckCircle, XCircle, RefreshCw, Trash2, Send } from 'lucide-react'
import toast from 'react-hot-toast'
import api from '../services/api'
import LoadingSpinner from '../components/LoadingSpinner'

interface QueueMessage {
  id: number
  client_name: string
  phone: string
  message: string
  status: 'pending' | 'processing' | 'sent' | 'failed'
  attempts: number
  max_attempts: number
  scheduled_at?: string
  created_at: string
  sent_at?: string
  error_message?: string
}

export default function WhatsAppQueue() {
  const [messages, setMessages] = useState<QueueMessage[]>([])
  const [loading, setLoading] = useState(true)
  const [filter, setFilter] = useState<string>('all')
  const [stats, setStats] = useState({
    pending: 0,
    processing: 0,
    sent: 0,
    failed: 0
  })

  useEffect(() => {
    loadQueue()
    loadStats()
    
    // Auto-refresh a cada 10 segundos
    const interval = setInterval(() => {
      loadQueue()
      loadStats()
    }, 10000)
    
    return () => clearInterval(interval)
  }, [filter])

  const loadStats = async () => {
    try {
      const response = await api.get('/api-whatsapp-queue.php?action=get_stats')
      if (response.data.success && response.data.stats) {
        setStats(response.data.stats)
      }
    } catch (error) {
      // Erro ao carregar estatísticas
    }
  }

  const loadQueue = async () => {
    try {
      const statusParam = filter !== 'all' ? `&status=${filter}` : ''
      const response = await api.get(`/api-whatsapp-queue.php?action=get_queue&page=1&per_page=100${statusParam}`)
      if (response.data.success && response.data.queue) {
        setMessages(response.data.queue)
      } else {
        setMessages([])
      }
    } catch (error) {
      setMessages([])
    } finally {
      setLoading(false)
    }
  }

  const handleRetry = async (id: number) => {
    if (!confirm('Deseja reenviar esta mensagem agora?')) return
    
    try {
      await api.post('/api-whatsapp-queue.php?action=retry', { id })
      toast.success('Mensagem reenviada para a fila!')
      loadQueue()
      loadStats()
    } catch (error) {
      toast.error('Erro ao reenviar mensagem')
    }
  }

  const handleDelete = async (id: number) => {
    if (!confirm('Deseja realmente excluir esta mensagem?')) return
    
    try {
      await api.post('/api-whatsapp-queue.php?action=delete', { id })
      toast.success('Mensagem excluída!')
      loadQueue()
      loadStats()
    } catch (error) {
      toast.error('Erro ao excluir mensagem')
    }
  }

  const handleForceProcess = async () => {
    if (!confirm('Deseja forçar o processamento imediato da fila?\n\nIsso irá processar as mensagens pendentes agora.')) return
    
    try {
      const response = await api.post('/api-whatsapp-queue.php?action=force_process')
      toast.success(response.data.message || 'Processamento da fila iniciado!')
      loadQueue()
      loadStats()
    } catch (error) {
      toast.error('Erro ao forçar processamento')
    }
  }

  const handleDeleteSent = async () => {
    if (!confirm('Deseja excluir TODAS as mensagens já enviadas?\n\nEsta ação não pode ser desfeita!')) return
    
    try {
      const response = await api.post('/api-whatsapp-queue.php?action=delete_sent')
      toast.success(`${response.data.deleted} mensagens enviadas foram excluídas!`)
      loadQueue()
      loadStats()
    } catch (error) {
      toast.error('Erro ao excluir mensagens')
    }
  }

  const getStatusIcon = (status: string) => {
    switch (status) {
      case 'sent':
        return <CheckCircle className="w-5 h-5 text-green-600" />
      case 'failed':
        return <XCircle className="w-5 h-5 text-red-600" />
      case 'processing':
        return <RefreshCw className="w-5 h-5 text-blue-600 animate-spin" />
      default:
        return <Clock className="w-5 h-5 text-yellow-600" />
    }
  }

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'sent': return 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400'
      case 'failed': return 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400'
      case 'processing': return 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400'
      default: return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400'
    }
  }

  const getStatusText = (status: string) => {
    switch (status) {
      case 'sent': return 'Enviada'
      case 'failed': return 'Falhou'
      case 'processing': return 'Processando'
      default: return 'Pendente'
    }
  }

  return (
    <div className="space-y-6">
      <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
          <h1 className="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white">Fila de Mensagens</h1>
          <p className="text-sm md:text-base text-gray-600 dark:text-gray-400 mt-1">Gerencie e monitore o envio de mensagens em massa</p>
        </div>
        <div className="flex flex-wrap gap-2">
          <button
            onClick={handleForceProcess}
            className="flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors text-sm"
          >
            <Send className="w-4 h-4" />
            Forçar Envio
          </button>
          <button
            onClick={handleDeleteSent}
            className="flex items-center gap-2 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors text-sm"
          >
            <Trash2 className="w-4 h-4" />
            Excluir Enviadas
          </button>
          <button
            onClick={() => { loadQueue(); loadStats(); }}
            className="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm"
          >
            <RefreshCw className="w-4 h-4" />
            Atualizar
          </button>
        </div>
      </div>

      {/* Stats */}
      <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div className="bg-white/80 dark:bg-gray-800/50 backdrop-blur-sm rounded-xl p-4 border border-gray-200 dark:border-gray-700">
          <div className="text-sm text-gray-600 dark:text-gray-400">Pendentes</div>
          <div className="text-2xl font-bold text-yellow-600">{stats.pending}</div>
        </div>
        <div className="bg-white/80 dark:bg-gray-800/50 backdrop-blur-sm rounded-xl p-4 border border-gray-200 dark:border-gray-700">
          <div className="text-sm text-gray-600 dark:text-gray-400">Processando</div>
          <div className="text-2xl font-bold text-blue-600">{stats.processing}</div>
        </div>
        <div className="bg-white/80 dark:bg-gray-800/50 backdrop-blur-sm rounded-xl p-4 border border-gray-200 dark:border-gray-700">
          <div className="text-sm text-gray-600 dark:text-gray-400">Enviadas</div>
          <div className="text-2xl font-bold text-green-600">{stats.sent}</div>
        </div>
        <div className="bg-white/80 dark:bg-gray-800/50 backdrop-blur-sm rounded-xl p-4 border border-gray-200 dark:border-gray-700">
          <div className="text-sm text-gray-600 dark:text-gray-400">Falhas</div>
          <div className="text-2xl font-bold text-red-600">{stats.failed}</div>
        </div>
      </div>

      {/* Filters */}
      <div className="flex flex-wrap gap-2">
        {['all', 'pending', 'processing', 'sent', 'failed'].map((status) => (
          <button
            key={status}
            onClick={() => setFilter(status)}
            className={`px-3 md:px-4 py-2 rounded-lg transition-colors text-sm md:text-base ${
              filter === status
                ? 'bg-green-600 text-white'
                : 'bg-white/80 dark:bg-gray-800/50 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'
            }`}
          >
            {status === 'all' ? 'Todas' : getStatusText(status)}
          </button>
        ))}
      </div>

      {loading ? (
        <LoadingSpinner />
      ) : (
        <div className="bg-white/80 dark:bg-gray-800/50 backdrop-blur-sm rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
          <div className="overflow-x-auto">
            <table className="w-full">
              <thead className="bg-gray-50 dark:bg-gray-900/50">
                <tr>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">ID</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Status</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Telefone</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Mensagem</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Tentativas</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Data</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Ações</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-200 dark:divide-gray-700">
                {messages.length === 0 ? (
                  <tr>
                    <td colSpan={7} className="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                      📭 Nenhuma mensagem na fila
                    </td>
                  </tr>
                ) : (
                  messages.map((message) => (
                    <tr key={message.id} className="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                      <td className="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">#{message.id}</td>
                      <td className="px-6 py-4">
                        <div className="flex items-center gap-2">
                          {getStatusIcon(message.status)}
                          <span className={`px-2 py-1 text-xs font-medium rounded ${getStatusColor(message.status)}`}>
                            {getStatusText(message.status)}
                          </span>
                        </div>
                      </td>
                      <td className="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">{message.phone}</td>
                      <td className="px-6 py-4 text-sm text-gray-600 dark:text-gray-400 max-w-xs truncate" title={message.message}>
                        {message.message.substring(0, 50)}{message.message.length > 50 ? '...' : ''}
                      </td>
                      <td className="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                        {message.attempts}/{message.max_attempts}
                      </td>
                      <td className="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                        {new Date(message.scheduled_at || message.created_at).toLocaleString('pt-BR')}
                      </td>
                      <td className="px-6 py-4">
                        <div className="flex gap-2">
                          {(message.status === 'pending' || message.status === 'failed') && (
                            <button
                              onClick={() => handleRetry(message.id)}
                              className="text-blue-600 hover:text-blue-700"
                              title="Reenviar"
                            >
                              <RefreshCw className="w-4 h-4" />
                            </button>
                          )}
                          <button
                            onClick={() => handleDelete(message.id)}
                            className="text-red-600 hover:text-red-700"
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
      )}
    </div>
  )
}
