import { useState, useEffect, useRef } from 'react'
import { MessageSquare, Zap, CheckCircle, XCircle, Loader, AlertTriangle, Clock, Save, Users, TrendingUp, Smartphone, X } from 'lucide-react'
import api from '../services/api'
import toast from 'react-hot-toast'

type ConnectionStatus = 'disconnected' | 'connecting' | 'connected' | 'error'

interface RateLimits {
  messages_per_minute: number
  messages_per_hour: number
  delay_between_messages: number
}

import { usePageTitle } from '@/hooks/usePageTitle'

export default function WhatsAppConnect() {
  usePageTitle('Conectar WhatsApp')
  const [status, setStatus] = useState<ConnectionStatus>('disconnected')
  const [qrCode, setQrCode] = useState<string | null>(null)
  const [accountInfo, setAccountInfo] = useState<any>(null)
  const [rateLimits, setRateLimits] = useState<RateLimits>({
    messages_per_minute: 20,
    messages_per_hour: 100,
    delay_between_messages: 3
  })
  const [savingLimits, setSavingLimits] = useState(false)
  const qrCheckInterval = useRef<NodeJS.Timeout | null>(null)
  const statusCheckInterval = useRef<NodeJS.Timeout | null>(null)

  useEffect(() => {
    // Carregar status e configurações
    checkStatus()
    loadRateLimits()
    
    // Configurar intervalo
    statusCheckInterval.current = setInterval(checkStatus, 5000)
    
    return () => {
      if (statusCheckInterval.current) clearInterval(statusCheckInterval.current)
      if (qrCheckInterval.current) clearInterval(qrCheckInterval.current)
    }
  }, [])

  const loadRateLimits = async () => {
    try {
      const response = await api.get('/api-whatsapp-settings.php')
      if (response.data.success && response.data.settings) {
        const settings = response.data.settings
        setRateLimits({
          messages_per_minute: settings.messages_per_minute || 20,
          messages_per_hour: settings.messages_per_hour || 100,
          delay_between_messages: settings.delay_between_messages || 3
        })
      }
    } catch (error) {
      // Usar valores padrão
    }
  }

  const saveRateLimits = async () => {
    try {
      setSavingLimits(true)
      const response = await api.post('/api-whatsapp-settings.php', {
        messages_per_minute: rateLimits.messages_per_minute,
        messages_per_hour: rateLimits.messages_per_hour,
        delay_between_messages: rateLimits.delay_between_messages
      })

      if (response.data.success) {
        toast.success('Limites salvos com sucesso!')
      } else {
        throw new Error(response.data.error || 'Erro ao salvar')
      }
    } catch (error: any) {
      toast.error(error.message || 'Erro ao salvar limites')
    } finally {
      setSavingLimits(false)
    }
  }

  const checkStatus = async () => {
    try {
      const response = await api.get('/api-whatsapp-status.php')
      if (response.data.success && response.data.session) {
        const session = response.data.session
        setStatus(session.status)
        if (session.status === 'connected' && session.profile_name) {
          setAccountInfo(session)
          setQrCode(null)
          if (qrCheckInterval.current) {
            clearInterval(qrCheckInterval.current)
            qrCheckInterval.current = null
          }
        }
      }
    } catch (error) {
      // Silently fail
    }
  }

  const handleConnect = async () => {
    try {
      setStatus('connecting')
      setQrCode(null)
      
      const response = await api.post('/api-whatsapp-native-connect.php', {})
      
      if (response.data.success) {
        toast.success('Instância criada! Escaneie o QR Code')
        if (response.data.qr_code) {
          setQrCode(response.data.qr_code)
        }
        startQRCheck()
      } else {
        throw new Error(response.data.error || 'Erro ao conectar')
      }
    } catch (error: any) {
      setStatus('error')
      toast.error(error.message || 'Erro ao conectar')
    }
  }

  const handleDisconnect = async () => {
    try {
      if (qrCheckInterval.current) clearInterval(qrCheckInterval.current)
      if (statusCheckInterval.current) clearInterval(statusCheckInterval.current)
      
      setStatus('disconnected')
      setQrCode(null)
      setAccountInfo(null)
      
      await api.post('/api-whatsapp-disconnect.php')
      toast.success('WhatsApp desconectado')
      
      statusCheckInterval.current = setInterval(checkStatus, 5000)
    } catch (error: any) {
      toast.error(error.message || 'Erro ao desconectar')
    }
  }

  const startQRCheck = () => {
    if (qrCheckInterval.current) clearInterval(qrCheckInterval.current)
    
    qrCheckInterval.current = setInterval(async () => {
      try {
        const response = await api.get('/api-whatsapp-qr.php')
        if (response.data.success) {
          if (response.data.connected) {
            clearInterval(qrCheckInterval.current!)
            qrCheckInterval.current = null
            setQrCode(null)
            setStatus('connected')
            toast.success('WhatsApp conectado!')
            checkStatus()
          } else if (response.data.qr_code) {
            setQrCode(response.data.qr_code)
          }
        }
      } catch (error) {
        // Continue trying
      }
    }, 3000)
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="border-b border-gray-200 dark:border-gray-700 pb-6">
        <h1 className="text-2xl md:text-3xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">
          Parear WhatsApp
        </h1>
        <p className="text-sm md:text-base text-gray-600 dark:text-gray-400 mt-1">
          Conecte seu WhatsApp para enviar mensagens automaticamente
        </p>
      </div>

      {/* Provider Info */}
      <div className="bg-gradient-to-br from-purple-50 to-blue-50 dark:from-purple-900/20 dark:to-blue-900/20 rounded-xl p-6 border-2 border-purple-200 dark:border-purple-700">
        <div className="flex items-start gap-4">
          <div className="w-12 h-12 bg-gradient-to-br from-purple-600 to-blue-600 rounded-xl flex items-center justify-center flex-shrink-0">
            <Zap className="w-6 h-6 text-white" />
          </div>
          <div className="flex-1">
            <div className="flex items-center gap-2 mb-2">
              <h3 className="text-lg font-bold text-gray-900 dark:text-white">API Premium</h3>
              <span className="px-2 py-1 bg-purple-600 text-white text-xs font-medium rounded">Recomendado</span>
            </div>
            <p className="text-sm text-gray-600 dark:text-gray-400 mb-4">
              Máxima performance e estabilidade para suas mensagens
            </p>
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div className="flex items-center gap-2 text-sm text-green-600 dark:text-green-400">
                <CheckCircle className="w-4 h-4 flex-shrink-0" />
                <span>Conexão ultra estável</span>
              </div>
              <div className="flex items-center gap-2 text-sm text-green-600 dark:text-green-400">
                <CheckCircle className="w-4 h-4 flex-shrink-0" />
                <span>Reconexão automática</span>
              </div>
              <div className="flex items-center gap-2 text-sm text-green-600 dark:text-green-400">
                <CheckCircle className="w-4 h-4 flex-shrink-0" />
                <span>Sistema de fila otimizado</span>
              </div>
              <div className="flex items-center gap-2 text-sm text-green-600 dark:text-green-400">
                <CheckCircle className="w-4 h-4 flex-shrink-0" />
                <span>Sem configuração externa</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* Connection Status */}
      <div className="bg-white/80 dark:bg-gray-800/50 backdrop-blur-sm rounded-xl p-6 border border-gray-200 dark:border-gray-700">
        <div className="flex items-center justify-between mb-6">
          <div className="flex items-center gap-4">
            <div className={`w-16 h-16 rounded-full flex items-center justify-center ${
              status === 'connected' ? 'bg-green-100 dark:bg-green-900/30' :
              status === 'connecting' ? 'bg-yellow-100 dark:bg-yellow-900/30' :
              status === 'error' ? 'bg-red-100 dark:bg-red-900/30' :
              'bg-gray-100 dark:bg-gray-800'
            }`}>
              {status === 'connected' ? <CheckCircle className="w-8 h-8 text-green-600 dark:text-green-400" /> :
               status === 'connecting' ? <Loader className="w-8 h-8 text-yellow-600 dark:text-yellow-400 animate-spin" /> :
               status === 'error' ? <XCircle className="w-8 h-8 text-red-600 dark:text-red-400" /> :
               <MessageSquare className="w-8 h-8 text-gray-600 dark:text-gray-400" />}
            </div>
            <div>
              <h3 className="text-lg font-semibold text-gray-900 dark:text-white">
                {status === 'connected' ? 'WhatsApp Conectado' :
                 status === 'connecting' ? 'Conectando...' :
                 status === 'error' ? 'Erro na Conexão' :
                 'WhatsApp Desconectado'}
              </h3>
              <p className="text-sm text-gray-600 dark:text-gray-400">
                {status === 'connected' ? 'Sua conta está conectada e funcionando' :
                 status === 'connecting' ? 'Escaneie o QR Code para conectar' :
                 status === 'error' ? 'Ocorreu um erro. Tente novamente' :
                 'Clique em "Conectar" para iniciar'}
              </p>
            </div>
          </div>
          
          <div className="flex flex-col sm:flex-row gap-3">
            {status === 'connected' ? (
              <button
                onClick={handleDisconnect}
                className="w-full sm:w-auto px-6 py-3 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors font-semibold flex items-center justify-center gap-2"
              >
                <Smartphone className="w-5 h-5" />
                Desconectar
              </button>
            ) : (
              <>
                <button
                  onClick={handleConnect}
                  disabled={status === 'connecting'}
                  className="flex-1 sm:flex-none px-6 py-3 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed font-semibold flex items-center justify-center gap-2"
                >
                  {status === 'connecting' ? (
                    <>
                      <Loader className="w-5 h-5 animate-spin" />
                      <span className="hidden sm:inline">Conectando...</span>
                      <span className="sm:hidden">Conectando</span>
                    </>
                  ) : (
                    <>
                      <Smartphone className="w-5 h-5" />
                      <span className="hidden sm:inline">Conectar WhatsApp</span>
                      <span className="sm:hidden">Conectar</span>
                    </>
                  )}
                </button>
                {status === 'connecting' && (
                  <button
                    onClick={handleDisconnect}
                    className="flex-1 sm:flex-none px-6 py-3 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors font-semibold flex items-center justify-center gap-2"
                  >
                    <X className="w-5 h-5" />
                    Cancelar
                  </button>
                )}
              </>
            )}
          </div>
        </div>

        {/* Loading State - Aguardando QR Code */}
        {status === 'connecting' && !qrCode && (
          <div className="mt-6 p-8 bg-blue-50 dark:bg-blue-900/20 rounded-xl border-2 border-blue-200 dark:border-blue-800">
            <div className="flex flex-col items-center gap-4">
              <Loader className="w-12 h-12 text-blue-600 dark:text-blue-400 animate-spin" />
              <div className="text-center">
                <h4 className="font-semibold text-gray-900 dark:text-white mb-2">
                  Conectando ao WhatsApp...
                </h4>
                <p className="text-sm text-gray-600 dark:text-gray-400">
                  Aguarde enquanto geramos o QR Code para você
                </p>
              </div>
            </div>
          </div>
        )}

        {/* QR Code */}
        {qrCode && (
          <div className="mt-6 p-6 bg-gray-50 dark:bg-gray-900/50 rounded-xl">
            <div className="flex items-center justify-between mb-4">
              <h4 className="font-semibold text-gray-900 dark:text-white">
                Escaneie o QR Code
              </h4>
              <button
                onClick={handleDisconnect}
                className="px-3 py-1.5 text-sm bg-red-100 hover:bg-red-200 dark:bg-red-900/30 dark:hover:bg-red-900/50 text-red-700 dark:text-red-400 rounded-lg transition-colors"
              >
                Cancelar
              </button>
            </div>
            <div className="flex justify-center">
              <img 
                src={qrCode.startsWith('data:') ? qrCode : `data:image/png;base64,${qrCode}`}
                alt="QR Code"
                className="w-64 h-64 rounded-lg"
              />
            </div>
            <p className="text-center text-sm text-gray-600 dark:text-gray-400 mt-4">
              Abra o WhatsApp → Configurações → Aparelhos conectados → Conectar aparelho
            </p>
          </div>
        )}

        {/* Account Info */}
        {accountInfo && status === 'connected' && (
          <div className="mt-6 p-6 bg-green-50 dark:bg-green-900/20 rounded-xl">
            <div className="flex items-center gap-4">
              <div className="w-16 h-16 bg-green-600 rounded-full flex items-center justify-center text-white text-xl font-bold">
                {accountInfo.profile_name?.charAt(0) || 'W'}
              </div>
              <div>
                <h4 className="font-semibold text-gray-900 dark:text-white">
                  {accountInfo.profile_name || 'WhatsApp'}
                </h4>
                <p className="text-sm text-gray-600 dark:text-gray-400">
                  {accountInfo.phone_number || 'Número não disponível'}
                </p>
              </div>
            </div>
          </div>
        )}
      </div>

      {/* Rate Limits Configuration */}
      <div className="bg-white/80 dark:bg-gray-800/50 backdrop-blur-sm rounded-xl p-6 border border-gray-200 dark:border-gray-700">
        <div className="flex items-center gap-3 mb-6">
          <div className="w-12 h-12 bg-gradient-to-br from-blue-500 to-purple-600 rounded-xl flex items-center justify-center">
            <Clock className="w-6 h-6 text-white" />
          </div>
          <div>
            <h3 className="text-lg font-semibold text-gray-900 dark:text-white">
              Configuração de Limites de Envio
            </h3>
            <p className="text-sm text-gray-600 dark:text-gray-400">
              Configure os limites para evitar bloqueios do WhatsApp
            </p>
          </div>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
          {/* Messages Per Minute */}
          <div className="space-y-2">
            <label className="flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-300">
              <TrendingUp className="w-4 h-4 text-blue-600 dark:text-blue-400" />
              Mensagens por Minuto
            </label>
            <input
              type="number"
              min="1"
              max="60"
              value={rateLimits.messages_per_minute}
              onChange={(e) => setRateLimits({ ...rateLimits, messages_per_minute: parseInt(e.target.value) || 1 })}
              className="w-full px-4 py-3 bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all"
            />
            <p className="text-xs text-gray-500 dark:text-gray-400">
              Recomendado: 15-20 para evitar bloqueios
            </p>
          </div>

          {/* Messages Per Hour */}
          <div className="space-y-2">
            <label className="flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-300">
              <Users className="w-4 h-4 text-green-600 dark:text-green-400" />
              Mensagens por Hora
            </label>
            <input
              type="number"
              min="10"
              max="500"
              value={rateLimits.messages_per_hour}
              onChange={(e) => setRateLimits({ ...rateLimits, messages_per_hour: parseInt(e.target.value) || 10 })}
              className="w-full px-4 py-3 bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all"
            />
            <p className="text-xs text-gray-500 dark:text-gray-400">
              Recomendado: 80-100 para uso seguro
            </p>
          </div>

          {/* Delay Between Messages */}
          <div className="space-y-2">
            <label className="flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-300">
              <Clock className="w-4 h-4 text-purple-600 dark:text-purple-400" />
              Delay entre Mensagens (seg)
            </label>
            <input
              type="number"
              min="1"
              max="60"
              value={rateLimits.delay_between_messages}
              onChange={(e) => setRateLimits({ ...rateLimits, delay_between_messages: parseInt(e.target.value) || 1 })}
              className="w-full px-4 py-3 bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all"
            />
            <p className="text-xs text-gray-500 dark:text-gray-400">
              Recomendado: 3-5 segundos
            </p>
          </div>
        </div>

        {/* Warning Alert */}
        <div className="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4 mb-6">
          <div className="flex items-start gap-3">
            <AlertTriangle className="w-5 h-5 text-yellow-600 dark:text-yellow-400 flex-shrink-0 mt-0.5" />
            <div>
              <h4 className="text-sm font-semibold text-yellow-900 dark:text-yellow-300 mb-1">
                ⚠️ Atenção
              </h4>
              <p className="text-sm text-yellow-800 dark:text-yellow-400">
                Configurações muito agressivas podem resultar em bloqueio da conta WhatsApp. 
                Use os valores recomendados para garantir a segurança da sua conta.
              </p>
            </div>
          </div>
        </div>

        {/* Save Button */}
        <button
          onClick={saveRateLimits}
          disabled={savingLimits}
          className="w-full md:w-auto px-6 py-3 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white rounded-lg font-semibold transition-all flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          {savingLimits ? (
            <>
              <Loader className="w-5 h-5 animate-spin" />
              Salvando...
            </>
          ) : (
            <>
              <Save className="w-5 h-5" />
              Salvar Configuração
            </>
          )}
        </button>
      </div>

      {/* Features Section */}
      <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
        {/* Envio Automático */}
        <div className="bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-xl p-6 border border-green-200 dark:border-green-800">
          <div className="flex items-center gap-3 mb-4">
            <div className="w-10 h-10 bg-green-600 rounded-lg flex items-center justify-center">
              <Zap className="w-5 h-5 text-white" />
            </div>
            <h3 className="text-lg font-semibold text-gray-900 dark:text-white">
              Envio Automático
            </h3>
          </div>
          <p className="text-sm text-gray-600 dark:text-gray-400 mb-4">
            Envie cobranças automaticamente para clientes
          </p>
          <ul className="space-y-2">
            <li className="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
              <CheckCircle className="w-4 h-4 text-green-600 dark:text-green-400 flex-shrink-0" />
              Cobrança de renovação
            </li>
            <li className="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
              <CheckCircle className="w-4 h-4 text-green-600 dark:text-green-400 flex-shrink-0" />
              Lembretes de vencimento
            </li>
            <li className="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
              <CheckCircle className="w-4 h-4 text-green-600 dark:text-green-400 flex-shrink-0" />
              Confirmação de pagamento
            </li>
            <li className="flex items-center gap-2 text-sm text-yellow-700 dark:text-yellow-400">
              <AlertTriangle className="w-4 h-4 flex-shrink-0" />
              Avisos de suspensão
            </li>
          </ul>
        </div>

        {/* Gestão de Clientes */}
        <div className="bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-xl p-6 border border-blue-200 dark:border-blue-800">
          <div className="flex items-center gap-3 mb-4">
            <div className="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center">
              <Users className="w-5 h-5 text-white" />
            </div>
            <h3 className="text-lg font-semibold text-gray-900 dark:text-white">
              Gestão de Clientes
            </h3>
          </div>
          <p className="text-sm text-gray-600 dark:text-gray-400 mb-4">
            Integração com sua base de clientes
          </p>
          <ul className="space-y-2">
            <li className="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
              <CheckCircle className="w-4 h-4 text-blue-600 dark:text-blue-400 flex-shrink-0" />
              Sincronização automática
            </li>
            <li className="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
              <CheckCircle className="w-4 h-4 text-blue-600 dark:text-blue-400 flex-shrink-0" />
              Números validados
            </li>
            <li className="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
              <CheckCircle className="w-4 h-4 text-blue-600 dark:text-blue-400 flex-shrink-0" />
              Histórico de mensagens
            </li>
            <li className="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
              <CheckCircle className="w-4 h-4 text-blue-600 dark:text-blue-400 flex-shrink-0" />
              Status de entrega
            </li>
          </ul>
        </div>
      </div>
    </div>
  )
}
