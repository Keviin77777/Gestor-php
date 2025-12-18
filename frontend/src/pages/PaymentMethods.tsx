import { useEffect, useState } from 'react'
import { CreditCard, Check, X, AlertCircle, Loader2 } from 'lucide-react'
import api from '@/services/api'
import '../styles/payment-methods.css'

interface PaymentMethod {
  enabled: boolean
  configured: boolean
  updated_at?: string
  config?: any
}

interface PaymentMethods {
  mercadopago?: PaymentMethod
  asaas?: PaymentMethod
  efibank?: PaymentMethod
  ciabra?: PaymentMethod
}

export default function PaymentMethods() {
  const [loading, setLoading] = useState(true)
  const [methods, setMethods] = useState<PaymentMethods>({})
  const [isAdmin, setIsAdmin] = useState(false)
  const [selectedMethod, setSelectedMethod] = useState<string | null>(null)
  const [showModal, setShowModal] = useState(false)
  const [saving, setSaving] = useState(false)
  const [testing, setTesting] = useState(false)

  // Form states
  const [formData, setFormData] = useState<any>({})

  useEffect(() => {
    loadMethods()
  }, [])

  const loadMethods = async () => {
    try {
      const response = await api.get('/api-payment-methods.php')
      if (response.data.success) {
        setMethods(response.data.methods)
        setIsAdmin(response.data.is_admin)
      }
    } catch (error) {
      console.error('Erro ao carregar métodos:', error)
    } finally {
      setLoading(false)
    }
  }

  const openModal = (method: string) => {
    setSelectedMethod(method)
    const methodData = methods[method as keyof PaymentMethods]
    
    if (methodData?.config) {
      setFormData(methodData.config)
    } else {
      setFormData({})
    }
    
    setShowModal(true)
  }

  const closeModal = () => {
    setShowModal(false)
    setSelectedMethod(null)
    setFormData({})
  }

  const handleSave = async () => {
    if (!selectedMethod) return

    try {
      setSaving(true)
      const response = await api.post('/api-payment-methods.php', {
        method: selectedMethod,
        config: {
          ...formData,
          enabled: formData.enabled ?? true
        }
      })

      if (response.data.success) {
        alert('Configuração salva com sucesso!')
        closeModal()
        loadMethods()
      } else {
        alert(response.data.error || 'Erro ao salvar')
      }
    } catch (error: any) {
      alert(error.response?.data?.error || 'Erro ao salvar configuração')
    } finally {
      setSaving(false)
    }
  }

  const handleTest = async () => {
    if (!selectedMethod) return

    try {
      setTesting(true)
      const response = await api.post('/api-payment-methods.php?action=test', {
        method: selectedMethod,
        ...formData
      })

      if (response.data.success) {
        alert('✅ Conexão testada com sucesso!\n\n' + JSON.stringify(response.data.account_info, null, 2))
      } else {
        alert('❌ Erro ao testar: ' + response.data.error)
      }
    } catch (error: any) {
      alert('❌ Erro ao testar: ' + (error.response?.data?.error || 'Erro desconhecido'))
    } finally {
      setTesting(false)
    }
  }

  const getMethodInfo = (method: string) => {
    const info: Record<string, { name: string; description: string; icon: string }> = {
      mercadopago: {
        name: 'Mercado Pago',
        description: 'Pagamentos via PIX com QR Code',
        icon: '💳'
      },
      asaas: {
        name: 'Asaas',
        description: 'Pagamentos via PIX com QR Code',
        icon: '💰'
      },
      efibank: {
        name: 'EFI Bank',
        description: 'Pagamentos via PIX (Gerencianet)',
        icon: '🏦'
      },
      ciabra: {
        name: 'Ciabra',
        description: 'Integração com Ciabra',
        icon: '🔗'
      }
    }
    return info[method] || { name: method, description: '', icon: '💳' }
  }

  const renderFormFields = () => {
    if (!selectedMethod) return null

    switch (selectedMethod) {
      case 'mercadopago':
        return (
          <>
            <div className="form-group">
              <label>Public Key</label>
              <input
                type="text"
                value={formData.public_key || ''}
                onChange={(e) => setFormData({ ...formData, public_key: e.target.value })}
                placeholder="APP_USR-..."
                className="form-input"
              />
            </div>
            <div className="form-group">
              <label>Access Token</label>
              <input
                type="password"
                value={formData.access_token || ''}
                onChange={(e) => setFormData({ ...formData, access_token: e.target.value })}
                placeholder="APP_USR-..."
                className="form-input"
              />
            </div>
          </>
        )

      case 'asaas':
        return (
          <>
            <div className="form-group">
              <label>API Key</label>
              <input
                type="password"
                value={formData.api_key || ''}
                onChange={(e) => setFormData({ ...formData, api_key: e.target.value })}
                placeholder="$aact_..."
                className="form-input"
              />
              <small className="text-gray-500 text-xs mt-1 block">
                O sistema detecta automaticamente se é sandbox ou produção
              </small>
            </div>
          </>
        )

      case 'efibank':
        return (
          <>
            <div className="form-group">
              <label>Client ID</label>
              <input
                type="text"
                value={formData.client_id || ''}
                onChange={(e) => setFormData({ ...formData, client_id: e.target.value })}
                placeholder="Client_Id_..."
                className="form-input"
              />
            </div>
            <div className="form-group">
              <label>Client Secret</label>
              <input
                type="password"
                value={formData.client_secret || ''}
                onChange={(e) => setFormData({ ...formData, client_secret: e.target.value })}
                placeholder="Client_Secret_..."
                className="form-input"
              />
            </div>
            <div className="form-group">
              <label>Chave PIX</label>
              <input
                type="text"
                value={formData.pix_key || ''}
                onChange={(e) => setFormData({ ...formData, pix_key: e.target.value })}
                placeholder="email@example.com ou CPF/CNPJ"
                className="form-input"
              />
            </div>
            <div className="form-group">
              <label>Certificado (opcional)</label>
              <input
                type="text"
                value={formData.certificate || ''}
                onChange={(e) => setFormData({ ...formData, certificate: e.target.value })}
                placeholder="/path/to/certificate.pem"
                className="form-input"
              />
            </div>
            <div className="form-group">
              <label className="flex items-center gap-2">
                <input
                  type="checkbox"
                  checked={formData.sandbox || false}
                  onChange={(e) => setFormData({ ...formData, sandbox: e.target.checked })}
                  className="form-checkbox"
                />
                <span>Modo Sandbox (Homologação)</span>
              </label>
            </div>
          </>
        )

      case 'ciabra':
        return (
          <>
            <div className="form-group">
              <label>API Key</label>
              <input
                type="password"
                value={formData.api_key || ''}
                onChange={(e) => setFormData({ ...formData, api_key: e.target.value })}
                placeholder="API Key do Ciabra"
                className="form-input"
              />
            </div>
          </>
        )

      default:
        return null
    }
  }

  if (loading) {
    return (
      <div className="flex items-center justify-center min-h-screen">
        <Loader2 className="w-8 h-8 animate-spin text-primary-600" />
      </div>
    )
  }

  const availableMethods = ['mercadopago', 'asaas', ...(isAdmin ? ['efibank'] : []), 'ciabra']

  return (
    <div className="space-y-6 p-4 md:p-6">
      {/* Header */}
      <div className="border-b border-gray-200 dark:border-gray-700 pb-6">
        <h1 className="text-3xl font-bold text-gray-900 dark:text-white mb-2">
          Métodos de Pagamento
        </h1>
        <p className="text-gray-600 dark:text-gray-400">
          Configure os provedores de pagamento disponíveis
        </p>
      </div>

      {/* Payment Methods Grid */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        {availableMethods.map((method) => {
          const methodData = methods[method as keyof PaymentMethods]
          const info = getMethodInfo(method)
          const isConfigured = methodData?.configured || false
          const isEnabled = methodData?.enabled || false
          const isComingSoon = method === 'ciabra'

          return (
            <div
              key={method}
              className={`bg-white dark:bg-gray-800 rounded-2xl border-2 p-6 transition-all ${
                isComingSoon
                  ? 'opacity-50 border-gray-200 dark:border-gray-700'
                  : isEnabled
                  ? 'border-green-500'
                  : isConfigured
                  ? 'border-yellow-500'
                  : 'border-gray-200 dark:border-gray-700'
              }`}
            >
              <div className="flex items-start justify-between mb-4">
                <div className="flex items-center gap-3">
                  <div className="text-4xl">{info.icon}</div>
                  <div>
                    <h3 className="text-lg font-bold text-gray-900 dark:text-white">
                      {info.name}
                    </h3>
                    <p className="text-sm text-gray-600 dark:text-gray-400">
                      {info.description}
                    </p>
                  </div>
                </div>
                <div>
                  {isComingSoon ? (
                    <span className="inline-flex items-center gap-1 px-2 py-1 bg-purple-100 dark:bg-purple-900/20 text-purple-600 dark:text-purple-400 rounded-full text-xs font-semibold">
                      🚀 Em Breve
                    </span>
                  ) : isEnabled ? (
                    <span className="inline-flex items-center gap-1 px-2 py-1 bg-green-100 dark:bg-green-900/20 text-green-600 dark:text-green-400 rounded-full text-xs font-semibold">
                      <Check className="w-3 h-3" />
                      Ativo
                    </span>
                  ) : isConfigured ? (
                    <span className="inline-flex items-center gap-1 px-2 py-1 bg-yellow-100 dark:bg-yellow-900/20 text-yellow-600 dark:text-yellow-400 rounded-full text-xs font-semibold">
                      <AlertCircle className="w-3 h-3" />
                      Inativo
                    </span>
                  ) : (
                    <span className="inline-flex items-center gap-1 px-2 py-1 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 rounded-full text-xs font-semibold">
                      <X className="w-3 h-3" />
                      Não configurado
                    </span>
                  )}
                </div>
              </div>

              {isComingSoon ? (
                <p className="text-sm text-purple-600 dark:text-purple-400 mb-4 font-medium">
                  Este método de pagamento estará disponível em breve
                </p>
              ) : methodData?.updated_at ? (
                <p className="text-xs text-gray-500 dark:text-gray-500 mb-4">
                  Atualizado em: {new Date(methodData.updated_at).toLocaleString('pt-BR')}
                </p>
              ) : null}

              <button
                onClick={() => !isComingSoon && openModal(method)}
                disabled={isComingSoon}
                className={`w-full py-2 rounded-lg font-semibold transition-colors ${
                  isComingSoon
                    ? 'bg-gray-300 dark:bg-gray-700 text-gray-500 dark:text-gray-500 cursor-not-allowed'
                    : 'bg-primary-600 hover:bg-primary-700 text-white'
                }`}
              >
                {isComingSoon ? 'Indisponível' : isConfigured ? 'Editar Configuração' : 'Configurar'}
              </button>
            </div>
          )
        })}
      </div>

      {/* Info Box */}
      <div className="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-6">
        <h3 className="text-lg font-semibold text-blue-900 dark:text-blue-300 mb-3 flex items-center gap-2">
          <AlertCircle className="w-5 h-5" />
          Como Funciona
        </h3>
        <ul className="space-y-2 text-sm text-blue-800 dark:text-blue-300">
          <li className="flex items-start gap-2">
            <Check className="w-4 h-4 mt-0.5 flex-shrink-0" />
            Configure um ou mais provedores de pagamento
          </li>
          <li className="flex items-start gap-2">
            <Check className="w-4 h-4 mt-0.5 flex-shrink-0" />
            Seus clientes poderão pagar faturas via PIX automaticamente
          </li>
          <li className="flex items-start gap-2">
            <Check className="w-4 h-4 mt-0.5 flex-shrink-0" />
            Renovações automáticas após confirmação de pagamento
          </li>
          <li className="flex items-start gap-2">
            <Check className="w-4 h-4 mt-0.5 flex-shrink-0" />
            Notificações via WhatsApp quando o pagamento for confirmado
          </li>
        </ul>
      </div>

      {/* Modal */}
      {showModal && selectedMethod && (
        <div className="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 flex items-center justify-center p-2 sm:p-4">
          <div className="bg-white dark:bg-gray-800 rounded-2xl max-w-2xl w-full max-h-[95vh] sm:max-h-[90vh] overflow-y-auto shadow-2xl">
            <div className="p-4 sm:p-6">
              {/* Modal Header */}
              <div className="flex items-center justify-between mb-4 sm:mb-6">
                <div className="flex items-center gap-2 sm:gap-3 flex-1 min-w-0">
                  <div className="text-2xl sm:text-3xl flex-shrink-0">{getMethodInfo(selectedMethod).icon}</div>
                  <div className="min-w-0 flex-1">
                    <h2 className="text-lg sm:text-2xl font-bold text-gray-900 dark:text-white truncate">
                      {getMethodInfo(selectedMethod).name}
                    </h2>
                    <p className="text-xs sm:text-sm text-gray-600 dark:text-gray-400">
                      Configure as credenciais de acesso
                    </p>
                  </div>
                </div>
                <button
                  onClick={closeModal}
                  className="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors flex-shrink-0"
                >
                  <X className="w-5 h-5 sm:w-6 sm:h-6 text-gray-600 dark:text-gray-400" />
                </button>
              </div>

              {/* Form */}
              <div className="space-y-3 sm:space-y-4 mb-4 sm:mb-6">
                {renderFormFields()}

                <div className="form-group">
                  <label className="flex items-center gap-2">
                    <input
                      type="checkbox"
                      checked={formData.enabled ?? true}
                      onChange={(e) => setFormData({ ...formData, enabled: e.target.checked })}
                      className="form-checkbox"
                    />
                    <span className="font-semibold text-sm sm:text-base">Ativar este método de pagamento</span>
                  </label>
                </div>
              </div>

              {/* Actions */}
              <div className="flex flex-col sm:flex-row gap-2 sm:gap-3">
                <button
                  onClick={closeModal}
                  className="w-full sm:flex-1 py-2.5 sm:py-3 text-sm sm:text-base bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg font-semibold hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors"
                >
                  Cancelar
                </button>
                <button
                  onClick={handleTest}
                  disabled={testing}
                  className="w-full sm:flex-1 py-2.5 sm:py-3 text-sm sm:text-base bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-semibold transition-colors flex items-center justify-center gap-1.5 sm:gap-2 disabled:opacity-50"
                >
                  {testing ? (
                    <>
                      <Loader2 className="w-4 h-4 sm:w-5 sm:h-5 animate-spin" />
                      Testando...
                    </>
                  ) : (
                    <>
                      <CreditCard className="w-4 h-4 sm:w-5 sm:h-5" />
                      <span className="hidden xs:inline">Testar </span>Conexão
                    </>
                  )}
                </button>
                <button
                  onClick={handleSave}
                  disabled={saving}
                  className="w-full sm:flex-1 py-2.5 sm:py-3 text-sm sm:text-base bg-green-600 hover:bg-green-700 text-white rounded-lg font-semibold transition-colors flex items-center justify-center gap-1.5 sm:gap-2 disabled:opacity-50"
                >
                  {saving ? (
                    <>
                      <Loader2 className="w-4 h-4 sm:w-5 sm:h-5 animate-spin" />
                      Salvando...
                    </>
                  ) : (
                    <>
                      <Check className="w-4 h-4 sm:w-5 sm:h-5" />
                      Salvar
                    </>
                  )}
                </button>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  )
}
