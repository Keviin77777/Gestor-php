import { NavLink } from 'react-router-dom'
import { useState, useEffect } from 'react'
import { 
  LayoutDashboard, 
  Users, 
  FileText, 
  Server, 
  CreditCard, 
  MessageSquare,
  Calendar,
  Smartphone,
  Upload,
  Clock,
  Inbox,
  BarChart3,
  X,
  AlertCircle,
  CheckCircle,
  Zap
} from 'lucide-react'
import api from '../services/api'

interface MenuItem {
  name: string
  href: string
  icon: any
}

interface SidebarProps {
  isOpen: boolean
  onClose: () => void
}

interface Category {
  name: string
  items: MenuItem[]
}

const navigation: Category[] = [
  {
    name: 'Conta',
    items: [
      { name: 'Renovar Acesso', href: '/renew-access', icon: Zap },
    ]
  },
  {
    name: 'Principal',
    items: [
      { name: 'Dashboard', href: '/', icon: LayoutDashboard },
    ]
  },
  {
    name: 'Relatórios',
    items: [
      { name: 'Geral', href: '/reports', icon: BarChart3 },
      { name: 'Gráfico Financeiro', href: '/reports/financial', icon: BarChart3 },
      { name: 'Detalhamento Mensal', href: '/reports/monthly', icon: Calendar },
    ]
  },
  {
    name: 'Gestão',
    items: [
      { name: 'Clientes', href: '/clients', icon: Users },
      { name: 'Planos', href: '/plans', icon: Calendar },
      { name: 'Aplicativos', href: '/applications', icon: Smartphone },
      { name: 'Importar Clientes', href: '/clients/import', icon: Upload },
      { name: 'Faturas', href: '/invoices', icon: FileText },
      { name: 'Servidores', href: '/servers', icon: Server },
    ]
  },
  {
    name: 'WhatsApp',
    items: [
      { name: 'Parear WhatsApp', href: '/whatsapp', icon: MessageSquare },
      { name: 'Templates', href: '/whatsapp/templates', icon: FileText },
      { name: 'Agendamento', href: '/whatsapp/scheduling', icon: Clock },
      { name: 'Fila de Mensagens', href: '/whatsapp/queue', icon: Inbox },
    ]
  },
  {
    name: 'Financeiro',
    items: [
      { name: 'Métodos de Pagamento', href: '/payment-methods', icon: CreditCard },
    ]
  },
]

