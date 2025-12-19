import { useState, useEffect } from 'react'
import { MessageSquare, Edit, Eye, Trash2, Power, Check, X, Plus } from 'lucide-react'
import toast from 'react-hot-toast'
import api from '../services/api'
import LoadingSpinner from '../components/LoadingSpinner'

interface Template {
  id: string
  name: string
  type: string
  title: string
  message: string
  is_active: boolean
  is_default: boolean
}

const typeLabels: Record<string, string> = {
  'welcome': 'Boas-vindas',
  'invoice_generated': 'Renovacao',
  'renewed': 'Renovacao',
  'expires_3d': 'Lembrete (Antes)',
  'expires_7d': 'Lembrete (Antes)',
  'expires_today': 'Vencimento',
  'expired_1d': 'Lembrete (Apos)',
  'expired_3d': 'Lembrete (Apos)',
  'custom': 'Personalizado'
}

const typeColors: Record<string, string> = {
  'welcome': '#10b981',
  'invoice_generated': '#10b981',
  'renewed': '#10b981',
  'expires_3d': '#f59e0b',
  'expires_7d': '#f59e0b',
  'expires_today': '#f97316',
  'expired_1d': '#ef4444',
  'expired_3d': '#ef4444',
  'custom': '#6366f1'
}

const variables = [
  'cliente_nome', 'cliente_usuario', 'cliente_senha', 'cliente_servidor',
  'cliente_plano', 'cliente_vencimento', 'cliente_valor',
  'fatura_valor', 'fatura_vencimento', 'fatura_periodo'
]

const sampleData: Record<string, string> = {
  'cliente_nome': 'João Silva',
  'cliente_usuario': 'joao.silva',
  'cliente_senha': 'senha123',
  'cliente_servidor': 'servidor1.iptv.com',
  'cliente_plano': 'Plano Premium',
  'cliente_vencimento': '15/12/2024',
  'cliente_valor': '29,90',
  'fatura_valor': '29,90',
  'fatura_vencimento': '15/12/2024',
  'fatura_periodo': 'Dezembro 2024'
}

