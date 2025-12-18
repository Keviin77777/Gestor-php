import api from './api';
import type { Invoice } from '@/types';

export interface InvoiceSummary {
  pending: { count: number; amount: number };
  paid: { count: number; amount: number };
  overdue: { count: number; amount: number };
  total: { count: number; amount: number };
}

export interface InvoicesResponse {
  success: boolean;
  invoices: Invoice[];
  summary: InvoiceSummary;
}

export const invoiceService = {
  // Listar todas as faturas
  async getAll(): Promise<InvoicesResponse> {
    const response = await api.get<InvoicesResponse>('/api-invoices.php');
    return response.data;
  },

  // Listar faturas de um cliente específico
  async getByClient(clientId: string): Promise<InvoicesResponse> {
    const response = await api.get<InvoicesResponse>(`/api-invoices.php?client_id=${clientId}`);
    return response.data;
  },

  // Marcar fatura como paga
  async markAsPaid(invoiceId: string): Promise<{ success: boolean; message: string; client_renewed?: boolean }> {
    const response = await api.put(`/api-invoices.php?id=${invoiceId}&action=mark-paid`);
    return response.data;
  },

  // Desmarcar fatura como paga
  async unmarkAsPaid(invoiceId: string): Promise<{ success: boolean; message: string }> {
    const response = await api.put(`/api-invoices.php?id=${invoiceId}&action=unmark-paid`);
    return response.data;
  },

  // Excluir fatura
  async delete(invoiceId: string): Promise<{ success: boolean; message: string }> {
    const response = await api.delete(`/api-invoices.php?id=${invoiceId}`);
    return response.data;
  },

  // Criar fatura
  async create(invoice: {
    client_id: string;
    description: string;
    value: number;
    discount: number;
    due_date: string;
  }): Promise<{ success: boolean; message: string; invoice_id?: string }> {
    const response = await api.post('/api-invoices.php', invoice);
    return response.data;
  },

  // Atualizar fatura
  async update(
    invoiceId: string,
    invoice: {
      client_id: string;
      description: string;
      value: number;
      discount: number;
      due_date: string;
    }
  ): Promise<{ success: boolean; message: string }> {
    const response = await api.put(`/api-invoices.php?id=${invoiceId}`, invoice);
    return response.data;
  },
};
