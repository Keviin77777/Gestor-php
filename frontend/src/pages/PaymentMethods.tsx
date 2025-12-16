import { useEffect, useState } from 'react'
import { CreditCard, Check, X } from 'lucide-react'
import { paymentMethodService } from '@/services/paymentMethodService'
import toast from 'react-hot-toast'

export default function PaymentMethods() {
  const [providers, setProviders] = useState({
    mercadopago: { enabled: false, loading: true },
    asaas: { enabled: false, loading: true },
  })

  useEffect(() => {
    loadProviders()
  }, [])

  const loadProviders = async () => {
    try {
      const [mp, asaas] = await Promise.all([
        paymentMethodService.getConfig('mercadopago'),
        paymentMethodService.getConfig('asaas'),
      ])

      setProviders({
        mercadopago: { enabled: mp.config?.enabled || false, loading: false },
        asaas: { enabled: asaas.config?.enabled || false, loading: false },
      })
    } catch (error) {
      toast.error('Erro ao carregar métodos de pagamento')
    }
  }

  const providerCards = [
    {
      id: 'mercadopago',
      name: 'Mercado Pago',
      description: 'Pagamentos via PIX com QR Code',
      color: 'bg-blue-500',
    },
    {
      id: 'asaas',
      name: 'Asaas',
      description: 'Pagamentos via PIX com QR Code',
      color: 'bg-green-500',
    },
  ]

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white">
          Métodos de Pagamento
        </h1>
        <p className="text-sm md:text-base text-gray-600 dark:text-gray-400 mt-1">
          Configure os provedores de pagamento disponíveis
        </p>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        {providerCards.map((provider) => {
          const status = providers[provider.id as keyof typeof providers]
          
          return (
            <div
              key={provider.id}
              className="bg-white/80 dark:bg-gray-800/50 backdrop-blur-sm rounded-xl shadow-sm border border-gray-200 dark:border-gray-700/50 p-6"
            >
              <div className="flex items-start justify-between mb-4">
                <div className={`${provider.color} p-3 rounded-lg`}>
                  <CreditCard className="w-6 h-6 text-white" />
                </div>
                {status.loading ? (
                  <span className="px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-800">
                    Carregando...
                  </span>
                ) : (
                  <span
                    className={`px-2 py-1 text-xs font-medium rounded-full flex items-center gap-1 ${
                      status.enabled
                        ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400'
                        : 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400'
                    }`}
                  >
                    {status.enabled ? (
                      <>
                        <Check className="w-3 h-3" />
                        Ativo
                      </>
                    ) : (
                      <>
                        <X className="w-3 h-3" />
                        Inativo
                      </>
                    )}
                  </span>
                )}
              </div>

              <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                {provider.name}
              </h3>
              <p className="text-sm text-gray-600 dark:text-gray-400 mb-4">
                {provider.description}
              </p>

              <button className="w-full px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-colors">
                Configurar
              </button>
            </div>
          )
        })}
      </div>

      <div className="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-6">
        <h3 className="text-lg font-semibold text-blue-900 dark:text-blue-300 mb-2">
          Como Funciona
        </h3>
        <ul className="space-y-2 text-sm text-blue-800 dark:text-blue-400">
          <li className="flex items-start gap-2">
            <Check className="w-5 h-5 flex-shrink-0 mt-0.5" />
            <span>Configure um ou mais provedores de pagamento</span>
          </li>
          <li className="flex items-start gap-2">
            <Check className="w-5 h-5 flex-shrink-0 mt-0.5" />
            <span>Seus clientes poderão pagar faturas via PIX automaticamente</span>
          </li>
          <li className="flex items-start gap-2">
            <Check className="w-5 h-5 flex-shrink-0 mt-0.5" />
            <span>Renovações automáticas após confirmação de pagamento</span>
          </li>
          <li className="flex items-start gap-2">
            <Check className="w-5 h-5 flex-shrink-0 mt-0.5" />
            <span>Notificações via WhatsApp quando o pagamento for confirmado</span>
          </li>
        </ul>
      </div>
    </div>
  )
}
