import { useState } from 'react'
import { Upload, Download, AlertCircle, CheckCircle } from 'lucide-react'
import toast from 'react-hot-toast'
import api from '../services/api'

export default function ClientsImport() {
  const [file, setFile] = useState<File | null>(null)
  const [importing, setImporting] = useState(false)
  const [result, setResult] = useState<any>(null)

  const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    if (e.target.files && e.target.files[0]) {
      setFile(e.target.files[0])
      setResult(null)
    }
  }

  const handleImport = async () => {
    if (!file) {
      toast.error('Selecione um arquivo CSV')
      return
    }

    setImporting(true)
    const formData = new FormData()
    formData.append('file', file)

    try {
      const response = await api.post('/api-clients-import.php', formData, {
        headers: {
          'Content-Type': 'multipart/form-data'
        }
      })
      
      setResult(response.data)
      toast.success('Importação concluída!')
    } catch (error: any) {
      toast.error(error.response?.data?.message || 'Erro ao importar clientes')
    } finally {
      setImporting(false)
    }
  }

  const downloadTemplate = () => {
    const csvContent = 'name,email,phone,username,iptv_password,renewal_date,value,server,mac,screens,plan,application_id\n' +
                       'João Silva,joao@email.com,11999999999,joao123,senha123,2024-12-31,50.00,servidor1,00:00:00:00:00:01,2,Plano Básico,1'
    
    const blob = new Blob([csvContent], { type: 'text/csv' })
    const url = window.URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = 'template-importacao-clientes.csv'
    a.click()
    window.URL.revokeObjectURL(url)
  }

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Importar Clientes</h1>
        <p className="text-gray-600 dark:text-gray-400 mt-1">Importe clientes em massa via arquivo CSV</p>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* Upload Section */}
        <div className="bg-white/80 dark:bg-gray-800/50 backdrop-blur-sm rounded-xl p-6 border border-gray-200 dark:border-gray-700">
          <h2 className="text-lg font-semibold text-gray-900 dark:text-white mb-4">Upload do Arquivo</h2>
          
          <div className="space-y-4">
            <div className="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-8 text-center">
              <Upload className="w-12 h-12 text-gray-400 mx-auto mb-4" />
              <input
                type="file"
                accept=".csv"
                onChange={handleFileChange}
                className="hidden"
                id="file-upload"
              />
              <label
                htmlFor="file-upload"
                className="cursor-pointer text-primary-600 hover:text-primary-700 font-medium"
              >
                Clique para selecionar
              </label>
              <p className="text-sm text-gray-500 dark:text-gray-400 mt-2">
                ou arraste o arquivo CSV aqui
              </p>
              {file && (
                <p className="text-sm text-gray-700 dark:text-gray-300 mt-4 font-medium">
                  Arquivo selecionado: {file.name}
                </p>
              )}
            </div>

            <button
              onClick={handleImport}
              disabled={!file || importing}
              className="w-full px-4 py-3 bg-primary-600 text-white rounded-lg hover:bg-primary-700 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
            >
              {importing ? (
                <>
                  <div className="animate-spin rounded-full h-5 w-5 border-b-2 border-white"></div>
                  Importando...
                </>
              ) : (
                <>
                  <Upload className="w-5 h-5" />
                  Importar Clientes
                </>
              )}
            </button>
          </div>
        </div>

        {/* Instructions Section */}
        <div className="bg-white/80 dark:bg-gray-800/50 backdrop-blur-sm rounded-xl p-6 border border-gray-200 dark:border-gray-700">
          <h2 className="text-lg font-semibold text-gray-900 dark:text-white mb-4">Instruções</h2>
          
          <div className="space-y-4">
            <div className="flex items-start gap-3">
              <div className="flex-shrink-0 w-6 h-6 bg-primary-100 dark:bg-primary-900/30 text-primary-600 dark:text-primary-400 rounded-full flex items-center justify-center text-sm font-bold">
                1
              </div>
              <div>
                <p className="text-sm text-gray-700 dark:text-gray-300">
                  Baixe o template CSV clicando no botão abaixo
                </p>
              </div>
            </div>

            <div className="flex items-start gap-3">
              <div className="flex-shrink-0 w-6 h-6 bg-primary-100 dark:bg-primary-900/30 text-primary-600 dark:text-primary-400 rounded-full flex items-center justify-center text-sm font-bold">
                2
              </div>
              <div>
                <p className="text-sm text-gray-700 dark:text-gray-300">
                  Preencha o arquivo com os dados dos clientes
                </p>
              </div>
            </div>

            <div className="flex items-start gap-3">
              <div className="flex-shrink-0 w-6 h-6 bg-primary-100 dark:bg-primary-900/30 text-primary-600 dark:text-primary-400 rounded-full flex items-center justify-center text-sm font-bold">
                3
              </div>
              <div>
                <p className="text-sm text-gray-700 dark:text-gray-300">
                  Faça o upload do arquivo preenchido
                </p>
              </div>
            </div>

            <button
              onClick={downloadTemplate}
              className="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 flex items-center justify-center gap-2"
            >
              <Download className="w-5 h-5" />
              Baixar Template CSV
            </button>
          </div>

          <div className="mt-6 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
            <div className="flex gap-2">
              <AlertCircle className="w-5 h-5 text-blue-600 dark:text-blue-400 flex-shrink-0" />
              <div className="text-sm text-blue-800 dark:text-blue-300">
                <p className="font-medium mb-1">Campos obrigatórios:</p>
                <ul className="list-disc list-inside space-y-1">
                  <li>Nome</li>
                  <li>Email</li>
                  <li>Telefone</li>
                  <li>Username</li>
                  <li>Data de Renovação</li>
                </ul>
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* Results Section */}
      {result && (
        <div className="bg-white/80 dark:bg-gray-800/50 backdrop-blur-sm rounded-xl p-6 border border-gray-200 dark:border-gray-700">
          <h2 className="text-lg font-semibold text-gray-900 dark:text-white mb-4">Resultado da Importação</h2>
          
          <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div className="p-4 bg-green-50 dark:bg-green-900/20 rounded-lg">
              <div className="flex items-center gap-2 text-green-600 dark:text-green-400 mb-1">
                <CheckCircle className="w-5 h-5" />
                <span className="font-medium">Importados</span>
              </div>
              <p className="text-2xl font-bold text-green-700 dark:text-green-300">
                {result.imported || 0}
              </p>
            </div>

            <div className="p-4 bg-red-50 dark:bg-red-900/20 rounded-lg">
              <div className="flex items-center gap-2 text-red-600 dark:text-red-400 mb-1">
                <AlertCircle className="w-5 h-5" />
                <span className="font-medium">Erros</span>
              </div>
              <p className="text-2xl font-bold text-red-700 dark:text-red-300">
                {result.errors?.length || 0}
              </p>
            </div>

            <div className="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
              <div className="flex items-center gap-2 text-blue-600 dark:text-blue-400 mb-1">
                <AlertCircle className="w-5 h-5" />
                <span className="font-medium">Total</span>
              </div>
              <p className="text-2xl font-bold text-blue-700 dark:text-blue-300">
                {result.total || 0}
              </p>
            </div>
          </div>

          {result.errors && result.errors.length > 0 && (
            <div>
              <h3 className="font-medium text-gray-900 dark:text-white mb-2">Erros encontrados:</h3>
              <div className="space-y-2 max-h-64 overflow-y-auto">
                {result.errors.map((error: any, index: number) => (
                  <div key={index} className="p-3 bg-red-50 dark:bg-red-900/20 rounded-lg text-sm">
                    <p className="text-red-800 dark:text-red-300">
                      <span className="font-medium">Linha {error.line}:</span> {error.message}
                    </p>
                  </div>
                ))}
              </div>
            </div>
          )}
        </div>
      )}
    </div>
  )
}
