import { useEffect, useState } from 'react'
import { Plus, Search, Edit, Trash2 } from 'lucide-react'
import toast from 'react-hot-toast'
import LoadingSpinner from '../components/LoadingSpinner'

interface Application {
  id: number
  name: string
  description?: string
  created_at: string
}

import { usePageTitle } from '@/hooks/usePageTitle'

export default function Applications() {
  usePageTitle('Aplicações')
  const [applications, setApplications] = useState<Application[]>([])
  const [loading, setLoading] = useState(true)
  const [searchTerm, setSearchTerm] = useState('')
  const [itemsPerPage, setItemsPerPage] = useState(10)
  const [currentPage, setCurrentPage] = useState(1)
  const [showForm, setShowForm] = useState(false)
  const [editingApp, setEditingApp] = useState<Application | null>(null)
  const [saving, setSaving] = useState(false)
  const [formData, setFormData] = useState<Partial<Application>>({
    name: '',
    description: ''
  })

  useEffect(() => {
    loadApplications()
  }, [])

  useEffect(() => {
    if (editingApp) {
      setFormData(editingApp)
      setShowForm(true)
    } else if (!showForm) {
      setFormData({ name: '', description: '' })
    }
  }, [editingApp, showForm])

  const loadApplications = async () => {
    try {
      const response = await fetch('/api-applications.php')
      const data = await response.json()
      if (data.success) {
        setApplications(data.applications || [])
      }
    } catch (error) {
      toast.error('Erro ao carregar aplicativos')
    } finally {
      setLoading(false)
    }
  }

  const filteredApplications = applications.filter((app) =>
    app.name.toLowerCase().includes(searchTerm.toLowerCase())
  )

  // Paginação
  const totalPages = Math.ceil(filteredApplications.length / itemsPerPage)
  const startIndex = (currentPage - 1) * itemsPerPage
  const endIndex = startIndex + itemsPerPage
  const paginatedApplications = filteredApplications.slice(startIndex, endIndex)

  // Reset para página 1 quando filtros mudarem
  useEffect(() => {
    setCurrentPage(1)
  }, [searchTerm, itemsPerPage])

  const handleSave = async (e: React.FormEvent) => {
    e.preventDefault()
    setSaving(true)
    try {
      const method = editingApp ? 'PUT' : 'POST'
      const url = editingApp ? `/api-applications.php?id=${editingApp.id}` : '/api-applications.php'
      
      const response = await fetch(url, {
        method,
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('token')}`,
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(formData)
      })

      const data = await response.json()
      if (data.success) {
        toast.success(editingApp ? 'Aplicativo atualizado!' : 'Aplicativo criado!')
        setShowForm(false)
        setEditingApp(null)
        loadApplications()
      } else {
        toast.error(data.error || 'Erro ao salvar')
      }
    } catch (error) {
      toast.error('Erro ao salvar')
    } finally {
      setSaving(false)
    }
  }

  const handleCancel = () => {
    setShowForm(false)
    setEditingApp(null)
  }

  const handleDelete = async (id: number, name: string) => {
    if (window.confirm(`Tem certeza que deseja excluir o aplicativo "${name}"?`)) {
      try {
        const response = await fetch(`/api-applications.php?id=${id}`, {
          method: 'DELETE',
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`
          }
        })
        const data = await response.json()
        if (data.success) {
          toast.success('Aplicativo excluído!')
          loadApplications()
        } else {
          toast.error(data.error || 'Erro ao excluir')
        }
      } catch (error) {
        toast.error('Erro ao excluir')
      }
    }
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
          <h1 className="text-2xl md:text-3xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">Aplicativos</h1>
          <p className="text-sm md:text-base text-gray-600 dark:text-gray-400 mt-1">
            {filteredApplications.length} {filteredApplications.length === 1 ? 'aplicativo' : 'aplicativos'}
          </p>
        </div>
        <button 
          onClick={() => setShowForm(true)}
          className="flex items-center gap-2 px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-colors text-sm md:text-base w-full sm:w-auto justify-center"
        >
          <Plus className="w-5 h-5" />
          Novo Aplicativo
        </button>
      </div>

      {/* Form */}
      {showForm && (
        <div className="bg-white/60 dark:bg-gray-800/30 backdrop-blur-md rounded-xl border border-gray-200/50 dark:border-gray-700/50 p-6 mb-6 animate-in slide-in-from-top duration-300">
          <h3 className="text-xl font-semibold text-gray-900 dark:text-white mb-6">
            {editingApp ? 'Editar Aplicativo' : 'Novo Aplicativo'}
          </h3>
          <form onSubmit={handleSave}>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
              <div>
                <label className="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">Nome *</label>
                <input 
                  type="text" 
                  required 
                  value={formData.name} 
                  onChange={(e) => setFormData({ ...formData, name: e.target.value })} 
                  className="w-full px-4 py-2.5 rounded-lg border border-gray-200 dark:border-gray-700/50 bg-white/80 dark:bg-gray-900/30 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all" 
                  placeholder="Ex: Netflix, Disney+, HBO Max"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">Descrição</label>
                <input 
                  type="text" 
                  value={formData.description || ''} 
                  onChange={(e) => setFormData({ ...formData, description: e.target.value })} 
                  className="w-full px-4 py-2.5 rounded-lg border border-gray-200 dark:border-gray-700/50 bg-white/80 dark:bg-gray-900/30 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all" 
                  placeholder="Ex: Streaming de filmes e séries"
                />
              </div>
            </div>
            <div className="flex justify-end gap-3">
              <button 
                type="button" 
                onClick={handleCancel} 
                className="px-6 py-2.5 text-gray-700 dark:text-gray-200 bg-white/80 dark:bg-gray-900/30 hover:bg-gray-100 dark:hover:bg-gray-700/50 rounded-lg transition-colors border border-gray-200 dark:border-gray-700/50"
              >
                Cancelar
              </button>
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
                placeholder="Buscar aplicativos..."
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
                <option value={10} className="bg-white dark:bg-gray-800">10</option>
                <option value={30} className="bg-white dark:bg-gray-800">30</option>
                <option value={50} className="bg-white dark:bg-gray-800">50</option>
                <option value={100} className="bg-white dark:bg-gray-800">100</option>
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
                <th className="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                  Nome
                </th>
                <th className="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                  Descrição
                </th>
                <th className="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                  Criado em
                </th>
                <th className="px-6 py-4 text-right text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                  Ações
                </th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-200 dark:divide-gray-700/30">
              {loading ? (
                <tr>
                  <td colSpan={4} className="px-6 py-4">
                    <LoadingSpinner />
                  </td>
                </tr>
              ) : paginatedApplications.length === 0 ? (
                <tr>
                  <td colSpan={4} className="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                    Nenhum aplicativo encontrado
                  </td>
                </tr>
              ) : (
                paginatedApplications.map((app) => (
                  <tr key={app.id} className="hover:bg-white/40 dark:hover:bg-gray-700/20 transition-colors">
                    <td className="px-6 py-4 whitespace-nowrap">
                      <span className="px-2 py-1 text-xs font-medium rounded bg-purple-100 text-purple-800 dark:bg-purple-900/20 dark:text-purple-400">
                        {app.name}
                      </span>
                    </td>
                    <td className="px-6 py-4">
                      <span className="text-sm text-gray-600 dark:text-gray-400">
                        {app.description || '-'}
                      </span>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap">
                      <span className="px-2 py-1 text-xs font-medium rounded bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400">
                        {new Date(app.created_at).toLocaleDateString('pt-BR')}
                      </span>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                      <div className="flex items-center justify-end gap-1.5">
                        <button 
                          onClick={() => setEditingApp(app)}
                          className="p-2 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-all hover:scale-110" 
                          title="Editar Aplicativo"
                        >
                          <Edit className="w-4 h-4" />
                        </button>
                        <button 
                          onClick={() => handleDelete(app.id, app.name)} 
                          className="p-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-all hover:scale-110" 
                          title="Excluir Aplicativo"
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
                Mostrando {startIndex + 1} a {Math.min(endIndex, filteredApplications.length)} de {filteredApplications.length} aplicativos
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