export default function WhatsAppTemplates() {
  const [templates, setTemplates] = useState<Template[]>([])
  const [loading, setLoading] = useState(true)
  const [showModal, setShowModal] = useState(false)
  const [showViewModal, setShowViewModal] = useState(false)
  const [showVariables, setShowVariables] = useState(false)
  const [currentTemplate, setCurrentTemplate] = useState<Template | null>(null)
  const [formData, setFormData] = useState({
    id: '',
    name: '',
    type: '',
    title: '',
    message: '',
    is_active: true,
    is_default: false
  })

  useEffect(() => {
    loadTemplates()
  }, [])

  useEffect(() => {
    const handleClickOutside = (e: MouseEvent) => {
      const target = e.target as HTMLElement
      if (showVariables && !target.closest('#templateMessage') && !target.closest('.absolute.z-10')) {
        setShowVariables(false)
      }
    }

    document.addEventListener('mousedown', handleClickOutside)
    return () => document.removeEventListener('mousedown', handleClickOutside)
  }, [showVariables])

  const loadTemplates = async () => {
    try {
      const response = await api.get('/api-whatsapp-templates.php')
      if (response.data.success && response.data.templates) {
        setTemplates(response.data.templates)
      } else {
        setTemplates([])
      }
    } catch (error) {
      setTemplates([])
    } finally {
      setLoading(false)
    }
  }

  const handleNew = () => {
    setFormData({
      id: '',
      name: '',
      type: '',
      title: '',
      message: '',
      is_active: true,
      is_default: false
    })
    setShowModal(true)
  }

  const handleEdit = (template: Template) => {
    setFormData({
      id: template.id,
      name: template.name,
      type: template.type,
      title: template.title,
      message: template.message,
      is_active: template.is_active,
      is_default: template.is_default
    })
    setShowModal(true)
  }

  const handleView = (template: Template) => {
    setCurrentTemplate(template)
    setShowViewModal(true)
  }

  const handleSave = async () => {
    if (!formData.name || !formData.type || !formData.title || !formData.message) {
      toast.error('Preencha todos os campos obrigatórios')
      return
    }

    try {
      await api.post('/api-whatsapp-templates.php', {
        ...formData,
        is_active: formData.is_active ? 1 : 0,
        is_default: formData.is_default ? 1 : 0
      })

      toast.success('Template salvo com sucesso!')
      setShowModal(false)
      loadTemplates()
    } catch (error) {
      toast.error('Erro ao salvar template')
    }
  }

  const handleToggle = async (template: Template) => {
    try {
      await api.post('/api-whatsapp-templates.php', {
        id: template.id,
        name: template.name,
        type: template.type,
        title: template.title,
        message: template.message,
        is_active: template.is_active ? 0 : 1,
        is_default: template.is_default ? 1 : 0
      })

      toast.success(template.is_active ? 'Template desativado!' : 'Template ativado!')
      loadTemplates()
    } catch (error) {
      toast.error('Erro ao alterar status')
    }
  }

  const handleDelete = async (templateId: string) => {
    if (!confirm('Tem certeza que deseja excluir este template?')) return

    try {
      await api.delete(`/api-whatsapp-templates.php?id=${templateId}`)
      toast.success('Template excluído com sucesso!')
      loadTemplates()
    } catch (error: any) {
      toast.error(error.response?.data?.message || 'Erro ao excluir template')
    }
  }

  const insertVariable = (variable: string) => {
    const textarea = document.getElementById('templateMessage') as HTMLTextAreaElement
    if (!textarea) return

    const start = textarea.selectionStart
    const end = textarea.selectionEnd
    const text = formData.message
    const before = text.substring(0, start)
    const after = text.substring(end)

    setFormData({
      ...formData,
      message: before + `{{${variable}}}` + after
    })

    setTimeout(() => {
      textarea.focus()
      textarea.setSelectionRange(start + variable.length + 4, start + variable.length + 4)
    }, 0)
  }

  const formatPreview = (message: string) => {
    let formatted = message
    Object.keys(sampleData).forEach(variable => {
      const regex = new RegExp(`\\{\\{${variable}\\}\\}`, 'g')
      formatted = formatted.replace(regex, `<span class="text-blue-600 dark:text-blue-400 font-semibold">${sampleData[variable]}</span>`)
    })
    formatted = formatted.replace(/\n/g, '<br>')
    formatted = formatted.replace(/\*([^*]+)\*/g, '<strong>$1</strong>')
    return formatted
  }

  const getTypeColor = (type: string) => typeColors[type] || '#6366f1'

  return (
    <div className="space-y-6">
      <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
          <h1 className="text-2xl md:text-3xl font-bold bg-gradient-to-r from-cyan-500 via-blue-500 to-purple-600 bg-clip-text text-transparent">Templates WhatsApp</h1>
          <p className="text-sm md:text-base text-gray-600 dark:text-gray-400 mt-1">
            Gerencie seus modelos de mensagens
          </p>
        </div>
        <button
          onClick={handleNew}
          className="flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors"
        >
          <Plus className="w-5 h-5" />
          Novo Template
        </button>
      </div>

      {loading ? (
        <LoadingSpinner />
      ) : templates.length === 0 ? (
        <div className="bg-white/80 dark:bg-gray-800/50 backdrop-blur-sm rounded-xl p-12 text-center border border-gray-200 dark:border-gray-700">
          <MessageSquare className="w-16 h-16 mx-auto text-gray-400 mb-4" />
          <h3 className="text-xl font-semibold text-gray-900 dark:text-white mb-2">
            Nenhum template encontrado
          </h3>
          <p className="text-gray-600 dark:text-gray-400 mb-4">
            Crie seu primeiro template de mensagem
          </p>
          <button
            onClick={handleNew}
            className="inline-flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors"
          >
            Criar Template
          </button>
        </div>
      ) : (
        <div className="bg-white/80 dark:bg-gray-800/50 backdrop-blur-sm rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
          <div className="overflow-x-auto">
            <table className="w-full">
              <thead className="bg-gray-50 dark:bg-gray-900/50">
                <tr>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Nome</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Tipo</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Mídia</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Padrão</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Status</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Ações</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-200 dark:divide-gray-700">
                {templates.map((template) => {
                  const color = getTypeColor(template.type)

                  return (
                    <tr
                      key={template.id}
                      className={`hover:bg-gray-50 dark:hover:bg-gray-700/50 ${!template.is_active ? 'opacity-60' : ''}`}
                    >
                      <td className="px-6 py-4">
                        <div className="flex items-center gap-3">
                          <div
                            className="w-10 h-10 rounded-lg flex items-center justify-center"
                            style={{ backgroundColor: `${color}20`, color }}
                          >
                            <MessageSquare className="w-5 h-5" />
                          </div>
                          <div>
                            <div className="text-sm font-medium text-gray-900 dark:text-white">
                              {template.name}
                            </div>
                            <div className="text-xs text-gray-500 dark:text-gray-400 truncate max-w-xs">
                              {template.message.substring(0, 60)}{template.message.length > 60 ? '...' : ''}
                            </div>
                          </div>
                        </div>
                      </td>
                      <td className="px-6 py-4">
                        <span
                          className="px-2 py-1 text-xs font-medium rounded"
                          style={{ backgroundColor: `${color}20`, color }}
                        >
                          {typeLabels[template.type] || template.type}
                        </span>
                      </td>
                      <td className="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                        NÃO
                      </td>
                      <td className="px-6 py-4">
                        {template.is_default ? (
                          <Check className="w-5 h-5 text-green-600 dark:text-green-400" />
                        ) : null}
                      </td>
                      <td className="px-6 py-4">
                        {template.is_active ? (
                          <Check className="w-5 h-5 text-green-600 dark:text-green-400" />
                        ) : (
                          <X className="w-5 h-5 text-gray-400" />
                        )}
                      </td>
                      <td className="px-6 py-4">
                        <div className="flex gap-3">
                          <button
                            onClick={() => handleToggle(template)}
                            className="text-gray-600 hover:text-gray-700 dark:text-gray-400 transition-colors"
                            title={template.is_active ? 'Desativar' : 'Ativar'}
                          >
                            <Power className="w-5 h-5" />
                          </button>
                          <button
                            onClick={() => handleView(template)}
                            className="text-blue-600 hover:text-blue-700 dark:text-blue-400 transition-colors"
                            title="Visualizar"
                          >
                            <Eye className="w-5 h-5" />
                          </button>
                          <button
                            onClick={() => handleEdit(template)}
                            className="text-green-600 hover:text-green-700 dark:text-green-400 transition-colors"
                            title="Editar"
                          >
                            <Edit className="w-5 h-5" />
                          </button>
                          <button
                            onClick={() => handleDelete(template.id)}
                            className="text-red-600 hover:text-red-700 dark:text-red-400 transition-colors"
                            title="Excluir"
                          >
                            <Trash2 className="w-5 h-5" />
                          </button>
                        </div>
                      </td>
                    </tr>
                  )
                })}
              </tbody>
            </table>
          </div>
        </div>
      )}

      {/* Modal Criar/Editar */}
      {showModal && (
        <div className="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 p-4">
          <div className="bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-4xl w-full max-h-[90vh] overflow-y-auto">
            <div className="p-6 border-b border-gray-200 dark:border-gray-700">
              <div className="flex items-center justify-between">
                <h2 className="text-xl font-bold text-gray-900 dark:text-white">
                  {formData.id ? 'Editar Template' : 'Novo Template'}
                </h2>
                <button
                  onClick={() => setShowModal(false)}
                  className="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                >
                  <X className="w-6 h-6" />
                </button>
              </div>
            </div>

            <div className="p-6 space-y-4">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Nome do Template *
                  </label>
                  <input
                    type="text"
                    value={formData.name}
                    onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                    className="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                    required
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Tipo *
                  </label>
                  <select
                    value={formData.type}
                    onChange={(e) => setFormData({ ...formData, type: e.target.value })}
                    className="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                    required
                  >
                    <option value="">Selecione...</option>
                    <option value="welcome">Boas Vindas</option>
                    <option value="invoice_generated">Fatura Gerada</option>
                    <option value="renewed">Renovação Confirmada</option>
                    <option value="expires_3d">Vence em 3 dias</option>
                    <option value="expires_7d">Vence em 7 dias</option>
                    <option value="expires_today">Vence Hoje</option>
                    <option value="expired_1d">Vencido há 1 dia</option>
                    <option value="expired_3d">Vencido há 3 dias</option>
                    <option value="custom">Personalizado</option>
                  </select>
                </div>
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Título *
                </label>
                <input
                  type="text"
                  value={formData.title}
                  onChange={(e) => setFormData({ ...formData, title: e.target.value })}
                  className="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                  required
                />
              </div>

              <div className="relative">
                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Mensagem *
                </label>
                <div className="relative">
                  <textarea
                    id="templateMessage"
                    value={formData.message}
                    onChange={(e) => setFormData({ ...formData, message: e.target.value })}
                    onFocus={() => setShowVariables(true)}
                    rows={10}
                    className="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white font-mono text-sm"
                    required
                    placeholder="Digite sua mensagem aqui... Clique para ver as variáveis disponíveis"
                  />

                  {showVariables && (
                    <div className="absolute z-10 mt-2 w-full bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg shadow-lg max-h-60 overflow-y-auto">
                      <div className="p-3 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                        <span className="text-sm font-medium text-gray-700 dark:text-gray-300">
                          Clique para inserir uma variável
                        </span>
                        <button
                          type="button"
                          onClick={() => setShowVariables(false)}
                          className="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                        >
                          <X className="w-4 h-4" />
                        </button>
                      </div>
                      <div className="p-2">
                        {variables.map(variable => (
                          <button
                            key={variable}
                            type="button"
                            onClick={() => {
                              insertVariable(variable)
                              setShowVariables(false)
                            }}
                            className="w-full text-left px-3 py-2 text-sm hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded transition-colors flex items-center justify-between group"
                          >
                            <span className="text-gray-700 dark:text-gray-300">{variable}</span>
                            <span className="text-xs text-blue-600 dark:text-blue-400 opacity-0 group-hover:opacity-100 transition-opacity">
                              {'{{'}{variable}{'}}'}
                            </span>
                          </button>
                        ))}
                      </div>
                    </div>
                  )}
                </div>
                <small className="text-gray-500 dark:text-gray-400 mt-1 block">
                  Use {'{{'} variavel {'}'} para inserir variáveis dinâmicas. Clique no campo para ver as opções.
                </small>
              </div>

              <div className="flex gap-4">
                <label className="flex items-center gap-2 cursor-pointer">
                  <input
                    type="checkbox"
                    checked={formData.is_active}
                    onChange={(e) => setFormData({ ...formData, is_active: e.target.checked })}
                    className="w-4 h-4 text-green-600 rounded focus:ring-green-500"
                  />
                  <span className="text-sm text-gray-700 dark:text-gray-300">Template Ativo</span>
                </label>
                <label className="flex items-center gap-2 cursor-pointer">
                  <input
                    type="checkbox"
                    checked={formData.is_default}
                    onChange={(e) => setFormData({ ...formData, is_default: e.target.checked })}
                    className="w-4 h-4 text-green-600 rounded focus:ring-green-500"
                  />
                  <span className="text-sm text-gray-700 dark:text-gray-300">Template Padrão</span>
                </label>
              </div>
            </div>

            <div className="p-6 border-t border-gray-200 dark:border-gray-700 flex gap-3">
              <button
                onClick={() => setShowModal(false)}
                className="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700"
              >
                Cancelar
              </button>
              <button
                onClick={handleSave}
                className="flex-1 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700"
              >
                Salvar Template
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Modal Visualizar */}
      {showViewModal && currentTemplate && (
        <div className="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 p-4">
          <div className="bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div className="p-6 border-b border-gray-200 dark:border-gray-700">
              <div className="flex items-center justify-between">
                <h2 className="text-xl font-bold text-gray-900 dark:text-white">
                  Visualizar Template
                </h2>
                <button
                  onClick={() => setShowViewModal(false)}
                  className="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                >
                  <X className="w-6 h-6" />
                </button>
              </div>
            </div>

            <div className="p-6">
              <div className="bg-gradient-to-b from-green-500 to-green-600 rounded-t-3xl p-4 text-white">
                <div className="flex items-center gap-3">
                  <div className="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center">
                    <MessageSquare className="w-6 h-6" />
                  </div>
                  <div>
                    <div className="font-semibold">WhatsApp Business</div>
                    <div className="text-xs opacity-90">Agora</div>
                  </div>
                </div>
              </div>
              <div className="bg-gray-100 dark:bg-gray-900 p-6 rounded-b-3xl">
                <div className="bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-lg">
                  <div
                    className="text-sm text-gray-900 dark:text-white whitespace-pre-wrap leading-snug"
                    dangerouslySetInnerHTML={{ __html: formatPreview(currentTemplate.message) }}
                  />
                  <div className="flex justify-end mt-2">
                    <Check className="w-4 h-4 text-blue-500" />
                  </div>
                </div>
              </div>
            </div>

            <div className="p-6 border-t border-gray-200 dark:border-gray-700 flex gap-3">
              <button
                onClick={() => setShowViewModal(false)}
                className="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700"
              >
                Fechar
              </button>
              <button
                onClick={() => {
                  setShowViewModal(false)
                  handleEdit(currentTemplate)
                }}
                className="flex-1 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700"
              >
                Editar Template
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  )
}
