import { X } from 'lucide-react'
import { useState, useEffect } from 'react'
import type { Client } from '@/types'

interface ClientModalProps {
  isOpen: boolean
  onClose: () => void
  onSave: (client: Partial<Client>) => Promise<void>
  client?: Client | null
}

export default function ClientModal({ isOpen, onClose, onSave, client }: ClientModalProps) {
  const [formData, setFormData] = useState<Partial<Client>>({
    name: '',
    email: '',
    phone: '',
    username: '',
    password: '',
    plan: 'Personalizado',
    value: 0,
    renewal_date: '',
    server: '',
    mac: '',
    screens: 1,
    notifications: 'sim',
    notes: '',
  })
  const [loading, setLoading] = useState(false)

  useEffect(() => {
    if (client) {
      setFormData(client)
    } else {
      setFormData({
        name: '',
        email: '',
        phone: '',
        username: '',
        password: '',
        plan: 'Personalizado',
        value: 0,
        renewal_date: '',
        server: '',
        mac: '',
        screens: 1,
        notifications: 'sim',
        notes: '',
      })
    }
  }, [client, isOpen])

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    setLoading(true)
    try {
      await onSave(formData)
      onClose()
    } finally {
      setLoading(false)
    }
  }

  if (!isOpen) return null

  return (
    <div className="fixed inset-0 z-50 overflow-y-auto">
      <div className="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div className="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75 dark:bg-gray-900 dark:bg-opacity-75" onClick={onClose} />
        
        <div className="inline-block w-full max-w-4xl my-8 overflow-hidden text-left align-middle transition-all transform bg-white/60 dark:bg-gray-800/30 backdrop-blur-md shadow-xl rounded-2xl border border-gray-200/50 dark:border-gray-700/50">
          <div className="flex items-center justify-between p-6 border-b border-gray-200 dark:border-gray-700/50">
            <h3 className="text-2xl font-bold text-gray-900 dark:text-white">
              {client ? 'Editar Cliente' : 'Novo Cliente'}
            </h3>
            <button onClick={onClose} className="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700">
              <X className="w-6 h-6" />
            </button>
          </div>

          <form onSubmit={handleSubmit} className="p-6">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label className="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">Nome *</label>
                <input type="text" required value={formData.name} onChange={(e) => setFormData({ ...formData, name: e.target.value })} className="w-full px-4 py-2.5 rounded-lg border border-gray-200 dark:border-gray-700/50 bg-white/80 dark:bg-gray-900/30 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all" />
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">Email</label>
                <input type="email" value={formData.email} onChange={(e) => setFormData({ ...formData, email: e.target.value })} className="w-full px-4 py-2.5 rounded-lg border border-gray-200 dark:border-gray-700/50 bg-white/80 dark:bg-gray-900/30 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all" />
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">WhatsApp</label>
                <input type="tel" value={formData.phone} onChange={(e) => setFormData({ ...formData, phone: e.target.value })} className="w-full px-4 py-2.5 rounded-lg border border-gray-200 dark:border-gray-700/50 bg-white/80 dark:bg-gray-900/30 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all" />
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">Usuário IPTV</label>
                <input type="text" value={formData.username} onChange={(e) => setFormData({ ...formData, username: e.target.value })} className="w-full px-4 py-2.5 rounded-lg border border-gray-200 dark:border-gray-700/50 bg-white/80 dark:bg-gray-900/30 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all" />
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">Senha IPTV</label>
                <input type="text" value={formData.password} onChange={(e) => setFormData({ ...formData, password: e.target.value })} className="w-full px-4 py-2.5 rounded-lg border border-gray-200 dark:border-gray-700/50 bg-white/80 dark:bg-gray-900/30 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all" />
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">Plano</label>
                <input type="text" value={formData.plan} onChange={(e) => setFormData({ ...formData, plan: e.target.value })} className="w-full px-4 py-2.5 rounded-lg border border-gray-200 dark:border-gray-700/50 bg-white/80 dark:bg-gray-900/30 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all" />
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">Valor *</label>
                <input type="number" required step="0.01" value={formData.value} onChange={(e) => setFormData({ ...formData, value: parseFloat(e.target.value) })} className="w-full px-4 py-2.5 rounded-lg border border-gray-200 dark:border-gray-700/50 bg-white/80 dark:bg-gray-900/30 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all" />
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">Data de Vencimento *</label>
                <input type="date" required value={formData.renewal_date} onChange={(e) => setFormData({ ...formData, renewal_date: e.target.value })} className="w-full px-4 py-2.5 rounded-lg border border-gray-200 dark:border-gray-700/50 bg-white/80 dark:bg-gray-900/30 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all" />
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">Servidor</label>
                <input type="text" value={formData.server} onChange={(e) => setFormData({ ...formData, server: e.target.value })} className="w-full px-4 py-2.5 rounded-lg border border-gray-200 dark:border-gray-700/50 bg-white/80 dark:bg-gray-900/30 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all" />
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">MAC Address</label>
                <input type="text" value={formData.mac} onChange={(e) => setFormData({ ...formData, mac: e.target.value })} className="w-full px-4 py-2.5 rounded-lg border border-gray-200 dark:border-gray-700/50 bg-white/80 dark:bg-gray-900/30 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all" />
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">Número de Telas</label>
                <input type="number" min="1" value={formData.screens} onChange={(e) => setFormData({ ...formData, screens: parseInt(e.target.value) })} className="w-full px-4 py-2.5 rounded-lg border border-gray-200 dark:border-gray-700/50 bg-white/80 dark:bg-gray-900/30 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all" />
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">Notificações</label>
                <select value={formData.notifications} onChange={(e) => setFormData({ ...formData, notifications: e.target.value as 'sim' | 'nao' })} className="w-full px-4 py-2.5 rounded-lg border border-gray-200 dark:border-gray-700/50 bg-white/80 dark:bg-gray-900/30 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all">
                  <option value="sim" className="bg-white dark:bg-gray-800">Sim</option>
                  <option value="nao" className="bg-white dark:bg-gray-800">Não</option>
                </select>
              </div>

              <div className="md:col-span-2">
                <label className="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">Observações</label>
                <textarea rows={3} value={formData.notes} onChange={(e) => setFormData({ ...formData, notes: e.target.value })} className="w-full px-4 py-2.5 rounded-lg border border-gray-200 dark:border-gray-700/50 bg-white/80 dark:bg-gray-900/30 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all" />
              </div>
            </div>

            <div className="flex justify-end gap-3 mt-6 pt-6 border-t border-gray-200/50 dark:border-gray-700/30">
              <button type="button" onClick={onClose} className="px-6 py-2.5 text-gray-700 dark:text-gray-200 bg-white/80 dark:bg-gray-900/30 hover:bg-gray-100 dark:hover:bg-gray-700/50 rounded-lg transition-colors border border-gray-200 dark:border-gray-700/50">
                Cancelar
              </button>
              <button type="submit" disabled={loading} className="px-6 py-2.5 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-colors disabled:opacity-50 shadow-sm">
                {loading ? 'Salvando...' : 'Salvar'}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  )
}
