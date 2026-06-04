export interface User {
  id: number
  first_name: string
  last_name: string
  email: string
  created_at?: string
  updated_at?: string
}

export interface LoginPayload {
  email: string
  password: string
}

export interface RegisterPayload {
  first_name: string
  last_name: string
  email: string
  password: string
  password_confirmation: string
}

export interface ResetPasswordPayload {
  email: string
  token: string
  password: string
  password_confirmation: string
}

export type ValidationErrors = Record<string, string[]>

export interface ApiErrorBody {
  message?: string
  errors?: ValidationErrors
}