export default function Sidebar({ isOpen, onClose }: SidebarProps) {
  const [whatsappStatus, setWhatsappStatus] = useState<'connected' | 'disconnected'>('disconnected')
  const [expiryDate, setExpiryDate] = useState<string | null>(null)
  const [daysUntilExpiry, setDaysUntilExpiry] = useState<number>(0)

  useEffect(() => {
    // Verificar status do WhatsApp em BACKGROUND (não bloqueia renderização)
    const checkWhatsAppStatus = async () => {
      try {
        const response = await api.get('/api-whatsapp-status.php')
        if (response.data.success && response.data.session?.status === 'connected') {
          setWhatsappStatus('connected')
        } else {
          setWhatsappStatus('disconnected')
        }
      } catch (error) {
        setWhatsappStatus('disconnected')
      }
    }

    // Buscar data de vencimento do usuário da API em BACKGROUND
    const loadUserExpiry = async () => {
      try {
        const response = await api.get('/api-profile.php')
        if (response.data.success && response.data.user) {
          const user = response.data.user
          
          // Verificar plan_expires_at
          if (user.plan_expires_at) {
            setExpiryDate(user.plan_expires_at)
            
            // Calcular dias até vencer
            const expiry = new Date(user.plan_expires_at)
            const today = new Date()
            today.setHours(0, 0, 0, 0)
            expiry.setHours(0, 0, 0, 0)
            
            const diffTime = expiry.getTime() - today.getTime()
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
            setDaysUntilExpiry(diffDays)
          }
        }
      } catch (error) {
        // Erro ao carregar dados
      }
    }

    // Executar em background sem bloquear
    checkWhatsAppStatus()
    loadUserExpiry()

    // Verificar status a cada 30 segundos
    const interval = setInterval(checkWhatsAppStatus, 30000)
    return () => clearInterval(interval)
  }, [])

  const formatExpiryDate = (date: string) => {
    try {
      return new Date(date).toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit', year: 'numeric' })
    } catch {
      return date
    }
  }

  return (
    <>
      {/* Mobile overlay */}
      {isOpen && (
        <div 
          className="fixed inset-0 bg-black/50 z-40 lg:hidden"
          onClick={onClose}
        />
      )}

      {/* Sidebar */}
      <div className={`
        fixed lg:static inset-y-0 left-0 z-50
        w-64 bg-white dark:bg-[#0f1419] border-r border-gray-200 dark:border-gray-800/50 
        flex flex-col h-screen
        transform transition-transform duration-300 ease-in-out
        ${isOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'}
      `}>
        <div className="border-b border-gray-200 dark:border-gray-800/50">
          <div className="flex items-center justify-between h-16 px-4">
            <h1 className="text-xl md:text-2xl font-bold bg-gradient-to-r from-primary-600 to-purple-600 bg-clip-text text-transparent">
              UltraGestor
            </h1>
            <button
              onClick={onClose}
              className="lg:hidden p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800/50"
            >
              <X className="w-5 h-5 text-gray-600 dark:text-gray-400" />
            </button>
          </div>

          {/* Status Cards */}
          <div className="px-3 pb-3 space-y-2">
            {/* WhatsApp Status */}
            <div className={`flex items-center justify-between px-3 py-2 rounded-lg ${
              whatsappStatus === 'connected' 
                ? 'bg-green-50 dark:bg-green-900/20' 
                : 'bg-gray-50 dark:bg-gray-800/50'
            }`}>
              <div className="flex items-center gap-2">
                {whatsappStatus === 'connected' ? (
                  <CheckCircle className="w-4 h-4 text-green-600 dark:text-green-400" />
                ) : (
                  <AlertCircle className="w-4 h-4 text-gray-400 dark:text-gray-600" />
                )}
                <span className="text-xs font-medium text-gray-700 dark:text-gray-300">
                  WhatsApp
                </span>
              </div>
              <span className={`text-xs font-semibold ${
                whatsappStatus === 'connected'
                  ? 'text-green-600 dark:text-green-400'
                  : 'text-gray-500 dark:text-gray-500'
              }`}>
                {whatsappStatus === 'connected' ? 'Online' : 'Offline'}
              </span>
            </div>

            {/* Expiry Date */}
            {expiryDate && (
              <div className={`flex items-center justify-between px-3 py-2 rounded-lg ${
                daysUntilExpiry <= 7 
                  ? 'bg-red-50 dark:bg-red-900/20' 
                  : daysUntilExpiry <= 30
                  ? 'bg-yellow-50 dark:bg-yellow-900/20'
                  : 'bg-blue-50 dark:bg-blue-900/20'
              }`}>
                <div className="flex items-center gap-2">
                  <Calendar className={`w-4 h-4 ${
                    daysUntilExpiry <= 7 
                      ? 'text-red-600 dark:text-red-400' 
                      : daysUntilExpiry <= 30
                      ? 'text-yellow-600 dark:text-yellow-400'
                      : 'text-blue-600 dark:text-blue-400'
                  }`} />
                  <span className="text-xs font-medium text-gray-700 dark:text-gray-300">
                    Vencimento
                  </span>
                </div>
                <div className="text-right">
                  <div className={`text-xs font-semibold ${
                    daysUntilExpiry <= 7 
                      ? 'text-red-600 dark:text-red-400' 
                      : daysUntilExpiry <= 30
                      ? 'text-yellow-600 dark:text-yellow-400'
                      : 'text-blue-600 dark:text-blue-400'
                  }`}>
                    {daysUntilExpiry > 0 ? `${daysUntilExpiry}d` : 'Vencido'}
                  </div>
                  <div className="text-[10px] text-gray-500 dark:text-gray-500">
                    {formatExpiryDate(expiryDate)}
                  </div>
                </div>
              </div>
            )}
          </div>
        </div>
        
        <nav className="flex-1 overflow-y-auto py-4 px-3">
          {navigation.map((category, categoryIndex) => (
            <div key={category.name} className={categoryIndex > 0 ? 'mt-6' : ''}>
              {/* Category Header */}
              <div className="px-4 mb-2">
                <h3 className="text-xs font-semibold text-gray-500 dark:text-gray-600 uppercase tracking-wider">
                  {category.name}
                </h3>
              </div>
              
              {/* Category Items */}
              <div className="space-y-1">
                {category.items.map((item) => (
                  <NavLink
                    key={item.name}
                    to={item.href!}
                    onClick={onClose}
                    className={({ isActive }) =>
                      `flex items-center px-4 py-2.5 rounded-lg transition-colors ${
                        isActive
                          ? 'bg-primary-50 dark:bg-primary-500/10 text-primary-600 dark:text-primary-400'
                          : 'text-gray-700 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800/50'
                      }`
                    }
                  >
                    <item.icon className="w-5 h-5 mr-3" />
                    <span className="text-sm font-medium">{item.name}</span>
                  </NavLink>
                ))}
              </div>
            </div>
          ))}
        </nav>
      </div>
    </>
  )
}
