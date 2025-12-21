import { useState, useEffect } from 'react'
import { TrendingUp, DollarSign, Users, FileText } from 'lucide-react'
import { BarChart, Bar, LineChart, Line, PieChart, Pie, Cell, XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer } from 'recharts'
import api from '../services/api'
import LoadingSpinner from '../components/LoadingSpinner'

const COLORS = ['#10b981', '#3b82f6', '#f59e0b', '#ef4444', '#8b5cf6']

import { usePageTitle } from '@/hooks/usePageTitle'

export default function Reports() {
  usePageTitle('Relatórios')
  const [loading, setLoading] = useState(true)
  const [period, setPeriod] = useState<'week' | 'month' | 'year'>('month')
  const [stats, setStats] = useState<any>({
    revenue: [],
    clients: [],
    invoices: [],
    plans: []
  })

  useEffect(() => {
    loadReports()
  }, [period])

  const loadReports = async () => {
    setLoading(true)
    try {
      const response = await api.get(`/api-reports.php?period=${period}`)
      setStats(response.data)
    } catch (error) {
      // Erro ao carregar relatórios
    } finally {
      setLoading(false)
    }
  }

  if (loading) {
    return <LoadingSpinner />
  }

  return (
    <div className="space-y-6">
      <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
          <h1 className="text-2xl md:text-3xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">Relatórios</h1>
          <p className="text-sm md:text-base text-gray-600 dark:text-gray-400 mt-1">Análise de desempenho e estatísticas</p>
        </div>
        
        <div className="flex gap-2 w-full sm:w-auto">
          {(['week', 'month', 'year'] as const).map((p) => (
            <button
              key={p}
              onClick={() => setPeriod(p)}
              className={`flex-1 sm:flex-none px-3 md:px-4 py-2 rounded-lg transition-colors text-sm md:text-base ${
                period === p
                  ? 'bg-primary-600 text-white'
                  : 'bg-white/80 dark:bg-gray-800/50 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'
              }`}
            >
              {p === 'week' ? 'Semana' : p === 'month' ? 'Mês' : 'Ano'}
            </button>
          ))}
        </div>
      </div>

      {/* Summary Cards */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div className="bg-white/80 dark:bg-gray-800/50 backdrop-blur-sm rounded-xl p-6 border border-gray-200 dark:border-gray-700">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm text-gray-600 dark:text-gray-400">Receita Total</p>
              <p className="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                R$ {stats.total_revenue?.toFixed(2) || '0.00'}
              </p>
            </div>
            <div className="p-3 bg-green-100 dark:bg-green-900/30 rounded-lg">
              <DollarSign className="w-6 h-6 text-green-600 dark:text-green-400" />
            </div>
          </div>
        </div>

        <div className="bg-white/80 dark:bg-gray-800/50 backdrop-blur-sm rounded-xl p-6 border border-gray-200 dark:border-gray-700">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm text-gray-600 dark:text-gray-400">Novos Clientes</p>
              <p className="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                {stats.new_clients || 0}
              </p>
            </div>
            <div className="p-3 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
              <Users className="w-6 h-6 text-blue-600 dark:text-blue-400" />
            </div>
          </div>
        </div>

        <div className="bg-white/80 dark:bg-gray-800/50 backdrop-blur-sm rounded-xl p-6 border border-gray-200 dark:border-gray-700">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm text-gray-600 dark:text-gray-400">Faturas Pagas</p>
              <p className="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                {stats.paid_invoices || 0}
              </p>
            </div>
            <div className="p-3 bg-purple-100 dark:bg-purple-900/30 rounded-lg">
              <FileText className="w-6 h-6 text-purple-600 dark:text-purple-400" />
            </div>
          </div>
        </div>

        <div className="bg-white/80 dark:bg-gray-800/50 backdrop-blur-sm rounded-xl p-6 border border-gray-200 dark:border-gray-700">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm text-gray-600 dark:text-gray-400">Taxa de Conversão</p>
              <p className="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                {stats.conversion_rate || 0}%
              </p>
            </div>
            <div className="p-3 bg-yellow-100 dark:bg-yellow-900/30 rounded-lg">
              <TrendingUp className="w-6 h-6 text-yellow-600 dark:text-yellow-400" />
            </div>
          </div>
        </div>
      </div>

      {/* Charts */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* Revenue Chart */}
        <div className="bg-white/80 dark:bg-gray-800/50 backdrop-blur-sm rounded-xl p-4 md:p-6 border border-gray-200 dark:border-gray-700">
          <h3 className="text-base md:text-lg font-semibold text-gray-900 dark:text-white mb-4">Receita por Período</h3>
          <div className="w-full overflow-x-auto">
            <div className="min-w-[300px]">
              <ResponsiveContainer width="100%" height={300}>
                <LineChart data={stats.revenue || []} margin={{ top: 5, right: 10, left: -10, bottom: 5 }}>
                  <CartesianGrid strokeDasharray="3 3" stroke="#374151" opacity={0.1} />
                  <XAxis 
                    dataKey="date" 
                    stroke="#9ca3af" 
                    tick={{ fontSize: 12 }}
                    angle={-45}
                    textAnchor="end"
                    height={60}
                  />
                  <YAxis stroke="#9ca3af" tick={{ fontSize: 12 }} />
                  <Tooltip 
                    contentStyle={{ 
                      backgroundColor: '#1f2937', 
                      border: 'none', 
                      borderRadius: '8px',
                      color: '#fff',
                      fontSize: '12px'
                    }}
                    wrapperStyle={{ zIndex: 1000 }}
                  />
                  <Legend wrapperStyle={{ fontSize: '12px' }} />
                  <Line type="monotone" dataKey="value" stroke="#10b981" strokeWidth={2} name="Receita" />
                </LineChart>
              </ResponsiveContainer>
            </div>
          </div>
        </div>

        {/* Clients Chart */}
        <div className="bg-white/80 dark:bg-gray-800/50 backdrop-blur-sm rounded-xl p-4 md:p-6 border border-gray-200 dark:border-gray-700">
          <h3 className="text-base md:text-lg font-semibold text-gray-900 dark:text-white mb-4">Crescimento de Clientes</h3>
          <div className="w-full overflow-x-auto">
            <div className="min-w-[300px]">
              <ResponsiveContainer width="100%" height={300}>
                <BarChart data={stats.clients || []} margin={{ top: 5, right: 10, left: -10, bottom: 5 }}>
                  <CartesianGrid strokeDasharray="3 3" stroke="#374151" opacity={0.1} />
                  <XAxis 
                    dataKey="date" 
                    stroke="#9ca3af" 
                    tick={{ fontSize: 12 }}
                    angle={-45}
                    textAnchor="end"
                    height={60}
                  />
                  <YAxis stroke="#9ca3af" tick={{ fontSize: 12 }} />
                  <Tooltip 
                    contentStyle={{ 
                      backgroundColor: '#1f2937', 
                      border: 'none', 
                      borderRadius: '8px',
                      color: '#fff',
                      fontSize: '12px'
                    }}
                    wrapperStyle={{ zIndex: 1000 }}
                  />
                  <Legend wrapperStyle={{ fontSize: '12px' }} />
                  <Bar dataKey="count" fill="#3b82f6" name="Clientes" />
                </BarChart>
              </ResponsiveContainer>
            </div>
          </div>
        </div>

        {/* Plans Distribution */}
        <div className="bg-white/80 dark:bg-gray-800/50 backdrop-blur-sm rounded-xl p-4 md:p-6 border border-gray-200 dark:border-gray-700">
          <h3 className="text-base md:text-lg font-semibold text-gray-900 dark:text-white mb-4">Distribuição por Plano</h3>
          <div className="w-full overflow-x-auto">
            <div className="min-w-[300px]">
              <ResponsiveContainer width="100%" height={300}>
                <PieChart>
                  <Pie
                    data={stats.plans || []}
                    cx="50%"
                    cy="50%"
                    labelLine={false}
                    label={({ name, percent }) => {
                      // Mobile: apenas porcentagem, Desktop: nome + porcentagem
                      const isMobile = window.innerWidth < 640
                      return isMobile ? `${(percent * 100).toFixed(0)}%` : `${name}: ${(percent * 100).toFixed(0)}%`
                    }}
                    outerRadius={window.innerWidth < 640 ? 60 : 80}
                    fill="#8884d8"
                    dataKey="value"
                  >
                    {(stats.plans || []).map((_: any, index: number) => (
                      <Cell key={`cell-${index}`} fill={COLORS[index % COLORS.length]} />
                    ))}
                  </Pie>
                  <Tooltip 
                    contentStyle={{ 
                      backgroundColor: '#1f2937', 
                      border: 'none', 
                      borderRadius: '8px',
                      color: '#fff',
                      fontSize: '12px'
                    }}
                    wrapperStyle={{ zIndex: 1000 }}
                  />
                  <Legend wrapperStyle={{ fontSize: '11px' }} />
                </PieChart>
              </ResponsiveContainer>
            </div>
          </div>
        </div>

        {/* Invoice Status */}
        <div className="bg-white/80 dark:bg-gray-800/50 backdrop-blur-sm rounded-xl p-4 md:p-6 border border-gray-200 dark:border-gray-700">
          <h3 className="text-base md:text-lg font-semibold text-gray-900 dark:text-white mb-4">Status de Faturas</h3>
          <div className="w-full overflow-x-auto">
            <div className="min-w-[300px]">
              <ResponsiveContainer width="100%" height={300}>
                <BarChart data={stats.invoices || []} margin={{ top: 5, right: 10, left: -10, bottom: 5 }}>
                  <CartesianGrid strokeDasharray="3 3" stroke="#374151" opacity={0.1} />
                  <XAxis 
                    dataKey="status" 
                    stroke="#9ca3af" 
                    tick={{ fontSize: 12 }}
                    angle={-45}
                    textAnchor="end"
                    height={60}
                  />
                  <YAxis stroke="#9ca3af" tick={{ fontSize: 12 }} />
                  <Tooltip 
                    contentStyle={{ 
                      backgroundColor: '#1f2937', 
                      border: 'none', 
                      borderRadius: '8px',
                      color: '#fff',
                      fontSize: '12px'
                    }}
                    wrapperStyle={{ zIndex: 1000 }}
                  />
                  <Legend wrapperStyle={{ fontSize: '12px' }} />
                  <Bar dataKey="count" fill="#f59e0b" name="Quantidade" />
                </BarChart>
              </ResponsiveContainer>
            </div>
          </div>
        </div>
      </div>
    </div>
  )
}
