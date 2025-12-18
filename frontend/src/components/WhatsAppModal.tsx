import { useState, useEffect } from 'react'
import { X, Send, FileText, MessageSquare, Loader2, Check, AlertCircle } from 'lucide-react'
import api from '@/services/api'
import toast from 'react-hot-toast'
import type { Client } from '@/types'

interface WhatsAppModalProps {
  client: Client
  onClose: () => void
}

interface Template {
  id: string
  name: string
  message: string
  is_active: boolean
}

export default function WhatsAppModal({ client, onClose }: WhatsAppModalProps) {
  const [activeTab, setActiveTab] = useState<'template' | 'custom'>('template')
  const [templates, setTemplates] = useState<Template[]>([])
  const [selectedTemplate, setSelectedTemplate] = useState<string>('')
  const [customMessage, setCustomMessage] = useState('')
  const [preview, setPreview] = useState('')
  const [sending, setSending] = useState(false)
  const [checking, setChecking] = useState(true)
  const [whatsappStatus, setWhatsappStatus] = useState<{
    connected: boolean
    message: string
  }>({ connected: false, message: '' })

  useEffect(() => {
    checkWhatsAppStatus()
    loadTemplates()
  }, [])

  useEffect(() => {
    if (selectedTemplate && activeTab === 'template') {
      generatePreview()
    }
  }, [selectedTemplate])

  const checkWhatsAppStatus = async () => {
    try {
      const response = await api.get('/api-whatsapp-check.php')
      if (response.data.success) {
        const status = response.data.status
        setWhatsappStatus({
          connected: status.has_session && status.has_settings,
          message: status.has_session 
            ? 'WhatsApp conectado e pronto' 
            : 'WhatsApp não está conectado'
        })
      } else {
        setWhatsappStatus({
          connected: false,
          message: 'Erro ao verificar status'
        })
      }
    } catch (error) {
      setWhatsappStatus({
        connected: false,
        message: 'Erro ao conectar com o servidor'
      })
    } finally {
      setChecking(false)
    }
  }

  const loadTemplates = async () => {
    try {
      const response = await api.get('/api-whatsapp-templates.php')
      if (response.data.success) {
        setTemplates(response.data.templates.filter((t: Template) => t.is_active))
      }
    } catch (error) {
      console.error('Erro ao carregar templates:', error)
    }
  }

  const generatePreview = () => {
    const template = templates.find(t => t.id === selectedTemplate)
    if (!template) return

    let message = template.message

    // Substituir variáveis básicas para preview
    const variables: Record<string, string> = {
      'cliente_nome': client.name,
      'cliente_telefone': client.phone || '',
      'cliente_plano': client.plan || 'Personalizado',
      'cliente_valor': `R$ ${(client.value || 0).toFixed(2)}`,
      'cliente_vencimento': client.renewal_date || '',
      'cliente_usuario': client.username || '',
      'cliente_senha': '********', // Não mostrar senha no preview
      'cliente_servidor': client.server || '',
      'cliente_mac': client.mac || '',
      'cliente_telas': String(client.screens || 1)
    }

    Object.keys(variables).forEach(key => {
      message = message.replace(new RegExp(`{{${key}}}`, 'g'), variables[key])
      message = message.replace(new RegExp(`{${key}}`, 'g'), variables[key])
    })

    setPreview(message)
  }

  const handleSend = async () => {
    if (!whatsappStatus.connected) {
      toast.error('WhatsApp não está conectado. Conecte primeiro na aba WhatsApp.', {
        duration: 4000,
        icon: '📱'
      })
      return
    }

    if (activeTab === 'template' && !selectedTemplate) {
      toast.error('Selecione um template', {
        icon: '⚠️'
      })
      return
    }

    if (activeTab === 'custom' && !customMessage.trim()) {
      toast.error('Digite uma mensagem', {
        icon: '⚠️'
      })
      return
    }

    try {
      setSending(true)
      const response = await api.post('/api-whatsapp-send.php', {
        phone: client.phone,
        message: activeTab === 'template' ? 'template' : customMessage,
        template_id: activeTab === 'template' ? selectedTemplate : null,
        client_id: client.id
      })

      if (response.data.success) {
        toast.success('Mensagem enviada com sucesso!', {
          duration: 4000,
          icon: '✅'
        })
        onClose()
      } else {
        toast.error(response.data.message || 'Erro ao enviar mensagem', {
          duration: 5000,
          icon: '❌'
        })
      }
    } catch (error: any) {
      toast.error(error.response?.data?.message || 'Erro ao enviar mensagem', {
        duration: 5000,
        icon: '❌'
      })
    } finally {
      setSending(false)
    }
  }

  return (
    <div className="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 flex items-center justify-center p-2 sm:p-4">
      <div className="bg-white dark:bg-gray-800 rounded-2xl max-w-3xl w-full max-h-[95vh] sm:max-h-[90vh] overflow-hidden shadow-2xl flex flex-col">
        {/* Header */}
        <div className="p-4 sm:p-6 border-b border-gray-200 dark:border-gray-700">
          <div className="flex items-center justify-between mb-3 sm:mb-4">
            <div className="flex items-center gap-2 sm:gap-3 flex-1 min-w-0">
              <div className="w-10 h-10 sm:w-12 sm:h-12 bg-green-100 dark:bg-green-900/20 rounded-full flex items-center justify-center flex-shrink-0">
                <MessageSquare className="w-5 h-5 sm:w-6 sm:h-6 text-green-600 dark:text-green-400" />
              </div>
              <div className="min-w-0 flex-1">
                <h2 className="text-lg sm:text-2xl font-bold text-gray-900 dark:text-white truncate">
                  Enviar WhatsApp
                </h2>
                <p className="text-xs sm:text-sm text-gray-600 dark:text-gray-400 truncate">
                  {client.name} • {client.phone}
                </p>
              </div>
            </div>
            <button
              onClick={onClose}
              className="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors flex-shrink-0"
            >
              <X className="w-5 h-5 sm:w-6 sm:h-6 text-gray-600 dark:text-gray-400" />
            </button>
          </div>

          {/* Status */}
          {checking ? (
            <div className="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
              <Loader2 className="w-4 h-4 animate-spin" />
              Verificando conexão...
            </div>
          ) : (
            <div className={`flex items-center gap-2 text-sm font-medium ${
              whatsappStatus.connected 
                ? 'text-green-600 dark:text-green-400' 
                : 'text-red-600 dark:text-red-400'
            }`}>
              {whatsappStatus.connected ? (
                <Check className="w-4 h-4" />
              ) : (
                <AlertCircle className="w-4 h-4" />
              )}
              {whatsappStatus.message}
            </div>
          )}
        </div>

        {/* Tabs */}
        <div className="flex border-b border-gray-200 dark:border-gray-700">
          <button
            onClick={() => setActiveTab('template')}
            className={`flex-1 px-3 sm:px-6 py-2.5 sm:py-3 font-semibold transition-colors flex items-center justify-center gap-1.5 sm:gap-2 text-sm sm:text-base ${
              activeTab === 'template'
                ? 'text-primary-600 border-b-2 border-primary-600'
                : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'
            }`}
          >
            <FileText className="w-4 h-4 sm:w-5 sm:h-5" />
            <span className="hidden xs:inline">Usar </span>Template
          </button>
          <button
            onClick={() => setActiveTab('custom')}
            className={`flex-1 px-3 sm:px-6 py-2.5 sm:py-3 font-semibold transition-colors flex items-center justify-center gap-1.5 sm:gap-2 text-sm sm:text-base ${
              activeTab === 'custom'
                ? 'text-primary-600 border-b-2 border-primary-600'
                : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'
            }`}
          >
            <MessageSquare className="w-4 h-4 sm:w-5 sm:h-5" />
            <span className="hidden xs:inline">Mensagem </span>Personalizada
          </button>
        </div>

        {/* Content */}
        <div className="flex-1 overflow-y-auto p-4 sm:p-6">
          {activeTab === 'template' ? (
            <div className="space-y-3 sm:space-y-4">
              <div>
                <label className="block text-xs sm:text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                  Selecione um Template
                </label>
                <select
                  value={selectedTemplate}
                  onChange={(e) => setSelectedTemplate(e.target.value)}
                  className="w-full px-3 sm:px-4 py-2.5 sm:py-3 text-sm sm:text-base border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                >
                  <option value="">Escolha um template...</option>
                  {templates.map((template) => (
                    <option key={template.id} value={template.id}>
                      {template.name}
                    </option>
                  ))}
                </select>
              </div>

              {preview && (
                <div>
                  <label className="block text-xs sm:text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                    Preview da Mensagem
                  </label>
                  <div className="bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg p-3 sm:p-4">
                    <pre className="whitespace-pre-wrap text-xs sm:text-sm text-gray-900 dark:text-white font-sans leading-snug">
                      {preview}
                    </pre>
                  </div>
                  <p className="text-xs text-gray-500 dark:text-gray-400 mt-2">
                    * As variáveis serão substituídas automaticamente ao enviar
                  </p>
                </div>
              )}
            </div>
          ) : (
            <div>
              <label className="block text-xs sm:text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                Digite sua mensagem
              </label>
              <textarea
                value={customMessage}
                onChange={(e) => setCustomMessage(e.target.value)}
                placeholder="Digite a mensagem que deseja enviar..."
                rows={8}
                className="w-full px-3 sm:px-4 py-2.5 sm:py-3 text-sm sm:text-base border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent resize-none"
              />
              <p className="text-xs text-gray-500 dark:text-gray-400 mt-2">
                Você pode usar variáveis como: {'{cliente_nome}'}, {'{cliente_telefone}'}, {'{cliente_plano}'}
              </p>
            </div>
          )}
        </div>

        {/* Footer */}
        <div className="p-4 sm:p-6 border-t border-gray-200 dark:border-gray-700 flex gap-2 sm:gap-3">
          <button
            onClick={onClose}
            className="flex-1 py-2.5 sm:py-3 text-sm sm:text-base bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg font-semibold hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors"
          >
            Cancelar
          </button>
          <button
            onClick={handleSend}
            disabled={sending || !whatsappStatus.connected}
            className="flex-1 py-2.5 sm:py-3 text-sm sm:text-base bg-green-600 hover:bg-green-700 text-white rounded-lg font-semibold transition-colors flex items-center justify-center gap-1.5 sm:gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {sending ? (
              <>
                <Loader2 className="w-4 h-4 sm:w-5 sm:h-5 animate-spin" />
                <span className="hidden xs:inline">Enviando...</span>
                <span className="xs:hidden">...</span>
              </>
            ) : (
              <>
                <Send className="w-4 h-4 sm:w-5 sm:h-5" />
                <span className="hidden xs:inline">Enviar Mensagem</span>
                <span className="xs:hidden">Enviar</span>
              </>
            )}
          </button>
        </div>
      </div>
    </div>
  )
}
