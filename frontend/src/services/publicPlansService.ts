import axios from 'axios'

const API_URL = import.meta.env.VITE_API_URL || 'http://localhost'

export interface PublicPlan {
  id: number
  name: string
  description: string
  price: number
  duration_days: number
  is_trial: boolean
  is_active: boolean
}

export interface PublicPlansResponse {
  success: boolean
  plans: PublicPlan[]
  total: number
  error?: string
}

export const publicPlansService = {
  async getPlans(): Promise<PublicPlansResponse> {
    try {
      const response = await axios.get<PublicPlansResponse>(`${API_URL}/api-public-plans.php`)
      return response.data
    } catch (error: any) {
      throw new Error(error.response?.data?.error || 'Erro ao buscar planos')
    }
  }
}
