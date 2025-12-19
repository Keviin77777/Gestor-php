import { useState, useEffect } from 'react'
import { Calendar, Clock, Edit, X, Check } from 'lucide-react'
import toast from 'react-hot-toast'
import api from '../services/api'
import LoadingSpinner from '../components/LoadingSpinner'

interface Template {
  id: string
  name: string
  type: string
  title: string
  message: string
  is_scheduled: boolean
  scheduled_days: string | null
  scheduled_time: string | null
  is_active: boolean
}

const typeLabels: Record<string, string> = {
  'welcome': 'Boas Vindas',
  'invoice_generated': 'Fatura Gerada',
  'renewed': 'Renovação Confirmada',
  'expires_3d': 'Vence em 3 dias',
  'expires_7d': 'Vence em 7 dias',
  'expires_today': 'Vence Hoje',
  'expired_1d': 'Vencido há 1 dia',
  'expired_3d': 'Vencido há 3 dias',
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

const dayLabels: Record<string, string> = {
  'sunday': 'DOM',
  'monday': 'SEG',
  'tuesday': 'TER',
  'wednesday': 'QUA',
  'thursday': 'QUI',
  'friday': 'SEX',
  'saturday': 'SÁB'
}

const dayFullLabels: Record<string, string> = {
  'sunday': 'Domingo',
  'monday': 'Segunda-feira',
  'tuesday': 'Terça-feira',
  'wednesday': 'Quarta-feira',
  'thursday': 'Quinta-feira',
  'friday': 'Sexta-feira',
  'saturday': 'Sábado'
}

const allDays = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday']

export default function WhatsAppScheduling() {
  const [templates, setTemplates] = useState<Template[]>([])
  const [loading, setLoading] = useState(true)
  const [showModal, setShowModal] = useState(false)
  const [currentTemplate, setCurrentTemplate] = useState<Template | null>(null)
  const [scheduledTime, setScheduledTime] = useState('09:00')
  const [selectedDays, setSelectedDays] = useState<string[]>([])

  useEffect(() => {
    loadTemplates()
  }, [])

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

  const handleEdit = (template: Template) => {
    setCurrentTemplate(template)
    setScheduledTime(template.scheduled_time || '09:00')
    
    try {
      const days = template.scheduled_days ? JSON.parse(template.scheduled_days) : []
      setSelectedDays(Array.isArray(days) ? days : [])
    } catch {
      setSelectedDays([])
    }
    
    setShowModal(true)
  }

  const handleSave = async () => {
    if (!currentTemplate) return

    if (selectedDays.length === 0) {
      toast.error('Selecione pelo menos um dia da semana')
      return
    }

    try {
      await api.put('/api-whatsapp-templates.php', {
        id: currentTemplate.id,
        is_scheduled: true,
        scheduled_days: selectedDays,
        scheduled_time: scheduledTime
      })

      toast.success('Agendamento salvo com sucesso!')
      setShowModal(false)
      loadTemplates()
    } catch (error) {
      toast.error('Erro ao salvar agendamento')
    }
  }

  const handleDisable = async (templateId: string) => {
    if (!confirm('Deseja desativar este agendamento?')) return

    try {
      await api.put('/api-whatsapp-templates.php', {
        id: templateId,
        is_scheduled: false,
        scheduled_days: [],
        scheduled_time: null
      })

      toast.success('Agendamento desativado!')
      loadTemplates()
    } catch (error) {
      toast.error('Erro ao desativar agendamento')
    }
  }

  const toggleDay = (day: string) => {
    setSelectedDays(prev =>
      prev.includes(day) ? prev.filter(d => d !== day) : [...prev, day]
    )
  }

  const toggleAllDays = () => {
    setSelectedDays(prev => prev.length === allDays.length ? [] : [...allDays])
  }

  const getTypeColor = (type: string) => typeColors[type] || '#6366f1'

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl md:text-3xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">Agendamento WhatsApp</h1>
        <p className="text-sm md:text-base text-gray-600 dark:text-gray-400 mt-1">
          Configure o envio automático de mensagens por template
        </p>
      </div>

      {loading ? (
        <LoadingSpinner />
      ) : templates.length === 0 ? (
        <div className="bg-white/80 dark:bg-gray-800/50 backdrop-blur-sm rounded-xl p-12 text-center border border-gray-200 dark:border-gray-700">
          <Calendar className="w-16 h-16 mx-auto text-gray-400 mb-4" />
          <h3 className="text-xl font-semibold text-gray-900 dark:text-white mb-2">
            Nenhum template encontrado
          </h3>
          <p className="text-gray-600 dark:text-gray-400 mb-4">
            Você precisa criar templates primeiro para configurar agendamentos.
          </p>
          <a
            href="/whatsapp/templates"
            className="inline-flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors"
          >
            Criar Template
          </a>
        </div>
      ) : (
        <div className="bg-white/80 dark:bg-gray-800/50 backdrop-blur-sm rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
          <div className="overflow-x-auto">
            <table className="w-full">
              <thead className="bg-gray-50 dark:bg-gray-900/50">
                <tr>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Template</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Tipo</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Dias da Semana</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Horário</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Status</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Ações</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-200 dark:divide-gray-700">
                {templates.map((template) => {
                  const color = getTypeColor(template.type)
                  const days = template.scheduled_days ? JSON.parse(template.scheduled_days) : []
                  
                  return (
                    <tr
                      key={template.id}
                      className={`hover:bg-gray-50 dark:hover:bg-gray-700/50 ${!template.is_scheduled ? 'opacity-60' : ''}`}
                    >
                      <td className="px-6 py-4">
                        <div className="flex items-center gap-3">
                          <div
                            className="w-10 h-10 rounded-lg flex items-center justify-center"
                            style={{ backgroundColor: `${color}20`, color }}
                          >
                            <Calendar className="w-5 h-5" />
                          </div>
                          <div>
                            <div className="text-sm font-medium text-gray-900 dark:text-white">
                              {template.name}
                            </div>
                            <div className="text-xs text-gray-500 dark:text-gray-400">
                              {template.title}
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
                      <td className="px-6 py-4">
                        <div className="flex flex-wrap gap-1">
                          {allDays.map(day => (
                            <span
                              key={day}
                              className={`px-2 py-1 text-xs font-medium rounded ${
                                days.includes(day)
                                  ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400'
                                  : 'bg-gray-100 text-gray-400 dark:bg-gray-800 dark:text-gray-600'
                              }`}
                            >
                              {dayLabels[day]}
                            </span>
                          ))}
                        </div>
                      </td>
                      <td className="px-6 py-4">
                        <div className="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                          <Clock className="w-4 h-4" />
                          {template.scheduled_time || '09:00'}
                        </div>
                      </td>
                      <td className="px-6 py-4">
                        {template.is_scheduled ? (
                          <span className="flex items-center gap-1 text-sm font-medium text-green-600 dark:text-green-400">
                            <Check className="w-4 h-4" />
                            Ativo
                          </span>
                        ) : (
                          <span className="flex items-center gap-1 text-sm font-medium text-gray-400">
                            <X className="w-4 h-4" />
                            Inativo
                          </span>
                        )}
                      </td>
                      <td className="px-6 py-4">
                        <div className="flex gap-2">
                          <button
                            onClick={() => handleEdit(template)}
                            className="text-blue-600 hover:text-blue-700 dark:text-blue-400"
                            title="Configurar"
                          >
                            <Edit className="w-4 h-4" />
                          </button>
                          {template.is_scheduled && (
                            <button
                              onClick={() => handleDisable(template.id)}
                              className="text-red-600 hover:text-red-700 dark:text-red-400"
                              title="Desativar"
                            >
                              <X className="w-4 h-4" />
                            </button>
                          )}
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

      {/* Modal de Configuração */}
      {showModal && currentTemplate && (
        <div className="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 p-4">
          <div className="bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div className="p-6 border-b border-gray-200 dark:border-gray-700">
              <div className="flex items-center justify-between">
                <h2 className="text-xl font-bold text-gray-900 dark:text-white">
                  Configurar Agendamento
                </h2>
                <button
                  onClick={() => setShowModal(false)}
                  className="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                >
                  <X className="w-6 h-6" />
                </button>
              </div>
            </div>

            <div className="p-6 space-y-6">
              {/* Template Info */}
              <div className="bg-gray-50 dark:bg-gray-900/50 rounded-lg p-4">
                <div className="flex items-center gap-3">
                  <div
                    className="w-12 h-12 rounded-lg flex items-center justify-center"
                    style={{
                      backgroundColor: `${getTypeColor(currentTemplate.type)}20`,
                      color: getTypeColor(currentTemplate.type)
                    }}
                  >
                    <Calendar className="w-6 h-6" />
                  </div>
                  <div>
                    <div className="font-medium text-gray-900 dark:text-white">
                      {currentTemplate.name}
                    </div>
                    <div className="text-sm text-gray-500 dark:text-gray-400">
                      {typeLabels[currentTemplate.type]}
                    </div>
                  </div>
                </div>
              </div>

              {/* Horário */}
              <div>
                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Horário do envio
                </label>
                <input
                  type="time"
                  value={scheduledTime}
                  onChange={(e) => setScheduledTime(e.target.value)}
                  className="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                />
              </div>

              {/* Dias da Semana */}
              <div>
                <div className="flex items-center justify-between mb-3">
                  <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Dias da semana
                  </label>
                  <button
                    type="button"
                    onClick={toggleAllDays}
                    className="text-sm text-blue-600 hover:text-blue-700 dark:text-blue-400"
                  >
                    {selectedDays.length === allDays.length ? 'Desmarcar Todos' : 'Marcar Todos'}
                  </button>
                </div>
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-2">
                  {allDays.map(day => (
                    <label
                      key={day}
                      className={`flex items-center gap-3 p-3 rounded-lg border-2 cursor-pointer transition-colors ${
                        selectedDays.includes(day)
                          ? 'border-green-500 bg-green-50 dark:bg-green-900/20'
                          : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600'
                      }`}
                    >
                      <input
                        type="checkbox"
                        checked={selectedDays.includes(day)}
                        onChange={() => toggleDay(day)}
                        className="w-4 h-4 text-green-600 rounded focus:ring-green-500"
                      />
                      <span className="text-sm font-medium text-gray-900 dark:text-white">
                        {dayFullLabels[day]}
                      </span>
                    </label>
                  ))}
                </div>
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
                Salvar
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  )
}
