import { useState, useEffect } from 'react'
import { useAuthStore } from '@/stores/useAuthStore'
import { authService } from '@/services/authService'
import toast from 'react-hot-toast'
import { Eye, EyeOff, Users, MessageCircle, BarChart3, Shield, Loader2, LogIn, UserPlus } from 'lucide-react'
import { usePageTitle } from '@/hooks/usePageTitle'

export default function Login() {
  usePageTitle('Login')
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [name, setName] = useState('')
  const [whatsapp, setWhatsapp] = useState('')
  const [passwordConfirm, setPasswordConfirm] = useState('')
  const [showPassword, setShowPassword] = useState(false)
  const [showPasswordConfirm, setShowPasswordConfirm] = useState(false)
  const [remember, setRemember] = useState(false)
  const [acceptTerms, setAcceptTerms] = useState(false)
  const [loading, setLoading] = useState(false)
  
  // Detectar se deve abrir em modo registro via URL
  const searchParams = new URLSearchParams(window.location.search)
  const [isRegister, setIsRegister] = useState(searchParams.get('register') === 'true')
  
  const { login } = useAuthStore()

  useEffect(() => {
    // Animação de entrada
    const elements = document.querySelectorAll('.animate-in')
    elements.forEach((el, index) => {
      setTimeout(() => {
        el.classList.add('show')
      }, index * 100)
    })
  }, [isRegister])

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    
    if (isRegister) {
      // Validações de registro
      if (password !== passwordConfirm) {
        toast.error('As senhas não coincidem')
        return
      }
      
      if (password.length < 6) {
        toast.error('A senha deve ter no mínimo 6 caracteres')
        return
      }
      
      if (!acceptTerms) {
        toast.error('Você deve aceitar os termos de uso')
        return
      }
    }
    
    setLoading(true)

    try {
      if (isRegister) {
        const data = await authService.register({ name, email, password, whatsapp })
        
        if (data.success && data.token && data.user) {
          login(data.user, data.token)
          toast.success('Conta criada com sucesso! Bem-vindo!')
        } else {
          throw new Error('Resposta inválida do servidor')
        }
      } else {
        const data = await authService.login({ email, password })

        if (data.success && data.token && data.user) {
          login(data.user, data.token)
          toast.success('Login realizado com sucesso!')
        } else {
          throw new Error('Resposta inválida do servidor')
        }
      }
    } catch (error: any) {
      const errorMessage = error.response?.data?.error || error.message || (isRegister ? 'Erro ao criar conta' : 'Erro ao fazer login')
      toast.error(errorMessage)
    } finally {
      setLoading(false)
    }
  }
  
  const formatWhatsApp = (value: string) => {
    const numbers = value.replace(/\D/g, '')
    if (numbers.length <= 2) return numbers
    if (numbers.length <= 7) return `(${numbers.slice(0, 2)}) ${numbers.slice(2)}`
    return `(${numbers.slice(0, 2)}) ${numbers.slice(2, 7)}-${numbers.slice(7, 11)}`
  }

  return (
    <div className="min-h-screen flex overflow-hidden bg-gradient-to-br from-slate-900 via-blue-900 to-slate-900 dark:from-slate-950 dark:via-blue-950 dark:to-slate-950">
      {/* Background animado */}
      <div className="absolute inset-0 overflow-hidden">
        <div className="absolute -top-40 -right-40 w-80 h-80 bg-blue-500/20 rounded-full blur-3xl animate-pulse"></div>
        <div className="absolute -bottom-40 -left-40 w-80 h-80 bg-purple-500/20 rounded-full blur-3xl animate-pulse delay-1000"></div>
        <div className="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-96 h-96 bg-indigo-500/10 rounded-full blur-3xl animate-pulse delay-500"></div>
      </div>

      <div className="relative z-10 w-full flex flex-col lg:flex-row">
        {/* Lado Esquerdo - Informações */}
        <div className="hidden lg:flex lg:w-1/2 xl:w-3/5 flex-col justify-between p-12 xl:p-16">
          <div className="space-y-8">
            {/* Logo e Título */}
            <div className="space-y-4 animate-in opacity-0 translate-y-4 transition-all duration-700 ease-out">
              <div className="flex items-center gap-4">
                <img 
                  src="https://i.imgur.com/I9Ha06L.png" 
                  alt="UltraGestor Logo" 
                  className="h-16 w-auto"
                />
                <div>
                  <h1 className="text-4xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">
                    UltraGestor
                  </h1>
                  <p className="text-slate-400 text-sm">Sistema Profissional de Gestão IPTV</p>
                </div>
              </div>
            </div>

            {/* Stats */}
            <div className="grid grid-cols-3 gap-6 animate-in opacity-0 translate-y-4 transition-all duration-700 ease-out delay-100">
              <div className="bg-white/5 backdrop-blur-sm border border-white/10 rounded-2xl p-6 hover:bg-white/10 transition-all">
                <div className="text-3xl font-bold text-white mb-1">500+</div>
                <div className="text-slate-400 text-sm">Clientes Ativos</div>
              </div>
              <div className="bg-white/5 backdrop-blur-sm border border-white/10 rounded-2xl p-6 hover:bg-white/10 transition-all">
                <div className="text-3xl font-bold text-white mb-1">99.9%</div>
                <div className="text-slate-400 text-sm">Uptime</div>
              </div>
              <div className="bg-white/5 backdrop-blur-sm border border-white/10 rounded-2xl p-6 hover:bg-white/10 transition-all">
                <div className="text-3xl font-bold text-white mb-1">24/7</div>
                <div className="text-slate-400 text-sm">Suporte</div>
              </div>
            </div>

            {/* Features */}
            <div className="space-y-4">
              <div className="bg-white/5 backdrop-blur-sm border border-white/10 rounded-2xl p-6 hover:bg-white/10 transition-all animate-in opacity-0 translate-y-4 duration-700 ease-out delay-200">
                <div className="flex items-start gap-4">
                  <div className="w-12 h-12 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center flex-shrink-0">
                    <Users className="w-6 h-6 text-white" />
                  </div>
                  <div>
                    <h3 className="text-white font-semibold mb-1">Gestão Completa</h3>
                    <p className="text-slate-400 text-sm">Controle total de clientes, servidores e assinaturas em uma plataforma unificada</p>
                  </div>
                </div>
              </div>

              <div className="bg-white/5 backdrop-blur-sm border border-white/10 rounded-2xl p-6 hover:bg-white/10 transition-all animate-in opacity-0 translate-y-4 duration-700 ease-out delay-300">
                <div className="flex items-start gap-4">
                  <div className="w-12 h-12 bg-gradient-to-br from-green-500 to-green-600 rounded-xl flex items-center justify-center flex-shrink-0">
                    <MessageCircle className="w-6 h-6 text-white" />
                  </div>
                  <div>
                    <h3 className="text-white font-semibold mb-1">Automação WhatsApp</h3>
                    <p className="text-slate-400 text-sm">Mensagens automáticas, lembretes e notificações inteligentes para seus clientes</p>
                  </div>
                </div>
              </div>

              <div className="bg-white/5 backdrop-blur-sm border border-white/10 rounded-2xl p-6 hover:bg-white/10 transition-all animate-in opacity-0 translate-y-4 duration-700 ease-out delay-400">
                <div className="flex items-start gap-4">
                  <div className="w-12 h-12 bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl flex items-center justify-center flex-shrink-0">
                    <BarChart3 className="w-6 h-6 text-white" />
                  </div>
                  <div>
                    <h3 className="text-white font-semibold mb-1">Dashboard Avançado</h3>
                    <p className="text-slate-400 text-sm">Relatórios em tempo real, métricas detalhadas e insights para seu negócio</p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          {/* Footer */}
          <div className="flex items-center gap-2 text-slate-400 text-sm animate-in opacity-0 translate-y-4 duration-700 ease-out delay-500">
            <Shield className="w-4 h-4" />
            <span>100% Seguro • Cloud Backup • Suporte 24/7</span>
          </div>
        </div>

        {/* Lado Direito - Formulário */}
        <div className="flex-1 flex items-center justify-center p-6 lg:p-12">
          <div className="w-full max-w-md">
            <div className="bg-white/10 dark:bg-white/5 backdrop-blur-xl border border-white/20 dark:border-white/10 rounded-3xl shadow-2xl p-8 lg:p-10">
              {/* Header do Form */}
              <div className="text-center mb-8 animate-in opacity-0 translate-y-4 transition-all duration-700 ease-out">
                <div className="flex flex-col items-center gap-3 mb-4">
                  <img 
                    src="https://i.imgur.com/I9Ha06L.png" 
                    alt="UltraGestor Logo" 
                    className="h-20 w-auto"
                  />
                  <h1 className="text-2xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">
                    UltraGestor
                  </h1>
                </div>
                <h2 className="text-3xl font-bold text-white mb-2">
                  {isRegister ? 'Criar Conta' : 'Bem-vindo de volta!'}
                </h2>
                <p className="text-slate-300">
                  {isRegister ? 'Preencha os dados para criar sua conta' : 'Faça login para acessar sua conta'}
                </p>
              </div>

              {/* Formulário */}
              <form onSubmit={handleSubmit} className="space-y-5">
                {isRegister && (
                  <>
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4 animate-in opacity-0 translate-y-4 transition-all duration-700 ease-out delay-100">
                      <div>
                        <label className="block text-sm font-medium text-slate-200 mb-2">
                          Nome Completo
                        </label>
                        <input
                          type="text"
                          value={name}
                          onChange={(e) => setName(e.target.value)}
                          className="w-full px-4 py-3 rounded-xl bg-white/10 border border-white/20 text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                          placeholder="Digite seu nome completo"
                          required
                        />
                      </div>

                      <div>
                        <label className="block text-sm font-medium text-slate-200 mb-2">
                          E-mail
                        </label>
                        <input
                          type="email"
                          value={email}
                          onChange={(e) => setEmail(e.target.value)}
                          className="w-full px-4 py-3 rounded-xl bg-white/10 border border-white/20 text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                          placeholder="Digite seu e-mail"
                          required
                        />
                      </div>
                    </div>

                    <div className="animate-in opacity-0 translate-y-4 transition-all duration-700 ease-out delay-150">
                      <label className="block text-sm font-medium text-slate-200 mb-2">
                        WhatsApp
                      </label>
                      <input
                        type="tel"
                        value={whatsapp}
                        onChange={(e) => setWhatsapp(formatWhatsApp(e.target.value))}
                        className="w-full px-4 py-3 rounded-xl bg-white/10 border border-white/20 text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                        placeholder="(00) 00000-0000"
                        required
                      />
                      <small className="text-slate-400 text-xs mt-1 block">Usado para suporte e notificações importantes</small>
                    </div>
                  </>
                )}

                {!isRegister && (
                  <div className="animate-in opacity-0 translate-y-4 transition-all duration-700 ease-out delay-100">
                    <label className="block text-sm font-medium text-slate-200 mb-2">
                      Email ou Usuário
                    </label>
                    <input
                      type="email"
                      value={email}
                      onChange={(e) => setEmail(e.target.value)}
                      className="w-full px-4 py-3 rounded-xl bg-white/10 border border-white/20 text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                      placeholder="Digite seu email ou usuário"
                      required
                    />
                    <small className="text-slate-400 text-xs mt-1 block">Digite suas credenciais para fazer login</small>
                  </div>
                )}

                <div className={`${isRegister ? 'grid grid-cols-1 md:grid-cols-2 gap-4' : ''} animate-in opacity-0 translate-y-4 transition-all duration-700 ease-out delay-200`}>
                  <div>
                    <label className="block text-sm font-medium text-slate-200 mb-2">
                      Senha
                    </label>
                    <div className="relative">
                      <input
                        type={showPassword ? 'text' : 'password'}
                        value={password}
                        onChange={(e) => setPassword(e.target.value)}
                        className="w-full px-4 py-3 pr-12 rounded-xl bg-white/10 border border-white/20 text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                        placeholder="••••••••••••"
                        required
                        minLength={6}
                      />
                      <button
                        type="button"
                        onClick={() => setShowPassword(!showPassword)}
                        className="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-white transition-colors"
                      >
                        {showPassword ? <EyeOff className="w-5 h-5" /> : <Eye className="w-5 h-5" />}
                      </button>
                    </div>
                    {isRegister && <small className="text-slate-400 text-xs mt-1 block">Mínimo de 6 caracteres</small>}
                  </div>

                  {isRegister && (
                    <div>
                      <label className="block text-sm font-medium text-slate-200 mb-2">
                        Confirmar Senha
                      </label>
                      <div className="relative">
                        <input
                          type={showPasswordConfirm ? 'text' : 'password'}
                          value={passwordConfirm}
                          onChange={(e) => setPasswordConfirm(e.target.value)}
                          className="w-full px-4 py-3 pr-12 rounded-xl bg-white/10 border border-white/20 text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                          placeholder="••••••••••••"
                          required
                          minLength={6}
                        />
                        <button
                          type="button"
                          onClick={() => setShowPasswordConfirm(!showPasswordConfirm)}
                          className="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-white transition-colors"
                        >
                          {showPasswordConfirm ? <EyeOff className="w-5 h-5" /> : <Eye className="w-5 h-5" />}
                        </button>
                      </div>
                    </div>
                  )}
                </div>

                {isRegister ? (
                  <div className="animate-in opacity-0 translate-y-4 transition-all duration-700 ease-out delay-300">
                    <label className="flex items-start gap-2 cursor-pointer">
                      <input
                        type="checkbox"
                        checked={acceptTerms}
                        onChange={(e) => setAcceptTerms(e.target.checked)}
                        className="w-4 h-4 mt-0.5 rounded border-white/20 bg-white/10 text-blue-600 focus:ring-2 focus:ring-blue-500"
                        required
                      />
                      <span className="text-sm text-slate-300">
                        Eu aceito os <a href="#" className="text-blue-400 hover:text-blue-300">termos de uso</a> e <a href="#" className="text-blue-400 hover:text-blue-300">política de privacidade</a>
                      </span>
                    </label>
                  </div>
                ) : (
                  <div className="flex items-center justify-between animate-in opacity-0 translate-y-4 transition-all duration-700 ease-out delay-300">
                    <label className="flex items-center gap-2 cursor-pointer">
                      <input
                        type="checkbox"
                        checked={remember}
                        onChange={(e) => setRemember(e.target.checked)}
                        className="w-4 h-4 rounded border-white/20 bg-white/10 text-blue-600 focus:ring-2 focus:ring-blue-500"
                      />
                      <span className="text-sm text-slate-300">Lembrar-me</span>
                    </label>
                    <a href="#" className="text-sm text-blue-400 hover:text-blue-300 transition-colors">
                      Esqueceu a senha?
                    </a>
                  </div>
                )}

                <button
                  type="submit"
                  disabled={loading}
                  className="w-full bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white font-semibold py-3 rounded-xl transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2 shadow-lg hover:shadow-xl animate-in opacity-0 translate-y-4 duration-700 ease-out delay-400"
                >
                  {loading ? (
                    <>
                      <Loader2 className="w-5 h-5 animate-spin" />
                      {isRegister ? 'Criando conta...' : 'Entrando...'}
                    </>
                  ) : (
                    <>
                      {isRegister ? <UserPlus className="w-5 h-5" /> : <LogIn className="w-5 h-5" />}
                      {isRegister ? 'Criar Conta e Começar Trial' : 'Entrar no Sistema'}
                    </>
                  )}
                </button>

                <div className="relative my-6 animate-in opacity-0 translate-y-4 transition-all duration-700 ease-out delay-500">
                  <div className="absolute inset-0 flex items-center">
                    <div className="w-full border-t border-white/20"></div>
                  </div>
                  <div className="relative flex justify-center text-sm">
                    <span className="px-4 bg-transparent text-slate-400">ou</span>
                  </div>
                </div>

                <button
                  type="button"
                  onClick={() => {
                    setIsRegister(!isRegister)
                    // Limpar campos ao trocar de modo
                    setEmail('')
                    setPassword('')
                    setName('')
                    setWhatsapp('')
                    setPasswordConfirm('')
                    setAcceptTerms(false)
                  }}
                  className="w-full bg-white/10 hover:bg-white/20 border border-white/20 text-white font-semibold py-3 rounded-xl transition-all flex items-center justify-center gap-2 animate-in opacity-0 translate-y-4 duration-700 ease-out delay-600"
                >
                  {isRegister ? <LogIn className="w-5 h-5" /> : <UserPlus className="w-5 h-5" />}
                  {isRegister ? 'Já tenho uma conta' : 'Criar Nova Conta'}
                </button>

                <div className="flex items-center justify-center gap-2 text-slate-400 text-sm mt-6 animate-in opacity-0 translate-y-4 transition-all duration-700 ease-out delay-700">
                  <Shield className="w-4 h-4" />
                  <span>{isRegister ? '3 dias grátis • Sem compromisso • Cancele quando quiser' : 'Conexão segura e criptografada'}</span>
                </div>
              </form>
            </div>

            {/* Mobile Footer */}
            <div className="lg:hidden text-center mt-6 text-slate-400 text-sm">
              <p>&copy; 2025 UltraGestor. Todos os direitos reservados.</p>
            </div>
          </div>
        </div>
      </div>

      <style>{`
        .animate-in {
          opacity: 0;
          transform: translateY(20px);
        }
        
        .animate-in.show {
          opacity: 1;
          transform: translateY(0);
        }
        
        @keyframes pulse {
          0%, 100% {
            opacity: 0.3;
          }
          50% {
            opacity: 0.5;
          }
        }
        
        .delay-100 { transition-delay: 100ms; }
        .delay-200 { transition-delay: 200ms; }
        .delay-300 { transition-delay: 300ms; }
        .delay-400 { transition-delay: 400ms; }
        .delay-500 { transition-delay: 500ms; }
        .delay-600 { transition-delay: 600ms; }
        .delay-700 { transition-delay: 700ms; }
        .delay-1000 { animation-delay: 1s; }
      `}</style>
    </div>
  )
}
