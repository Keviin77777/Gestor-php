import { useState, useEffect } from 'react'
import { BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer } from 'recharts'
import { TrendingUp, TrendingDown, DollarSign, Calendar, BarChart3 } from 'lucide-react'
import api from '@/services/api'
import LoadingSpinner from '@/components/LoadingSpinner'

interface MonthData {
  month: string
  vendas: number
  entradas: number
  saidas: number
  custos: number
}

export default function FinancialReport() {
  const [loading, setLoading] = useState(true)
  const [year, setYear] = useState(new Date().getFullYear())
  const [data, setData] = useState<MonthData[]>([])
  const [totals, setTotals] = useState({
    vendas: 0,
    entradas: 0,
    saidas: 0,
    custos: 0,
    saldo: 0
  })

  useEffect(() => {
    loadFinancialData()
  }, [year])

  const loadFinancialData = async () => {
    setLoading(true)
    try {
      const response = await api.get(`/api-financial-report.php?year=${year}`)
      if (response.data.success) {
        // Filtrar apenas meses com dados reais
        const filteredData = (response.data.monthly_data || []).filter((item: MonthData) => 
          item.vendas > 0 || item.entradas > 0 || item.saidas > 0 || item.custos > 0
        )
        setData(filteredData)
        setTotals(response.data.totals || {})
      }
    } catch (error) {
      console.error('Erro ao carregar relatório financeiro:', error)
    } finally {
      setLoading(false)
    }
  }

  const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('pt-BR', {
      style: 'currency',
      currency: 'BRL'
    }).format(value)
  }

  const formatCompact = (value: number) => {
    if (value >= 1000000) {
      return `R$ ${(value / 1000000).toFixed(1)}M`
    } else if (value >= 1000) {
      return `R$ ${(value / 1000).toFixed(1)}K`
    }
    return formatCurrency(value)
  }

  const CustomTooltip = ({ active, payload }: any) => {
    if (active && payload && payload.length) {
      return (
        <div className="bg-white dark:bg-gray-900 border-2 border-gray-200 dark:border-gray-700 rounded-xl p-4 shadow-2xl">
          <p className="text-gray-900 dark:text-white font-bold mb-3 text-base">{payload[0].payload.month}</p>
          <div className="space-y-2">
            {payload.map((entry: any, index: number) => (
              <div key={index} className="flex items-center justify-between gap-4">
                <div className="flex items-center gap-2">
                  <div className="w-3 h-3 rounded-full" style={{ backgroundColor: entry.color }}></div>
                  <span className="text-sm font-medium text-gray-700 dark:text-gray-300">{entry.name}</span>
                </div>
                <span className="text-sm font-bold" style={{ color: entry.color }}>
                  {formatCurrency(entry.value)}
                </span>
              </div>
            ))}
          </div>
        </div>
      )
    }
    return null
  }

  if (loading) {
    return <LoadingSpinner />
  }

  return (
    <div className="space-y-8 p-4 md:p-6">
      {/* Header */}
      <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
          <div className="flex items-center gap-3 mb-2">
            <div className="p-2 bg-primary-100 dark:bg-primary-900/30 rounded-lg">
              <BarChart3 className="w-6 h-6 text-primary-600 dark:text-primary-400" />
            </div>
            <h1 className="text-3xl font-bold text-gray-900 dark:text-white">
              Gráfico Financeiro
            </h1>
          </div>
          <p className="text-gray-600 dark:text-gray-400 ml-14">
            Análise detalhada do desempenho financeiro
          </p>
        </div>

        {/* Year Selector */}
        <div className="flex items-center gap-3 bg-white dark:bg-gray-800 px-4 py-2 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm">
          <Calendar className="w-5 h-5 text-gray-500 dark:text-gray-400" />
          <select
            value={year}
            onChange={(e) => setYear(Number(e.target.value))}
            className="bg-transparent text-gray-900 dark:text-white font-semibold focus:outline-none cursor-pointer"
          >
            {[2023, 2024, 2025, 2026].map((y) => (
              <option key={y} value={y}>{y}</option>
            ))}
          </select>
        </div>
      </div>

      {/* Summary Cards */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
        <div className="bg-white dark:bg-gray-800 rounded-xl p-6 border-l-4 border-green-500 shadow-sm hover:shadow-md transition-shadow">
          <div className="flex items-center justify-between mb-3">
            <p className="text-sm font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wide">Vendas</p>
            <div className="p-2 bg-green-100 dark:bg-green-900/30 rounded-lg">
              <TrendingUp className="w-5 h-5 text-green-600 dark:text-green-400" />
            </div>
          </div>
          <p className="text-2xl font-bold text-gray-900 dark:text-white">{formatCompact(totals.vendas)}</p>
          <p className="text-xs text-gray-500 dark:text-gray-500 mt-1">Total recebido</p>
        </div>

        <div className="bg-white dark:bg-gray-800 rounded-xl p-6 border-l-4 border-blue-500 shadow-sm hover:shadow-md transition-shadow">
          <div className="flex items-center justify-between mb-3">
            <p className="text-sm font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wide">Entradas</p>
            <div className="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
              <DollarSign className="w-5 h-5 text-blue-600 dark:text-blue-400" />
            </div>
          </div>
          <p className="text-2xl font-bold text-gray-900 dark:text-white">{formatCompact(totals.entradas)}</p>
          <p className="text-xs text-gray-500 dark:text-gray-500 mt-1">Receitas totais</p>
        </div>

        <div className="bg-white dark:bg-gray-800 rounded-xl p-6 border-l-4 border-red-500 shadow-sm hover:shadow-md transition-shadow">
          <div className="flex items-center justify-between mb-3">
            <p className="text-sm font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wide">Saídas</p>
            <div className="p-2 bg-red-100 dark:bg-red-900/30 rounded-lg">
              <TrendingDown className="w-5 h-5 text-red-600 dark:text-red-400" />
            </div>
          </div>
          <p className="text-2xl font-bold text-gray-900 dark:text-white">{formatCompact(totals.saidas)}</p>
          <p className="text-xs text-gray-500 dark:text-gray-500 mt-1">Despesas pagas</p>
        </div>

        <div className="bg-white dark:bg-gray-800 rounded-xl p-6 border-l-4 border-purple-500 shadow-sm hover:shadow-md transition-shadow">
          <div className="flex items-center justify-between mb-3">
            <p className="text-sm font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wide">Custos</p>
            <div className="p-2 bg-purple-100 dark:bg-purple-900/30 rounded-lg">
              <DollarSign className="w-5 h-5 text-purple-600 dark:text-purple-400" />
            </div>
          </div>
          <p className="text-2xl font-bold text-gray-900 dark:text-white">{formatCompact(totals.custos)}</p>
          <p className="text-xs text-gray-500 dark:text-gray-500 mt-1">Custos operacionais</p>
        </div>

        <div className={`bg-white dark:bg-gray-800 rounded-xl p-6 border-l-4 ${totals.saldo >= 0 ? 'border-emerald-500' : 'border-orange-500'} shadow-sm hover:shadow-md transition-shadow`}>
          <div className="flex items-center justify-between mb-3">
            <p className="text-sm font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wide">Saldo</p>
            <div className={`p-2 ${totals.saldo >= 0 ? 'bg-emerald-100 dark:bg-emerald-900/30' : 'bg-orange-100 dark:bg-orange-900/30'} rounded-lg`}>
              {totals.saldo >= 0 ? (
                <TrendingUp className="w-5 h-5 text-emerald-600 dark:text-emerald-400" />
              ) : (
                <TrendingDown className="w-5 h-5 text-orange-600 dark:text-orange-400" />
              )}
            </div>
          </div>
          <p className={`text-2xl font-bold ${totals.saldo >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-orange-600 dark:text-orange-400'}`}>
            {formatCompact(totals.saldo)}
          </p>
          <p className="text-xs text-gray-500 dark:text-gray-500 mt-1">Resultado líquido</p>
        </div>
      </div>

      {/* Chart */}
      <div className="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700 shadow-sm">
        <div className="flex items-center justify-between mb-6">
          <h3 className="text-xl font-bold text-gray-900 dark:text-white">
            Movimentação Financeira
          </h3>
          <div className="text-sm text-gray-500 dark:text-gray-400">
            {data.length} {data.length === 1 ? 'mês' : 'meses'} com movimentação
          </div>
        </div>
        
        {data.length > 0 ? (
          <ResponsiveContainer width="100%" height={450}>
            <BarChart data={data} margin={{ top: 20, right: 30, left: 20, bottom: 5 }}>
              <CartesianGrid strokeDasharray="3 3" stroke="#e5e7eb" className="dark:stroke-gray-700" opacity={0.5} />
              <XAxis 
                dataKey="month" 
                stroke="#6b7280"
                style={{ fontSize: '13px', fontWeight: 500 }}
              />
              <YAxis 
                stroke="#6b7280"
                style={{ fontSize: '13px', fontWeight: 500 }}
                tickFormatter={(value) => formatCompact(value)}
              />
              <Tooltip content={<CustomTooltip />} cursor={{ fill: 'rgba(0, 0, 0, 0.05)' }} />
              <Legend 
                wrapperStyle={{ paddingTop: '20px' }}
                iconType="circle"
              />
              <Bar dataKey="vendas" fill="#10b981" name="Vendas" radius={[6, 6, 0, 0]} />
              <Bar dataKey="entradas" fill="#3b82f6" name="Entradas" radius={[6, 6, 0, 0]} />
              <Bar dataKey="saidas" fill="#ef4444" name="Saídas" radius={[6, 6, 0, 0]} />
              <Bar dataKey="custos" fill="#8b5cf6" name="Custos Servidores" radius={[6, 6, 0, 0]} />
            </BarChart>
          </ResponsiveContainer>
        ) : (
          <div className="flex flex-col items-center justify-center py-20 text-gray-500 dark:text-gray-400">
            <BarChart3 className="w-16 h-16 mb-4 opacity-30" />
            <p className="text-lg font-medium">Nenhum dado disponível para {year}</p>
            <p className="text-sm mt-1">Selecione outro ano ou aguarde movimentações financeiras</p>
          </div>
        )}
      </div>
    </div>
  )
}
