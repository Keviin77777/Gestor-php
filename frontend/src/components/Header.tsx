import { Moon, Sun, LogOut, User, ChevronDown, Menu, Send } from 'lucide-react'
import { useState, useEffect } from 'react'
import { useNavigate } from 'react-router-dom'
import { useAuthStore } from '@/stores/useAuthStore'
import { authService } from '@/services/authService'

interface HeaderProps {
  onMenuClick: () => void
}

export default function Header({ onMenuClick }: HeaderProps) {
  const [darkMode, setDarkMode] = useState(false)
  const [showMenu, setShowMenu] = useState(false)
  const [telegramLink, setTelegramLink] = useState('https://t.me/+jim14-gGOBFhNWMx')
  const [whatsappNumber, setWhatsappNumber] = useState('14997349352')
  const { user, logout } = useAuthStore()
  const navigate = useNavigate()

  useEffect(() => {
    const isDark = localStorage.getItem('theme') === 'dark'
    setDarkMode(isDark)
    if (isDark) {
      document.documentElement.classList.add('dark')
    }

    // Buscar links sociais do admin
    const fetchSocialLinks = async () => {
      try {
        const response = await authService.getProfile()
        if (response.user) {
          if (response.user.telegram_link) {
            setTelegramLink(response.user.telegram_link)
          }
          if (response.user.whatsapp_number) {
            setWhatsappNumber(response.user.whatsapp_number)
          }
        }
      } catch (error) {
        // Usar valores padrão em caso de erro
      }
    }

    fetchSocialLinks()
  }, [])

  const toggleDarkMode = () => {
    const newMode = !darkMode
    setDarkMode(newMode)
    if (newMode) {
      document.documentElement.classList.add('dark')
      localStorage.setItem('theme', 'dark')
    } else {
      document.documentElement.classList.remove('dark')
      localStorage.setItem('theme', 'light')
    }
  }

  return (
    <header className="h-16 bg-white dark:bg-[#0f1419] border-b border-gray-200 dark:border-gray-800/50 flex items-center justify-between px-4 md:px-6">
      <button
        onClick={onMenuClick}
        className="lg:hidden p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800/50 transition-colors"
      >
        <Menu className="w-6 h-6 text-gray-600 dark:text-gray-400" />
      </button>

      <div className="flex-1 lg:flex-none" />
      
      <div className="flex items-center gap-2 md:gap-4">
        <a
          href={`https://wa.me/${whatsappNumber}`}
          target="_blank"
          rel="noopener noreferrer"
          className="p-2 rounded-lg hover:bg-green-50 dark:hover:bg-green-900/20 transition-colors group"
          title="Fale conosco no WhatsApp"
        >
          <svg className="w-5 h-5 text-green-600 dark:text-green-400 group-hover:scale-110 transition-transform" fill="currentColor" viewBox="0 0 24 24">
            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
          </svg>
        </a>

        <a
          href={telegramLink}
          target="_blank"
          rel="noopener noreferrer"
          className="p-2 rounded-lg hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors group"
          title="Junte-se ao nosso canal no Telegram"
        >
          <Send className="w-5 h-5 text-blue-600 dark:text-blue-400 group-hover:scale-110 transition-transform" />
        </a>

        <button
          onClick={toggleDarkMode}
          className="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800/50 transition-colors"
        >
          {darkMode ? (
            <Sun className="w-5 h-5 text-gray-600 dark:text-gray-300" />
          ) : (
            <Moon className="w-5 h-5 text-gray-600" />
          )}
        </button>

        <div className="relative">
          <button
            onClick={() => setShowMenu(!showMenu)}
            className="flex items-center gap-2 md:gap-3 hover:bg-gray-100 dark:hover:bg-gray-800/50 rounded-lg p-2 transition-colors"
          >
            <div className="text-right hidden md:block">
              <p className="text-sm font-medium text-gray-900 dark:text-white">
                {user?.name}
              </p>
              <p className="text-xs text-gray-500 dark:text-gray-400">
                {user?.email}
              </p>
            </div>
            <div className="w-8 h-8 md:w-10 md:h-10 rounded-full bg-primary-600 flex items-center justify-center text-white font-semibold text-sm md:text-base">
              {user?.name?.charAt(0).toUpperCase()}
            </div>
            <ChevronDown className="w-4 h-4 text-gray-500 dark:text-gray-400 hidden md:block" />
          </button>

          {showMenu && (
            <>
              <div 
                className="fixed inset-0 z-10" 
                onClick={() => setShowMenu(false)}
              />
              <div className="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 py-1 z-20">
                <div className="md:hidden px-4 py-2 border-b border-gray-200 dark:border-gray-700">
                  <p className="text-sm font-medium text-gray-900 dark:text-white">
                    {user?.name}
                  </p>
                  <p className="text-xs text-gray-500 dark:text-gray-400">
                    {user?.email}
                  </p>
                </div>
                <button
                  onClick={() => {
                    navigate('/profile')
                    setShowMenu(false)
                  }}
                  className="w-full flex items-center gap-2 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700"
                >
                  <User className="w-4 h-4" />
                  Meu Perfil
                </button>
                <hr className="my-1 border-gray-200 dark:border-gray-700" />
                <button
                  onClick={async () => {
                    setShowMenu(false)
                    try {
                      // Limpar estado primeiro
                      logout()
                      // Chamar API de logout
                      await authService.logout()
                    } catch (error) {
                      // Ignorar erros da API
                    } finally {
                      // Forçar redirecionamento usando window.location
                      window.location.href = '/login'
                    }
                  }}
                  className="w-full flex items-center gap-2 px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20"
                >
                  <LogOut className="w-4 h-4" />
                  Sair
                </button>
              </div>
            </>
          )}
        </div>
      </div>
    </header>
  )
}
