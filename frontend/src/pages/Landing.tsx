import { useState, useEffect } from 'react'
import { Link } from 'react-router-dom'
import { 
  Sun, Moon, Menu, X, Zap, Users, BarChart3, MessageSquare, 
  Shield, Clock, CheckCircle, ArrowRight, Star, Mail, Phone, MapPin, Loader2 
} from 'lucide-react'
import { publicPlansService, PublicPlan } from '../services/publicPlansService'
import toast from 'react-hot-toast'

export default function Landing() {
  const [isDark, setIsDark] = useState(true)
  const [isMenuOpen, setIsMenuOpen] = useState(false)
  const [isScrolled, setIsScrolled] = useState(false)
  const [plans, setPlans] = useState<PublicPlan[]>([])
  const [loadingPlans, setLoadingPlans] = useState(true)

  useEffect(() => {
    const handleScroll = () => setIsScrolled(window.scrollY > 50)
    window.addEventListener('scroll', handleScroll)
    return () => window.removeEventListener('scroll', handleScroll)
  }, [])

  useEffect(() => {
    document.documentElement.classList.toggle('dark', isDark)
  }, [isDark])

  useEffect(() => {
    loadPlans()
  }, [])

  const loadPlans = async () => {
    try {
      setLoadingPlans(true)
      const response = await publicPlansService.getPlans()
      if (response.success) {
        setPlans(response.plans)
      }
    } catch (error: any) {
      toast.error('Erro ao carregar planos')
    } finally {
      setLoadingPlans(false)
    }
  }

  const getPlanFeatures = (plan: PublicPlan) => {
    const baseFeatures = [
      `${plan.duration_days} dias de acesso`,
      'Dashboard completo',
      'Gestão de clientes',
      'Relatórios básicos'
    ]

    if (!plan.is_trial) {
      baseFeatures.push(
        'Automação WhatsApp',
        'Múltiplos servidores',
        'Suporte prioritário',
        'Relatórios avançados'
      )
    }

    return baseFeatures
  }

  const formatPrice = (price: number) => {
    if (price === 0) return '0'
    return price.toFixed(2).replace('.', ',')
  }

  const getPopularPlanId = () => {
    const paidPlans = plans.filter(p => !p.is_trial)
    if (paidPlans.length > 1) {
      return paidPlans[Math.floor(paidPlans.length / 2)].id
    }
    return null
  }

  const features = [
    {
      icon: Users,
      title: 'Gestão de Clientes',
      description: 'Controle completo de clientes com histórico, status e renovações automáticas.',
      items: ['Cadastro rápido', 'Histórico completo', 'Status em tempo real']
    },
    {
      icon: MessageSquare,
      title: 'Automação WhatsApp',
      description: 'Envio automático de mensagens, lembretes e notificações via WhatsApp.',
      items: ['Mensagens automáticas', 'Templates personalizados', 'Agendamento inteligente']
    },
    {
      icon: BarChart3,
      title: 'Relatórios Avançados',
      description: 'Análises detalhadas de faturamento, clientes e performance do negócio.',
      items: ['Dashboard em tempo real', 'Gráficos interativos', 'Exportação de dados']
    },
    {
      icon: Shield,
      title: 'Segurança Total',
      description: 'Proteção de dados com criptografia e backup automático.',
      items: ['Dados criptografados', 'Backup automático', 'Acesso seguro']
    },
    {
      icon: Clock,
      title: 'Gestão de Faturas',
      description: 'Controle completo de pagamentos, PIX automático e integração Asaas.',
      items: ['PIX automático', 'Integração Asaas', 'Controle de inadimplência']
    },
    {
      icon: Zap,
      title: 'Performance',
      description: 'Sistema rápido e responsivo com interface moderna e intuitiva.',
      items: ['Interface moderna', 'Carregamento rápido', 'Mobile-first']
    }
  ]

  return (
    <div className={isDark ? 'dark' : ''}>
      <div className="min-h-screen bg-gradient-to-br from-slate-50 to-slate-100 dark:from-slate-950 dark:to-slate-900 transition-colors duration-300">
        
        {/* Header */}
        <header className={`fixed top-0 left-0 right-0 z-50 transition-all duration-300 ${
          isScrolled 
            ? 'bg-white/80 dark:bg-slate-900/80 backdrop-blur-xl shadow-lg' 
            : 'bg-transparent'
        }`}>
          <nav className="container mx-auto px-4 py-4">
            <div className="flex items-center justify-between">
              {/* Logo */}
              <Link to="/" className="flex items-center gap-3 group">
                <div className="w-10 h-10 bg-gradient-to-br from-blue-600 to-purple-600 rounded-xl flex items-center justify-center transform group-hover:scale-110 transition-transform">
                  <Zap className="w-6 h-6 text-white" />
                </div>
                <span className="text-xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">
                  UltraGestor
                </span>
              </Link>

              {/* Desktop Menu */}
              <div className="hidden md:flex items-center gap-8">
                <a href="#features" className="text-slate-600 dark:text-slate-300 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
                  Recursos
                </a>
                <a href="#plans" className="text-slate-600 dark:text-slate-300 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
                  Planos
                </a>
                <a href="#about" className="text-slate-600 dark:text-slate-300 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
                  Sobre
                </a>
                <a href="#contact" className="text-slate-600 dark:text-slate-300 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
                  Contato
                </a>
                
                {/* Theme Toggle */}
                <button
                  onClick={() => setIsDark(!isDark)}
                  className="p-2 rounded-lg bg-slate-200 dark:bg-slate-800 hover:bg-slate-300 dark:hover:bg-slate-700 transition-colors"
                  aria-label="Toggle theme"
                >
                  {isDark ? <Sun className="w-5 h-5 text-yellow-500" /> : <Moon className="w-5 h-5 text-slate-700" />}
                </button>

                <Link
                  to="/login"
                  className="px-6 py-2.5 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-lg font-semibold hover:shadow-lg hover:scale-105 transition-all"
                >
                  Acessar Sistema
                </Link>
              </div>

              {/* Mobile Menu Button */}
              <div className="flex md:hidden items-center gap-2">
                <button
                  onClick={() => setIsDark(!isDark)}
                  className="p-2 rounded-lg bg-slate-200 dark:bg-slate-800"
                  aria-label="Toggle theme"
                >
                  {isDark ? <Sun className="w-5 h-5 text-yellow-500" /> : <Moon className="w-5 h-5 text-slate-700" />}
                </button>
                <button
                  onClick={() => setIsMenuOpen(!isMenuOpen)}
                  className="p-2 rounded-lg bg-slate-200 dark:bg-slate-800"
                  aria-label="Toggle menu"
                >
                  {isMenuOpen ? <X className="w-6 h-6" /> : <Menu className="w-6 h-6" />}
                </button>
              </div>
            </div>

            {/* Mobile Menu */}
            {isMenuOpen && (
              <div className="md:hidden mt-4 p-4 bg-white dark:bg-slate-800 rounded-xl shadow-xl">
                <div className="flex flex-col gap-4">
                  <a href="#features" onClick={() => setIsMenuOpen(false)} className="text-slate-600 dark:text-slate-300 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
                    Recursos
                  </a>
                  <a href="#plans" onClick={() => setIsMenuOpen(false)} className="text-slate-600 dark:text-slate-300 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
                    Planos
                  </a>
                  <a href="#about" onClick={() => setIsMenuOpen(false)} className="text-slate-600 dark:text-slate-300 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
                    Sobre
                  </a>
                  <a href="#contact" onClick={() => setIsMenuOpen(false)} className="text-slate-600 dark:text-slate-300 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
                    Contato
                  </a>
                  <Link
                    to="/login"
                    className="px-6 py-2.5 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-lg font-semibold text-center"
                  >
                    Acessar Sistema
                  </Link>
                </div>
              </div>
            )}
          </nav>
        </header>

        {/* Hero Section */}
        <section className="pt-24 md:pt-32 pb-12 md:pb-20 px-4">
          <div className="container mx-auto">
            <div className="grid lg:grid-cols-2 gap-8 md:gap-12 items-center">
              {/* Hero Content */}
              <div className="space-y-8 animate-fade-in">
                <div className="inline-flex items-center gap-2 px-4 py-2 bg-blue-100 dark:bg-blue-900/30 rounded-full text-blue-600 dark:text-blue-400 text-sm font-semibold">
                  <Star className="w-4 h-4" />
                  Sistema Profissional de Gestão IPTV
                </div>
                
                <h1 className="text-3xl sm:text-4xl md:text-5xl lg:text-6xl font-bold leading-tight">
                  <span className="text-slate-900 dark:text-white">Gerencie seu</span>
                  <br />
                  <span className="bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">
                    Negócio IPTV
                  </span>
                  <br />
                  <span className="text-slate-900 dark:text-white">com Eficiência</span>
                </h1>

                <p className="text-base md:text-lg lg:text-xl text-slate-600 dark:text-slate-300 leading-relaxed">
                  Sistema completo de gestão para provedores IPTV com automação WhatsApp, 
                  controle de clientes, faturas e interface moderna.
                </p>

                <div className="flex flex-col sm:flex-row gap-3 md:gap-4">
                  <Link
                    to="/login?register=true"
                    className="px-6 md:px-8 py-3 md:py-4 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-xl font-semibold hover:shadow-2xl hover:scale-105 transition-all flex items-center justify-center gap-2 text-sm md:text-base"
                  >
                    Começar Agora
                    <ArrowRight className="w-4 md:w-5 h-4 md:h-5" />
                  </Link>
                  <a
                    href="#plans"
                    className="px-6 md:px-8 py-3 md:py-4 bg-white dark:bg-slate-800 text-slate-900 dark:text-white rounded-xl font-semibold hover:shadow-xl transition-all border-2 border-slate-200 dark:border-slate-700 flex items-center justify-center text-sm md:text-base"
                  >
                    Ver Planos
                  </a>
                </div>

                {/* Stats */}
                <div className="flex gap-4 md:gap-8 pt-6 md:pt-8">
                  <div>
                    <div className="text-2xl md:text-3xl font-bold text-blue-600 dark:text-blue-400">99.9%</div>
                    <div className="text-xs md:text-sm text-slate-600 dark:text-slate-400">Uptime</div>
                  </div>
                  <div>
                    <div className="text-2xl md:text-3xl font-bold text-purple-600 dark:text-purple-400">24/7</div>
                    <div className="text-xs md:text-sm text-slate-600 dark:text-slate-400">Suporte</div>
                  </div>
                  <div>
                    <div className="text-2xl md:text-3xl font-bold text-green-600 dark:text-green-400">1000+</div>
                    <div className="text-xs md:text-sm text-slate-600 dark:text-slate-400">Clientes</div>
                  </div>
                </div>
              </div>

              {/* Hero Visual */}
              <div className="relative">
                <div className="relative bg-gradient-to-br from-blue-600 to-purple-600 rounded-3xl p-1 shadow-2xl transform hover:scale-105 transition-transform duration-500">
                  <div className="bg-white dark:bg-slate-900 rounded-3xl overflow-hidden">
                    {/* Dashboard Preview */}
                    <div className="bg-slate-100 dark:bg-slate-800 p-4 border-b border-slate-200 dark:border-slate-700">
                      <div className="flex items-center gap-2">
                        <div className="w-3 h-3 rounded-full bg-red-500"></div>
                        <div className="w-3 h-3 rounded-full bg-yellow-500"></div>
                        <div className="w-3 h-3 rounded-full bg-green-500"></div>
                        <div className="ml-4 text-sm text-slate-600 dark:text-slate-400 font-medium">UltraGestor Dashboard</div>
                      </div>
                    </div>
                    
                    <div className="p-6 space-y-4">
                      {/* Cards */}
                      <div className="grid grid-cols-3 gap-4">
                        {[
                          { color: 'blue', value: '1,234' },
                          { color: 'green', value: 'R$ 45K' },
                          { color: 'purple', value: '98%' }
                        ].map((card, i) => (
                          <div key={i} className="bg-slate-50 dark:bg-slate-800 rounded-xl p-4 border-l-4 border-blue-600">
                            <div className="text-2xl font-bold text-slate-900 dark:text-white">{card.value}</div>
                            <div className="text-xs text-slate-600 dark:text-slate-400 mt-1">Métrica</div>
                          </div>
                        ))}
                      </div>
                      
                      {/* Chart */}
                      <div className="bg-slate-50 dark:bg-slate-800 rounded-xl p-4 h-40 flex items-end gap-2">
                        {[40, 70, 45, 80, 60, 90, 75].map((height, i) => (
                          <div key={i} className="flex-1 bg-gradient-to-t from-blue-600 to-purple-600 rounded-t" style={{ height: `${height}%` }}></div>
                        ))}
                      </div>
                    </div>
                  </div>
                </div>
                
                {/* Floating Elements */}
                <div className="absolute -top-4 -right-4 w-24 h-24 bg-blue-500 rounded-full blur-3xl opacity-50 animate-pulse"></div>
                <div className="absolute -bottom-4 -left-4 w-32 h-32 bg-purple-500 rounded-full blur-3xl opacity-50 animate-pulse"></div>
              </div>
            </div>
          </div>
        </section>

        {/* Features Section */}
        <section id="features" className="py-12 md:py-20 px-4 bg-white dark:bg-slate-900">
          <div className="container mx-auto">
            <div className="text-center mb-16">
              <div className="inline-flex items-center gap-2 px-4 py-2 bg-blue-100 dark:bg-blue-900/30 rounded-full text-blue-600 dark:text-blue-400 text-sm font-semibold mb-4">
                <Zap className="w-4 h-4" />
                Recursos Poderosos
              </div>
              <h2 className="text-3xl md:text-4xl lg:text-5xl font-bold text-slate-900 dark:text-white mb-4">
                Tudo que você precisa em um só lugar
              </h2>
              <p className="text-base md:text-lg lg:text-xl text-slate-600 dark:text-slate-300 max-w-2xl mx-auto">
                Ferramentas profissionais para gerenciar seu negócio IPTV com eficiência e praticidade
              </p>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 md:gap-8">
              {features.map((feature, index) => (
                <div
                  key={index}
                  className="group bg-slate-50 dark:bg-slate-800 rounded-2xl p-6 md:p-8 hover:shadow-2xl md:hover:-translate-y-2 transition-all duration-300 border border-slate-200 dark:border-slate-700"
                >
                  <div className="w-14 h-14 bg-gradient-to-br from-blue-600 to-purple-600 rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                    <feature.icon className="w-7 h-7 text-white" />
                  </div>
                  
                  <h3 className="text-xl font-bold text-slate-900 dark:text-white mb-3">
                    {feature.title}
                  </h3>
                  
                  <p className="text-slate-600 dark:text-slate-300 mb-4 leading-relaxed">
                    {feature.description}
                  </p>
                  
                  <ul className="space-y-2">
                    {feature.items.map((item, i) => (
                      <li key={i} className="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-400">
                        <CheckCircle className="w-4 h-4 text-green-500 flex-shrink-0" />
                        {item}
                      </li>
                    ))}
                  </ul>
                </div>
              ))}
            </div>
          </div>
        </section>

        {/* About Section */}
        <section id="about" className="py-12 md:py-20 px-4 bg-slate-50 dark:bg-slate-950">
          <div className="container mx-auto">
            <div className="grid lg:grid-cols-2 gap-12 items-center">
              <div>
                <div className="inline-flex items-center gap-2 px-4 py-2 bg-purple-100 dark:bg-purple-900/30 rounded-full text-purple-600 dark:text-purple-400 text-sm font-semibold mb-6">
                  <Shield className="w-4 h-4" />
                  Sobre o Sistema
                </div>
                
                <h2 className="text-3xl md:text-4xl lg:text-5xl font-bold text-slate-900 dark:text-white mb-6">
                  Solução completa para seu negócio IPTV
                </h2>
                
                <p className="text-base md:text-lg text-slate-600 dark:text-slate-300 mb-8 leading-relaxed">
                  O UltraGestor foi desenvolvido especialmente para provedores IPTV que buscam 
                  profissionalizar sua gestão com automação, controle e eficiência.
                </p>

                <div className="space-y-6">
                  {[
                    { icon: Zap, title: 'Rápido e Eficiente', desc: 'Interface moderna e responsiva' },
                    { icon: Shield, title: 'Seguro e Confiável', desc: 'Dados protegidos e backup automático' },
                    { icon: Clock, title: 'Economia de Tempo', desc: 'Automação de tarefas repetitivas' }
                  ].map((item, i) => (
                    <div key={i} className="flex gap-4">
                      <div className="w-12 h-12 bg-gradient-to-br from-blue-600 to-purple-600 rounded-lg flex items-center justify-center flex-shrink-0">
                        <item.icon className="w-6 h-6 text-white" />
                      </div>
                      <div>
                        <h4 className="text-lg font-semibold text-slate-900 dark:text-white mb-1">
                          {item.title}
                        </h4>
                        <p className="text-slate-600 dark:text-slate-400">
                          {item.desc}
                        </p>
                      </div>
                    </div>
                  ))}
                </div>

                <div className="flex flex-col sm:flex-row gap-3 md:gap-4 mt-8">
                  <Link
                    to="/login?register=true"
                    className="px-6 md:px-8 py-3 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-xl font-semibold hover:shadow-xl transition-all text-center text-sm md:text-base"
                  >
                    Começar Agora
                  </Link>
                  <a
                    href="#contact"
                    className="px-6 md:px-8 py-3 bg-white dark:bg-slate-800 text-slate-900 dark:text-white rounded-xl font-semibold hover:shadow-xl transition-all border-2 border-slate-200 dark:border-slate-700 text-center text-sm md:text-base"
                  >
                    Falar com Suporte
                  </a>
                </div>
              </div>

              <div className="relative">
                <div className="bg-gradient-to-br from-blue-600 to-purple-600 rounded-3xl p-1 shadow-2xl">
                  <div className="bg-white dark:bg-slate-900 rounded-3xl p-8 h-96 flex items-center justify-center">
                    <BarChart3 className="w-48 h-48 text-slate-300 dark:text-slate-700" />
                  </div>
                </div>
              </div>
            </div>
          </div>
        </section>

        {/* Plans Section */}
        <section id="plans" className="py-12 md:py-20 px-4 bg-white dark:bg-slate-900">
          <div className="container mx-auto">
            <div className="text-center mb-16">
              <div className="inline-flex items-center gap-2 px-4 py-2 bg-purple-100 dark:bg-purple-900/30 rounded-full text-purple-600 dark:text-purple-400 text-sm font-semibold mb-4">
                <Star className="w-4 h-4" />
                Planos e Preços
              </div>
              <h2 className="text-3xl md:text-4xl lg:text-5xl font-bold text-slate-900 dark:text-white mb-4">
                Escolha o plano ideal para você
              </h2>
              <p className="text-base md:text-lg lg:text-xl text-slate-600 dark:text-slate-300 max-w-2xl mx-auto">
                Planos flexíveis para todos os tamanhos de negócio
              </p>
            </div>

            {loadingPlans ? (
              <div className="flex flex-col items-center justify-center py-12">
                <Loader2 className="w-12 h-12 text-blue-600 animate-spin mb-4" />
                <p className="text-slate-600 dark:text-slate-400">Carregando planos...</p>
              </div>
            ) : plans.length === 0 ? (
              <div className="text-center py-12">
                <p className="text-slate-600 dark:text-slate-400">Nenhum plano disponível no momento.</p>
              </div>
            ) : (
              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 md:gap-8 max-w-6xl mx-auto">
                {plans.map((plan) => {
                  const isPopular = plan.id === getPopularPlanId()
                  const features = getPlanFeatures(plan)
                  
                  return (
                    <div
                      key={plan.id}
                      className={`relative bg-slate-50 dark:bg-slate-800 rounded-2xl p-6 md:p-8 border-2 transition-all duration-300 ${
                        isPopular
                          ? 'border-blue-600 shadow-2xl md:scale-105'
                          : 'border-slate-200 dark:border-slate-700 hover:shadow-xl md:hover:-translate-y-2'
                      }`}
                    >
                      {isPopular && (
                        <div className="absolute -top-3 md:-top-4 left-1/2 -translate-x-1/2 px-3 md:px-4 py-1 bg-gradient-to-r from-blue-600 to-purple-600 text-white text-xs md:text-sm font-bold rounded-full whitespace-nowrap">
                          ⭐ Mais Popular
                        </div>
                      )}
                      
                      <div className="text-center mb-6">
                        <div className={`inline-block px-3 py-1 rounded-full text-xs font-semibold mb-4 ${
                          plan.is_trial
                            ? 'bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400'
                            : 'bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400'
                        }`}>
                          {plan.is_trial ? 'Gratuito' : 'Premium'}
                        </div>
                        
                        <h3 className="text-xl md:text-2xl font-bold text-slate-900 dark:text-white mb-2">
                          {plan.name}
                        </h3>
                        
                        <div className="flex items-baseline justify-center gap-1 mb-2">
                          <span className="text-base md:text-lg text-slate-600 dark:text-slate-400">R$</span>
                          <span className={`text-4xl md:text-5xl font-bold ${
                            plan.price === 0
                              ? 'text-green-600 dark:text-green-400'
                              : 'text-slate-900 dark:text-white'
                          }`}>
                            {formatPrice(plan.price)}
                          </span>
                        </div>
                        
                        <p className="text-sm text-slate-600 dark:text-slate-400">
                          {plan.description}
                        </p>
                      </div>

                      <ul className="space-y-3 mb-8">
                        {features.map((feature, i) => (
                          <li key={i} className="flex items-center gap-3 text-slate-700 dark:text-slate-300">
                            <CheckCircle className="w-5 h-5 text-green-500 flex-shrink-0" />
                            <span className="text-sm">{feature}</span>
                          </li>
                        ))}
                      </ul>

                      <Link
                        to="/login?register=true"
                        className={`w-full py-3 rounded-xl font-semibold transition-all flex items-center justify-center gap-2 ${
                          isPopular
                            ? 'bg-gradient-to-r from-blue-600 to-purple-600 text-white hover:shadow-xl hover:scale-105'
                            : 'bg-slate-200 dark:bg-slate-700 text-slate-900 dark:text-white hover:bg-slate-300 dark:hover:bg-slate-600'
                        }`}
                      >
                        <Zap className="w-5 h-5" />
                        {plan.is_trial ? 'Começar Grátis' : 'Assinar Agora'}
                      </Link>
                    </div>
                  )
                })}
              </div>
            )}
          </div>
        </section>

        {/* Contact Section */}
        <section id="contact" className="py-12 md:py-20 px-4 bg-white dark:bg-slate-900">
          <div className="container mx-auto max-w-4xl">
            <div className="text-center mb-16">
              <div className="inline-flex items-center gap-2 px-4 py-2 bg-green-100 dark:bg-green-900/30 rounded-full text-green-600 dark:text-green-400 text-sm font-semibold mb-4">
                <Mail className="w-4 h-4" />
                Entre em Contato
              </div>
              <h2 className="text-3xl md:text-4xl lg:text-5xl font-bold text-slate-900 dark:text-white mb-4">
                Pronto para começar?
              </h2>
              <p className="text-base md:text-lg lg:text-xl text-slate-600 dark:text-slate-300">
                Entre em contato conosco e descubra como podemos ajudar seu negócio
              </p>
            </div>

            <div className="grid md:grid-cols-3 gap-8 mb-12">
              {[
                { icon: Mail, title: 'Email', value: 'contato@ultragestor.com', link: 'mailto:contato@ultragestor.com' },
                { icon: Phone, title: 'Telefone', value: '(11) 99999-9999', link: 'tel:+5511999999999' },
                { icon: MapPin, title: 'Localização', value: 'São Paulo, Brasil', link: '#' }
              ].map((contact, i) => (
                <div key={i} className="text-center">
                  <div className="w-14 h-14 bg-gradient-to-br from-blue-600 to-purple-600 rounded-xl flex items-center justify-center mx-auto mb-4">
                    <contact.icon className="w-7 h-7 text-white" />
                  </div>
                  <h4 className="text-lg font-semibold text-slate-900 dark:text-white mb-2">
                    {contact.title}
                  </h4>
                  <a
                    href={contact.link}
                    className="text-blue-600 dark:text-blue-400 hover:underline"
                  >
                    {contact.value}
                  </a>
                </div>
              ))}
            </div>

            <div className="bg-gradient-to-br from-blue-600 to-purple-600 rounded-2xl md:rounded-3xl p-8 md:p-12 text-center text-white">
              <h3 className="text-2xl md:text-3xl font-bold mb-4">
                Comece a usar o UltraGestor hoje!
              </h3>
              <p className="text-base md:text-xl mb-6 md:mb-8 opacity-90">
                Transforme a gestão do seu negócio IPTV com nossa plataforma completa
              </p>
              <div className="flex flex-col sm:flex-row gap-3 md:gap-4 justify-center">
                <Link
                  to="/login?register=true"
                  className="px-6 md:px-8 py-3 md:py-4 bg-white text-blue-600 rounded-xl font-semibold hover:shadow-2xl hover:scale-105 transition-all text-sm md:text-base"
                >
                  Acessar Sistema
                </Link>
                <a
                  href="https://wa.me/5511999999999"
                  target="_blank"
                  rel="noopener noreferrer"
                  className="px-6 md:px-8 py-3 md:py-4 bg-green-500 text-white rounded-xl font-semibold hover:shadow-2xl hover:scale-105 transition-all flex items-center justify-center gap-2 text-sm md:text-base"
                >
                  <MessageSquare className="w-5 h-5" />
                  WhatsApp
                </a>
              </div>
            </div>
          </div>
        </section>

        {/* Footer */}
        <footer className="bg-slate-50 dark:bg-slate-950 border-t border-slate-200 dark:border-slate-800 py-12 px-4">
          <div className="container mx-auto">
            <div className="grid md:grid-cols-4 gap-8 mb-8">
              {/* Brand */}
              <div className="md:col-span-2">
                <div className="flex items-center gap-3 mb-4">
                  <div className="w-10 h-10 bg-gradient-to-br from-blue-600 to-purple-600 rounded-xl flex items-center justify-center">
                    <Zap className="w-6 h-6 text-white" />
                  </div>
                  <span className="text-xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">
                    UltraGestor
                  </span>
                </div>
                <p className="text-slate-600 dark:text-slate-400 mb-4">
                  Sistema profissional de gestão IPTV com automação WhatsApp, 
                  controle de clientes e interface moderna.
                </p>
              </div>

              {/* Links */}
              <div>
                <h4 className="font-semibold text-slate-900 dark:text-white mb-4">Produto</h4>
                <ul className="space-y-2">
                  <li><a href="#features" className="text-slate-600 dark:text-slate-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">Recursos</a></li>
                  <li><a href="#plans" className="text-slate-600 dark:text-slate-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">Planos</a></li>
                  <li><a href="#about" className="text-slate-600 dark:text-slate-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">Sobre</a></li>
                  <li><Link to="/login" className="text-slate-600 dark:text-slate-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">Login</Link></li>
                </ul>
              </div>

              {/* Contact */}
              <div>
                <h4 className="font-semibold text-slate-900 dark:text-white mb-4">Contato</h4>
                <ul className="space-y-2">
                  <li><a href="mailto:contato@ultragestor.com" className="text-slate-600 dark:text-slate-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">Email</a></li>
                  <li><a href="tel:+5511999999999" className="text-slate-600 dark:text-slate-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">Telefone</a></li>
                  <li><a href="https://wa.me/5511999999999" target="_blank" rel="noopener noreferrer" className="text-slate-600 dark:text-slate-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">WhatsApp</a></li>
                </ul>
              </div>
            </div>

            <div className="border-t border-slate-200 dark:border-slate-800 pt-8 flex flex-col md:flex-row justify-between items-center gap-4">
              <p className="text-slate-600 dark:text-slate-400 text-sm">
                © 2024 UltraGestor. Todos os direitos reservados.
              </p>
              <div className="flex gap-4">
                <a href="#" className="w-10 h-10 bg-slate-200 dark:bg-slate-800 rounded-lg flex items-center justify-center hover:bg-blue-600 hover:text-white transition-colors">
                  <svg className="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                </a>
                <a href="#" className="w-10 h-10 bg-slate-200 dark:bg-slate-800 rounded-lg flex items-center justify-center hover:bg-blue-600 hover:text-white transition-colors">
                  <svg className="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/></svg>
                </a>
                <a href="#" className="w-10 h-10 bg-slate-200 dark:bg-slate-800 rounded-lg flex items-center justify-center hover:bg-blue-600 hover:text-white transition-colors">
                  <svg className="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.894 8.221l-1.97 9.28c-.145.658-.537.818-1.084.508l-3-2.21-1.446 1.394c-.14.18-.357.295-.6.295-.002 0-.003 0-.005 0l.213-3.054 5.56-5.022c.24-.213-.054-.334-.373-.121l-6.869 4.326-2.96-.924c-.64-.203-.658-.64.135-.954l11.566-4.458c.538-.196 1.006.128.832.941z"/></svg>
                </a>
              </div>
            </div>
          </div>
        </footer>
      </div>
    </div>
  )
}
