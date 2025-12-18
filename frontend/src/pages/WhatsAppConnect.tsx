import { useState, useEffect, useRef } from 'react'
import { MessageSquare, Zap, DollarSign, CheckCircle, XCircle, Loader, AlertTriangle } from 'lucide-react'
import api from '../services/api'
import toast from 'react-hot-toast'

type ConnectionStatus = 'disconnected' | 'connecting' | 'connected' | 'error'
type Provider = 'native' | 'evolution'

export default function WhatsAppConnect() {
  const [status, setStatus] = useState<ConnectionStatus>('disconnected')
  const [provider, setProvider] = useState<Provider>('native')
  const [qrCode, setQrCode] = useState<string | null>(null)
  const [accountInfo, setAccountInfo] = useState<any>(null)
  const qrCheckInterval = useRef<NodeJS.Timeout | null>(null)
  const statusCheckInterval = useRef<NodeJS.Timeout | null>(null)

  useEffect(() => {
    // Carregar status imediatamente (sem await para não bloquear)
    checkStatus()
    
    // Configurar intervalo
    statusCheckInterval.current = setInterval(checkStatus, 5000)
    
    return () => {
      if (statusCheckInterval.current) clearInterval(statusCheckInterval.current)
      if (qrCheckInterval.current) clearInterval(qrCheckInterval.current)
    }
  }, [])

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
      
      const endpoint = provider === 'native' 
        ? '/api-whatsapp-native-connect.php' 
        : '/api-whatsapp-connect.php'
      
      const response = await api.post(endpoint, {})
      
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
      <div>
        <h1 className="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white">
          Parear WhatsApp
        </h1>
        <p className="text-sm md:text-base text-gray-600 dark:text-gray-400 mt-1">
          Conecte seu WhatsApp para enviar mensagens automaticamente
        </p>
      </div>

      {/* Provider Selection */}
      <div className="bg-white/80 dark:bg-gray-800/50 backdrop-blur-sm rounded-xl p-6 border border-gray-200 dark:border-gray-700">
        <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-4">
          Escolha o Provedor de API
        </h3>
        
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          {/* API Premium */}
          <label className={`relative cursor-pointer ${provider === 'native' ? 'ring-2 ring-primary-500' : ''} rounded-xl transition-all`}>
            <input
              type="radio"
              name="provider"
              value="native"
              checked={provider === 'native'}
              onChange={(e) => setProvider(e.target.value as Provider)}
              className="sr-only"
            />
            <div className="bg-gradient-to-br from-purple-50 to-blue-50 dark:from-purple-900/20 dark:to-blue-900/20 p-6 rounded-xl border-2 border-transparent hover:border-purple-200 dark:hover:border-purple-700 transition-all">
              <div className="flex items-start justify-between mb-3">
                <Zap className="w-8 h-8 text-purple-600 dark:text-purple-400" />
                <span className="px-2 py-1 bg-purple-600 text-white text-xs font-medium rounded">Recomendado</span>
              </div>
              <h4 className="text-lg font-bold text-gray-900 dark:text-white mb-2">API Premium</h4>
              <p className="text-sm text-gray-600 dark:text-gray-400 mb-4">Máxima performance e estabilidade</p>
              <ul className="space-y-2 text-sm">
                <li className="flex items-center text-green-600 dark:text-green-400">
                  <CheckCircle className="w-4 h-4 mr-2" />
                  Conexão ultra estável
                </li>
                <li className="flex items-center text-green-600 dark:text-green-400">
                  <CheckCircle className="w-4 h-4 mr-2" />
                  Reconexão automática
                </li>
                <li className="flex items-center text-green-600 dark:text-green-400">
                  <CheckCircle className="w-4 h-4 mr-2" />
                  Sistema de fila otimizado
                </li>
              </ul>
            </div>
          </label>

          {/* API Básica */}
          <label className={`relative cursor-pointer ${provider === 'evolution' ? 'ring-2 ring-primary-500' : ''} rounded-xl transition-all`}>
            <input
              type="radio"
              name="provider"
              value="evolution"
              checked={provider === 'evolution'}
              onChange={(e) => setProvider(e.target.value as Provider)}
              className="sr-only"
            />
            <div className="bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-800/50 dark:to-gray-900/50 p-6 rounded-xl border-2 border-transparent hover:border-gray-300 dark:hover:border-gray-600 transition-all">
              <div className="flex items-start justify-between mb-3">
                <DollarSign className="w-8 h-8 text-gray-600 dark:text-gray-400" />
              </div>
              <h4 className="text-lg font-bold text-gray-900 dark:text-white mb-2">API Básica</h4>
              <p className="text-sm text-gray-600 dark:text-gray-400 mb-4">Solução simples e funcional</p>
              <ul className="space-y-2 text-sm">
                <li className="flex items-center text-yellow-600 dark:text-yellow-400">
                  <AlertTriangle className="w-4 h-4 mr-2" />
                  Requer configuração externa
                </li>
                <li className="flex items-center text-yellow-600 dark:text-yellow-400">
                  <AlertTriangle className="w-4 h-4 mr-2" />
                  Estabilidade moderada
                </li>
              </ul>
            </div>
          </label>
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
              <span className="inline-block mt-2 px-2 py-1 bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 text-xs font-medium rounded">
                {provider === 'native' ? 'API Premium' : 'API Básica'}
              </span>
            </div>
          </div>
          
          <div>
            {status === 'connected' ? (
              <button
                onClick={handleDisconnect}
                className="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors"
              >
                Desconectar
              </button>
            ) : (
              <button
                onClick={handleConnect}
                disabled={status === 'connecting'}
                className="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-colors disabled:opacity-50"
              >
                {status === 'connecting' ? 'Conectando...' : 'Conectar WhatsApp'}
              </button>
            )}
          </div>
        </div>

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
    </div>
  )
}
