import { X, Calendar, DollarSign, User, FileText, Clock, CheckCircle, AlertCircle, Copy } from 'lucide-react';
import { Invoice } from '../services/invoiceService';
import toast from 'react-hot-toast';

interface InvoiceViewModalProps {
  isOpen: boolean;
  onClose: () => void;
  invoice: Invoice | null;
}

export default function InvoiceViewModal({ isOpen, onClose, invoice }: InvoiceViewModalProps) {
  if (!isOpen || !invoice) return null;

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

  const formatDateTime = (dateString: string) => {
    if (!dateString) return 'N/A';
    
    const date = new Date(dateString);
    return date.toLocaleString('pt-BR', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    });
  };

  const statusConfig = {
    pending: {
      icon: <Clock className="w-5 h-5" />,
      label: 'Pendente',
      bgColor: 'bg-amber-100 dark:bg-amber-500/20',
      textColor: 'text-amber-700 dark:text-amber-400',
      borderColor: 'border-amber-200 dark:border-amber-500/30',
    },
    paid: {
      icon: <CheckCircle className="w-5 h-5" />,
      label: 'Paga',
      bgColor: 'bg-green-100 dark:bg-green-500/20',
      textColor: 'text-green-700 dark:text-green-400',
      borderColor: 'border-green-200 dark:border-green-500/30',
    },
    overdue: {
      icon: <AlertCircle className="w-5 h-5" />,
      label: 'Vencida',
      bgColor: 'bg-red-100 dark:bg-red-500/20',
      textColor: 'text-red-700 dark:text-red-400',
      borderColor: 'border-red-200 dark:border-red-500/30',
    },
  };

  const status = statusConfig[invoice.status];

  return (
    <div className="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 p-4">
      <div className="bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto border border-gray-200 dark:border-gray-700">
        {/* Header */}
        <div className="flex items-center justify-between p-6 border-b border-gray-200 dark:border-gray-700">
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 rounded-lg bg-indigo-100 dark:bg-indigo-500/20 flex items-center justify-center">
              <FileText className="w-5 h-5 text-indigo-600 dark:text-indigo-400" />
            </div>
            <div>
              <h2 className="text-xl font-bold text-gray-900 dark:text-white">
                Fatura #{invoice.id}
              </h2>
              <p className="text-sm text-gray-500 dark:text-gray-400">
                Detalhes da fatura
              </p>
            </div>
          </div>
          <button
            onClick={onClose}
            className="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200"
          >
            <X className="w-5 h-5" />
          </button>
        </div>

        {/* Content */}
        <div className="p-6 space-y-6">
          {/* Status Badge */}
          <div className="flex justify-center">
            <div className={`inline-flex items-center gap-2 px-4 py-2 rounded-lg border ${status.bgColor} ${status.textColor} ${status.borderColor}`}>
              {status.icon}
              <span className="font-semibold">{status.label}</span>
            </div>
          </div>

          {/* Cliente */}
          <div className="bg-gray-50 dark:bg-gray-900/50 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
            <div className="flex items-start gap-3">
              <div className="w-10 h-10 rounded-lg bg-cyan-100 dark:bg-cyan-500/20 flex items-center justify-center flex-shrink-0">
                <User className="w-5 h-5 text-cyan-600 dark:text-cyan-400" />
              </div>
              <div className="flex-1">
                <p className="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">
                  Cliente
                </p>
                <p className="text-lg font-semibold text-gray-900 dark:text-white">
                  {invoice.client_name}
                </p>
              </div>
            </div>
          </div>

          {/* Valores */}
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            {/* Valor */}
            <div className="bg-gray-50 dark:bg-gray-900/50 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
              <div className="flex items-start gap-3">
                <div className="w-10 h-10 rounded-lg bg-green-100 dark:bg-green-500/20 flex items-center justify-center flex-shrink-0">
                  <DollarSign className="w-5 h-5 text-green-600 dark:text-green-400" />
                </div>
                <div className="flex-1">
                  <p className="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">
                    Valor Total
                  </p>
                  <p className="text-xl font-bold text-green-600 dark:text-green-400">
                    {formatCurrency(invoice.final_value)}
                  </p>
                </div>
              </div>
            </div>

            {/* Vencimento */}
            <div className="bg-gray-50 dark:bg-gray-900/50 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
              <div className="flex items-start gap-3">
                <div className={`w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0 ${
                  invoice.status === 'overdue' 
                    ? 'bg-red-100 dark:bg-red-500/20' 
                    : 'bg-blue-100 dark:bg-blue-500/20'
                }`}>
                  <Calendar className={`w-5 h-5 ${
                    invoice.status === 'overdue'
                      ? 'text-red-600 dark:text-red-400'
                      : 'text-blue-600 dark:text-blue-400'
                  }`} />
                </div>
                <div className="flex-1">
                  <p className="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">
                    Vencimento
                  </p>
                  <p className={`text-lg font-semibold ${
                    invoice.status === 'overdue'
                      ? 'text-red-600 dark:text-red-400'
                      : 'text-gray-900 dark:text-white'
                  }`}>
                    {formatDate(invoice.due_date)}
                  </p>
                </div>
              </div>
            </div>
          </div>

          {/* Data de Pagamento (se paga) */}
          {invoice.payment_date && (
            <div className="bg-green-50 dark:bg-green-900/20 rounded-lg p-4 border border-green-200 dark:border-green-700">
              <div className="flex items-start gap-3">
                <div className="w-10 h-10 rounded-lg bg-green-100 dark:bg-green-500/20 flex items-center justify-center flex-shrink-0">
                  <CheckCircle className="w-5 h-5 text-green-600 dark:text-green-400" />
                </div>
                <div className="flex-1">
                  <p className="text-xs font-medium text-green-600 dark:text-green-400 uppercase tracking-wider mb-1">
                    Data de Pagamento
                  </p>
                  <p className="text-lg font-semibold text-green-700 dark:text-green-300">
                    {formatDateTime(invoice.payment_date)}
                  </p>
                </div>
              </div>
            </div>
          )}

          {/* Link de Pagamento */}
          {invoice.status !== 'paid' && (
            <div className="bg-indigo-50 dark:bg-indigo-900/20 rounded-lg p-4 border border-indigo-200 dark:border-indigo-700">
              <div className="flex items-start gap-3">
                <div className="w-10 h-10 rounded-lg bg-indigo-100 dark:bg-indigo-500/20 flex items-center justify-center flex-shrink-0">
                  <FileText className="w-5 h-5 text-indigo-600 dark:text-indigo-400" />
                </div>
                <div className="flex-1 min-w-0">
                  <p className="text-xs font-medium text-indigo-600 dark:text-indigo-400 uppercase tracking-wider mb-2">
                    Link de Pagamento
                  </p>
                  <div className="flex items-center gap-2">
                    <input
                      type="text"
                      readOnly
                      value={invoice.payment_link || `${import.meta.env.VITE_API_URL || 'http://localhost:8000'}/checkout.php?invoice=${invoice.id}`}
                      className="flex-1 px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-700 dark:text-gray-300 font-mono"
                    />
                    <button
                      onClick={() => {
                        const link = invoice.payment_link || `${import.meta.env.VITE_API_URL || 'http://localhost:8000'}/checkout.php?invoice=${invoice.id}`;
                        navigator.clipboard.writeText(link).then(() => {
                          toast.success('Link copiado para a área de transferência!', {
                            duration: 3000,
                            position: 'top-right',
                            icon: '📋',
                          });
                        }).catch(() => {
                          toast.error('Erro ao copiar link', {
                            duration: 3000,
                            position: 'top-right',
                          });
                        });
                      }}
                      className="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-medium rounded-lg transition-colors whitespace-nowrap flex items-center gap-2"
                    >
                      <Copy className="w-4 h-4" />
                      Copiar Link
                    </button>
                  </div>
                  <p className="text-xs text-gray-500 dark:text-gray-400 mt-2">
                    Compartilhe este link com o cliente para realizar o pagamento
                  </p>
                </div>
              </div>
            </div>
          )}

          {/* Informações Adicionais */}
          <div className="bg-gray-50 dark:bg-gray-900/50 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
            <h3 className="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">
              Informações Adicionais
            </h3>
            <div className="space-y-2">
              <div className="flex justify-between items-center py-2 border-b border-gray-200 dark:border-gray-700">
                <span className="text-sm text-gray-600 dark:text-gray-400">ID da Fatura</span>
                <span className="text-sm font-medium text-gray-900 dark:text-white">#{invoice.id}</span>
              </div>
              <div className="flex justify-between items-center py-2 border-b border-gray-200 dark:border-gray-700">
                <span className="text-sm text-gray-600 dark:text-gray-400">Data de Criação</span>
                <span className="text-sm font-medium text-gray-900 dark:text-white">
                  {formatDateTime(invoice.created_at)}
                </span>
              </div>
              {invoice.updated_at && (
                <div className="flex justify-between items-center py-2">
                  <span className="text-sm text-gray-600 dark:text-gray-400">Última Atualização</span>
                  <span className="text-sm font-medium text-gray-900 dark:text-white">
                    {formatDateTime(invoice.updated_at)}
                  </span>
                </div>
              )}
            </div>
          </div>
        </div>

        {/* Footer */}
        <div className="flex gap-3 p-6 border-t border-gray-200 dark:border-gray-700">
          <button
            onClick={onClose}
            className="flex-1 px-4 py-2 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 font-medium rounded-lg transition-colors"
          >
            Fechar
          </button>
        </div>
      </div>
    </div>
  );
}
