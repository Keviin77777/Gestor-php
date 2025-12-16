import { useEffect, useState } from 'react'
import { Check, Trash2 } from 'lucide-react'
import { invoiceService } from '@/services/invoiceService'
import toast from 'react-hot-toast'
import type { Invoice } from '@/types'
import LoadingSpinner from '../components/LoadingSpinner'

export default function Invoices() {
  const [invoices, setInvoices] = useState<Invoice[]>([])
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    loadInvoices()
  }, [])

  const loadInvoices = async () => {
    try {
      const result = await invoiceService.getAll()
      if (result.success) {
        setInvoices(result.invoices)
      }
    } catch (error) {
      toast.error('Erro ao carregar faturas')
    } finally {
      setLoading(false)
    }
  }

  const handleMarkAsPaid = async (id: string) => {
    try {
      await invoiceService.markAsPaid(id)
      toast.success('Fatura marcada como paga!')
      loadInvoices()
    } catch (error) {
      toast.error('Erro ao marcar fatura como paga')
    }
  }

  const handleDelete = async (id: string) => {
    if (window.confirm('Tem certeza que deseja excluir esta fatura?')) {
      try {
        await invoiceService.delete(id)
        toast.success('Fatura excluída!')
        loadInvoices()
      } catch (error) {
        toast.error('Erro ao excluir fatura')
      }
    }
  }

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white">Faturas</h1>
        <p className="text-sm md:text-base text-gray-600 dark:text-gray-400 mt-1">Gerencie as faturas dos clientes</p>
      </div>

      <div className="bg-white/80 dark:bg-gray-800/50 backdrop-blur-sm rounded-xl shadow-sm border border-gray-200 dark:border-gray-700/50">
        <div className="overflow-x-auto">
          <table className="w-full">
            <thead className="bg-gray-50 dark:bg-gray-700">
              <tr>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Cliente</th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Descrição</th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Valor</th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Vencimento</th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Status</th>
                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Ações</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-200 dark:divide-gray-700">
              {loading ? (
                <tr><td colSpan={6} className="px-6 py-4"><LoadingSpinner /></td></tr>
              ) : invoices.length === 0 ? (
                <tr><td colSpan={6} className="px-6 py-4 text-center">Nenhuma fatura encontrada</td></tr>
              ) : (
                invoices.map((invoice) => (
                  <tr key={invoice.id} className="hover:bg-gray-50 dark:hover:bg-gray-700">
                    <td className="px-6 py-4 text-sm text-gray-900 dark:text-white">{invoice.client_name}</td>
                    <td className="px-6 py-4 text-sm text-gray-900 dark:text-white">{invoice.description}</td>
                    <td className="px-6 py-4 text-sm text-gray-900 dark:text-white">R$ {Number(invoice.final_value || invoice.value || 0).toFixed(2)}</td>
                    <td className="px-6 py-4 text-sm text-gray-900 dark:text-white">{new Date(invoice.due_date).toLocaleDateString('pt-BR')}</td>
                    <td className="px-6 py-4">
                      <span className={`px-2 py-1 text-xs font-medium rounded-full ${
                        invoice.status === 'paid' ? 'bg-green-100 text-green-800' :
                        invoice.status === 'overdue' ? 'bg-red-100 text-red-800' :
                        'bg-yellow-100 text-yellow-800'
                      }`}>
                        {invoice.status === 'paid' ? 'Paga' : invoice.status === 'overdue' ? 'Vencida' : 'Pendente'}
                      </span>
                    </td>
                    <td className="px-6 py-4 text-right">
                      <div className="flex items-center justify-end gap-2">
                        {invoice.status !== 'paid' && (
                          <button onClick={() => handleMarkAsPaid(invoice.id)} className="p-2 text-green-600 hover:bg-green-50 rounded-lg">
                            <Check className="w-4 h-4" />
                          </button>
                        )}
                        <button onClick={() => handleDelete(invoice.id)} className="p-2 text-red-600 hover:bg-red-50 rounded-lg">
                          <Trash2 className="w-4 h-4" />
                        </button>
                      </div>
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  )
}
