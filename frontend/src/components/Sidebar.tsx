import { NavLink } from 'react-router-dom'
import { useState } from 'react'
import { 
  LayoutDashboard, 
  Users, 
  FileText, 
  Server, 
  CreditCard, 
  MessageSquare,
  ChevronDown,
  List,
  Calendar,
  Smartphone,
  Upload,
  Clock,
  Inbox,
  BarChart3,
  X
} from 'lucide-react'

interface SubMenuItem {
  name: string
  href: string
  icon: any
}

interface MenuItem {
  name: string
  href?: string
  icon: any
  submenu?: SubMenuItem[]
}

interface SidebarProps {
  isOpen: boolean
  onClose: () => void
}

const navigation: MenuItem[] = [
  { name: 'Dashboard', href: '/', icon: LayoutDashboard },
  { 
    name: 'Clientes', 
    icon: Users,
    submenu: [
      { name: 'Lista de Clientes', href: '/clients', icon: List },
      { name: 'Planos', href: '/plans', icon: Calendar },
      { name: 'Aplicativos', href: '/applications', icon: Smartphone },
      { name: 'Importar', href: '/clients/import', icon: Upload },
    ]
  },
  { name: 'Faturas', href: '/invoices', icon: FileText },
  { name: 'Servidores', href: '/servers', icon: Server },
  { 
    name: 'WhatsApp', 
    icon: MessageSquare,
    submenu: [
      { name: 'Parear WhatsApp', href: '/whatsapp', icon: MessageSquare },
      { name: 'Templates', href: '/whatsapp/templates', icon: FileText },
      { name: 'Agendamento', href: '/whatsapp/scheduling', icon: Clock },
      { name: 'Fila de Mensagens', href: '/whatsapp/queue', icon: Inbox },
    ]
  },
  { name: 'Métodos de Pagamento', href: '/payment-methods', icon: CreditCard },
  { name: 'Relatórios', href: '/reports', icon: BarChart3 },
]

export default function Sidebar({ isOpen, onClose }: SidebarProps) {
  const [openMenus, setOpenMenus] = useState<string[]>(['Clientes'])

  const toggleMenu = (menuName: string) => {
    setOpenMenus(prev => 
      prev.includes(menuName) 
        ? prev.filter(name => name !== menuName)
        : [...prev, menuName]
    )
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
        <div className="flex items-center justify-between h-16 border-b border-gray-200 dark:border-gray-800/50 px-4">
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
        
        <nav className="flex-1 overflow-y-auto py-4 px-3">
          {navigation.map((item) => (
            <div key={item.name} className="mb-1">
              {item.submenu ? (
                <>
                  <button
                    onClick={() => toggleMenu(item.name)}
                    className={`w-full flex items-center justify-between px-4 py-3 rounded-lg transition-all ${
                      openMenus.includes(item.name)
                        ? 'bg-primary-50 dark:bg-primary-500/10 text-primary-600 dark:text-primary-400'
                        : 'text-gray-700 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800/50'
                    }`}
                  >
                    <div className="flex items-center">
                      <item.icon className="w-5 h-5 mr-3" />
                      <span className="font-medium">{item.name}</span>
                    </div>
                    <ChevronDown 
                      className={`w-4 h-4 transition-transform ${
                        openMenus.includes(item.name) ? 'rotate-180' : ''
                      }`} 
                    />
                  </button>
                  
                  <div 
                    className={`overflow-hidden transition-all duration-300 ${
                      openMenus.includes(item.name) ? 'max-h-96 opacity-100' : 'max-h-0 opacity-0'
                    }`}
                  >
                    <div className="ml-4 mt-1 space-y-1 border-l-2 border-gray-200 dark:border-gray-800 pl-2">
                      {item.submenu.map((subItem) => (
                        <NavLink
                          key={subItem.name}
                          to={subItem.href}
                          onClick={onClose}
                          className={({ isActive }) =>
                            `flex items-center px-3 py-2 rounded-lg text-sm transition-colors ${
                              isActive
                                ? 'bg-primary-100 dark:bg-primary-500/10 text-primary-700 dark:text-primary-400 font-medium'
                                : 'text-gray-600 dark:text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800/50 hover:text-gray-900 dark:hover:text-gray-300'
                            }`
                          }
                        >
                          <subItem.icon className="w-4 h-4 mr-2" />
                          <span>{subItem.name}</span>
                        </NavLink>
                      ))}
                    </div>
                  </div>
                </>
              ) : (
                <NavLink
                  to={item.href!}
                  onClick={onClose}
                  className={({ isActive }) =>
                    `flex items-center px-4 py-3 rounded-lg transition-colors ${
                      isActive
                        ? 'bg-primary-50 dark:bg-primary-500/10 text-primary-600 dark:text-primary-400'
                        : 'text-gray-700 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800/50'
                    }`
                  }
                >
                  <item.icon className="w-5 h-5 mr-3" />
                  <span className="font-medium">{item.name}</span>
                </NavLink>
              )}
            </div>
          ))}
        </nav>
      </div>
    </>
  )
}
