import { BrowserRouter, Routes, Route, Navigate, useLocation } from 'react-router-dom'
import { Toaster } from 'react-hot-toast'
import { useAuthStore } from './stores/useAuthStore'
import { useEffect, useState } from 'react'
import api from './services/api'

// Layouts
import DashboardLayout from './components/layouts/DashboardLayout'

// Pages
import Dashboard from './pages/Dashboard'
import Clients from './pages/Clients'
import ClientsImport from './pages/ClientsImport'
import Invoices from './pages/Invoices'
import Servers from './pages/Servers'
import PaymentMethods from './pages/PaymentMethods'
import WhatsAppConnect from './pages/WhatsAppConnect'
import WhatsAppTemplates from './pages/WhatsAppTemplates'
import WhatsAppScheduling from './pages/WhatsAppScheduling'
import WhatsAppQueue from './pages/WhatsAppQueue'
import Plans from './pages/Plans'
import Applications from './pages/Applications'
import Profile from './pages/Profile'
import Reports from './pages/Reports'
import FinancialReport from './pages/FinancialReport'
import MonthlyReport from './pages/MonthlyReport'
import RenewAccess from './pages/RenewAccess'
import Login from './pages/Login'
import Landing from './pages/Landing'

// Admin Pages
import Resellers from './pages/admin/Resellers'
import ResellerPlans from './pages/admin/ResellerPlans'
import PaymentHistory from './pages/admin/PaymentHistory'
import ResellerNotifications from './pages/admin/ResellerNotifications'

// Componente para verificar status do plano
function PlanGuard({ children }: { children: React.ReactNode }) {
  const location = useLocation()
  const [planStatus, setPlanStatus] = useState<{
    isExpired: boolean
    isAdmin: boolean
    daysRemaining: number
  } | null>(null)
  const [checking, setChecking] = useState(true)

  useEffect(() => {
    checkPlanStatus()
  }, [])

  const checkPlanStatus = async () => {
    try {
      const response = await api.get('/api-auth-me.php')
      if (response.data.success) {
        const userData = response.data.user
        const isAdmin = userData.is_admin || userData.role === 'admin'
        const daysRemaining = userData.days_remaining || 0
        const isExpired = !isAdmin && daysRemaining < 0

        setPlanStatus({
          isExpired,
          isAdmin,
          daysRemaining
        })
      }
    } catch (error) {
      console.error('Erro ao verificar plano:', error)
    } finally {
      setChecking(false)
    }
  }

  if (checking) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-gray-50 dark:bg-gray-900">
        <div className="text-center">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
          <p className="mt-4 text-gray-600 dark:text-gray-400">Verificando plano...</p>
        </div>
      </div>
    )
  }

  // Se o plano está vencido e não está na página de renovação, redirecionar
  if (planStatus?.isExpired && location.pathname !== '/renew-access') {
    return <Navigate to="/renew-access" replace />
  }

  // Se o plano está ativo e está na página de renovação, permitir (pode querer renovar antecipadamente)
  return <>{children}</>
}

function App() {
  const { isAuthenticated, isLoading, loadFromStorage } = useAuthStore()

  useEffect(() => {
    loadFromStorage()
  }, [loadFromStorage])

  // Mostrar loading enquanto verifica autenticação
  if (isLoading) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-gray-50 dark:bg-gray-900">
        <div className="text-center">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
          <p className="mt-4 text-gray-600 dark:text-gray-400">Carregando...</p>
        </div>
      </div>
    )
  }

  if (!isAuthenticated) {
    return (
      <>
        <BrowserRouter>
          <Routes>
            <Route path="/" element={<Landing />} />
            <Route path="/login" element={<Login />} />
            <Route path="*" element={<Navigate to="/login" replace />} />
          </Routes>
        </BrowserRouter>
        <Toaster position="top-right" />
      </>
    )
  }

  return (
    <>
      <BrowserRouter>
        <PlanGuard>
          <Routes>
            <Route element={<DashboardLayout />}>
              <Route path="/dashboard" element={<Dashboard />} />
              <Route path="/renew-access" element={<RenewAccess />} />
              <Route path="/clients" element={<Clients />} />
              <Route path="/clients/import" element={<ClientsImport />} />
              <Route path="/plans" element={<Plans />} />
              <Route path="/applications" element={<Applications />} />
              <Route path="/invoices" element={<Invoices />} />
              <Route path="/servers" element={<Servers />} />
              <Route path="/payment-methods" element={<PaymentMethods />} />
              <Route path="/whatsapp" element={<WhatsAppConnect />} />
              <Route path="/whatsapp/templates" element={<WhatsAppTemplates />} />
              <Route path="/whatsapp/scheduling" element={<WhatsAppScheduling />} />
              <Route path="/whatsapp/queue" element={<WhatsAppQueue />} />
              <Route path="/profile" element={<Profile />} />
              <Route path="/reports" element={<Reports />} />
              <Route path="/reports/financial" element={<FinancialReport />} />
              <Route path="/reports/monthly" element={<MonthlyReport />} />
              
              {/* Admin Routes */}
              <Route path="/admin/resellers" element={<Resellers />} />
              <Route path="/admin/reseller-plans" element={<ResellerPlans />} />
              <Route path="/admin/payment-history" element={<PaymentHistory />} />
              <Route path="/admin/reseller-notifications" element={<ResellerNotifications />} />
            </Route>
            {/* Redirecionar apenas rotas públicas para dashboard quando autenticado */}
            <Route path="/" element={<Navigate to="/dashboard" replace />} />
            <Route path="/login" element={<Navigate to="/dashboard" replace />} />
          </Routes>
        </PlanGuard>
      </BrowserRouter>
      <Toaster position="top-right" />
    </>
  )
}

export default App
