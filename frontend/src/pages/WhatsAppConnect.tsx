import { useState, useEffect } from 'react'
import { Smartphone, QrCode, CheckCircle, XCircle, RefreshCw } from 'lucide-react'
import toast from 'react-hot-toast'
import api from '../services/api'

export default function WhatsAppConnect() {
  const [qrCode, setQrCode] = useState<string>('')
  const [status, setStatus] = useState<'disconnected' | 'connecting' | 'connected'>('disconnected')
  const [loading, setLoading] = useState(false)
  const [instanceName, setInstanceName] = useState('default')

  useEffect(() => {
    checkStatus()
  }, [])

  const checkStatus = async () => {
    try {
      const response = await api.get('/api-whatsapp-status.php')
      setStatus(response.data.status || 'disconnected')
    } catch (error) {
      console.error('Erro ao verificar status')
    }
  }

  const handleConnect = async () => {
    setLoading(true)
    setStatus('connecting')
    
    try {
      const response = await api.post('/api-whatsapp-qr.php', { instance: instanceName })
      
      if (response.data.qrCode) {
        setQrCode(response.data.qrCode)
        toast.success('QR Code gerado! Escaneie com seu WhatsApp')
        
        // Poll para verificar conexão
        const interval = setInterval(async () => {
          const statusResponse = await api.get('/api-whatsapp-status.php')
          if (statusResponse.data.status === 'connected') {
            setStatus('connected')
            setQrCode('')
            clearInterval(interval)
            toast.success('WhatsApp conectado com sucesso!')
          }
        }, 3000)
        
        // Limpar interval após 2 minutos
        setTimeout(() => clearInterval(interval), 120000)
      }
    } catch (error: any) {
      toast.error(error.response?.data?.message || 'Erro ao gerar QR Code')
      setStatus('disconnected')
    } finally {
      setLoading(false)
    }
  }

  const handleDisconnect = async () => {
    if (!confirm('Deseja realmente desconectar o WhatsApp?')) return
    
    try {
      await api.post('/api-whatsapp-disconnect.php', { instance: instanceName })
      setStatus('disconnected')
      setQrCode('')
      toast.success('WhatsApp desconectado')
    } catch (error) {
      toast.error('Erro ao desconectar')
    }
  }

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white">Conectar WhatsApp</h1>
        <p className="text-sm md:text-base text-gray-600 dark:text-gray-400 mt-1">Conecte sua conta WhatsApp para enviar mensagens</p>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* Status Card */}
        <div className="bg-white/80 dark:bg-gray-800/50 backdrop-blur-sm rounded-xl p-6 border border-gray-200 dark:border-gray-700">
          <h2 className="text-lg font-semibold text-gray-900 dark:text-white mb-4">Status da Conexão</h2>
          
          <div className="space-y-4">
            <div className="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-900/50 rounded-lg">
              <div className="flex items-center gap-3">
                <Smartphone className="w-8 h-8 text-green-600" />
                <div>
                  <p className="font-medium text-gray-900 dark:text-white">WhatsApp</p>
                  <p className="text-sm text-gray-500 dark:text-gray-400">Instância: {instanceName}</p>
                </div>
              </div>
              
              <div className="flex items-center gap-2">
                {status === 'connected' && (
                  <>
                    <CheckCircle className="w-6 h-6 text-green-600" />
                    <span className="text-sm font-medium text-green-600">Conectado</span>
                  </>
                )}
                {status === 'connecting' && (
                  <>
                    <RefreshCw className="w-6 h-6 text-blue-600 animate-spin" />
                    <span className="text-sm font-medium text-blue-600">Conectando...</span>
                  </>
                )}
                {status === 'disconnected' && (
                  <>
                    <XCircle className="w-6 h-6 text-red-600" />
                    <span className="text-sm font-medium text-red-600">Desconectado</span>
                  </>
                )}
              </div>
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Nome da Instância
              </label>
              <input
                type="text"
                value={instanceName}
                onChange={(e) => setInstanceName(e.target.value)}
                disabled={status === 'connected'}
                className="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white disabled:opacity-50"
              />
            </div>

            {status === 'disconnected' && (
              <button
                onClick={handleConnect}
                disabled={loading}
                className="w-full px-4 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50 flex items-center justify-center gap-2"
              >
                {loading ? (
                  <>
                    <RefreshCw className="w-5 h-5 animate-spin" />
                    Gerando QR Code...
                  </>
                ) : (
                  <>
                    <QrCode className="w-5 h-5" />
                    Conectar WhatsApp
                  </>
                )}
              </button>
            )}

            {status === 'connected' && (
              <button
                onClick={handleDisconnect}
                className="w-full px-4 py-3 bg-red-600 text-white rounded-lg hover:bg-red-700 flex items-center justify-center gap-2"
              >
                <XCircle className="w-5 h-5" />
                Desconectar
              </button>
            )}
          </div>
        </div>

        {/* QR Code Card */}
        <div className="bg-white/80 dark:bg-gray-800/50 backdrop-blur-sm rounded-xl p-6 border border-gray-200 dark:border-gray-700">
          <h2 className="text-lg font-semibold text-gray-900 dark:text-white mb-4">QR Code</h2>
          
          {qrCode ? (
            <div className="space-y-4">
              <div className="flex justify-center p-4 bg-white rounded-lg">
                <img src={qrCode} alt="QR Code" className="w-48 h-48 md:w-64 md:h-64" />
              </div>
              <div className="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                <p className="text-sm text-blue-800 dark:text-blue-300">
                  <strong>Como conectar:</strong>
                </p>
                <ol className="list-decimal list-inside text-sm text-blue-700 dark:text-blue-400 mt-2 space-y-1">
                  <li>Abra o WhatsApp no seu celular</li>
                  <li>Toque em Mais opções (⋮) e depois em Aparelhos conectados</li>
                  <li>Toque em Conectar um aparelho</li>
                  <li>Aponte seu celular para esta tela para escanear o código</li>
                </ol>
              </div>
            </div>
          ) : (
            <div className="flex flex-col items-center justify-center h-80 text-gray-400 dark:text-gray-600">
              <QrCode className="w-24 h-24 mb-4" />
              <p className="text-center">
                {status === 'connected' 
                  ? 'WhatsApp já está conectado'
                  : 'Clique em "Conectar WhatsApp" para gerar o QR Code'
                }
              </p>
            </div>
          )}
        </div>
      </div>
    </div>
  )
}
