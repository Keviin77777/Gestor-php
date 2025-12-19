import { useState, useEffect } from 'react'
import { Upload, Download, AlertCircle, CheckCircle, FileText, Play, Plus, ArrowLeft, Info, Trash2, ChevronLeft, ChevronRight } from 'lucide-react'
import toast from 'react-hot-toast'
import * as XLSX from 'xlsx'
import ConfirmModal from '@/components/ConfirmModal'

interface ClientData {
  index: number
  name: string
  username: string
  iptv_password: string
  phone: string
  renewal_date: string
  server: string
  application: string
  mac: string
  plan: string
  email: string
  value: number
  screens: number
  notes: string
  errors: string[]
  valid: boolean
  isSigmaFormat?: boolean
}

export default function ClientsImport() {
  const [currentStep, setCurrentStep] = useState(1)
  const [file, setFile] = useState<File | null>(null)
  const [parsedData, setParsedData] = useState<ClientData[]>([])
  const [servers, setServers] = useState<any[]>([])
  const [applications, setApplications] = useState<any[]>([])
  const [plans, setPlans] = useState<any[]>([])
  const [importing, setImporting] = useState(false)
  const [creatingPlans, setCreatingPlans] = useState(false)
  const [currentPage, setCurrentPage] = useState(1)
  const [itemsPerPage] = useState(20)
  const [showPlanWarningModal, setShowPlanWarningModal] = useState(false)
  const [missingPlans, setMissingPlans] = useState<string[]>([])
  const [showConfirmImportModal, setShowConfirmImportModal] = useState(false)

  useEffect(() => {
    loadServers()
    loadApplications()
    loadPlans()
  }, [])

  const loadServers = async () => {
    try {
      const response = await fetch('/api-servers.php', {
        headers: { 'Authorization': `Bearer ${localStorage.getItem('token')}` }
      })
      const data = await response.json()
      if (data.success) setServers(data.servers || [])
    } catch (error) {
      // Erro ao carregar servidores
    }
  }

  const loadApplications = async () => {
    try {
      const response = await fetch('/api-applications.php', {
        headers: { 'Authorization': `Bearer ${localStorage.getItem('token')}` }
      })
      const data = await response.json()
      if (data.success && data.applications) {
        setApplications(data.applications)
      } else {
        // Fallback
        setApplications([
          { id: 1, name: 'NextApp' },
          { id: 2, name: 'SmartIPTV' },
          { id: 3, name: 'IPTV Smarters' },
          { id: 4, name: 'TiviMate' }
        ])
      }
    } catch (error) {
      setApplications([
        { id: 1, name: 'NextApp' },
        { id: 2, name: 'SmartIPTV' },
        { id: 3, name: 'IPTV Smarters' },
        { id: 4, name: 'TiviMate' }
      ])
    }
  }

  const loadPlans = async () => {
    try {
      const response = await fetch('/api-plans.php', {
        headers: { 'Authorization': `Bearer ${localStorage.getItem('token')}` }
      })
      const data = await response.json()
      if (data.success && data.plans) {
        setPlans(data.plans.map((p: any) => ({
          id: p.id,
          name: p.name,
          price: parseFloat(p.price) || 0
        })))
      }
    } catch (error) {
      // Erro ao carregar planos
    }
  }

  const downloadTemplate = () => {
    const csvContent = 'nome,email,whatsapp,usuario_iptv,senha_iptv,vencimento,valor,servidor,mac,telas,plano,aplicativo\n' +
                       'João Silva,joao@email.com,11999999999,joao123,senha123,2024-12-31,50.00,Servidor1,00:00:00:00:00:01,2,Plano Básico,NextApp'
    
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' })
    const url = window.URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = 'template-importacao-clientes.csv'
    a.click()
    window.URL.revokeObjectURL(url)
    toast.success('Template baixado!')
  }

  const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const selectedFile = e.target.files?.[0]
    if (!selectedFile) return

    const isXlsx = selectedFile.name.endsWith('.xlsx')
    const isCsv = selectedFile.name.endsWith('.csv')

    if (!isXlsx && !isCsv) {
      toast.error('Apenas arquivos .xlsx ou .csv são aceitos')
      return
    }

    if (selectedFile.size > 10 * 1024 * 1024) {
      toast.error('Arquivo muito grande. Máximo 10MB')
      return
    }

    setFile(selectedFile)
    processFile(selectedFile)
  }

  const processFile = async (file: File) => {
    try {
      const data = await readExcelFile(file)
      const validated = validateData(data)
      
      setParsedData(validated)
      setCurrentStep(2)
      
      // Verificar se é formato Sigma
      const isSigma = validated.length > 0 && validated[0].isSigmaFormat
      if (isSigma) {
        toast.success(`${validated.length} clientes carregados (Formato Sigma detectado)`, { duration: 4000 })
      } else {
        toast.success(`${validated.length} clientes carregados`)
      }
    } catch (error: any) {
      toast.error('Erro ao processar arquivo: ' + error.message)
    }
  }

  const readExcelFile = (file: File): Promise<any[]> => {
    return new Promise((resolve, reject) => {
      const reader = new FileReader()
      const isCsv = file.name.endsWith('.csv')

      reader.onload = (e) => {
        try {
          let jsonData: any[]

          if (isCsv) {
            const text = e.target?.result as string
            const workbook = XLSX.read(text, { type: 'string' })
            const firstSheet = workbook.Sheets[workbook.SheetNames[0]]
            jsonData = XLSX.utils.sheet_to_json(firstSheet)
          } else {
            const data = new Uint8Array(e.target?.result as ArrayBuffer)
            const workbook = XLSX.read(data, { type: 'array' })
            const firstSheet = workbook.Sheets[workbook.SheetNames[0]]
            jsonData = XLSX.utils.sheet_to_json(firstSheet)
          }

          if (jsonData.length === 0) {
            reject(new Error('Planilha vazia'))
            return
          }

          if (jsonData.length > 1000) {
            reject(new Error('Máximo de 1000 clientes por importação'))
            return
          }

          resolve(jsonData)
        } catch (error) {
          reject(error)
        }
      }

      reader.onerror = () => reject(new Error('Erro ao ler arquivo'))

      if (isCsv) {
        reader.readAsText(file)
      } else {
        reader.readAsArrayBuffer(file)
      }
    })
  }

  const validateData = (data: any[]): ClientData[] => {
    return data.map((row, index) => {
      // Detectar formato Sigma (tem username, password, expiry_date e package)
      const isSigmaFormat = row.hasOwnProperty('username') && row.hasOwnProperty('password') && 
                           row.hasOwnProperty('expiry_date') && row.hasOwnProperty('package')

      let client: ClientData

      if (isSigmaFormat) {
        // Formato Sigma: username,password,expiry_date,connections,name,whatsapp,telegram,email,note,plan_price,server,package
        // Exemplo: alexfibra2,102030,"2026-08-09 23:59:59",1,,,,,,300.00,"CINE PULSE","COMPLETO C/ADULTOS 12 Meses"
        
        // Formatar data do Sigma (pode vir como "2026-08-09 23:59:59")
        let formattedDate = row.expiry_date || ''
        if (formattedDate && typeof formattedDate === 'string' && formattedDate.includes(' ')) {
          formattedDate = formattedDate.split(' ')[0] // Pegar apenas a parte da data
        }
        
        client = {
          index: index + 1,
          name: row.name || row.note || `Cliente ${index + 1}`,
          username: String(row.username || '').trim(),
          iptv_password: String(row.password || '').trim(),
          phone: row.whatsapp || row.telegram || '',
          renewal_date: formattedDate,
          server: row.server || '',
          application: 'NextApp', // Padrão para Sigma
          mac: '',
          plan: row.package || '',
          email: row.email || '',
          value: parseFloat(row.plan_price) || 0,
          screens: parseInt(row.connections) || 1,
          notes: row.note || '',
          errors: [],
          valid: true,
          isSigmaFormat: true
        }
      } else {
        // Formato padrão do sistema
        client = {
          index: index + 1,
          name: row.nome || row.Nome || row.NOME || row.name || '',
          username: row.usuario_iptv || row['Usuário IPTV'] || row.USUARIO_IPTV || row.username || '',
          iptv_password: row.senha_iptv || row['Senha IPTV'] || row.SENHA_IPTV || row.password || '',
          phone: row.whatsapp || row.WhatsApp || row.WHATSAPP || '',
          renewal_date: row.vencimento || row.Vencimento || row.VENCIMENTO || row.expiry_date || '',
          server: row.servidor || row.Servidor || row.SERVIDOR || row.server || '',
          application: row.aplicativo || row.Aplicativo || row.APLICATIVO || row.application || '',
          mac: row.mac || row.MAC || row.Mac || '',
          plan: row.plano || row.Plano || row.PLANO || row.package || '',
          email: row.email || row.Email || row.EMAIL || '',
          value: parseFloat(row.valor || row.value || row.plan_price || 0),
          screens: parseInt(row.telas || row.screens || row.connections || 1),
          notes: row.observacoes || row.notes || row.note || '',
          errors: [],
          valid: true,
          isSigmaFormat: false
        }
      }

      // Normalizar data para formato YYYY-MM-DD
      if (client.renewal_date) {
        client.renewal_date = formatDateForInput(client.renewal_date)
      }

      // Validar campos obrigatórios
      const errors: string[] = []
      if (!client.name) errors.push('Nome é obrigatório')
      if (!client.username) errors.push('Usuário IPTV é obrigatório')
      if (!client.iptv_password) errors.push('Senha IPTV é obrigatória')
      if (!client.phone) errors.push('WhatsApp é obrigatório')
      if (!client.renewal_date) errors.push('Vencimento é obrigatório')
      if (!client.server) errors.push('Servidor é obrigatório')
      if (!client.application) errors.push('Aplicativo é obrigatório')
      if (!client.plan) errors.push('Plano é obrigatório')

      client.errors = errors
      client.valid = errors.length === 0

      return client
    })
  }

  const createMissingPlans = async () => {
    if (servers.length === 0) {
      toast.error('Você precisa cadastrar pelo menos um servidor antes de criar planos')
      return
    }

    const uniquePlans = [...new Set(parsedData.map(c => c.plan).filter(p => p))]
    const plansToCreate = uniquePlans.filter(planName => !plans.find(p => p.name === planName))

    if (plansToCreate.length === 0) {
      toast.error('Todos os planos já existem no sistema')
      return
    }

    const selectedServerId = servers[0]?.id

    if (!window.confirm(`Deseja criar ${plansToCreate.length} plano(s) automaticamente no servidor "${servers[0]?.name}"?\n\n${plansToCreate.slice(0, 10).join('\n')}${plansToCreate.length > 10 ? '\n...' : ''}`)) {
      return
    }

    setCreatingPlans(true)
    let created = 0

    try {
      for (const planName of plansToCreate) {
        const clientWithPlan = parsedData.find(c => c.plan === planName)
        const planValue = clientWithPlan?.value || 25.00

        const response = await fetch('/api-plans.php', {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({
            name: planName,
            price: planValue,
            server_id: selectedServerId,
            duration_days: 30,
            max_screens: 1,
            description: 'Plano importado automaticamente da planilha'
          })
        })

        const data = await response.json()
        if (data.success) created++
      }

      await loadPlans()
      toast.success(`${created} plano(s) criado(s) com sucesso!`)
      
      // Revalidar dados
      setParsedData(validateData(parsedData))
    } catch (error) {
      toast.error('Erro ao criar planos')
    } finally {
      setCreatingPlans(false)
    }
  }

  const handleImport = async () => {
    const validClients = parsedData.filter(c => c.valid)

    if (validClients.length === 0) {
      toast.error('Nenhum cliente válido para importar')
      return
    }

    // Verificar se há planos que não existem no sistema
    const plansNotInSystem = validClients
      .map(c => c.plan)
      .filter((plan, index, self) => plan && !plans.find(p => p.name === plan) && self.indexOf(plan) === index)

    if (plansNotInSystem.length > 0) {
      setMissingPlans(plansNotInSystem)
      setShowPlanWarningModal(true)
      return
    }

    // Se não há planos faltando, mostrar confirmação normal
    setShowConfirmImportModal(true)
  }

  const confirmImport = async () => {
    setShowConfirmImportModal(false)
    setShowPlanWarningModal(false)
    
    const validClients = parsedData.filter(c => c.valid)
    setImporting(true)
    try {
      const response = await fetch('/api-clients-import.php', {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('token')}`,
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({ clients: validClients })
      })

      const data = await response.json()

      if (data.success) {
        toast.success(`${data.imported} cliente(s) importado(s) com sucesso!`)
        setTimeout(() => {
          window.location.href = '/clients'
        }, 2000)
      } else {
        toast.error('Erro ao importar clientes: ' + data.error)
      }
    } catch (error: any) {
      toast.error('Erro ao importar clientes')
    } finally {
      setImporting(false)
    }
  }

  const updateClient = (index: number, field: keyof ClientData, value: any) => {
    const updated = [...parsedData]
    updated[index] = { ...updated[index], [field]: value }

    // Se o campo for 'plan', atualizar o valor automaticamente
    if (field === 'plan' && value) {
      const selectedPlan = plans.find(p => p.name === value)
      if (selectedPlan && selectedPlan.price) {
        updated[index].value = selectedPlan.price
      }
    }

    // Revalidar
    const errors: string[] = []
    const client = updated[index]
    if (!client.name) errors.push('Nome é obrigatório')
    if (!client.username) errors.push('Usuário IPTV é obrigatório')
    if (!client.iptv_password) errors.push('Senha IPTV é obrigatória')
    if (!client.phone) errors.push('WhatsApp é obrigatório')
    if (!client.renewal_date) errors.push('Vencimento é obrigatório')
    if (!client.server) errors.push('Servidor é obrigatório')
    if (!client.plan) errors.push('Plano é obrigatório')
    if (!client.application) errors.push('Aplicativo é obrigatório')

    updated[index].errors = errors
    updated[index].valid = errors.length === 0

    setParsedData(updated)
  }

  const applyBulkServer = (value: string) => {
    if (!value) return
    const updated = parsedData.map(c => {
      const errors = []
      const updatedClient = { ...c, server: value }
      
      if (!updatedClient.name) errors.push('Nome é obrigatório')
      if (!updatedClient.username) errors.push('Usuário IPTV é obrigatório')
      if (!updatedClient.iptv_password) errors.push('Senha IPTV é obrigatória')
      if (!updatedClient.phone) errors.push('WhatsApp é obrigatório')
      if (!updatedClient.renewal_date) errors.push('Vencimento é obrigatório')
      if (!updatedClient.server) errors.push('Servidor é obrigatório')
      if (!updatedClient.plan) errors.push('Plano é obrigatório')
      if (!updatedClient.application) errors.push('Aplicativo é obrigatório')
      
      return { ...updatedClient, errors, valid: errors.length === 0 }
    })
    setParsedData(updated)
    toast.success(`Servidor "${value}" aplicado para todos os clientes`)
  }

  const applyBulkPlan = (value: string) => {
    if (!value) return
    const selectedPlan = plans.find(p => p.name === value)
    const updated = parsedData.map(c => {
      const errors = []
      const updatedClient = {
        ...c,
        plan: value,
        value: selectedPlan?.price || c.value
      }
      
      if (!updatedClient.name) errors.push('Nome é obrigatório')
      if (!updatedClient.username) errors.push('Usuário IPTV é obrigatório')
      if (!updatedClient.iptv_password) errors.push('Senha IPTV é obrigatória')
      if (!updatedClient.phone) errors.push('WhatsApp é obrigatório')
      if (!updatedClient.renewal_date) errors.push('Vencimento é obrigatório')
      if (!updatedClient.server) errors.push('Servidor é obrigatório')
      if (!updatedClient.plan) errors.push('Plano é obrigatório')
      if (!updatedClient.application) errors.push('Aplicativo é obrigatório')
      
      return { ...updatedClient, errors, valid: errors.length === 0 }
    })
    setParsedData(updated)
    toast.success(`Plano "${value}" aplicado para todos os clientes`)
  }

  const applyBulkApp = (value: string) => {
    if (!value) return
    const updated = parsedData.map(c => {
      const errors = []
      const updatedClient = { ...c, application: value }
      
      if (!updatedClient.name) errors.push('Nome é obrigatório')
      if (!updatedClient.username) errors.push('Usuário IPTV é obrigatório')
      if (!updatedClient.iptv_password) errors.push('Senha IPTV é obrigatória')
      if (!updatedClient.phone) errors.push('WhatsApp é obrigatório')
      if (!updatedClient.renewal_date) errors.push('Vencimento é obrigatório')
      if (!updatedClient.server) errors.push('Servidor é obrigatório')
      if (!updatedClient.plan) errors.push('Plano é obrigatório')
      if (!updatedClient.application) errors.push('Aplicativo é obrigatório')
      
      return { ...updatedClient, errors, valid: errors.length === 0 }
    })
    setParsedData(updated)
    toast.success(`Aplicativo "${value}" aplicado para todos os clientes`)
  }

  const removeExpiredClients = () => {
    const today = new Date().toISOString().split('T')[0]
    const expired = parsedData.filter(c => c.renewal_date && c.renewal_date < today)

    if (expired.length === 0) {
      toast.error('Nenhum cliente vencido encontrado')
      return
    }

    if (!window.confirm(`Deseja remover ${expired.length} cliente(s) vencido(s) da importação?\n\nEsta ação não pode ser desfeita.`)) {
      return
    }

    const filtered = parsedData.filter(c => !c.renewal_date || c.renewal_date >= today)
    setParsedData(filtered.map((c, idx) => ({ ...c, index: idx + 1 })))
    toast.success(`${expired.length} cliente(s) vencido(s) removido(s)`)
  }

  const removeTestClients = () => {
    const testKeywords = ['teste', 'test', 'demo', 'trial', 'prova']
    const testClients = parsedData.filter(c => {
      const name = (c.name || '').toLowerCase()
      const plan = (c.plan || '').toLowerCase()
      const username = (c.username || '').toLowerCase()
      return testKeywords.some(k => name.includes(k) || plan.includes(k) || username.includes(k))
    })

    if (testClients.length === 0) {
      toast.error('Nenhum cliente de teste encontrado')
      return
    }

    if (!window.confirm(`Deseja remover ${testClients.length} cliente(s) de teste da importação?\n\nSerão removidos clientes com "teste", "test", "demo", "trial" ou "prova" no nome, usuário ou plano.\n\nEsta ação não pode ser desfeita.`)) {
      return
    }

    const filtered = parsedData.filter(c => {
      const name = (c.name || '').toLowerCase()
      const plan = (c.plan || '').toLowerCase()
      const username = (c.username || '').toLowerCase()
      return !testKeywords.some(k => name.includes(k) || plan.includes(k) || username.includes(k))
    })

    setParsedData(filtered.map((c, idx) => ({ ...c, index: idx + 1 })))
    toast.success(`${testClients.length} cliente(s) de teste removido(s)`)
  }

  const formatDateForInput = (dateStr: string) => {
    if (!dateStr) return ''
    
    // Converter para string se for número
    const dateString = String(dateStr).trim()
    
    // Já está no formato YYYY-MM-DD
    if (/^\d{4}-\d{2}-\d{2}$/.test(dateString)) return dateString
    
    // Formato Sigma: "2026-08-09 23:59:59" -> "2026-08-09"
    if (/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/.test(dateString)) {
      return dateString.split(' ')[0]
    }
    
    // Formato DD/MM/YYYY -> YYYY-MM-DD
    if (/^\d{2}\/\d{2}\/\d{4}$/.test(dateString)) {
      const [day, month, year] = dateString.split('/')
      return `${year}-${month.padStart(2, '0')}-${day.padStart(2, '0')}`
    }
    
    // Formato DD-MM-YYYY -> YYYY-MM-DD
    if (/^\d{2}-\d{2}-\d{4}$/.test(dateString)) {
      const [day, month, year] = dateString.split('-')
      return `${year}-${month.padStart(2, '0')}-${day.padStart(2, '0')}`
    }
    
    // Serial number do Excel (número de dias desde 1900-01-01)
    if (!isNaN(Number(dateString)) && Number(dateString) > 0) {
      const excelEpoch = new Date(1899, 11, 30)
      const days = Math.floor(Number(dateString))
      const date = new Date(excelEpoch.getTime() + days * 86400000)
      
      if (!isNaN(date.getTime())) {
        const year = date.getFullYear()
        const month = String(date.getMonth() + 1).padStart(2, '0')
        const day = String(date.getDate()).padStart(2, '0')
        return `${year}-${month}-${day}`
      }
    }
    
    // Tentar parsear como Date (último recurso)
    try {
      const date = new Date(dateString)
      if (!isNaN(date.getTime())) {
        const year = date.getFullYear()
        const month = String(date.getMonth() + 1).padStart(2, '0')
        const day = String(date.getDate()).padStart(2, '0')
        return `${year}-${month}-${day}`
      }
    } catch (e) {
      // Erro ao formatar data
    }
    
    return ''
  }

  const resetImport = () => {
    setCurrentStep(1)
    setFile(null)
    setParsedData([])
  }

  const validClients = parsedData.filter(c => c.valid).length
  const invalidClients = parsedData.length - validClients
  const missingPlansInData = [...new Set(parsedData.map(c => c.plan).filter(p => p && !plans.find(pl => pl.name === p)))]

  // Paginação
  const totalPages = Math.ceil(parsedData.length / itemsPerPage)
  const startIndex = (currentPage - 1) * itemsPerPage
  const endIndex = startIndex + itemsPerPage
  const currentData = parsedData.slice(startIndex, endIndex)

  // Reset página ao mudar dados
  useEffect(() => {
    setCurrentPage(1)
  }, [parsedData.length])

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white">Importar Clientes</h1>
        <p className="text-sm md:text-base text-gray-600 dark:text-gray-400 mt-1">Importe clientes em massa via arquivo Excel ou CSV</p>
      </div>

      {/* Step 1: Método e Upload */}
      {currentStep === 1 && (
        <div className="space-y-6">
          {/* Alertas Informativos */}
          <div className="space-y-4">
            <div className="bg-yellow-50 dark:bg-yellow-900/30 border-l-4 border-yellow-500 dark:border-yellow-400 p-4 rounded-lg">
              <div className="flex gap-3">
                <AlertCircle className="w-5 h-5 text-yellow-600 dark:text-yellow-300 flex-shrink-0 mt-0.5" />
                <div className="text-sm text-yellow-800 dark:text-yellow-100">
                  <p className="font-semibold mb-1">IMPORTANTE: Campos obrigatórios</p>
                  <p className="text-yellow-700 dark:text-yellow-200">Sua planilha deve conter: Nome, Usuário IPTV, Senha IPTV, WhatsApp, Vencimento, Servidor, Aplicativo e Plano</p>
                </div>
              </div>
            </div>

            <div className="bg-blue-50 dark:bg-blue-900/30 border-l-4 border-blue-500 dark:border-blue-400 p-4 rounded-lg">
              <div className="flex gap-3">
                <Info className="w-5 h-5 text-blue-600 dark:text-blue-300 flex-shrink-0 mt-0.5" />
                <div className="text-sm text-blue-800 dark:text-blue-100">
                  <p className="font-semibold mb-1">DICA</p>
                  <p className="text-blue-700 dark:text-blue-200">Se sua planilha não contiver todos os campos, continue para o preview e preencha os campos com erro manualmente</p>
                </div>
              </div>
            </div>

            <div className="bg-green-50 dark:bg-green-900/30 border-l-4 border-green-500 dark:border-green-400 p-4 rounded-lg">
              <div className="flex gap-3">
                <CheckCircle className="w-5 h-5 text-green-600 dark:text-green-300 flex-shrink-0 mt-0.5" />
                <div className="text-sm text-green-800 dark:text-green-100">
                  <p className="font-semibold mb-1">SUPORTE SIGMA</p>
                  <p className="text-green-700 dark:text-green-200">O sistema detecta automaticamente exportações do painel Sigma (CSV) e mapeia os campos corretamente</p>
                </div>
              </div>
            </div>
          </div>

          {/* Upload e Template */}
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div className="bg-white/60 dark:bg-gray-800/30 backdrop-blur-md rounded-xl border border-gray-200/50 dark:border-gray-700/50 p-6">
              <h2 className="text-lg font-semibold text-gray-900 dark:text-white mb-4">1. Baixar Template</h2>
              <p className="text-sm text-gray-600 dark:text-gray-300 mb-4">Baixe o modelo CSV com as colunas corretas</p>
              <button onClick={downloadTemplate} className="w-full px-4 py-3 bg-primary-600 text-white rounded-lg hover:bg-primary-700 flex items-center justify-center gap-2 transition-all">
                <Download className="w-5 h-5" />
                Baixar Template
              </button>
            </div>

            <div className="bg-white/60 dark:bg-gray-800/30 backdrop-blur-md rounded-xl border border-gray-200/50 dark:border-gray-700/50 p-6">
              <h2 className="text-lg font-semibold text-gray-900 dark:text-white mb-4">2. Upload do Arquivo</h2>
              <div className="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-8 text-center hover:border-primary-500 dark:hover:border-primary-400 transition-colors">
                <Upload className="w-12 h-12 text-gray-400 dark:text-gray-500 mx-auto mb-4" />
                <input type="file" accept=".xlsx,.csv" onChange={handleFileChange} className="hidden" id="file-upload" />
                <label htmlFor="file-upload" className="cursor-pointer text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300 font-medium">
                  Selecionar arquivo Excel ou CSV
                </label>
                <p className="text-xs text-gray-500 dark:text-gray-300 mt-2">Máximo 10MB • Até 1000 clientes</p>
                {file && <p className="text-sm text-gray-700 dark:text-gray-200 mt-4 font-medium">📄 {file.name}</p>}
              </div>
            </div>
          </div>
        </div>
      )}

      {/* Step 2: Preview e Validação */}
      {currentStep === 2 && (
        <div className="space-y-6">
          {/* Botão Voltar */}
          <button onClick={resetImport} className="flex items-center gap-2 px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors font-medium border border-gray-300 dark:border-gray-600">
            <ArrowLeft className="w-4 h-4" />
            Voltar
          </button>

          {/* Estatísticas */}
          <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div className="bg-white/60 dark:bg-gray-800/30 backdrop-blur-md rounded-xl border border-gray-200/50 dark:border-gray-700/50 p-6">
              <div className="flex items-center gap-4">
                <div className="w-12 h-12 bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg flex items-center justify-center">
                  <FileText className="w-6 h-6 text-white" />
                </div>
                <div>
                  <p className="text-2xl font-bold text-gray-900 dark:text-white">{parsedData.length}</p>
                  <p className="text-sm text-gray-600 dark:text-gray-400">Total de Clientes</p>
                </div>
              </div>
            </div>

            <div className="bg-white/60 dark:bg-gray-800/30 backdrop-blur-md rounded-xl border border-green-200/50 dark:border-green-700/50 p-6">
              <div className="flex items-center gap-4">
                <div className="w-12 h-12 bg-gradient-to-br from-green-500 to-green-600 rounded-lg flex items-center justify-center">
                  <CheckCircle className="w-6 h-6 text-white" />
                </div>
                <div>
                  <p className="text-2xl font-bold text-green-600 dark:text-green-400">{validClients}</p>
                  <p className="text-sm text-gray-600 dark:text-gray-400">Válidos</p>
                </div>
              </div>
            </div>

            <div className="bg-white/60 dark:bg-gray-800/30 backdrop-blur-md rounded-xl border border-red-200/50 dark:border-red-700/50 p-6">
              <div className="flex items-center gap-4">
                <div className="w-12 h-12 bg-gradient-to-br from-red-500 to-red-600 rounded-lg flex items-center justify-center">
                  <AlertCircle className="w-6 h-6 text-white" />
                </div>
                <div>
                  <p className="text-2xl font-bold text-red-600 dark:text-red-400">{invalidClients}</p>
                  <p className="text-sm text-gray-600 dark:text-gray-400">Com Erros</p>
                </div>
              </div>
            </div>
          </div>

          {/* Ações em Massa e Botões */}
          <div className="bg-white/60 dark:bg-gray-800/30 backdrop-blur-md rounded-xl border border-gray-200/50 dark:border-gray-700/50 p-6 space-y-4">
            {/* Ações em Massa */}
            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div>
                <label className="block text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase mb-2">Servidor para Todos</label>
                <select onChange={(e) => applyBulkServer(e.target.value)} className="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 dark:focus:ring-primary-400 transition-colors">
                  <option value="">Selecione...</option>
                  {servers.map(s => <option key={s.id} value={s.name}>{s.name}</option>)}
                </select>
              </div>

              <div>
                <label className="block text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase mb-2">Plano para Todos</label>
                <select onChange={(e) => applyBulkPlan(e.target.value)} className="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 dark:focus:ring-primary-400 transition-colors">
                  <option value="">Selecione...</option>
                  {plans.map(p => <option key={p.id} value={p.name}>{p.name}</option>)}
                </select>
              </div>

              <div>
                <label className="block text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase mb-2">App para Todos</label>
                <select onChange={(e) => applyBulkApp(e.target.value)} className="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 dark:focus:ring-primary-400 transition-colors">
                  <option value="">Selecione...</option>
                  {applications.map(a => <option key={a.id} value={a.name}>{a.name}</option>)}
                </select>
              </div>
            </div>

            {/* Ações Rápidas */}
            <div className="border-t border-gray-200 dark:border-gray-700 pt-4">
              <label className="block text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase mb-3">Ações Rápidas</label>
              <div className="flex flex-wrap gap-2">
                <button onClick={createMissingPlans} disabled={creatingPlans || missingPlansInData.length === 0} className="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50 flex items-center gap-2 text-sm">
                  <Plus className="w-4 h-4" />
                  {creatingPlans ? 'Criando...' : `Criar Planos (${missingPlansInData.length})`}
                </button>
                <button onClick={removeExpiredClients} className="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 flex items-center gap-2 text-sm">
                  <Trash2 className="w-4 h-4" />
                  Excluir Vencidos
                </button>
                <button onClick={removeTestClients} className="px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 flex items-center gap-2 text-sm">
                  <Trash2 className="w-4 h-4" />
                  Excluir Testes
                </button>
              </div>
            </div>

            {/* Botões de Ação */}
            <div className="border-t border-gray-200 dark:border-gray-700 pt-4 flex flex-col sm:flex-row gap-3">
              <button onClick={resetImport} className="px-6 py-3 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 font-medium rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                Cancelar
              </button>
              <button onClick={handleImport} disabled={importing || invalidClients > 0} className="flex-1 px-6 py-3 bg-gradient-to-r from-green-600 to-green-700 text-white rounded-lg hover:from-green-700 hover:to-green-800 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2 transition-all">
                {importing ? (
                  <>
                    <div className="animate-spin rounded-full h-5 w-5 border-b-2 border-white"></div>
                    Importando...
                  </>
                ) : (
                  <>
                    <Play className="w-5 h-5" />
                    Importar {validClients} Clientes
                  </>
                )}
              </button>
            </div>
          </div>

          {/* Tabela de Preview */}
          <div className="bg-white/60 dark:bg-gray-800/30 backdrop-blur-md rounded-xl border border-gray-200/50 dark:border-gray-700/50 overflow-hidden">
            <div className="p-4 sm:p-6 border-b border-gray-200 dark:border-gray-700">
              <h2 className="text-lg sm:text-xl font-semibold text-gray-900 dark:text-white">Preview dos Dados</h2>
              <p className="text-sm text-gray-600 dark:text-gray-300 mt-1">Revise e edite os dados antes de importar • Página {currentPage} de {totalPages}</p>
            </div>

            {/* Indicador de scroll mobile */}
            <div className="block sm:hidden px-4 py-2 bg-blue-50 dark:bg-blue-900/30 border-b border-blue-200 dark:border-blue-700">
              <p className="text-xs text-blue-700 dark:text-blue-200 flex items-center gap-2 font-medium">
                <Info className="w-4 h-4" />
                Deslize para o lado para ver todos os campos
              </p>
            </div>

            <div className="overflow-x-auto">
              <table className="w-full text-sm min-w-[1200px]">
                <thead className="bg-gray-100 dark:bg-gray-900/50 sticky top-0">
                  <tr>
                    <th className="px-3 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-200 uppercase">#</th>
                    <th className="px-3 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-200 uppercase">Nome</th>
                    <th className="px-3 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-200 uppercase">Usuário</th>
                    <th className="px-3 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-200 uppercase">Senha</th>
                    <th className="px-3 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-200 uppercase">WhatsApp</th>
                    <th className="px-3 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-200 uppercase">Vencimento</th>
                    <th className="px-3 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-200 uppercase">Servidor</th>
                    <th className="px-3 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-200 uppercase">Plano</th>
                    <th className="px-3 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-200 uppercase">App</th>
                    <th className="px-3 py-3 text-center text-xs font-semibold text-gray-700 dark:text-gray-200 uppercase">Status</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-gray-200 dark:divide-gray-700/50">
                  {currentData.map((client) => {
                    const idx = parsedData.findIndex(c => c.index === client.index)
                    return (
                    <tr key={client.index} className="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                      <td className="px-3 py-2 text-center text-gray-600 dark:text-gray-400 font-medium">{client.index}</td>
                      <td className="px-3 py-2">
                        <input type="text" value={client.name} onChange={(e) => updateClient(idx, 'name', e.target.value)} className={`w-full px-2 py-1 rounded border text-sm transition-colors ${!client.name ? 'border-red-500 bg-red-50 dark:bg-red-900/30 dark:border-red-400 text-gray-900 dark:text-red-100' : 'border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100'} focus:outline-none focus:ring-2 focus:ring-primary-500 dark:focus:ring-primary-400`} placeholder="Nome" />
                      </td>
                      <td className="px-3 py-2">
                        <input type="text" value={client.username} onChange={(e) => updateClient(idx, 'username', e.target.value)} className={`w-full px-2 py-1 rounded border text-sm transition-colors ${!client.username ? 'border-red-500 bg-red-50 dark:bg-red-900/30 dark:border-red-400 text-gray-900 dark:text-red-100' : 'border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100'} focus:outline-none focus:ring-2 focus:ring-primary-500 dark:focus:ring-primary-400`} placeholder="Usuário" />
                      </td>
                      <td className="px-3 py-2">
                        <input type="text" value={client.iptv_password} onChange={(e) => updateClient(idx, 'iptv_password', e.target.value)} className={`w-full px-2 py-1 rounded border text-sm transition-colors ${!client.iptv_password ? 'border-red-500 bg-red-50 dark:bg-red-900/30 dark:border-red-400 text-gray-900 dark:text-red-100' : 'border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100'} focus:outline-none focus:ring-2 focus:ring-primary-500 dark:focus:ring-primary-400`} placeholder="Senha" />
                      </td>
                      <td className="px-3 py-2">
                        <input type="text" value={client.phone} onChange={(e) => updateClient(idx, 'phone', e.target.value)} className={`w-full px-2 py-1 rounded border text-sm transition-colors ${!client.phone ? 'border-red-500 bg-red-50 dark:bg-red-900/30 dark:border-red-400 text-gray-900 dark:text-red-100' : 'border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100'} focus:outline-none focus:ring-2 focus:ring-primary-500 dark:focus:ring-primary-400`} placeholder="11999999999" />
                      </td>
                      <td className="px-3 py-2">
                        <input type="date" value={formatDateForInput(client.renewal_date)} onChange={(e) => updateClient(idx, 'renewal_date', e.target.value)} className={`w-full px-2 py-1 rounded border text-sm transition-colors ${!client.renewal_date ? 'border-red-500 bg-red-50 dark:bg-red-900/30 dark:border-red-400 text-gray-900 dark:text-red-100' : 'border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100'} focus:outline-none focus:ring-2 focus:ring-primary-500 dark:focus:ring-primary-400`} />
                      </td>
                      <td className="px-3 py-2">
                        <select value={client.server} onChange={(e) => updateClient(idx, 'server', e.target.value)} className={`w-full px-2 py-1 rounded border text-sm transition-colors ${!client.server ? 'border-red-500 bg-red-50 dark:bg-red-900/30 dark:border-red-400 text-gray-900 dark:text-red-100' : 'border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100'} focus:outline-none focus:ring-2 focus:ring-primary-500 dark:focus:ring-primary-400`}>
                          <option value="">Selecione...</option>
                          {servers.map(s => <option key={s.id} value={s.name}>{s.name}</option>)}
                        </select>
                      </td>
                      <td className="px-3 py-2">
                        <select value={client.plan} onChange={(e) => updateClient(idx, 'plan', e.target.value)} className={`w-full px-2 py-1 rounded border text-sm transition-colors ${!client.plan ? 'border-red-500 bg-red-50 dark:bg-red-900/30 dark:border-red-400 text-gray-900 dark:text-red-100' : 'border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100'} focus:outline-none focus:ring-2 focus:ring-primary-500 dark:focus:ring-primary-400`}>
                          <option value="">Selecione...</option>
                          {client.plan && !plans.find(p => p.name === client.plan) && (
                            <option value={client.plan} className="text-yellow-600 dark:text-yellow-400">⚠️ {client.plan} (Criar)</option>
                          )}
                          {plans.map(p => <option key={p.id} value={p.name}>{p.name}</option>)}
                        </select>
                      </td>
                      <td className="px-3 py-2">
                        <select value={client.application} onChange={(e) => updateClient(idx, 'application', e.target.value)} className={`w-full px-2 py-1 rounded border text-sm transition-colors ${!client.application ? 'border-red-500 bg-red-50 dark:bg-red-900/30 dark:border-red-400 text-gray-900 dark:text-red-100' : 'border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100'} focus:outline-none focus:ring-2 focus:ring-primary-500 dark:focus:ring-primary-400`}>
                          <option value="">Selecione...</option>
                          {applications.map(a => <option key={a.id} value={a.name}>{a.name}</option>)}
                        </select>
                      </td>
                      <td className="px-3 py-2 text-center">
                        {client.valid ? (
                          <span className="inline-flex items-center gap-1 px-2 py-1 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 rounded-full text-xs font-medium">
                            <CheckCircle className="w-3 h-3" />
                            Válido
                          </span>
                        ) : (
                          <span className="inline-flex items-center gap-1 px-2 py-1 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 rounded-full text-xs font-medium" title={client.errors.join(', ')}>
                            <AlertCircle className="w-3 h-3" />
                            Erro
                          </span>
                        )}
                      </td>
                    </tr>
                  )})}
                </tbody>
              </table>
            </div>

            {/* Paginação */}
            {totalPages > 1 && (
              <div className="p-4 border-t border-gray-200 dark:border-gray-700 flex flex-col sm:flex-row items-center justify-between gap-4">
                <div className="text-sm text-gray-700 dark:text-gray-200 font-medium">
                  Mostrando {startIndex + 1} a {Math.min(endIndex, parsedData.length)} de {parsedData.length} clientes
                </div>
                
                <div className="flex items-center gap-2">
                  <button
                    onClick={() => setCurrentPage(p => Math.max(1, p - 1))}
                    disabled={currentPage === 1}
                    className="p-2 rounded-lg border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                  >
                    <ChevronLeft className="w-5 h-5" />
                  </button>

                  {/* Páginas */}
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
                          className={`w-10 h-10 rounded-lg font-medium transition-colors ${
                            currentPage === pageNum
                              ? 'bg-primary-600 text-white'
                              : 'border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800'
                          }`}
                        >
                          {pageNum}
                        </button>
                      )
                    })}
                  </div>

                  <button
                    onClick={() => setCurrentPage(p => Math.min(totalPages, p + 1))}
                    disabled={currentPage === totalPages}
                    className="p-2 rounded-lg border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                  >
                    <ChevronRight className="w-5 h-5" />
                  </button>
                </div>
              </div>
            )}
          </div>
        </div>
      )}

      {/* Modal de Aviso - Planos Inexistentes */}
      <ConfirmModal
        isOpen={showPlanWarningModal}
        onCancel={() => setShowPlanWarningModal(false)}
        onConfirm={confirmImport}
        title="⚠️ Planos Não Encontrados"
        message={
          <div className="space-y-4">
            <p className="text-gray-700 dark:text-gray-300">
              Os seguintes planos não existem no sistema:
            </p>
            <div className="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-3">
              <ul className="space-y-1">
                {missingPlans.map((plan, index) => (
                  <li key={index} className="text-yellow-800 dark:text-yellow-300 flex items-center gap-2">
                    <span className="w-2 h-2 bg-yellow-500 rounded-full"></span>
                    {plan}
                  </li>
                ))}
              </ul>
            </div>
            <div className="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-3">
              <p className="text-blue-800 dark:text-blue-300 font-medium mb-2">💡 Opções disponíveis:</p>
              <ul className="text-blue-700 dark:text-blue-400 text-sm space-y-1">
                <li>1. Clique em "Criar Planos" acima</li>
                <li>2. Altere manualmente os planos na tabela</li>
                <li>3. Use o filtro "Plano para Todos"</li>
              </ul>
            </div>
            <p className="text-gray-700 dark:text-gray-300">
              Deseja continuar a importação mesmo assim?
            </p>
          </div>
        }
        confirmText="Continuar Importação"
        cancelText="Cancelar"
        type="warning"
      />

      {/* Modal de Confirmação - Importação Normal */}
      <ConfirmModal
        isOpen={showConfirmImportModal}
        onCancel={() => setShowConfirmImportModal(false)}
        onConfirm={confirmImport}
        title="Confirmar Importação"
        message={`Deseja importar ${parsedData.filter(c => c.valid).length} cliente(s)?`}
        confirmText="Importar"
        cancelText="Cancelar"
        type="info"
      />
    </div>
  )
}
