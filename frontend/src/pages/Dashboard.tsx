import { useEffect, useState } from 'react'
import { Users, DollarSign, AlertCircle, TrendingUp, Activity } from 'lucide-react'
import { useClientStore } from '@/stores/useClientStore'
import { AreaChart, Area, BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer, PieChart, Pie, Cell } from 'recharts'

export default function Dashboard() {
  const { clients, fetchClients } = useClientStore()
  const [stats, setStats] = useState({
    totalClients: 0,
    activeClients: 0,
    inactiveClients: 0,
    totalRevenue: 0,
    monthlyAverage: 0,
    todayRevenue: 0,
    expiringToday: 0,
  })

  useEffect(() => {
    fetchClients()
  }, [fetchClients])

  useEffect(() => {
    if (clients.length > 0) {
      const active = clients.filter(c => c.status === 'active').length
      const inactive = clients.filter(c => c.status !== 'active').length
      const suspended = clients.filter(c => c.status === 'suspended').length
      const revenue = clients.filter(c => c.status === 'active').reduce((sum, c) => sum + c.value, 0)
      const monthlyAvg = revenue / (active || 1)

      // Calcular clientes que vencem hoje
      const today = new Date().toISOString().split('T')[0]
      const expiringToday = clients.filter(c => c.renewal_date === today).length

      setStats({
        totalClients: clients.length,
        activeClients: active,
        inactiveClients: inactive + suspended,
        totalRevenue: revenue,
        monthlyAverage: monthlyAvg,
        todayRevenue: 0,
        expiringToday: expiringToday,
      })
    }
  }, [clients])

  // Dados para gráfico de área (Saldo Líquido) - Últimos 7 meses
  const liquidBalanceData = (() => {
    const months = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez']
    const currentMonth = new Date().getMonth()
    const data = []
    
    for (let i = 6; i >= 0; i--) {
      const monthIndex = (currentMonth - i + 12) % 12
      // Simular crescimento baseado na receita atual
      const baseValue = stats.totalRevenue * (0.7 + (i * 0.05))
      data.push({
        month: months[monthIndex],
        value: Math.round(baseValue)
      })
    }
    
    return data
  })()

  // Dados para gráfico de barras (Clientes Novos)
  const newClientsData = [
    { period: 'Total', value: stats.totalClients },
    { period: 'Mês Atual', value: Math.floor(stats.totalClients * 0.15) },
    { period: 'Melhor Dia', value: Math.floor(stats.totalClients * 0.08) },
  ]

  // Dados para gráfico de barras (Pagamentos)
  const paymentsData = [
    { period: 'Total', value: stats.totalRevenue },
    { period: 'Mês Atual', value: Math.round(stats.totalRevenue * 0.85) },
    { period: 'Melhor Dia', value: Math.round(stats.totalRevenue * 0.12) },
  ]

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
        <button className="px-4 py-2 bg-green-500 hover:bg-green-600 text-white rounded-lg font-medium transition-colors flex items-center gap-2 text-sm">
          <Activity className="w-4 h-4" />
          Conectado
        </button>
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
              <p className="text-gray-900 dark:text-white text-2xl font-bold">{stats.inactiveClients}</p>
              <p className="text-orange-600 dark:text-orange-400 text-xs mt-1">-1% vs mês anterior</p>
            </div>
            <div className="w-12 h-12 rounded-full bg-purple-100 dark:bg-purple-500/20 flex items-center justify-center">
              <AlertCircle className="w-6 h-6 text-purple-600 dark:text-purple-400" />
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

      {/* Gráficos */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* Saldo Líquido do Mês */}
        <div className="bg-white/80 dark:bg-gray-800/50 backdrop-blur-sm rounded-2xl p-6 shadow-lg border border-gray-200 dark:border-gray-700/50">
          <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6">
            <div>
              <h3 className="text-base md:text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                <TrendingUp className="w-5 h-5 text-green-500" />
                Saldo Líquido do Mês
              </h3>
              <p className="text-xl md:text-2xl font-bold text-green-500 mt-1">R$ {stats.totalRevenue.toFixed(2)}</p>
            </div>
            <select className="w-full sm:w-auto px-3 py-2 bg-gray-100 dark:bg-gray-700 rounded-lg text-sm border-0 focus:ring-2 focus:ring-primary-500">
              <option>Dezembro 2025</option>
              <option>Novembro 2025</option>
              <option>Outubro 2025</option>
            </select>
          </div>
          <ResponsiveContainer width="100%" height={200}>
            <AreaChart data={liquidBalanceData}>
              <defs>
                <linearGradient id="colorValue" x1="0" y1="0" x2="0" y2="1">
                  <stop offset="5%" stopColor="#10b981" stopOpacity={0.3}/>
                  <stop offset="95%" stopColor="#10b981" stopOpacity={0}/>
                </linearGradient>
              </defs>
              <CartesianGrid strokeDasharray="3 3" stroke="#374151" opacity={0.1} />
              <XAxis dataKey="month" stroke="#9ca3af" style={{ fontSize: '12px' }} />
              <YAxis stroke="#9ca3af" style={{ fontSize: '12px' }} />
              <Tooltip 
                contentStyle={{ 
                  backgroundColor: '#1f2937', 
                  border: 'none', 
                  borderRadius: '8px',
                  color: '#fff'
                }} 
              />
              <Area type="monotone" dataKey="value" stroke="#10b981" strokeWidth={2} fillOpacity={1} fill="url(#colorValue)" />
            </AreaChart>
          </ResponsiveContainer>
        </div>

        {/* Saldo Líquido do Ano */}
        <div className="bg-white/80 dark:bg-gray-800/50 backdrop-blur-sm rounded-2xl p-6 shadow-lg border border-gray-200 dark:border-gray-700/50">
          <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6">
            <div>
              <h3 className="text-base md:text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                <Activity className="w-5 h-5 text-blue-500" />
                Saldo Líquido do Ano
              </h3>
              <p className="text-xl md:text-2xl font-bold text-blue-500 mt-1">R$ {(stats.totalRevenue * 12).toFixed(2)}</p>
            </div>
            <select className="w-full sm:w-auto px-3 py-2 bg-gray-100 dark:bg-gray-700 rounded-lg text-sm border-0 focus:ring-2 focus:ring-primary-500">
              <option>2025</option>
              <option>2024</option>
            </select>
          </div>
          <ResponsiveContainer width="100%" height={200}>
            <AreaChart data={liquidBalanceData}>
              <defs>
                <linearGradient id="colorValueBlue" x1="0" y1="0" x2="0" y2="1">
                  <stop offset="5%" stopColor="#3b82f6" stopOpacity={0.3}/>
                  <stop offset="95%" stopColor="#3b82f6" stopOpacity={0}/>
                </linearGradient>
              </defs>
              <CartesianGrid strokeDasharray="3 3" stroke="#374151" opacity={0.1} />
              <XAxis dataKey="month" stroke="#9ca3af" style={{ fontSize: '12px' }} />
              <YAxis stroke="#9ca3af" style={{ fontSize: '12px' }} />
              <Tooltip 
                contentStyle={{ 
                  backgroundColor: '#1f2937', 
                  border: 'none', 
                  borderRadius: '8px',
                  color: '#fff'
                }} 
              />
              <Area type="monotone" dataKey="value" stroke="#3b82f6" strokeWidth={2} fillOpacity={1} fill="url(#colorValueBlue)" />
            </AreaChart>
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
            <select className="w-full sm:w-auto px-3 py-2 bg-gray-100 dark:bg-gray-700 rounded-lg text-sm border-0 focus:ring-2 focus:ring-primary-500">
              <option>Dezembro 2025</option>
            </select>
          </div>
          <div className="grid grid-cols-3 gap-4 mb-4">
            {newClientsData.map((item, index) => (
              <div key={index} className="text-center p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                <p className="text-2xl font-bold text-gray-900 dark:text-white">{item.value}</p>
                <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">{item.period}</p>
              </div>
            ))}
          </div>
          <ResponsiveContainer width="100%" height={150}>
            <BarChart data={newClientsData}>
              <CartesianGrid strokeDasharray="3 3" stroke="#374151" opacity={0.1} />
              <XAxis dataKey="period" stroke="#9ca3af" style={{ fontSize: '12px' }} />
              <YAxis stroke="#9ca3af" style={{ fontSize: '12px' }} />
              <Tooltip 
                contentStyle={{ 
                  backgroundColor: '#1f2937', 
                  border: 'none', 
                  borderRadius: '8px',
                  color: '#fff'
                }} 
              />
              <Bar dataKey="value" fill="#10b981" radius={[8, 8, 0, 0]} />
            </BarChart>
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
            <select className="w-full sm:w-auto px-3 py-2 bg-gray-100 dark:bg-gray-700 rounded-lg text-sm border-0 focus:ring-2 focus:ring-primary-500">
              <option>Dezembro 2025</option>
            </select>
          </div>
          <div className="grid grid-cols-3 gap-4 mb-4">
            {paymentsData.map((item, index) => (
              <div key={index} className="text-center p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                <p className="text-xl font-bold text-gray-900 dark:text-white">R$ {item.value.toFixed(2)}</p>
                <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">{item.period}</p>
              </div>
            ))}
          </div>
          <ResponsiveContainer width="100%" height={150}>
            <BarChart data={paymentsData}>
              <CartesianGrid strokeDasharray="3 3" stroke="#374151" opacity={0.1} />
              <XAxis dataKey="period" stroke="#9ca3af" style={{ fontSize: '12px' }} />
              <YAxis stroke="#9ca3af" style={{ fontSize: '12px' }} />
              <Tooltip 
                contentStyle={{ 
                  backgroundColor: '#1f2937', 
                  border: 'none', 
                  borderRadius: '8px',
                  color: '#fff'
                }} 
              />
              <Bar dataKey="value" fill="#f97316" radius={[8, 8, 0, 0]} />
            </BarChart>
          </ResponsiveContainer>
        </div>
      </div>

      {/* Distribuição de Status */}
      <div className="bg-white/80 dark:bg-gray-800/50 backdrop-blur-sm rounded-2xl p-4 md:p-6 shadow-lg border border-gray-200 dark:border-gray-700/50">
        <h3 className="text-base md:text-lg font-semibold text-gray-900 dark:text-white mb-6">
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
