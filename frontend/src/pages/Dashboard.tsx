import { useEffect, useState } from 'react'
import { Users, DollarSign, AlertCircle, TrendingUp, Activity } from 'lucide-react'
import { useClientStore } from '@/stores/useClientStore'
import { invoiceService } from '@/services/invoiceService'
import { Invoice } from '@/types'
import { AreaChart, Area, BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer, PieChart, Pie, Cell } from 'recharts'

export default function Dashboard() {
  const { clients, fetchClients } = useClientStore()
  const [invoices, setInvoices] = useState<Invoice[]>([])
  const [stats, setStats] = useState({
    totalClients: 0,
    activeClients: 0,
    inactiveClients: 0,
    totalRevenue: 0,
    monthlyAverage: 0,
    todayRevenue: 0,
    expiringToday: 0,
  })

  // Estados para filtros de período
  const [clientsPeriod, setClientsPeriod] = useState('this-month')
  const [paymentsPeriod, setPaymentsPeriod] = useState('this-month')

  // Inicializar com o mês/ano atual do sistema
  const currentDate = new Date()
  const [monthYearFilter, setMonthYearFilter] = useState({
    year: currentDate.getFullYear(),
    month: currentDate.getMonth()
  })
  const [yearFilter, setYearFilter] = useState(currentDate.getFullYear())

  useEffect(() => {
    // Carregar dados principais IMEDIATAMENTE
    fetchClients()
    loadInvoices()
  }, [fetchClients])

  const loadInvoices = async () => {
    try {
      const data = await invoiceService.getAll()
      setInvoices(data.invoices || [])
    } catch (error) {
      // Erro ao carregar faturas
      setInvoices([])
    }
  }

  useEffect(() => {
    if (clients.length > 0) {
      const active = clients.filter(c => c.status === 'active').length
      
      // Calcular inadimplentes: clientes com data de vencimento passada (independente do status)
      const today = new Date()
      today.setHours(0, 0, 0, 0)
      
      const expiredClients = clients.filter(c => {
        const renewalDate = new Date(c.renewal_date)
        renewalDate.setHours(0, 0, 0, 0)
        return renewalDate < today && c.status !== 'inactive'
      })
      
      const inactive = expiredClients.length
      const revenue = clients.filter(c => c.status === 'active').reduce((sum, c) => sum + c.value, 0)
      const monthlyAvg = revenue / (active || 1)

      // Calcular valor total dos clientes vencidos (receita perdida)
      const inactiveRevenue = expiredClients.reduce((sum, c) => sum + c.value, 0)

      // Calcular clientes que vencem hoje
      const todayStr = today.toISOString().split('T')[0]
      const expiringToday = clients.filter(c => c.renewal_date === todayStr).length

      setStats({
        totalClients: clients.length,
        activeClients: active,
        inactiveClients: inactive,
        totalRevenue: revenue,
        monthlyAverage: monthlyAvg,
        todayRevenue: inactiveRevenue, // Receita perdida dos vencidos
        expiringToday: expiringToday,
      })
    }
  }, [clients])

  // Dados REAIS para gráfico de Saldo Líquido do Mês (baseado em PAGAMENTOS)
  const liquidBalanceMonthData = (() => {
    const { year: currentYear, month: currentMonth } = monthYearFilter
    const daysInMonth = new Date(currentYear, currentMonth + 1, 0).getDate()
    const data: { day: string; value: number }[] = []

    // Inicializar todos os dias do mês
    for (let day = 1; day <= daysInMonth; day++) {
      data.push({
        day: day.toString().padStart(2, '0'),
        value: 0
      })
    }

    // Calcular receita por dia de PAGAMENTO (quando deu baixa)
    invoices.forEach(invoice => {
      if (invoice.payment_date && invoice.status === 'paid') {
        const paymentDate = new Date(invoice.payment_date)

        if (paymentDate.getFullYear() === currentYear && paymentDate.getMonth() === currentMonth) {
          const day = paymentDate.getDate()
          if (day >= 1 && day <= daysInMonth) {
            data[day - 1].value += parseFloat(invoice.final_value as any) || 0
          }
        }
      }
    })

    return data
  })()

  // Calcular totais (garantir que são números)
  const totalMonthRevenue = liquidBalanceMonthData.reduce((sum, d) => sum + (parseFloat(d.value as any) || 0), 0)

  // Dados REAIS para gráfico de Saldo Líquido do Ano (baseado em PAGAMENTOS)
  const liquidBalanceYearData = (() => {
    const months = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez']
    const data: { month: string; value: number }[] = []

    // Inicializar todos os meses
    for (let month = 0; month < 12; month++) {
      data.push({
        month: months[month],
        value: 0
      })
    }

    // Calcular receita por mês de PAGAMENTO
    invoices.forEach(invoice => {
      if (invoice.payment_date && invoice.status === 'paid') {
        const paymentDate = new Date(invoice.payment_date)
        if (paymentDate.getFullYear() === yearFilter) {
          const month = paymentDate.getMonth()
          data[month].value += parseFloat(invoice.final_value as any) || 0
        }
      }
    })

    return data
  })()

  // Função para calcular datas baseado no período
  const calculatePeriodDates = (period: string) => {
    const now = new Date()
    let startDate: Date, endDate: Date

    switch (period) {
      case 'today':
        startDate = new Date(now.getFullYear(), now.getMonth(), now.getDate())
        endDate = new Date(now.getFullYear(), now.getMonth(), now.getDate(), 23, 59, 59)
        break
      case 'yesterday':
        startDate = new Date(now.getFullYear(), now.getMonth(), now.getDate() - 1)
        endDate = new Date(now.getFullYear(), now.getMonth(), now.getDate() - 1, 23, 59, 59)
        break
      case 'this-week':
        const dayOfWeek = now.getDay()
        const diff = dayOfWeek === 0 ? 6 : dayOfWeek - 1
        startDate = new Date(now.getFullYear(), now.getMonth(), now.getDate() - diff)
        endDate = new Date(now.getFullYear(), now.getMonth(), now.getDate(), 23, 59, 59)
        break
      case 'last-week':
        const lastWeekStart = new Date(now)
        lastWeekStart.setDate(now.getDate() - now.getDay() - 6)
        const lastWeekEnd = new Date(lastWeekStart)
        lastWeekEnd.setDate(lastWeekStart.getDate() + 6)
        startDate = lastWeekStart
        endDate = lastWeekEnd
        break
      case 'this-month':
        startDate = new Date(now.getFullYear(), now.getMonth(), 1)
        endDate = new Date(now.getFullYear(), now.getMonth() + 1, 0, 23, 59, 59)
        break
      case 'last-month':
        startDate = new Date(now.getFullYear(), now.getMonth() - 1, 1)
        endDate = new Date(now.getFullYear(), now.getMonth(), 0, 23, 59, 59)
        break
      case '7':
      case '15':
      case '30':
      case '60':
      case '90':
      case '180':
        const days = parseInt(period)
        startDate = new Date(now.getFullYear(), now.getMonth(), now.getDate() - days + 1)
        endDate = new Date(now.getFullYear(), now.getMonth(), now.getDate(), 23, 59, 59)
        break
      case 'this-quarter':
        const currentQuarter = Math.floor(now.getMonth() / 3)
        startDate = new Date(now.getFullYear(), currentQuarter * 3, 1)
        endDate = new Date(now.getFullYear(), (currentQuarter + 1) * 3, 0, 23, 59, 59)
        break
      case 'last-quarter':
        const lastQuarter = Math.floor(now.getMonth() / 3) - 1
        const quarterYear = lastQuarter < 0 ? now.getFullYear() - 1 : now.getFullYear()
        const quarterMonth = lastQuarter < 0 ? 9 : lastQuarter * 3
        startDate = new Date(quarterYear, quarterMonth, 1)
        endDate = new Date(quarterYear, quarterMonth + 3, 0, 23, 59, 59)
        break
      case 'this-year':
        startDate = new Date(now.getFullYear(), 0, 1)
        endDate = new Date(now.getFullYear(), 11, 31, 23, 59, 59)
        break
      case 'last-year':
        startDate = new Date(now.getFullYear() - 1, 0, 1)
        endDate = new Date(now.getFullYear() - 1, 11, 31, 23, 59, 59)
        break
      default:
        startDate = new Date(now.getFullYear(), now.getMonth(), 1)
        endDate = new Date(now.getFullYear(), now.getMonth() + 1, 0, 23, 59, 59)
    }

    return { startDate, endDate }
  }

  // Dados REAIS para gráfico de Clientes Novos por dia
  const newClientsData = (() => {
    const { startDate, endDate } = calculatePeriodDates(clientsPeriod)
    const diffTime = Math.abs(endDate.getTime() - startDate.getTime())
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1

    // Inicializar array com zeros
    const data: { day: string; value: number }[] = []
    for (let i = 0; i < diffDays; i++) {
      const currentDate = new Date(startDate)
      currentDate.setDate(startDate.getDate() + i)
      data.push({
        day: currentDate.getDate().toString().padStart(2, '0'),
        value: 0
      })
    }

    // Contar clientes por dia de criação
    clients.forEach(client => {
      if (client.created_at) {
        const createdDate = new Date(client.created_at)
        if (createdDate >= startDate && createdDate <= endDate) {
          const daysDiff = Math.floor((createdDate.getTime() - startDate.getTime()) / (1000 * 60 * 60 * 24))
          if (daysDiff >= 0 && daysDiff < diffDays) {
            data[daysDiff].value++
          }
        }
      }
    })

    return data
  })()

  // Dados REAIS para gráfico de Pagamentos por dia (baseado em FATURAS PAGAS)
  const paymentsData = (() => {
    const { startDate, endDate } = calculatePeriodDates(paymentsPeriod)
    const diffTime = Math.abs(endDate.getTime() - startDate.getTime())
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1

    // Inicializar array com zeros
    const data: { day: string; value: number }[] = []
    for (let i = 0; i < diffDays; i++) {
      const currentDate = new Date(startDate)
      currentDate.setDate(startDate.getDate() + i)
      data.push({
        day: currentDate.getDate().toString().padStart(2, '0'),
        value: 0
      })
    }

    // Calcular receita por dia de PAGAMENTO (quando deu baixa na fatura)
    let totalDetected = 0
    let faturasProcessadas = 0

    invoices.forEach(invoice => {
      if (invoice.payment_date && invoice.status === 'paid') {
        const paymentDate = new Date(invoice.payment_date)

        if (paymentDate >= startDate && paymentDate <= endDate) {
          const daysDiff = Math.floor((paymentDate.getTime() - startDate.getTime()) / (1000 * 60 * 60 * 24))
          if (daysDiff >= 0 && daysDiff < diffDays) {
            const value = parseFloat(invoice.final_value as any) || 0
            data[daysDiff].value += value
            totalDetected += value
            faturasProcessadas++
          }
        }
      }
    })

    return data
  })()

  // Calcular total anual (garantir que são números)
  const totalYearRevenue = liquidBalanceYearData.reduce((sum, d) => sum + (parseFloat(d.value as any) || 0), 0)

  // Dados para gráfico de pizza (Status dos clientes)
  const statusData = [
    { name: 'Ativos', value: stats.activeClients, color: '#10b981' },
    { name: 'Inativos', value: stats.inactiveClients, color: '#ef4444' },
  ].filter(item => item.value > 0)

  const COLORS = ['#10b981', '#ef4444']

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
          <h1 className="text-2xl md:text-3xl font-bold bg-gradient-to-r from-primary-600 to-purple-600 bg-clip-text text-transparent">
            Estatísticas
          </h1>
          <p className="text-sm md:text-base text-gray-600 dark:text-gray-400 mt-1">
            Visão geral do seu negócio
          </p>
        </div>
      </div>

      {/* Cards de Estatísticas - Estilo Compacto */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        {/* Card 1 - Receita Mensal */}
        <div className="bg-white/80 dark:bg-gray-800/50 backdrop-blur-sm border border-gray-200 dark:border-gray-700/50 rounded-xl p-4 hover:border-gray-300 dark:hover:border-gray-600 transition-all shadow-sm hover:shadow-md">
          <div className="flex items-center justify-between">
            <div className="flex-1">
              <p className="text-gray-600 dark:text-gray-400 text-xs mb-1">Receita Mensal</p>
              <p className="text-gray-900 dark:text-white text-2xl font-bold">R$ {stats.totalRevenue.toFixed(2)}</p>
              <p className="text-green-600 dark:text-green-400 text-xs mt-1">+3% vs mês anterior</p>
            </div>
            <div className="w-12 h-12 rounded-full bg-green-100 dark:bg-green-500/20 flex items-center justify-center">
              <DollarSign className="w-6 h-6 text-green-600 dark:text-green-400" />
            </div>
          </div>
        </div>

        {/* Card 2 - Clientes Ativos */}
        <div className="bg-white/80 dark:bg-gray-800/50 backdrop-blur-sm border border-gray-200 dark:border-gray-700/50 rounded-xl p-4 hover:border-gray-300 dark:hover:border-gray-600 transition-all shadow-sm hover:shadow-md">
          <div className="flex items-center justify-between">
            <div className="flex-1">
              <p className="text-gray-600 dark:text-gray-400 text-xs mb-1">Clientes Ativos</p>
              <p className="text-gray-900 dark:text-white text-2xl font-bold">{stats.activeClients}</p>
              <p className="text-cyan-600 dark:text-cyan-400 text-xs mt-1">+8% novos clientes</p>
            </div>
            <div className="w-12 h-12 rounded-full bg-cyan-100 dark:bg-cyan-500/20 flex items-center justify-center">
              <Users className="w-6 h-6 text-cyan-600 dark:text-cyan-400" />
            </div>
          </div>
        </div>

        {/* Card 3 - Inadimplência */}
        <div className="bg-white/80 dark:bg-gray-800/50 backdrop-blur-sm border border-gray-200 dark:border-gray-700/50 rounded-xl p-4 hover:border-gray-300 dark:hover:border-gray-600 transition-all shadow-sm hover:shadow-md">
          <div className="flex items-center justify-between">
            <div className="flex-1">
              <p className="text-gray-600 dark:text-gray-400 text-xs mb-1">Inadimplência</p>
              <div className="flex items-baseline gap-2">
                <p className="text-gray-900 dark:text-white text-2xl font-bold">{stats.inactiveClients}</p>
                <p className="text-gray-500 dark:text-gray-400 text-sm">clientes</p>
              </div>
              <p className="text-red-600 dark:text-red-400 text-xs mt-1 font-medium">R$ {stats.todayRevenue.toFixed(2)} perdidos/mês</p>
            </div>
            <div className="w-12 h-12 rounded-full bg-red-100 dark:bg-red-500/20 flex items-center justify-center">
              <AlertCircle className="w-6 h-6 text-red-600 dark:text-red-400" />
            </div>
          </div>
        </div>

        {/* Card 4 - Lucro Líquido */}
        <div className="bg-white/80 dark:bg-gray-800/50 backdrop-blur-sm border border-gray-200 dark:border-gray-700/50 rounded-xl p-4 hover:border-gray-300 dark:hover:border-gray-600 transition-all shadow-sm hover:shadow-md">
          <div className="flex items-center justify-between">
            <div className="flex-1">
              <p className="text-gray-600 dark:text-gray-400 text-xs mb-1">Lucro Líquido</p>
              <p className="text-gray-900 dark:text-white text-2xl font-bold">R$ {(stats.totalRevenue * 0.7).toFixed(2)}</p>
              <p className="text-green-600 dark:text-green-400 text-xs mt-1">+5% vs mês anterior</p>
            </div>
            <div className="w-12 h-12 rounded-full bg-green-100 dark:bg-green-500/20 flex items-center justify-center">
              <TrendingUp className="w-6 h-6 text-green-600 dark:text-green-400" />
            </div>
          </div>
        </div>
      </div>

      {/* Gráficos de Saldo Líquido - Estilo Profissional */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* Saldo Líquido do Mês - Gráfico de Barras Verticais */}
        <div className="bg-white/80 dark:bg-gray-800/50 backdrop-blur-sm rounded-xl p-6 border border-gray-200 dark:border-gray-700/50 shadow-sm">
          <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6">
            <div>
              <h3 className="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <div className="p-2 bg-green-100 dark:bg-green-500/20 rounded-lg">
                  <TrendingUp className="w-5 h-5 text-green-600 dark:text-green-400" />
                </div>
                Saldo Líquido do Mês
              </h3>
              <div className="flex items-baseline gap-2 mt-2">
                <p className="text-3xl font-bold text-green-600 dark:text-green-400">
                  R$ {totalMonthRevenue.toFixed(2)}
                </p>
                <span className="text-sm text-gray-500 dark:text-gray-400">total</span>
              </div>
            </div>
            <select
              value={`${monthYearFilter.year}-${monthYearFilter.month}`}
              onChange={(e) => {
                const [year, month] = e.target.value.split('-').map(Number)
                setMonthYearFilter({ year, month })
              }}
              className="px-4 py-2 bg-gray-50 dark:bg-gray-900 rounded-lg text-sm border border-gray-200 dark:border-gray-700 focus:ring-2 focus:ring-primary-500 text-gray-900 dark:text-white font-medium"
            >
              {['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'].map((month, index) => (
                <option key={`${new Date().getFullYear()}-${index}`} value={`${new Date().getFullYear()}-${index}`}>
                  {month} {new Date().getFullYear()}
                </option>
              ))}
              {['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'].map((month, index) => (
                <option key={`${new Date().getFullYear() + 1}-${index}`} value={`${new Date().getFullYear() + 1}-${index}`}>
                  {month} {new Date().getFullYear() + 1}
                </option>
              ))}
            </select>
          </div>
          <ResponsiveContainer width="100%" height={250}>
            <BarChart data={liquidBalanceMonthData}>
              <defs>
                <linearGradient id="barGradient" x1="0" y1="0" x2="0" y2="1">
                  <stop offset="0%" stopColor="#10b981" stopOpacity={1} />
                  <stop offset="100%" stopColor="#059669" stopOpacity={1} />
                </linearGradient>
              </defs>
              <CartesianGrid strokeDasharray="3 3" stroke="#e5e7eb" className="dark:stroke-gray-700" opacity={0.5} />
              <XAxis 
                dataKey="day" 
                stroke="#9ca3af" 
                style={{ fontSize: '12px', fontWeight: 500 }} 
                tick={{ fill: '#6b7280' }}
              />
              <YAxis 
                stroke="#9ca3af" 
                style={{ fontSize: '12px', fontWeight: 500 }} 
                tick={{ fill: '#6b7280' }}
                tickFormatter={(value) => `R$ ${value}`}
              />
              <Tooltip
                contentStyle={{
                  backgroundColor: '#ffffff',
                  border: '2px solid #10b981',
                  borderRadius: '12px',
                  boxShadow: '0 10px 15px -3px rgb(0 0 0 / 0.1)',
                  padding: '12px'
                }}
                labelStyle={{ color: '#111827', fontWeight: 700, marginBottom: '4px' }}
                itemStyle={{ color: '#10b981', fontWeight: 600 }}
                formatter={(value: number) => [`R$ ${value.toFixed(2)}`, 'Receita']}
                cursor={{ fill: 'rgba(16, 185, 129, 0.1)' }}
              />
              <Bar dataKey="value" fill="url(#barGradient)" radius={[8, 8, 0, 0]} />
            </BarChart>
          </ResponsiveContainer>
        </div>

        {/* Saldo Líquido do Ano - Gráfico de Barras Verticais */}
        <div className="bg-white/80 dark:bg-gray-800/50 backdrop-blur-sm rounded-xl p-6 border border-gray-200 dark:border-gray-700/50 shadow-sm">
          <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6">
            <div>
              <h3 className="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <div className="p-2 bg-green-100 dark:bg-green-500/20 rounded-lg">
                  <Activity className="w-5 h-5 text-green-600 dark:text-green-400" />
                </div>
                Saldo Líquido do Ano
              </h3>
              <div className="flex items-baseline gap-2 mt-2">
                <p className="text-3xl font-bold text-green-600 dark:text-green-400">
                  R$ {totalYearRevenue.toFixed(2)}
                </p>
                <span className="text-sm text-gray-500 dark:text-gray-400">total</span>
              </div>
            </div>
            <select
              value={yearFilter}
              onChange={(e) => setYearFilter(parseInt(e.target.value))}
              className="px-4 py-2 bg-gray-50 dark:bg-gray-900 rounded-lg text-sm border border-gray-200 dark:border-gray-700 focus:ring-2 focus:ring-primary-500 text-gray-900 dark:text-white font-medium"
            >
              <option value={new Date().getFullYear()}>{new Date().getFullYear()}</option>
              <option value={new Date().getFullYear() - 1}>{new Date().getFullYear() - 1}</option>
              <option value={new Date().getFullYear() - 2}>{new Date().getFullYear() - 2}</option>
            </select>
          </div>
          <ResponsiveContainer width="100%" height={250}>
            <BarChart data={liquidBalanceYearData}>
              <defs>
                <linearGradient id="barGradientBlue" x1="0" y1="0" x2="0" y2="1">
                  <stop offset="0%" stopColor="#3b82f6" stopOpacity={1} />
                  <stop offset="100%" stopColor="#2563eb" stopOpacity={1} />
                </linearGradient>
              </defs>
              <CartesianGrid strokeDasharray="3 3" stroke="#e5e7eb" className="dark:stroke-gray-700" opacity={0.5} />
              <XAxis 
                dataKey="month" 
                stroke="#9ca3af" 
                style={{ fontSize: '12px', fontWeight: 500 }} 
                tick={{ fill: '#6b7280' }}
              />
              <YAxis 
                stroke="#9ca3af" 
                style={{ fontSize: '12px', fontWeight: 500 }} 
                tick={{ fill: '#6b7280' }}
                tickFormatter={(value) => `R$ ${(value / 1000).toFixed(0)}k`}
              />
              <Tooltip
                contentStyle={{
                  backgroundColor: '#ffffff',
                  border: '2px solid #3b82f6',
                  borderRadius: '12px',
                  boxShadow: '0 10px 15px -3px rgb(0 0 0 / 0.1)',
                  padding: '12px'
                }}
                labelStyle={{ color: '#111827', fontWeight: 700, marginBottom: '4px' }}
                itemStyle={{ color: '#3b82f6', fontWeight: 600 }}
                formatter={(value: number) => [`R$ ${value.toFixed(2)}`, 'Receita']}
                cursor={{ fill: 'rgba(59, 130, 246, 0.1)' }}
              />
              <Bar dataKey="value" fill="url(#barGradientBlue)" radius={[8, 8, 0, 0]} />
            </BarChart>
          </ResponsiveContainer>
        </div>
      </div>

      {/* Clientes Novos e Pagamentos */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* Clientes Novos Por Dia */}
        <div className="bg-white/80 dark:bg-gray-800/50 backdrop-blur-sm rounded-2xl p-6 shadow-lg border border-gray-200 dark:border-gray-700/50">
          <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6">
            <div>
              <h3 className="text-base md:text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                <Users className="w-5 h-5 text-green-500" />
                Clientes Novos Por Dia
              </h3>
              <p className="text-xs md:text-sm text-gray-500 dark:text-gray-400">Cadastros únicos do período selecionado</p>
            </div>
            <select
              value={clientsPeriod}
              onChange={(e) => setClientsPeriod(e.target.value)}
              className="w-full sm:w-auto px-3 py-2 bg-gray-100 dark:bg-gray-700 rounded-lg text-sm border-0 focus:ring-2 focus:ring-primary-500 text-gray-900 dark:text-white"
            >
              <optgroup label="Períodos Rápidos">
                <option value="today">Hoje</option>
                <option value="yesterday">Ontem</option>
                <option value="this-week">Esta Semana</option>
                <option value="last-week">Semana Passada</option>
                <option value="this-month">Este Mês</option>
                <option value="last-month">Mês Passado</option>
              </optgroup>
              <optgroup label="Por Quantidade de Dias">
                <option value="7">Últimos 7 dias</option>
                <option value="15">Últimos 15 dias</option>
                <option value="30">Últimos 30 dias</option>
                <option value="60">Últimos 60 dias</option>
                <option value="90">Últimos 90 dias</option>
                <option value="180">Últimos 180 dias</option>
              </optgroup>
              <optgroup label="Períodos Longos">
                <option value="this-quarter">Este Trimestre</option>
                <option value="last-quarter">Trimestre Passado</option>
                <option value="this-year">Este Ano</option>
                <option value="last-year">Ano Passado</option>
              </optgroup>
            </select>
          </div>
          <div className="grid grid-cols-3 gap-4 mb-4">
            <div className="text-center p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
              <p className="text-2xl font-bold text-gray-900 dark:text-white">{newClientsData.reduce((sum, item) => sum + item.value, 0)}</p>
              <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">Total</p>
            </div>
            <div className="text-center p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
              <p className="text-2xl font-bold text-gray-900 dark:text-white">{Math.max(...newClientsData.map(item => item.value))}</p>
              <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">Melhor Dia</p>
            </div>
            <div className="text-center p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
              <p className="text-2xl font-bold text-gray-900 dark:text-white">{Math.round(newClientsData.reduce((sum, item) => sum + item.value, 0) / newClientsData.length)}</p>
              <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">Média/Dia</p>
            </div>
          </div>
          <ResponsiveContainer width="100%" height={200}>
            <AreaChart data={newClientsData}>
              <defs>
                <linearGradient id="colorClients" x1="0" y1="0" x2="0" y2="1">
                  <stop offset="5%" stopColor="#10b981" stopOpacity={0.5} />
                  <stop offset="95%" stopColor="#10b981" stopOpacity={0.05} />
                </linearGradient>
              </defs>
              <CartesianGrid strokeDasharray="3 3" stroke="#e5e7eb" opacity={0.5} />
              <XAxis
                dataKey="day"
                stroke="#6b7280"
                style={{ fontSize: '11px' }}
                tick={{ fill: '#6b7280' }}
              />
              <YAxis
                stroke="#6b7280"
                style={{ fontSize: '11px' }}
                tick={{ fill: '#6b7280' }}
              />
              <Tooltip
                contentStyle={{
                  backgroundColor: 'rgba(255, 255, 255, 0.98)',
                  border: '1px solid #e5e7eb',
                  borderRadius: '8px',
                  color: '#111827',
                  boxShadow: '0 4px 6px -1px rgb(0 0 0 / 0.1)'
                }}
                labelStyle={{ color: '#6b7280', fontWeight: 600 }}
                itemStyle={{ color: '#10b981', fontWeight: 600 }}
              />
              <Area
                type="monotone"
                dataKey="value"
                stroke="#10b981"
                strokeWidth={3}
                fillOpacity={1}
                fill="url(#colorClients)"
              />
            </AreaChart>
          </ResponsiveContainer>
        </div>

        {/* Pagamentos Por Dia */}
        <div className="bg-white/80 dark:bg-gray-800/50 backdrop-blur-sm rounded-2xl p-6 shadow-lg border border-gray-200 dark:border-gray-700/50">
          <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6">
            <div>
              <h3 className="text-base md:text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                <DollarSign className="w-5 h-5 text-orange-500" />
                Pagamentos Por Dia
              </h3>
              <p className="text-xs md:text-sm text-gray-500 dark:text-gray-400">Faturamento único do período selecionado</p>
            </div>
            <select
              value={paymentsPeriod}
              onChange={(e) => setPaymentsPeriod(e.target.value)}
              className="w-full sm:w-auto px-3 py-2 bg-gray-100 dark:bg-gray-700 rounded-lg text-sm border-0 focus:ring-2 focus:ring-primary-500 text-gray-900 dark:text-white"
            >
              <optgroup label="Períodos Rápidos">
                <option value="today">Hoje</option>
                <option value="yesterday">Ontem</option>
                <option value="this-week">Esta Semana</option>
                <option value="last-week">Semana Passada</option>
                <option value="this-month">Este Mês</option>
                <option value="last-month">Mês Passado</option>
              </optgroup>
              <optgroup label="Por Quantidade de Dias">
                <option value="7">Últimos 7 dias</option>
                <option value="15">Últimos 15 dias</option>
                <option value="30">Últimos 30 dias</option>
                <option value="60">Últimos 60 dias</option>
                <option value="90">Últimos 90 dias</option>
                <option value="180">Últimos 180 dias</option>
              </optgroup>
              <optgroup label="Períodos Longos">
                <option value="this-quarter">Este Trimestre</option>
                <option value="last-quarter">Trimestre Passado</option>
                <option value="this-year">Este Ano</option>
                <option value="last-year">Ano Passado</option>
              </optgroup>
            </select>
          </div>
          <div className="grid grid-cols-3 gap-4 mb-4">
            <div className="text-center p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
              <p className="text-xl font-bold text-gray-900 dark:text-white">R$ {paymentsData.reduce((sum, item) => sum + item.value, 0).toFixed(2)}</p>
              <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">Total</p>
            </div>
            <div className="text-center p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
              <p className="text-xl font-bold text-gray-900 dark:text-white">R$ {Math.max(...paymentsData.map(item => item.value)).toFixed(2)}</p>
              <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">Melhor Dia</p>
            </div>
            <div className="text-center p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
              <p className="text-xl font-bold text-gray-900 dark:text-white">R$ {(paymentsData.reduce((sum, item) => sum + item.value, 0) / paymentsData.length).toFixed(2)}</p>
              <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">Média/Dia</p>
            </div>
          </div>
          <ResponsiveContainer width="100%" height={200}>
            <AreaChart data={paymentsData}>
              <defs>
                <linearGradient id="colorPayments" x1="0" y1="0" x2="0" y2="1">
                  <stop offset="5%" stopColor="#f59e0b" stopOpacity={0.5} />
                  <stop offset="95%" stopColor="#f59e0b" stopOpacity={0.05} />
                </linearGradient>
              </defs>
              <CartesianGrid strokeDasharray="3 3" stroke="#e5e7eb" opacity={0.5} />
              <XAxis
                dataKey="day"
                stroke="#6b7280"
                style={{ fontSize: '11px' }}
                tick={{ fill: '#6b7280' }}
              />
              <YAxis
                stroke="#6b7280"
                style={{ fontSize: '11px' }}
                tick={{ fill: '#6b7280' }}
                tickFormatter={(value) => `R$ ${value}`}
              />
              <Tooltip
                contentStyle={{
                  backgroundColor: 'rgba(255, 255, 255, 0.98)',
                  border: '1px solid #e5e7eb',
                  borderRadius: '8px',
                  color: '#111827',
                  boxShadow: '0 4px 6px -1px rgb(0 0 0 / 0.1)'
                }}
                labelStyle={{ color: '#6b7280', fontWeight: 600 }}
                itemStyle={{ color: '#f59e0b', fontWeight: 600 }}
                formatter={(value: number) => `R$ ${value.toFixed(2)}`}
              />
              <Area
                type="monotone"
                dataKey="value"
                stroke="#f59e0b"
                strokeWidth={3}
                fillOpacity={1}
                fill="url(#colorPayments)"
              />
            </AreaChart>
          </ResponsiveContainer>
        </div>
      </div>

      {/* Seções Informativas Profissionais */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Clientes com Plano Vencido */}
        <div className="bg-white/80 dark:bg-gray-800/50 backdrop-blur-sm rounded-xl border-l-4 border-red-500 p-6 shadow-sm">
          <div className="flex items-start justify-between mb-4">
            <div>
              <h3 className="text-lg font-bold text-gray-900 dark:text-white mb-1">
                Clientes com Plano Vencido
              </h3>
              <p className="text-sm text-gray-600 dark:text-gray-400">
                Informe aos seus clientes sobre o vencimento
              </p>
            </div>
            <div className="p-3 bg-red-100 dark:bg-red-900/30 rounded-lg">
              <AlertCircle className="w-6 h-6 text-red-600 dark:text-red-400" />
            </div>
          </div>
          
          <div className="space-y-3">
            {clients
              .filter(c => {
                const renewalDate = new Date(c.renewal_date)
                const today = new Date()
                today.setHours(0, 0, 0, 0)
                renewalDate.setHours(0, 0, 0, 0)
                return renewalDate < today && c.status !== 'inactive'
              })
              .slice(0, 5)
              .map(client => (
                <div key={client.id} className="flex items-center justify-between p-3 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-100 dark:border-red-800">
                  <div className="flex-1 min-w-0">
                    <p className="text-sm font-semibold text-gray-900 dark:text-white truncate">
                      {client.name}
                    </p>
                    <p className="text-xs text-gray-600 dark:text-gray-400">
                      Venceu: {new Date(client.renewal_date).toLocaleDateString('pt-BR')}
                    </p>
                  </div>
                  <span className="ml-2 px-2 py-1 text-xs font-bold text-red-700 dark:text-red-300 bg-red-200 dark:bg-red-900/40 rounded">
                    {Math.abs(Math.ceil((new Date(client.renewal_date).getTime() - new Date().getTime()) / (1000 * 60 * 60 * 24)))}d
                  </span>
                </div>
              ))}
            
            {clients.filter(c => {
              const renewalDate = new Date(c.renewal_date)
              const today = new Date()
              today.setHours(0, 0, 0, 0)
              renewalDate.setHours(0, 0, 0, 0)
              return renewalDate < today && c.status !== 'inactive'
            }).length === 0 && (
              <div className="text-center py-8 text-gray-500 dark:text-gray-400">
                <p className="text-sm">Nenhum cliente com plano vencido</p>
              </div>
            )}
          </div>
        </div>

        {/* Clientes Vencendo Hoje */}
        <div className="bg-white/80 dark:bg-gray-800/50 backdrop-blur-sm rounded-xl border-l-4 border-green-500 p-6 shadow-sm">
          <div className="flex items-start justify-between mb-4">
            <div>
              <h3 className="text-lg font-bold text-gray-900 dark:text-white mb-1">
                Clientes Vencendo Hoje
              </h3>
              <p className="text-sm text-gray-600 dark:text-gray-400">
                Informe aos seus clientes sobre o vencimento
              </p>
            </div>
            <div className="p-3 bg-green-100 dark:bg-green-900/30 rounded-lg">
              <Activity className="w-6 h-6 text-green-600 dark:text-green-400" />
            </div>
          </div>
          
          <div className="space-y-3">
            {clients
              .filter(c => {
                const renewalDate = new Date(c.renewal_date)
                const today = new Date()
                today.setHours(0, 0, 0, 0)
                renewalDate.setHours(0, 0, 0, 0)
                return renewalDate.getTime() === today.getTime()
              })
              .slice(0, 5)
              .map(client => (
                <div key={client.id} className="flex items-center justify-between p-3 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-100 dark:border-green-800">
                  <div className="flex-1 min-w-0">
                    <p className="text-sm font-semibold text-gray-900 dark:text-white truncate">
                      {client.name}
                    </p>
                    <p className="text-xs text-gray-600 dark:text-gray-400">
                      Plano: {client.plan} - R$ {client.value.toFixed(2)}
                    </p>
                  </div>
                  <span className="ml-2 px-2 py-1 text-xs font-bold text-green-700 dark:text-green-300 bg-green-200 dark:bg-green-900/40 rounded">
                    HOJE
                  </span>
                </div>
              ))}
            
            {clients.filter(c => {
              const renewalDate = new Date(c.renewal_date)
              const today = new Date()
              today.setHours(0, 0, 0, 0)
              renewalDate.setHours(0, 0, 0, 0)
              return renewalDate.getTime() === today.getTime()
            }).length === 0 && (
              <div className="text-center py-8 text-gray-500 dark:text-gray-400">
                <p className="text-sm">Nenhum cliente vencendo hoje</p>
              </div>
            )}
          </div>
        </div>

        {/* Clientes Próximo do Vencimento */}
        <div className="bg-white/80 dark:bg-gray-800/50 backdrop-blur-sm rounded-xl border-l-4 border-yellow-500 p-6 shadow-sm">
          <div className="flex items-start justify-between mb-4">
            <div>
              <h3 className="text-lg font-bold text-gray-900 dark:text-white mb-1">
                Próximo do Vencimento
              </h3>
              <p className="text-sm text-gray-600 dark:text-gray-400">
                Envie lembretes aos clientes com dias configurados
              </p>
            </div>
            <div className="p-3 bg-yellow-100 dark:bg-yellow-900/30 rounded-lg">
              <TrendingUp className="w-6 h-6 text-yellow-600 dark:text-yellow-400" />
            </div>
          </div>
          
          <div className="space-y-3">
            {clients
              .filter(c => {
                const renewalDate = new Date(c.renewal_date)
                const today = new Date()
                today.setHours(0, 0, 0, 0)
                renewalDate.setHours(0, 0, 0, 0)
                const diffDays = Math.ceil((renewalDate.getTime() - today.getTime()) / (1000 * 60 * 60 * 24))
                return diffDays > 0 && diffDays <= 7
              })
              .slice(0, 5)
              .map(client => {
                const renewalDate = new Date(client.renewal_date)
                const today = new Date()
                today.setHours(0, 0, 0, 0)
                renewalDate.setHours(0, 0, 0, 0)
                const diffDays = Math.ceil((renewalDate.getTime() - today.getTime()) / (1000 * 60 * 60 * 24))
                
                return (
                  <div key={client.id} className="flex items-center justify-between p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg border border-yellow-100 dark:border-yellow-800">
                    <div className="flex-1 min-w-0">
                      <p className="text-sm font-semibold text-gray-900 dark:text-white truncate">
                        {client.name}
                      </p>
                      <p className="text-xs text-gray-600 dark:text-gray-400">
                        Vence: {renewalDate.toLocaleDateString('pt-BR')}
                      </p>
                    </div>
                    <span className="ml-2 px-2 py-1 text-xs font-bold text-yellow-700 dark:text-yellow-300 bg-yellow-200 dark:bg-yellow-900/40 rounded">
                      {diffDays}d
                    </span>
                  </div>
                )
              })}
            
            {clients.filter(c => {
              const renewalDate = new Date(c.renewal_date)
              const today = new Date()
              today.setHours(0, 0, 0, 0)
              renewalDate.setHours(0, 0, 0, 0)
              const diffDays = Math.ceil((renewalDate.getTime() - today.getTime()) / (1000 * 60 * 60 * 24))
              return diffDays > 0 && diffDays <= 7
            }).length === 0 && (
              <div className="text-center py-8 text-gray-500 dark:text-gray-400">
                <p className="text-sm">Nenhum cliente próximo do vencimento</p>
              </div>
            )}
          </div>
        </div>
      </div>

      {/* Distribuição de Status */}
      <div className="bg-white/80 dark:bg-gray-800/50 backdrop-blur-sm rounded-xl p-6 border border-gray-200 dark:border-gray-700/50 shadow-sm">
        <h3 className="text-lg font-bold text-gray-900 dark:text-white mb-6">
          Distribuição de Clientes por Status
        </h3>
        <div className="flex items-center justify-center">
          <ResponsiveContainer width="100%" height={300}>
            <PieChart>
              <Pie
                data={statusData}
                cx="50%"
                cy="50%"
                labelLine={false}
                label={({ name, percent }) => `${name}: ${(percent * 100).toFixed(0)}%`}
                outerRadius={100}
                fill="#8884d8"
                dataKey="value"
              >
                {statusData.map((_, index) => (
                  <Cell key={`cell-${index}`} fill={COLORS[index % COLORS.length]} />
                ))}
              </Pie>
              <Tooltip />
            </PieChart>
          </ResponsiveContainer>
        </div>
      </div>
    </div>
  )
}
