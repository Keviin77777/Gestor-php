import api from './api'

export interface LoginCredentials {
  email: string
  password: string
}

export interface RegisterData {
  name: string
  email: string
  password: string
  whatsapp: string
}

export interface AuthResponse {
  success: boolean
  token: string
  user: {
    id: string
    email: string
    name: string
    role: string
    account_status?: string
    phone?: string
    plan_name?: string
    plan_expires_at?: string
  }
  message?: string
}

export const authService = {
  async login(credentials: LoginCredentials): Promise<AuthResponse> {
    const response = await api.post('/api-auth.php?action=login', credentials)
    return response.data
  },

  async register(data: RegisterData): Promise<AuthResponse> {
    const response = await api.post('/api-auth.php?action=register', data)
    return response.data
  },

  async logout(): Promise<void> {
    await api.post('/api-auth.php?action=logout')
  },

  async checkPlan(): Promise<any> {
    const response = await api.get('/api-auth.php?action=check_plan')
    return response.data
  },

  async getMe(): Promise<any> {
    const response = await api.get('/api-auth-me.php')
    return response.data
  }
}

export default authService
