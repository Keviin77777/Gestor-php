export interface User {
  id: string
  name: string
  email: string
  role: string
  phone?: string
  is_admin?: boolean
  plan_name?: string
  plan_expires_at?: string
  account_status?: string
  days_remaining?: number
}

export interface Client {
  id: string
  name: string
  email?: string
  phone?: string
  username?: string
  password?: string
  iptv_password?: string
  plan: string
  value: number
  renewal_date: string
  status: 'active' | 'inactive' | 'suspended' | 'expired'
  notes?: string
  server?: string
  mac?: string
  notifications?: 'sim' | 'nao'
  screens?: number
  application_id?: number
  application_name?: string
  applications?: string[]
  created_at?: string
}

export interface Invoice {
  id: string
  client_id: string
  client_name: string
  description?: string
  value?: number
  discount?: number
  final_value: number
  due_date: string
  status: 'pending' | 'paid' | 'overdue'
  payment_date?: string
  payment_link?: string
  created_at: string
  updated_at?: string
}

export interface Server {
  id: number
  name: string
  billing_type: 'fixed' | 'per_active'
  cost: string | number
  panel_type?: string | null
  panel_url?: string | null
  reseller_user?: string | null
  sigma_token?: string
  connected_clients?: number
  total_cost?: number
  status?: string
  created_at: string
}

export interface PaymentMethod {
  method_name: string
  enabled: boolean
  config_value: string
  updated_at: string
}

export interface WhatsAppTemplate {
  id: string
  name: string
  type: string
  title?: string
  message: string
  variables: string[]
  is_active: boolean
  is_default: boolean
  is_scheduled?: boolean
  scheduled_days?: number[]
  scheduled_time?: string
  created_at: string
}

export interface Plan {
  id: number
  name: string
  price: number
  duration_days: number
  status: 'active' | 'inactive'
  created_at: string
}

export interface Application {
  id: number
  name: string
  created_at: string
}

export interface DashboardStats {
  total_clients: number
  active_clients: number
  inactive_clients: number
  total_revenue: number
  pending_invoices: number
  overdue_invoices: number
}
