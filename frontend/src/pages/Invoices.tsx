import { useState, useEffect } from 'react';
import { invoiceService, InvoiceSummary } from '../services/invoiceService';
import type { Invoice } from '@/types';
import InvoiceModal, { InvoiceFormData } from '../components/InvoiceModal';
import InvoiceViewModal from '../components/InvoiceViewModal';
import LoadingSpinner from '../components/LoadingSpinner';
import toast from 'react-hot-toast';
import {
  FileText,
  Clock,
  CheckCircle,
  AlertCircle,
  TrendingUp,
  Eye,
  Edit,
  Trash2,
  Download,
  Plus,
  Search,
} from 'lucide-react';

type FilterStatus = 'all' | 'pending' | 'paid' | 'overdue';

export default function Invoices() {
  const [invoices, setInvoices] = useState<Invoice[]>([]);
  const [filteredInvoices, setFilteredInvoices] = useState<Invoice[]>([]);
  const [summary, setSummary] = useState<InvoiceSummary>({
    pending: { count: 0, amount: 0 },
    paid: { count: 0, amount: 0 },
    overdue: { count: 0, amount: 0 },
    total: { count: 0, amount: 0 },
  });
  const [loading, setLoading] = useState(true);
  const [filterStatus, setFilterStatus] = useState<FilterStatus>('all');
  const [searchTerm, setSearchTerm] = useState('');
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [editingInvoice, setEditingInvoice] = useState<InvoiceFormData | null>(null);
  const [isViewModalOpen, setIsViewModalOpen] = useState(false);
  const [viewingInvoice, setViewingInvoice] = useState<Invoice | null>(null);

  useEffect(() => {
    loadInvoices();
  }, []);

  useEffect(() => {
    filterInvoices();
  }, [invoices, filterStatus, searchTerm]);

  const loadInvoices = async () => {
    try {
      setLoading(true);
      const data = await invoiceService.getAll();
      
      // Calcular status overdue no frontend
      const invoicesWithStatus = data.invoices.map(invoice => {
        const dueDate = new Date(invoice.due_date);
        const isOverdue = dueDate < new Date() && invoice.status === 'pending';
        return {
          ...invoice,
          status: isOverdue ? 'overdue' as const : invoice.status,
        };
      });

      setInvoices(invoicesWithStatus);
      
      // Recalcular summary com status overdue
      const newSummary = { ...data.summary };
      const overdueInvoices = invoicesWithStatus.filter(inv => inv.status === 'overdue');
      newSummary.overdue = {
        count: overdueInvoices.length,
        amount: overdueInvoices.reduce((sum, inv) => sum + inv.final_value, 0),
      };
      newSummary.pending.count -= overdueInvoices.length;
      newSummary.pending.amount -= newSummary.overdue.amount;
      
      setSummary(newSummary);
    } catch (error) {
      console.error('Erro ao carregar faturas:', error);
      toast.error('Erro ao carregar faturas');
    } finally {
      setLoading(false);
    }
  };

  const filterInvoices = () => {
    let filtered = [...invoices];

    // Filtrar por status
    if (filterStatus !== 'all') {
      filtered = filtered.filter(inv => inv.status === filterStatus);
    }

    // Filtrar por busca
    if (searchTerm) {
      const term = searchTerm.toLowerCase();
      filtered = filtered.filter(
        inv =>
          inv.id.toLowerCase().includes(term) ||
          inv.client_name.toLowerCase().includes(term)
      );
    }

    setFilteredInvoices(filtered);
  };

  const handleMarkAsPaid = async (invoiceId: string) => {
    if (!confirm('Tem certeza que deseja marcar esta fatura como paga?')) return;

    const loadingToast = toast.loading('Marcando fatura como paga...');
    try {
      const result = await invoiceService.markAsPaid(invoiceId);
      
      if (result.client_renewed) {
        toast.success('Fatura paga! Cliente renovado e WhatsApp enviado! 🎉', { 
          id: loadingToast,
          duration: 5000 
        });
      } else {
        toast.success(result.message, { id: loadingToast });
      }
      
      loadInvoices();
    } catch (error) {
      console.error('Erro ao marcar fatura como paga:', error);
      toast.error('Erro ao marcar fatura como paga', { id: loadingToast });
    }
  };

  const handleDelete = async (invoiceId: string) => {
    if (!confirm('Tem certeza que deseja excluir esta fatura? Esta ação não pode ser desfeita.')) return;

    const loadingToast = toast.loading('Excluindo fatura...');
    try {
      await invoiceService.delete(invoiceId);
      toast.success('Fatura excluída com sucesso!', { id: loadingToast });
      loadInvoices();
    } catch (error) {
      console.error('Erro ao excluir fatura:', error);
      toast.error('Erro ao excluir fatura', { id: loadingToast });
    }
  };

  const handleOpenModal = (invoice?: Invoice) => {
    if (invoice) {
      setEditingInvoice({
        id: invoice.id,
        client_id: invoice.client_id,
        description: '',
        value: invoice.final_value,
        discount: 0,
        due_date: invoice.due_date,
      });
    } else {
      setEditingInvoice(null);
    }
    setIsModalOpen(true);
  };

  const handleCloseModal = () => {
    setIsModalOpen(false);
    setEditingInvoice(null);
  };

  const handleSaveInvoice = async (invoiceData: InvoiceFormData) => {
    const loadingToast = toast.loading(invoiceData.id ? 'Atualizando fatura...' : 'Criando fatura...');
    try {
      if (invoiceData.id) {
        await invoiceService.update(invoiceData.id, invoiceData);
        toast.success('Fatura atualizada com sucesso!', { id: loadingToast });
      } else {
        await invoiceService.create(invoiceData);
        toast.success('Fatura criada com sucesso!', { id: loadingToast });
      }
      handleCloseModal();
      loadInvoices();
    } catch (error) {
      console.error('Erro ao salvar fatura:', error);
      toast.error('Erro ao salvar fatura', { id: loadingToast });
    }
  };

  const handleViewInvoice = (invoice: Invoice) => {
    setViewingInvoice(invoice);
    setIsViewModalOpen(true);
  };

  const handleCloseViewModal = () => {
    setIsViewModalOpen(false);
    setViewingInvoice(null);
  };

  const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('pt-BR', {
      style: 'currency',
      currency: 'BRL',
    }).format(value);
  };

  const formatDate = (dateString: string) => {
    if (!dateString) return 'N/A';
    
    if (dateString.includes('/')) return dateString;
    
    if (dateString.includes('-')) {
      const [year, month, day] = dateString.split(' ')[0].split('-');
      return `${day}/${month}/${year}`;
    }
    
    return new Date(dateString).toLocaleDateString('pt-BR');
  };

  if (loading) {
    return <LoadingSpinner />;
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl md:text-3xl font-bold bg-gradient-to-r from-primary-600 to-purple-600 bg-clip-text text-transparent">
            Faturas
          </h1>
          <p className="text-sm md:text-base text-gray-600 dark:text-gray-400 mt-1">
            Gerencie as faturas dos seus clientes
          </p>
        </div>
        <button className="px-4 py-2 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 font-medium rounded-lg transition-colors flex items-center gap-2 border border-gray-300 dark:border-gray-600">
          <Download className="w-4 h-4" />
          Exportar
        </button>
      </div>

      {/* Summary Cards */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        {/* Card 1 - Faturas Pendentes */}
        <div className="bg-white/80 dark:bg-gray-800/50 backdrop-blur-sm border border-gray-200 dark:border-gray-700/50 rounded-xl p-4 hover:border-gray-300 dark:hover:border-gray-600 transition-all shadow-sm hover:shadow-md">
          <div className="flex items-center justify-between">
            <div className="flex-1">
              <p className="text-gray-600 dark:text-gray-400 text-xs mb-1">Faturas Pendentes</p>
              <p className="text-gray-900 dark:text-white text-2xl font-bold">{summary.pending.count}</p>
              <p className="text-amber-600 dark:text-amber-400 text-xs mt-1 font-semibold">
                {formatCurrency(summary.pending.amount)}
              </p>
            </div>
            <div className="w-12 h-12 rounded-full bg-amber-100 dark:bg-amber-500/20 flex items-center justify-center">
              <Clock className="w-6 h-6 text-amber-600 dark:text-amber-400" />
            </div>
          </div>
        </div>

        {/* Card 2 - Faturas Pagas */}
        <div className="bg-white/80 dark:bg-gray-800/50 backdrop-blur-sm border border-gray-200 dark:border-gray-700/50 rounded-xl p-4 hover:border-gray-300 dark:hover:border-gray-600 transition-all shadow-sm hover:shadow-md">
          <div className="flex items-center justify-between">
            <div className="flex-1">
              <p className="text-gray-600 dark:text-gray-400 text-xs mb-1">Faturas Pagas</p>
              <p className="text-gray-900 dark:text-white text-2xl font-bold">{summary.paid.count}</p>
              <p className="text-green-600 dark:text-green-400 text-xs mt-1 font-semibold">
                {formatCurrency(summary.paid.amount)}
              </p>
            </div>
            <div className="w-12 h-12 rounded-full bg-green-100 dark:bg-green-500/20 flex items-center justify-center">
              <CheckCircle className="w-6 h-6 text-green-600 dark:text-green-400" />
            </div>
          </div>
        </div>

        {/* Card 3 - Faturas Vencidas */}
        <div className="bg-white/80 dark:bg-gray-800/50 backdrop-blur-sm border border-gray-200 dark:border-gray-700/50 rounded-xl p-4 hover:border-gray-300 dark:hover:border-gray-600 transition-all shadow-sm hover:shadow-md">
          <div className="flex items-center justify-between">
            <div className="flex-1">
              <p className="text-gray-600 dark:text-gray-400 text-xs mb-1">Faturas Vencidas</p>
              <p className="text-gray-900 dark:text-white text-2xl font-bold">{summary.overdue.count}</p>
              <p className="text-red-600 dark:text-red-400 text-xs mt-1 font-semibold">
                {formatCurrency(summary.overdue.amount)}
              </p>
            </div>
            <div className="w-12 h-12 rounded-full bg-red-100 dark:bg-red-500/20 flex items-center justify-center">
              <AlertCircle className="w-6 h-6 text-red-600 dark:text-red-400" />
            </div>
          </div>
        </div>

        {/* Card 4 - Receita Total */}
        <div className="bg-white/80 dark:bg-gray-800/50 backdrop-blur-sm border border-gray-200 dark:border-gray-700/50 rounded-xl p-4 hover:border-gray-300 dark:hover:border-gray-600 transition-all shadow-sm hover:shadow-md">
          <div className="flex items-center justify-between">
            <div className="flex-1">
              <p className="text-gray-600 dark:text-gray-400 text-xs mb-1">Receita Total</p>
              <p className="text-gray-900 dark:text-white text-2xl font-bold">{summary.total.count}</p>
              <p className="text-indigo-600 dark:text-indigo-400 text-xs mt-1 font-semibold">
                {formatCurrency(summary.total.amount)}
              </p>
            </div>
            <div className="w-12 h-12 rounded-full bg-indigo-100 dark:bg-indigo-500/20 flex items-center justify-center">
              <TrendingUp className="w-6 h-6 text-indigo-600 dark:text-indigo-400" />
            </div>
          </div>
        </div>
      </div>

      {/* Filters & Search */}
      <div className="bg-white/80 dark:bg-gray-800/50 backdrop-blur-sm border border-gray-200 dark:border-gray-700/50 rounded-xl p-4 shadow-sm">
        <div className="flex flex-col sm:flex-row gap-4 items-start sm:items-center justify-between">
          <div className="flex flex-wrap gap-2">
            <button
              onClick={() => setFilterStatus('all')}
              className={`px-4 py-2 rounded-lg text-sm font-medium transition-all ${
                filterStatus === 'all'
                  ? 'bg-indigo-600 text-white shadow-md'
                  : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'
              }`}
            >
              Todas
            </button>
            <button
              onClick={() => setFilterStatus('pending')}
              className={`px-4 py-2 rounded-lg text-sm font-medium transition-all ${
                filterStatus === 'pending'
                  ? 'bg-indigo-600 text-white shadow-md'
                  : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'
              }`}
            >
              Em Aberto
            </button>
            <button
              onClick={() => setFilterStatus('paid')}
              className={`px-4 py-2 rounded-lg text-sm font-medium transition-all ${
                filterStatus === 'paid'
                  ? 'bg-indigo-600 text-white shadow-md'
                  : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'
              }`}
            >
              Pagas
            </button>
            <button
              onClick={() => setFilterStatus('overdue')}
              className={`px-4 py-2 rounded-lg text-sm font-medium transition-all ${
                filterStatus === 'overdue'
                  ? 'bg-indigo-600 text-white shadow-md'
                  : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'
              }`}
            >
              Vencidas
            </button>
          </div>

          <div className="flex gap-2 w-full sm:w-auto">
            <div className="relative flex-1 sm:flex-initial">
              <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 dark:text-gray-500" />
              <input
                type="text"
                placeholder="Buscar faturas..."
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                className="w-full sm:w-64 px-4 py-2 pl-10 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400 focus:border-transparent transition-colors"
              />
            </div>
            <button 
              onClick={() => handleOpenModal()}
              className="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-medium rounded-lg transition-colors flex items-center gap-2 whitespace-nowrap shadow-lg shadow-indigo-500/30"
            >
              <Plus className="w-4 h-4" />
              Nova Fatura
            </button>
          </div>
        </div>
      </div>

      {/* Invoices Table */}
      <div className="bg-white/80 dark:bg-gray-800/50 backdrop-blur-sm border border-gray-200 dark:border-gray-700/50 rounded-xl overflow-hidden shadow-sm">
        {filteredInvoices.length === 0 ? (
          <div className="text-center py-12">
            <div className="flex flex-col items-center gap-4">
              <div className="w-16 h-16 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                <FileText className="w-8 h-8 text-gray-400 dark:text-gray-500" />
              </div>
              <div>
                <h3 className="text-lg font-bold text-gray-900 dark:text-white mb-2">
                  Nenhuma fatura encontrada
                </h3>
                <p className="text-gray-600 dark:text-gray-400">
                  {searchTerm || filterStatus !== 'all'
                    ? 'Tente ajustar os filtros de busca'
                    : 'Crie sua primeira fatura para começar'}
                </p>
              </div>
              {!searchTerm && filterStatus === 'all' && (
                <button 
                  onClick={() => handleOpenModal()}
                  className="btn-primary flex items-center gap-2 mt-2"
                >
                  <Plus className="w-4 h-4" />
                  Nova Fatura
                </button>
              )}
            </div>
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full">
              <thead className="bg-gray-50 dark:bg-gray-900/50">
                <tr className="border-b border-gray-200 dark:border-gray-700">
                  <th className="text-left py-3 px-4 text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">ID</th>
                  <th className="text-left py-3 px-4 text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Cliente</th>
                  <th className="text-left py-3 px-4 text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Valor</th>
                  <th className="text-left py-3 px-4 text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Vencimento</th>
                  <th className="text-left py-3 px-4 text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Status</th>
                  <th className="text-right py-3 px-4 text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Ações</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-200 dark:divide-gray-700">
                {filteredInvoices.map((invoice) => (
                  <tr key={invoice.id} className="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                    <td className="py-3 px-4">
                      <span className="text-sm font-medium text-gray-700 dark:text-gray-300">#{invoice.id}</span>
                    </td>
                    <td className="py-3 px-4">
                      <span className="text-sm font-medium text-gray-900 dark:text-white">{invoice.client_name}</span>
                    </td>
                    <td className="py-3 px-4">
                      <span className="text-sm font-bold text-green-600 dark:text-green-400">
                        {formatCurrency(invoice.final_value)}
                      </span>
                    </td>
                    <td className="py-3 px-4">
                      <span className={`text-sm font-medium ${
                        invoice.status === 'overdue' 
                          ? 'text-red-600 dark:text-red-400 font-bold' 
                          : 'text-gray-700 dark:text-gray-300'
                      }`}>
                        {formatDate(invoice.due_date)}
                      </span>
                    </td>
                    <td className="py-3 px-4">
                      {invoice.status === 'paid' && (
                        <span className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-green-100 dark:bg-green-500/20 text-green-700 dark:text-green-400">
                          <CheckCircle className="w-3.5 h-3.5" />
                          Paga
                        </span>
                      )}
                      {invoice.status === 'pending' && (
                        <span className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-amber-100 dark:bg-amber-500/20 text-amber-700 dark:text-amber-400">
                          <Clock className="w-3.5 h-3.5" />
                          Pendente
                        </span>
                      )}
                      {invoice.status === 'overdue' && (
                        <span className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-red-100 dark:bg-red-500/20 text-red-700 dark:text-red-400">
                          <AlertCircle className="w-3.5 h-3.5" />
                          Vencida
                        </span>
                      )}
                    </td>
                    <td className="py-3 px-4">
                      <div className="flex items-center justify-end gap-2">
                        <button
                          onClick={() => handleViewInvoice(invoice)}
                          className="p-2 rounded-lg bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 transition-colors"
                          title="Visualizar"
                        >
                          <Eye className="w-4 h-4" />
                        </button>
                        {(invoice.status === 'pending' || invoice.status === 'overdue') && (
                          <button
                            onClick={() => handleMarkAsPaid(invoice.id)}
                            className="p-2 rounded-lg bg-green-600 hover:bg-green-500 text-white transition-colors"
                            title="Marcar como Paga"
                          >
                            <CheckCircle className="w-4 h-4" />
                          </button>
                        )}
                        <button
                          onClick={() => handleOpenModal(invoice)}
                          className="p-2 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white transition-colors"
                          title="Editar"
                        >
                          <Edit className="w-4 h-4" />
                        </button>
                        <button
                          onClick={() => handleDelete(invoice.id)}
                          className="p-2 rounded-lg bg-red-600 hover:bg-red-500 text-white transition-colors"
                          title="Excluir"
                        >
                          <Trash2 className="w-4 h-4" />
                        </button>
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>

      {/* Modals */}
      <InvoiceModal
        isOpen={isModalOpen}
        onClose={handleCloseModal}
        onSave={handleSaveInvoice}
        invoice={editingInvoice}
      />

      <InvoiceViewModal
        isOpen={isViewModalOpen}
        onClose={handleCloseViewModal}
        invoice={viewingInvoice}
      />
    </div>
  );
}
