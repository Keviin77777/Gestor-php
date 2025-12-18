import { useState, useEffect } from 'react'
import { Calendar, TrendingUp, TrendingDown, DollarSign, FileText } from 'lucide-react'
import api from '@/services/api'
import LoadingSpinner from '@/components/LoadingSpinner'

interface MonthDetail {
  month: string
  monthName: string
  vendas: number
  entradas: number
  saidas: number
  custos: number
  saldo: number
}

export default function MonthlyReport() {
  const [loading, setLoading] = useState(true)
  const [year, setYear] = useState(new Date().getFullYear())
  const [months, setMonths] = useState<MonthDetail[]>([])

  useEffect(() => {
    loadMonthlyData()
  }, [year])

  const loadMonthlyData = async () => {
    setLoading(true)
    try {
      const response = await api.get(`/api-monthly-report.php?year=${year}`)
      if (response.data.success) {
        // Filtrar apenas meses com dados reais
        const filteredMonths = (response.data.months || []).filter((month: MonthDetail) => 
          month.vendas > 0 || month.entradas > 0 || month.saidas > 0 || month.custos > 0
        )
        setMonths(filteredMonths)
      }
    } catch (error) {
      console.error('Erro ao carregar relatório mensal:', error)
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
              <FileText className="w-6 h-6 text-primary-600 dark:text-primary-400" />
            </div>
            <h1 className="text-3xl font-bold text-gray-900 dark:text-white">
              Detalhamento Mensal
            </h1>
          </div>
          <p className="text-gray-600 dark:text-gray-400 ml-14">
            Análise completa mês a mês do desempenho financeiro
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

      {/* Monthly Cards Grid */}
      {months.length > 0 ? (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
          {months.map((month) => (
            <div
              key={month.month}
              className="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6 shadow-sm hover:shadow-lg transition-all duration-300"
            >
              {/* Month Header */}
              <div className="mb-5">
                <h3 className="text-xl font-bold text-gray-900 dark:text-white mb-3">
                  {month.monthName}
                </h3>
                <div className={`flex items-center justify-between p-4 rounded-lg ${
                  month.saldo >= 0 
                    ? 'bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800' 
                    : 'bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800'
                }`}>
                  <div>
                    <p className="text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wide mb-1">
                      Saldo Líquido
                    </p>
                    <p className={`text-2xl font-bold ${
                      month.saldo >= 0 
                        ? 'text-emerald-600 dark:text-emerald-400' 
                        : 'text-red-600 dark:text-red-400'
                    }`}>
                      {formatCompact(month.saldo)}
                    </p>
                  </div>
                  <div className={`p-3 rounded-lg ${
                    month.saldo >= 0 
                      ? 'bg-emerald-100 dark:bg-emerald-900/40' 
                      : 'bg-red-100 dark:bg-red-900/40'
                  }`}>
                    {month.saldo >= 0 ? (
                      <TrendingUp className="w-6 h-6 text-emerald-600 dark:text-emerald-400" />
                    ) : (
                      <TrendingDown className="w-6 h-6 text-red-600 dark:text-red-400" />
                    )}
                  </div>
                </div>
              </div>

              {/* Financial Details */}
              <div className="space-y-3">
                {/* Vendas */}
                <div className="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-900/50 rounded-lg border border-gray-100 dark:border-gray-700">
                  <div className="flex items-center gap-3">
                    <div className="w-2 h-2 bg-green-500 rounded-full"></div>
                    <span className="text-sm font-semibold text-gray-700 dark:text-gray-300">
                      Vendas
                    </span>
                  </div>
                  <span className="text-sm font-bold text-gray-900 dark:text-white">
                    {formatCurrency(month.vendas)}
                  </span>
                </div>

                {/* Entradas */}
                <div className="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-900/50 rounded-lg border border-gray-100 dark:border-gray-700">
                  <div className="flex items-center gap-3">
                    <div className="w-2 h-2 bg-blue-500 rounded-full"></div>
                    <span className="text-sm font-semibold text-gray-700 dark:text-gray-300">
                      Entradas
                    </span>
                  </div>
                  <span className="text-sm font-bold text-gray-900 dark:text-white">
                    {formatCurrency(month.entradas)}
                  </span>
                </div>

                {/* Saídas */}
                <div className="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-900/50 rounded-lg border border-gray-100 dark:border-gray-700">
                  <div className="flex items-center gap-3">
                    <div className="w-2 h-2 bg-red-500 rounded-full"></div>
                    <span className="text-sm font-semibold text-gray-700 dark:text-gray-300">
                      Saídas
                    </span>
                  </div>
                  <span className="text-sm font-bold text-gray-900 dark:text-white">
                    {formatCurrency(month.saidas)}
                  </span>
                </div>

                {/* Custos */}
                <div className="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-900/50 rounded-lg border border-gray-100 dark:border-gray-700">
                  <div className="flex items-center gap-3">
                    <div className="w-2 h-2 bg-purple-500 rounded-full"></div>
                    <span className="text-sm font-semibold text-gray-700 dark:text-gray-300">
                      Custos
                    </span>
                  </div>
                  <span className="text-sm font-bold text-gray-900 dark:text-white">
                    {formatCurrency(month.custos)}
                  </span>
                </div>
              </div>
            </div>
          ))}
        </div>
      ) : (
        <div className="flex flex-col items-center justify-center py-20 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
          <FileText className="w-16 h-16 text-gray-300 dark:text-gray-600 mb-4" />
          <p className="text-lg font-semibold text-gray-900 dark:text-white mb-2">
            Nenhum dado disponível para {year}
          </p>
          <p className="text-sm text-gray-500 dark:text-gray-400">
            Selecione outro ano ou aguarde movimentações financeiras
          </p>
        </div>
      )}

      {/* Legend */}
      <div className="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700 shadow-sm">
        <h3 className="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
          <DollarSign className="w-5 h-5 text-primary-600" />
          Legenda dos Indicadores
        </h3>
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div className="flex items-start gap-3 p-3 bg-gray-50 dark:bg-gray-900/50 rounded-lg">
            <div className="w-10 h-10 bg-green-500 rounded-lg flex items-center justify-center flex-shrink-0">
              <TrendingUp className="w-5 h-5 text-white" />
            </div>
            <div>
              <p className="font-semibold text-gray-900 dark:text-white text-sm">Vendas</p>
              <p className="text-xs text-gray-600 dark:text-gray-400 mt-0.5">Total de faturas pagas pelos clientes</p>
            </div>
          </div>
          
          <div className="flex items-start gap-3 p-3 bg-gray-50 dark:bg-gray-900/50 rounded-lg">
            <div className="w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center flex-shrink-0">
              <DollarSign className="w-5 h-5 text-white" />
            </div>
            <div>
              <p className="font-semibold text-gray-900 dark:text-white text-sm">Entradas</p>
              <p className="text-xs text-gray-600 dark:text-gray-400 mt-0.5">Todas as receitas recebidas no mês</p>
            </div>
          </div>
          
          <div className="flex items-start gap-3 p-3 bg-gray-50 dark:bg-gray-900/50 rounded-lg">
            <div className="w-10 h-10 bg-red-500 rounded-lg flex items-center justify-center flex-shrink-0">
              <TrendingDown className="w-5 h-5 text-white" />
            </div>
            <div>
              <p className="font-semibold text-gray-900 dark:text-white text-sm">Saídas</p>
              <p className="text-xs text-gray-600 dark:text-gray-400 mt-0.5">Despesas e pagamentos realizados</p>
            </div>
          </div>
          
          <div className="flex items-start gap-3 p-3 bg-gray-50 dark:bg-gray-900/50 rounded-lg">
            <div className="w-10 h-10 bg-purple-500 rounded-lg flex items-center justify-center flex-shrink-0">
              <DollarSign className="w-5 h-5 text-white" />
            </div>
            <div>
              <p className="font-semibold text-gray-900 dark:text-white text-sm">Custos Servidores</p>
              <p className="text-xs text-gray-600 dark:text-gray-400 mt-0.5">Custos operacionais e infraestrutura</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  )
}
