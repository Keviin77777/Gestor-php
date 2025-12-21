import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom'
import { Toaster } from 'react-hot-toast'
import { useAuthStore } from './stores/useAuthStore'
import { useEffect } from 'react'

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

function App() {
  const { isAuthenticated, loadFromStorage } = useAuthStore()

  useEffect(() => {
    loadFromStorage()
  }, [loadFromStorage])

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
          {/* Redirecionar rotas públicas para dashboard quando autenticado */}
          <Route path="/" element={<Navigate to="/dashboard" replace />} />
          <Route path="/login" element={<Navigate to="/dashboard" replace />} />
          <Route path="*" element={<Navigate to="/dashboard" replace />} />
        </Routes>
      </BrowserRouter>
      <Toaster position="top-right" />
    </>
  )
}

export default App
