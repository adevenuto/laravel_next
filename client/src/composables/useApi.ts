import { ref } from 'vue'
import { extractMessage } from '@/lib/api'

export function useApi<TResult, TArgs extends unknown[]>(fn: (...args: TArgs) => Promise<TResult>) {
  const data = ref<TResult | null>(null)
  const error = ref<string | null>(null)
  const loading = ref(false)

  async function execute(...args: TArgs): Promise<TResult | null> {
    loading.value = true
    error.value = null
    try {
      const result = await fn(...args)
      data.value = result as TResult
      return result
    } catch (e: unknown) {
      error.value = extractMessage(e)
      return null
    } finally {
      loading.value = false
    }
  }

  function reset() {
    data.value = null
    error.value = null
    loading.value = false
  }

  return { data, error, loading, execute, reset }
}
