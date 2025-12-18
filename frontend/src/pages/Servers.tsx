import { useEffect, useState } from 'react'
import { Plus, Search, Edit, Trash2, Server as ServerIcon, Activity, Unplug } from 'lucide-react'
import { serverService } from '@/services/serverService'
import toast from 'react-hot-toast'
import LoadingSpinner from '../components/LoadingSpinner'

import type { Server } from '@/types'

export default function Servers() {
  const [servers, setServers] = useState<Server[]>([])
  const [loading, setLoading] = useState(true)
  const [searchTerm, setSearchTerm] = useState('')
  const [itemsPerPage, setItemsPerPage] = useState(10)
  const [currentPage, setCurrentPage] = useState(1)
  const [showForm, setShowForm] = useState(false)
  const [editingServer, setEditingServer] = useState<Server | null>(null)
  const [saving, setSaving] = useState(false)
  const [testingConnection, setTestingConnection] = useState(false)
  const [formData, setFormData] = useState<any>({
    name: '',
    billing_type: 'fixed',
    cost: '',
    panel_type: '',
    panel_url: '',
    reseller_user: '',
    sigma_token: ''
  })

  useEffect(() => {
    loadServers()
  }, [])

  useEffect(() => {
    if (editingServer) {
      setFormData({
        ...editingServer,
        sigma_token: '' // Não mostrar token ao editar
      })
      setShowForm(true)
    } else if (!showForm) {
      setFormData({
        name: '',
        billing_type: 'fixed',
        cost: '',
        panel_type: '',
        panel_url: '',
        reseller_user: '',
        sigma_token: ''
      })
    }
  }, [editingServer, showForm])

  const loadServers = async () => {
    try {
      const result = await serverService.getAll()
      if (result.success) {
        setServers(result.servers)
      }
    } catch (error) {
      toast.error('Erro ao carregar servidores')
    } finally {
      setLoading(false)
    }
  }

  const filteredServers = servers.filter((server) =>
    server.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
    (server.panel_url && server.panel_url.toLowerCase().includes(searchTerm.toLowerCase()))
  )

  const totalPages = Math.ceil(filteredServers.length / itemsPerPage)
  const startIndex = (currentPage - 1) * itemsPerPage
  const endIndex = startIndex + itemsPerPage
  const paginatedServers = filteredServers.slice(startIndex, endIndex)

  useEffect(() => {
    setCurrentPage(1)
  }, [searchTerm, itemsPerPage])

  const handleTestConnection = async () => {
    if (!formData.panel_url || !formData.reseller_user) {
      toast.error('Preencha URL e Usuário antes de testar')
      return
    }

    if (!editingServer && !formData.sigma_token) {
      toast.error('Preencha o Token antes de testar')
      return
    }

    setTestingConnection(true)
    try {
      const token = localStorage.getItem('token')
      const response = await fetch('/api-servers.php?action=test-sigma', {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          server_id: editingServer?.id,
          panel_url: formData.panel_url,
          reseller_user: formData.reseller_user,
          sigma_token: formData.sigma_token || undefined,
          use_saved_token: editingServer && !formData.sigma_token
        })
      })

      const result = await response.json()
      if (result.success) {
        toast.success('✅ Conexão estabelecida com sucesso!')
      } else {
        toast.error('❌ ' + (result.error || 'Erro na conexão'))
      }
    } catch (error) {
      toast.error('Erro ao testar conexão')
    } finally {
      setTestingConnection(false)
    }
  }

  const handleSave = async (e: React.FormEvent) => {
    e.preventDefault()
    setSaving(true)
    try {
      const data: any = {
        name: formData.name,
        billing_type: formData.billing_type,
        cost: formData.cost,
        panel_type: formData.panel_type || null,
        panel_url: formData.panel_url || null,
        reseller_user: formData.reseller_user || null
      }

      // Só incluir token se foi preenchido
      if (formData.sigma_token && formData.sigma_token.trim() !== '') {
        data.sigma_token = formData.sigma_token
      }

      if (editingServer) {
        await serverService.update(editingServer.id, data)
        toast.success('Servidor atualizado!')
      } else {
        await serverService.create(data)
        toast.success('Servidor criado!')
      }
      setShowForm(false)
      setEditingServer(null)
      loadServers()
    } catch (error) {
      toast.error('Erro ao salvar servidor')
    } finally {
      setSaving(false)
    }
  }

  const handleCancel = () => {
    setShowForm(false)
    setEditingServer(null)
  }

  const handleDelete = async (id: number, name: string) => {
    if (window.confirm(`Tem certeza que deseja excluir o servidor "${name}"?`)) {
      try {
        await serverService.delete(id)
        toast.success('Servidor excluído!')
        loadServers()
      } catch (error) {
        toast.error('Erro ao excluir servidor')
      }
    }
  }

  const handleDisconnectSigma = async (id: number, name: string) => {
    if (window.confirm(`Desconectar o servidor "${name}" do Sigma?\n\nIsso removerá as credenciais de integração.`)) {
      try {
        // Buscar o servidor atual para manter os dados obrigatórios
        const server = servers.find(s => s.id === id)
        if (!server) {
          toast.error('Servidor não encontrado')
          return
        }

        // Atualizar apenas removendo os dados do Sigma
        await serverService.update(id, {
          name: server.name,
          billing_type: server.billing_type,
          cost: server.cost,
          panel_type: null,
          panel_url: null,
          reseller_user: null,
          sigma_token: ''
        })
        toast.success('Servidor desconectado do Sigma!')
        loadServers()
      } catch (error) {
        toast.error('Erro ao desconectar servidor')
      }
    }
  }

  const formatCurrency = (value: any) => {
    const num = parseFloat(value) || 0
    return new Intl.NumberFormat('pt-BR', {
      style: 'currency',
      currency: 'BRL'
    }).format(num)
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
          <h1 className="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white">Servidores</h1>
          <p className="text-sm md:text-base text-gray-600 dark:text-gray-400 mt-1">
            {filteredServers.length} {filteredServers.length === 1 ? 'servidor' : 'servidores'}
          </p>
        </div>
        <button 
          onClick={() => setShowForm(true)}
          className="flex items-center gap-2 px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-colors text-sm md:text-base w-full sm:w-auto justify-center"
        >
          <Plus className="w-5 h-5" />
          Novo Servidor
        </button>
      </div>

      {/* Form */}
      {showForm && (
        <div className="bg-white/60 dark:bg-gray-800/30 backdrop-blur-md rounded-xl border border-gray-200/50 dark:border-gray-700/50 p-6 mb-6">
          <h3 className="text-xl font-semibold text-gray-900 dark:text-white mb-6">
            {editingServer ? 'Editar Servidor' : 'Novo Servidor'}
          </h3>
          <form onSubmit={handleSave}>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
              <div className="md:col-span-2">
                <label className="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">Nome *</label>
                <input 
                  type="text" 
                  required 
                  value={formData.name} 
                  onChange={(e) => setFormData({ ...formData, name: e.target.value })} 
                  className="w-full px-4 py-2.5 rounded-lg border border-gray-200 dark:border-gray-700/50 bg-white/80 dark:bg-gray-900/30 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all" 
                  placeholder="Ex: Servidor Principal"
                />
              </div>
              
              <div>
                <label className="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">Tipo de Cobrança *</label>
                <select 
                  required 
                  value={formData.billing_type} 
                  onChange={(e) => setFormData({ ...formData, billing_type: e.target.value })} 
                  className="w-full px-4 py-2.5 rounded-lg border border-gray-200 dark:border-gray-700/50 bg-white/80 dark:bg-gray-900/30 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all"
                >
                  <option value="fixed">Valor Fixo</option>
                  <option value="per_active">Por Cliente Ativo</option>
                </select>
              </div>
              
              <div>
                <label className="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">Custo Mensal (R$) *</label>
                <input 
                  type="number" 
                  step="0.01"
                  required 
                  value={formData.cost} 
                  onChange={(e) => setFormData({ ...formData, cost: e.target.value })} 
                  className="w-full px-4 py-2.5 rounded-lg border border-gray-200 dark:border-gray-700/50 bg-white/80 dark:bg-gray-900/30 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all" 
                  placeholder="0.00"
                />
              </div>
            </div>

            {/* Integração Sigma */}
            <div className="border-t border-gray-200 dark:border-gray-700 pt-6 mt-6">
              <h4 className="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <Activity className="w-5 h-5" />
                Integração com Painel Sigma
              </h4>
              
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div className="md:col-span-2">
                  <label className="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">Tipo de Painel</label>
                  <select 
                    value={formData.panel_type} 
                    onChange={(e) => setFormData({ ...formData, panel_type: e.target.value })} 
                    className="w-full px-4 py-2.5 rounded-lg border border-gray-200 dark:border-gray-700/50 bg-white/80 dark:bg-gray-900/30 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all"
                  >
                    <option value="">Nenhum</option>
                    <option value="sigma">Sigma</option>
                  </select>
                </div>

                {formData.panel_type === 'sigma' && (
                  <>
                    <div className="md:col-span-2">
                      <label className="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">URL do Painel *</label>
                      <input 
                        type="url" 
                        value={formData.panel_url} 
                        onChange={(e) => setFormData({ ...formData, panel_url: e.target.value })} 
                        className="w-full px-4 py-2.5 rounded-lg border border-gray-200 dark:border-gray-700/50 bg-white/80 dark:bg-gray-900/30 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all" 
                        placeholder="https://seu-painel.com.br"
                      />
                    </div>
                    
                    <div>
                      <label className="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">Usuário Revenda *</label>
                      <input 
                        type="text" 
                        value={formData.reseller_user} 
                        onChange={(e) => setFormData({ ...formData, reseller_user: e.target.value })} 
                        className="w-full px-4 py-2.5 rounded-lg border border-gray-200 dark:border-gray-700/50 bg-white/80 dark:bg-gray-900/30 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all" 
                        placeholder="seu_usuario"
                      />
                    </div>
                    
                    <div>
                      <label className="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">
                        Token do Sigma {editingServer && '*'}
                      </label>
                      <input 
                        type="password" 
                        value={formData.sigma_token} 
                        onChange={(e) => setFormData({ ...formData, sigma_token: e.target.value })} 
                        className="w-full px-4 py-2.5 rounded-lg border border-gray-200 dark:border-gray-700/50 bg-white/80 dark:bg-gray-900/30 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all" 
                        placeholder={editingServer ? "Deixe em branco para manter o atual" : "••••••••••••"}
                      />
                      {editingServer && (
                        <p className="text-xs text-green-600 dark:text-green-400 mt-1">
                          ✓ Token já configurado - preencha apenas para substituir
                        </p>
                      )}
                    </div>
                  </>
                )}
              </div>
            </div>

            <div className="flex justify-end gap-3 mt-6">
              <button 
                type="button" 
                onClick={handleCancel} 
                className="px-6 py-2.5 text-gray-700 dark:text-gray-200 bg-white/80 dark:bg-gray-900/30 hover:bg-gray-100 dark:hover:bg-gray-700/50 rounded-lg transition-colors border border-gray-200 dark:border-gray-700/50"
              >
                Cancelar
              </button>
              
              {formData.panel_type === 'sigma' && (
                <button 
                  type="button"
                  onClick={handleTestConnection}
                  disabled={testingConnection}
                  className="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors disabled:opacity-50 shadow-sm flex items-center gap-2"
                >
                  <Activity className="w-4 h-4" />
                  {testingConnection ? 'Testando...' : 'Testar Conexão'}
                </button>
              )}
              
              <button 
                type="submit" 
                disabled={saving} 
                className="px-6 py-2.5 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-colors disabled:opacity-50 shadow-sm"
              >
                {saving ? 'Salvando...' : 'Salvar'}
              </button>
            </div>
          </form>
        </div>
      )}

      {/* Filters */}
      <div className="bg-white/60 dark:bg-gray-800/30 backdrop-blur-md rounded-xl border border-gray-200/50 dark:border-gray-700/50 p-6 mb-6">
        <div className="flex flex-col gap-4">
          <div className="flex flex-col sm:flex-row gap-4">
            <div className="flex-1 relative">
              <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5" />
              <input
                type="text"
                placeholder="Buscar servidores..."
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                className="w-full pl-10 pr-4 py-2.5 rounded-lg border border-gray-200 dark:border-gray-700/50 bg-white/80 dark:bg-gray-900/30 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all"
              />
            </div>
          </div>

          <div className="flex items-center justify-between">
            <div className="flex items-center gap-2">
              <span className="text-sm text-gray-600 dark:text-gray-400">Mostrar:</span>
              <select
                value={itemsPerPage}
                onChange={(e) => setItemsPerPage(Number(e.target.value))}
                className="px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700/50 bg-white/80 dark:bg-gray-900/30 text-gray-900 dark:text-gray-100 text-sm focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all"
              >
                <option value={10}>10</option>
                <option value={30}>30</option>
                <option value={50}>50</option>
                <option value={100}>100</option>
              </select>
              <span className="text-sm text-gray-600 dark:text-gray-400">por página</span>
            </div>

            {searchTerm && (
              <button 
                onClick={() => setSearchTerm('')} 
                className="px-4 py-2 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors"
              >
                Limpar Filtros
              </button>
            )}
          </div>
        </div>
      </div>

      {/* Table */}
      <div className="bg-white/60 dark:bg-gray-800/30 backdrop-blur-md rounded-xl border border-gray-200/50 dark:border-gray-700/50 overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full">
            <thead className="bg-gray-50/50 dark:bg-gray-900/20 border-b border-gray-200/50 dark:border-gray-700/30">
              <tr>
                <th className="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Nome</th>
                <th className="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Tipo Cobrança</th>
                <th className="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Custo</th>
                <th className="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Painel</th>
                <th className="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Clientes</th>
                <th className="px-6 py-4 text-right text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Ações</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-200 dark:divide-gray-700/30">
              {loading ? (
                <tr>
                  <td colSpan={6} className="px-6 py-4">
                    <LoadingSpinner />
                  </td>
                </tr>
              ) : paginatedServers.length === 0 ? (
                <tr>
                  <td colSpan={6} className="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                    Nenhum servidor encontrado
                  </td>
                </tr>
              ) : (
                paginatedServers.map((server) => (
                  <tr key={server.id} className="hover:bg-white/40 dark:hover:bg-gray-700/20 transition-colors">
                    <td className="px-6 py-4 whitespace-nowrap">
                      <div className="flex items-center gap-2">
                        <ServerIcon className="w-4 h-4 text-primary-600 dark:text-primary-400" />
                        <span className="text-sm font-medium text-gray-900 dark:text-white">
                          {server.name}
                        </span>
                      </div>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap">
                      <span className="px-2 py-1 text-xs font-medium rounded bg-purple-100 text-purple-800 dark:bg-purple-900/20 dark:text-purple-400">
                        {server.billing_type === 'fixed' ? 'Fixo' : 'Por Ativo'}
                      </span>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap">
                      <div className="text-sm font-semibold text-gray-900 dark:text-white">
                        {formatCurrency(server.total_cost || server.cost)}
                      </div>
                      {server.billing_type === 'per_active' && (
                        <div className="text-xs text-gray-500 dark:text-gray-400">
                          {formatCurrency(server.cost)} × {server.connected_clients || 0}
                        </div>
                      )}
                    </td>
                    <td className="px-6 py-4">
                      {server.panel_type ? (
                        <div>
                          <span className="px-2 py-1 text-xs font-medium rounded bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400">
                            {server.panel_type.toUpperCase()}
                          </span>
                          {server.panel_url && (
                            <div className="text-xs text-gray-500 dark:text-gray-400 mt-1 truncate max-w-xs">
                              {server.panel_url}
                            </div>
                          )}
                        </div>
                      ) : (
                        <span className="text-sm text-gray-400">-</span>
                      )}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap">
                      <span className="text-sm font-medium text-gray-900 dark:text-white">
                        {server.connected_clients || 0}
                      </span>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                      <div className="flex items-center justify-end gap-1.5">
                        <button 
                          onClick={() => setEditingServer(server)}
                          className="p-2 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-all hover:scale-110" 
                          title="Editar Servidor"
                        >
                          <Edit className="w-4 h-4" />
                        </button>
                        {server.panel_type === 'sigma' && (
                          <button 
                            onClick={() => handleDisconnectSigma(server.id, server.name)}
                            className="p-2 text-orange-600 hover:bg-orange-50 dark:hover:bg-orange-900/20 rounded-lg transition-all hover:scale-110" 
                            title="Desconectar do Sigma"
                          >
                            <Unplug className="w-4 h-4" />
                          </button>
                        )}
                        <button 
                          onClick={() => handleDelete(server.id, server.name)} 
                          className="p-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-all hover:scale-110" 
                          title="Excluir Servidor"
                        >
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

        {/* Paginação */}
        {totalPages > 1 && (
          <div className="px-6 py-4 bg-gray-50/50 dark:bg-gray-900/20 border-t border-gray-200/50 dark:border-gray-700/30">
            <div className="flex items-center justify-between">
              <div className="text-sm text-gray-600 dark:text-gray-400">
                Mostrando {startIndex + 1} a {Math.min(endIndex, filteredServers.length)} de {filteredServers.length} servidores
              </div>
              <div className="flex items-center gap-2">
                <button
                  onClick={() => setCurrentPage(prev => Math.max(1, prev - 1))}
                  disabled={currentPage === 1}
                  className="px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 text-sm font-medium hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed transition-all"
                >
                  Anterior
                </button>
                
                <div className="flex items-center gap-1">
                  {Array.from({ length: Math.min(5, totalPages) }, (_, i) => {
                    let pageNum
                    if (totalPages <= 5) {
                      pageNum = i + 1
                    } else if (currentPage <= 3) {
                      pageNum = i + 1
                    } else if (currentPage >= totalPages - 2) {
                      pageNum = totalPages - 4 + i
                    } else {
                      pageNum = currentPage - 2 + i
                    }
                    
                    return (
                      <button
                        key={pageNum}
                        onClick={() => setCurrentPage(pageNum)}
                        className={`px-3 py-1.5 rounded-lg text-sm font-medium transition-all ${
                          currentPage === pageNum
                            ? 'bg-primary-600 text-white'
                            : 'border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700'
                        }`}
                      >
                        {pageNum}
                      </button>
                    )
                  })}
                </div>

                <button
                  onClick={() => setCurrentPage(prev => Math.min(totalPages, prev + 1))}
                  disabled={currentPage === totalPages}
                  className="px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 text-sm font-medium hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed transition-all"
                >
                  Próxima
                </button>
              </div>
            </div>
          </div>
        )}
      </div>
    </div>
  )
}
