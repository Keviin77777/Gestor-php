import { useState, useEffect } from 'react'
import { Bell, Send, CheckCircle, XCircle, Loader2, RefreshCw } from 'lucide-react'
import api from '../../services/api'
import toast from 'react-hot-toast'

interface Notification {
    id: number
    recipient_id: number
    recipient_name: string
    recipient_phone: string
    message: string
    message_type: string
    sent_at: string
    status: 'sent' | 'failed' | 'pending'
    error_message?: string
}

import { usePageTitle } from '@/hooks/usePageTitle'

export default function ResellerNotifications() {
    usePageTitle('Notificações')
    const [notifications, setNotifications] = useState<Notification[]>([])
    const [loading, setLoading] = useState(true)
    const [sending, setSending] = useState(false)

    useEffect(() => {
        loadNotifications()
    }, [])

    const loadNotifications = async () => {
        try {
            setLoading(true)
            const response = await api.get('/api-reseller-notifications.php')
            if (response.data.success) {
                setNotifications(response.data.notifications || [])
            }
        } catch (error: any) {
            toast.error('Erro ao carregar notificações')
        } finally {
            setLoading(false)
        }
    }

    const runAutomation = async () => {
        if (!confirm('Deseja executar a automação de lembretes agora?\n\nIsso enviará mensagens para todos os revendedores elegíveis.')) {
            return
        }

        try {
            setSending(true)
            const response = await api.post('/api-reseller-notifications.php', {
                action: 'run_automation'
            })

            if (response.data.success) {
                toast.success(`Automação executada! ${response.data.sent} mensagens enviadas`)
                loadNotifications()
            }
        } catch (error: any) {
            toast.error(error.response?.data?.error || 'Erro ao executar automação')
        } finally {
            setSending(false)
        }
    }

    const formatDate = (date: string) => {
        return new Date(date).toLocaleString('pt-BR')
    }

    const getStatusBadge = (status: string) => {
        const badges = {
            sent: { bg: 'bg-green-100 dark:bg-green-900/30', text: 'text-green-700 dark:text-green-400', icon: CheckCircle, label: 'Enviado' },
            failed: { bg: 'bg-red-100 dark:bg-red-900/30', text: 'text-red-700 dark:text-red-400', icon: XCircle, label: 'Falhou' },
            pending: { bg: 'bg-yellow-100 dark:bg-yellow-900/30', text: 'text-yellow-700 dark:text-yellow-400', icon: Loader2, label: 'Pendente' },
        }
        const badge = badges[status as keyof typeof badges] || badges.pending
        const Icon = badge.icon

        return (
            <span className={`inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-semibold ${badge.bg} ${badge.text}`}>
                <Icon className="w-3 h-3" />
                {badge.label}
            </span>
        )
    }

    if (loading) {
        return (
            <div className="flex items-center justify-center h-96">
                <Loader2 className="w-8 h-8 text-primary-600 animate-spin" />
            </div>
        )
    }

    return (
        <div className="space-y-6">
            {/* Header */}
            <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 className="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <Bell className="w-7 h-7 text-blue-600" />
                        Notificações de Revendedores
                    </h1>
                    <p className="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        Lembretes e cobranças automáticas via WhatsApp
                    </p>
                </div>
                <div className="flex gap-2">
                    <button
                        onClick={loadNotifications}
                        className="flex items-center gap-2 px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors"
                    >
                        <RefreshCw className="w-5 h-5" />
                        Atualizar
                    </button>
                    <button
                        onClick={runAutomation}
                        disabled={sending}
                        className="flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-lg hover:shadow-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <Send className="w-5 h-5" />
                        {sending ? 'Enviando...' : 'Executar Automação'}
                    </button>
                </div>
            </div>

            {/* Stats */}
            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div className="bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20 rounded-xl p-6 border border-green-200 dark:border-green-800">
                    <div className="flex items-center justify-between">
                        <div>
                            <p className="text-sm font-medium text-green-700 dark:text-green-400">Enviadas</p>
                            <p className="text-3xl font-bold text-green-900 dark:text-green-300 mt-1">
                                {notifications.filter(n => n.status === 'sent').length}
                            </p>
                        </div>
                        <CheckCircle className="w-12 h-12 text-green-600 dark:text-green-400 opacity-50" />
                    </div>
                </div>

                <div className="bg-gradient-to-br from-red-50 to-red-100 dark:from-red-900/20 dark:to-red-800/20 rounded-xl p-6 border border-red-200 dark:border-red-800">
                    <div className="flex items-center justify-between">
                        <div>
                            <p className="text-sm font-medium text-red-700 dark:text-red-400">Falhadas</p>
                            <p className="text-3xl font-bold text-red-900 dark:text-red-300 mt-1">
                                {notifications.filter(n => n.status === 'failed').length}
                            </p>
                        </div>
                        <XCircle className="w-12 h-12 text-red-600 dark:text-red-400 opacity-50" />
                    </div>
                </div>

                <div className="bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 rounded-xl p-6 border border-blue-200 dark:border-blue-800">
                    <div className="flex items-center justify-between">
                        <div>
                            <p className="text-sm font-medium text-blue-700 dark:text-blue-400">Total</p>
                            <p className="text-3xl font-bold text-blue-900 dark:text-blue-300 mt-1">
                                {notifications.length}
                            </p>
                        </div>
                        <Bell className="w-12 h-12 text-blue-600 dark:text-blue-400 opacity-50" />
                    </div>
                </div>
            </div>

            {/* Table */}
            <div className="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div className="overflow-x-auto">
                    <table className="w-full">
                        <thead className="bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-700">
                            <tr>
                                <th className="px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap">
                                    Revendedor
                                </th>
                                <th className="px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap">
                                    Telefone
                                </th>
                                <th className="px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap">
                                    Tipo
                                </th>
                                <th className="px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap">
                                    Status
                                </th>
                                <th className="px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap">
                                    Data
                                </th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-200 dark:divide-gray-700">
                            {notifications.length === 0 ? (
                                <tr>
                                    <td colSpan={5} className="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                        Nenhuma notificação enviada ainda
                                    </td>
                                </tr>
                            ) : (
                                notifications.map((notification) => (
                                    <tr key={notification.id} className="hover:bg-gray-50 dark:hover:bg-gray-900/50 transition-colors">
                                        <td className="px-6 py-4 whitespace-nowrap">
                                            <span className="font-medium text-gray-900 dark:text-white">
                                                {notification.recipient_name}
                                            </span>
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                                            {notification.recipient_phone}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap">
                                            <span className="text-sm text-gray-900 dark:text-white capitalize">
                                                {notification.message_type.replace('_', ' ')}
                                            </span>
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap">
                                            {getStatusBadge(notification.status)}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                                            {formatDate(notification.sent_at)}
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>
            </div>

            {/* Info Box */}
            <div className="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-4">
                <h3 className="font-semibold text-blue-900 dark:text-blue-300 mb-2">ℹ️ Como funciona</h3>
                <ul className="text-sm text-blue-800 dark:text-blue-400 space-y-1">
                    <li>• Lembretes são enviados 7, 3 e 1 dia antes do vencimento</li>
                    <li>• Cobranças são enviadas 1, 3 e 7 dias após o vencimento</li>
                    <li>• Apenas uma mensagem por dia é enviada para cada revendedor</li>
                    <li>• Configure um cron job para executar automaticamente: <code className="bg-blue-100 dark:bg-blue-900 px-2 py-1 rounded">php scripts/reseller-renewal-automation.php</code></li>
                </ul>
            </div>
        </div>
    )
}
