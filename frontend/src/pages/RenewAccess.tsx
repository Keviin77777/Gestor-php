import { useEffect, useState } from 'react'
import { Zap, Check, Clock, AlertCircle, QrCode, Copy, RefreshCw, X } from 'lucide-react'
import api from '@/services/api'
import LoadingSpinner from '@/components/LoadingSpinner'

interface Plan {
  id: string
  name: string
  price: number
  duration_days: number
  is_trial: boolean
  is_active: boolean
}

interface User {
  id: string
  name: string
  email: string
  plan_expires_at: string
  current_plan_id: string
  plan_name?: string
  days_remaining: number
}

interface PixData {
  success: boolean
  payment_id: string
  qr_code: string
  qr_code_base64?: string
  expiration_date?: string
}

export default function RenewAccess() {
  const [loading, setLoading] = useState(true)
  const [user, setUser] = useState<User | null>(null)
  const [plans, setPlans] = useState<Plan[]>([])
  const [showPixModal, setShowPixModal] = useState(false)
  const [pixData, setPixData] = useState<PixData | null>(null)
  const [selectedPlan, setSelectedPlan] = useState<Plan | null>(null)
  const [paymentStatus, setPaymentStatus] = useState<'pending' | 'approved' | 'rejected'>('pending')
  const [checkingPayment, setCheckingPayment] = useState(false)

  useEffect(() => {
    loadData()
  }, [])

  const loadData = async () => {
    try {
      const [userRes, plansRes] = await Promise.all([
        api.get('/api-auth-me.php'),
        api.get('/api-reseller-plans.php')
      ])

      if (userRes.data.success) {
        setUser(userRes.data.user)
      }

      if (plansRes.data.success) {
        setPlans(plansRes.data.plans)
      }
    } catch (error) {
      // Erro ao carregar dados
    } finally {
      setLoading(false)
    }
  }

  const getDaysRemainingInfo = (days: number) => {
    if (days < 0) {
      return {
        class: 'bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400',
        icon: <AlertCircle className="w-4 h-4" />,
        text: `Vencido há ${Math.abs(days)} ${Math.abs(days) === 1 ? 'dia' : 'dias'}`
      }
    } else if (days === 0) {
      return {
        class: 'bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400',
        icon: <Clock className="w-4 h-4" />,
        text: 'Vence hoje'
      }
    } else if (days <= 7) {
      return {
        class: 'bg-yellow-50 dark:bg-yellow-900/20 text-yellow-600 dark:text-yellow-400',
        icon: <Clock className="w-4 h-4" />,
        text: `${days} ${days === 1 ? 'dia restante' : 'dias restantes'}`
      }
    } else {
      return {
        class: 'bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400',
        icon: <Check className="w-4 h-4" />,
        text: `${days} dias restantes`
      }
    }
  }

  const getPlanDescription = (plan: Plan) => {
    if (plan.is_trial) return 'Período de teste gratuito'
    if (plan.duration_days <= 7) return 'Plano de curta duração'
    if (plan.duration_days <= 31) return 'Renovação mensal'
    if (plan.duration_days <= 93) return 'Plano trimestral com desconto'
    if (plan.duration_days <= 186) return 'Plano semestral econômico'
    if (plan.duration_days <= 365) return 'Plano anual com máximo desconto'
    return 'Plano personalizado'
  }

  const calculateSavings = (plan: Plan) => {
    if (plan.duration_days < 180) return null
    
    const monthlyPlan = plans.find(p => p.duration_days === 30 && !p.is_trial)
    if (!monthlyPlan) return null

    const monthlyTotal = (monthlyPlan.price * plan.duration_days) / 30
    const savings = monthlyTotal - plan.price
    const savingsPercent = ((savings / monthlyTotal) * 100).toFixed(0)

    if (savings > 0) {
      return {
        amount: savings.toFixed(2),
        percent: savingsPercent
      }
    }
    return null
  }

  const handleSelectPlan = async (plan: Plan) => {
    if (plan.is_trial) {
      alert('O plano trial não está disponível para renovação.')
      return
    }

    try {
      setSelectedPlan(plan)
      const response = await api.post('/api-reseller-renew-pix.php', {
        plan_id: plan.id
      })

      if (response.data.success) {
        setPixData(response.data)
        setShowPixModal(true)
        setPaymentStatus('pending')
        startPaymentCheck(response.data.payment_id)
      } else {
        alert(response.data.error || 'Erro ao gerar PIX')
      }
    } catch (error: any) {
      alert(error.response?.data?.error || 'Erro ao gerar PIX')
    }
  }

  const checkPayment = async (paymentId: string) => {
    try {
      setCheckingPayment(true)
      const response = await api.get(`/api-check-payment-status.php?payment_id=${paymentId}`)

      if (response.data.success) {
        if (response.data.status === 'approved') {
          setPaymentStatus('approved')
          setTimeout(() => {
            setShowPixModal(false)
            loadData()
          }, 2000)
        } else if (response.data.status === 'rejected' || response.data.status === 'cancelled') {
          setPaymentStatus('rejected')
        }
      }
    } catch (error) {
      // Erro ao verificar pagamento
    } finally {
      setCheckingPayment(false)
    }
  }

  const startPaymentCheck = (paymentId: string) => {
    const interval = setInterval(() => {
      checkPayment(paymentId)
    }, 5000)

    setTimeout(() => clearInterval(interval), 600000) // 10 minutos
  }

  const copyPixCode = () => {
    if (pixData?.qr_code) {
      navigator.clipboard.writeText(pixData.qr_code)
      alert('Código PIX copiado!')
    }
  }

  const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('pt-BR')
  }

  if (loading) {
    return <LoadingSpinner />
  }

  const daysInfo = user ? getDaysRemainingInfo(user.days_remaining) : null

  return (
    <div className="space-y-6 p-4 md:p-6">
      {/* Header */}
      <div className="text-center border-b border-gray-200 dark:border-gray-700 pb-6">
        <h1 className="text-3xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent flex items-center justify-center gap-3 mb-2">
          <Zap className="w-8 h-8 text-purple-600" />
          Renovar Acesso
        </h1>
        <p className="text-gray-600 dark:text-gray-400">
          Escolha o melhor plano para continuar usando o UltraGestor
        </p>
      </div>

      {/* Current Plan Card */}
      {user && (
        <div className="bg-gradient-to-br from-primary-600 to-purple-600 rounded-2xl p-6 text-white shadow-xl">
          <div className="text-center">
            <p className="text-sm font-semibold uppercase tracking-wider opacity-90 mb-2">
              Seu Plano Atual
            </p>
            <h2 className="text-3xl font-bold mb-2">{user.plan_name || 'Plano Desconhecido'}</h2>
            <p className="text-sm opacity-90 mb-3">
              Vence em: {formatDate(user.plan_expires_at)}
            </p>
            {daysInfo && (
              <div className={`inline-flex items-center gap-2 px-4 py-2 rounded-xl ${daysInfo.class} backdrop-blur-sm`}>
                {daysInfo.icon}
                <span className="font-semibold text-sm">{daysInfo.text}</span>
              </div>
            )}
          </div>
        </div>
      )}

      {/* Plans Grid */}
      <div>
        <h2 className="text-2xl font-bold text-gray-900 dark:text-white text-center mb-6">
          Planos Disponíveis
        </h2>
        
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          {plans.map((plan, index) => {
            const isCurrentPlan = user?.current_plan_id === plan.id
            const isRecommended = !plan.is_trial && index === 1
            const savings = calculateSavings(plan)

            return (
              <div
                key={plan.id}
                className={`relative bg-white dark:bg-gray-800 rounded-2xl border-2 p-6 transition-all hover:shadow-xl ${
                  isRecommended
                    ? 'border-green-500 scale-105'
                    : isCurrentPlan
                    ? 'border-primary-500'
                    : 'border-gray-200 dark:border-gray-700'
                }`}
              >
                {isRecommended && (
                  <div className="absolute -top-3 left-1/2 -translate-x-1/2 bg-green-500 text-white px-4 py-1 rounded-full text-xs font-bold">
                    ⭐ Recomendado
                  </div>
                )}

                <div className="text-center mb-6">
                  <h3 className="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                    {plan.name}
                  </h3>
                  <p className="text-sm text-gray-600 dark:text-gray-400 mb-4">
                    {getPlanDescription(plan)}
                  </p>
                  <div className="flex items-baseline justify-center gap-1">
                    <span className="text-xl font-semibold text-gray-600 dark:text-gray-400">R$</span>
                    <span className={`text-4xl font-bold ${plan.price === 0 ? 'text-green-600' : 'text-gray-900 dark:text-white'}`}>
                      {plan.price.toFixed(2).replace('.', ',')}
                    </span>
                  </div>
                  <p className="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    por {plan.duration_days} dias
                  </p>
                </div>

                {savings && (
                  <div className="bg-green-50 dark:bg-green-900/20 text-green-600 dark:text-green-400 px-3 py-2 rounded-lg text-center text-sm font-semibold mb-4">
                    Economize {savings.percent}% (R$ {savings.amount})
                  </div>
                )}

                <ul className="space-y-2 mb-6">
                  <li className="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                    <Check className="w-4 h-4 text-green-500 flex-shrink-0" />
                    {plan.duration_days} dias de acesso
                  </li>
                  <li className="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                    <Check className="w-4 h-4 text-green-500 flex-shrink-0" />
                    Gerenciamento de clientes
                  </li>
                  <li className="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                    <Check className="w-4 h-4 text-green-500 flex-shrink-0" />
                    Controle de faturas
                  </li>
                  <li className="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                    <Check className="w-4 h-4 text-green-500 flex-shrink-0" />
                    Relatórios completos
                  </li>
                  <li className="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                    <Check className="w-4 h-4 text-green-500 flex-shrink-0" />
                    Suporte técnico
                  </li>
                </ul>

                <button
                  onClick={() => handleSelectPlan(plan)}
                  disabled={isCurrentPlan || plan.is_trial}
                  className={`w-full py-3 rounded-lg font-semibold transition-all flex items-center justify-center gap-2 ${
                    isCurrentPlan
                      ? 'bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-400 cursor-not-allowed'
                      : plan.is_trial
                      ? 'bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-400 cursor-not-allowed'
                      : 'bg-primary-600 hover:bg-primary-700 text-white'
                  }`}
                >
                  {isCurrentPlan ? (
                    <>
                      <Check className="w-5 h-5" />
                      Plano Atual
                    </>
                  ) : plan.is_trial ? (
                    <>
                      <X className="w-5 h-5" />
                      Indisponível
                    </>
                  ) : (
                    <>
                      <Zap className="w-5 h-5" />
                      Selecionar Plano
                    </>
                  )}
                </button>
              </div>
            )
          })}
        </div>
      </div>

      {/* PIX Modal */}
      {showPixModal && pixData && selectedPlan && (
        <div className="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 flex items-center justify-center p-4">
          <div className="bg-white dark:bg-gray-800 rounded-2xl max-w-lg w-full max-h-[90vh] overflow-y-auto shadow-2xl">
            <div className="p-6">
              {/* Header */}
              <div className="text-center mb-6">
                <div className="w-16 h-16 bg-gradient-to-br from-primary-600 to-purple-600 rounded-full flex items-center justify-center mx-auto mb-4">
                  <QrCode className="w-8 h-8 text-white" />
                </div>
                <h2 className="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                  Pagamento via PIX
                </h2>
                <p className="text-sm text-gray-600 dark:text-gray-400">
                  Escaneie o QR Code ou copie o código
                </p>
              </div>

              {/* Plan Info */}
              <div className="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-4 mb-6">
                <div className="flex justify-between items-center mb-2">
                  <span className="text-sm text-gray-600 dark:text-gray-400">Plano:</span>
                  <span className="font-semibold text-gray-900 dark:text-white">{selectedPlan.name}</span>
                </div>
                <div className="flex justify-between items-center">
                  <span className="text-sm text-gray-600 dark:text-gray-400">Valor:</span>
                  <span className="text-xl font-bold text-primary-600">
                    R$ {selectedPlan.price.toFixed(2).replace('.', ',')}
                  </span>
                </div>
              </div>

              {/* QR Code */}
              <div className="bg-white p-6 rounded-xl border-2 border-gray-200 dark:border-gray-700 mb-6">
                {pixData.qr_code_base64 ? (
                  <img
                    src={`data:image/png;base64,${pixData.qr_code_base64}`}
                    alt="QR Code PIX"
                    className="w-full max-w-xs mx-auto"
                  />
                ) : (
                  <div className="text-center text-gray-500 py-12">
                    Use o código copia e cola abaixo
                  </div>
                )}
              </div>

              {/* PIX Code */}
              <div className="mb-6">
                <label className="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                  Código PIX Copia e Cola:
                </label>
                <div className="relative">
                  <textarea
                    value={pixData.qr_code}
                    readOnly
                    rows={3}
                    className="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-xs font-mono resize-none"
                  />
                  <button
                    onClick={copyPixCode}
                    className="absolute top-2 right-2 bg-primary-600 hover:bg-primary-700 text-white px-3 py-1 rounded-lg text-sm font-semibold flex items-center gap-2"
                  >
                    <Copy className="w-4 h-4" />
                    Copiar
                  </button>
                </div>
              </div>

              {/* Instructions */}
              <div className="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4 mb-6">
                <h4 className="text-sm font-semibold text-blue-900 dark:text-blue-300 mb-2 flex items-center gap-2">
                  <AlertCircle className="w-4 h-4" />
                  Como pagar:
                </h4>
                <ol className="text-sm text-blue-800 dark:text-blue-300 space-y-1 list-decimal list-inside">
                  <li>Abra o app do seu banco</li>
                  <li>Escolha pagar com PIX</li>
                  <li>Escaneie o QR Code ou cole o código</li>
                  <li>Confirme o pagamento</li>
                </ol>
              </div>

              {/* Payment Status */}
              <div className={`text-center p-4 rounded-lg mb-6 ${
                paymentStatus === 'approved'
                  ? 'bg-green-50 dark:bg-green-900/20 text-green-600 dark:text-green-400'
                  : paymentStatus === 'rejected'
                  ? 'bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400'
                  : 'bg-yellow-50 dark:bg-yellow-900/20 text-yellow-600 dark:text-yellow-400'
              }`}>
                <div className="flex items-center justify-center gap-2 font-semibold">
                  {paymentStatus === 'approved' ? (
                    <>
                      <Check className="w-5 h-5" />
                      Pagamento aprovado!
                    </>
                  ) : paymentStatus === 'rejected' ? (
                    <>
                      <X className="w-5 h-5" />
                      Pagamento não aprovado
                    </>
                  ) : (
                    <>
                      <div className="w-4 h-4 border-2 border-current border-t-transparent rounded-full animate-spin" />
                      Aguardando pagamento...
                    </>
                  )}
                </div>
              </div>

              {/* Actions */}
              <div className="flex gap-3">
                <button
                  onClick={() => setShowPixModal(false)}
                  className="flex-1 py-3 bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg font-semibold hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors"
                >
                  Fechar
                </button>
                <button
                  onClick={() => checkPayment(pixData.payment_id)}
                  disabled={checkingPayment}
                  className="flex-1 py-3 bg-primary-600 hover:bg-primary-700 text-white rounded-lg font-semibold transition-colors flex items-center justify-center gap-2 disabled:opacity-50"
                >
                  <RefreshCw className={`w-5 h-5 ${checkingPayment ? 'animate-spin' : ''}`} />
                  Verificar Pagamento
                </button>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  )
}
