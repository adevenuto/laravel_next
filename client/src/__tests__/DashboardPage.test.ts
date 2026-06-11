import { describe, expect, it, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { createTestingPinia } from '@pinia/testing'
import DashboardPage from '@/pages/DashboardPage.vue'
import { useLevelMapStore } from '@/stores/levelMap'
import { useUserStore } from '@/stores/me'
import type { LevelMapResponse } from '@/types/domain'
import type { MeProfile } from '@/types/me'

const push = vi.fn()
vi.mock('vue-router', () => ({
  useRoute: () => ({ query: {} }),
  useRouter: () => ({ push, replace: vi.fn() }),
}))

vi.mock('@/lib/lessonsApi', () => ({
  fetchMe: vi.fn(),
  fetchLevelMap: vi.fn(),
  fetchLessonPlay: vi.fn(),
  submitAttempt: vi.fn(),
  completeLesson: vi.fn(),
}))

const stubMap: LevelMapResponse = {
  level: {
    id: 1,
    number: 1,
    slug: 'level-1',
    title: 'Survival & Sound System',
    promise_text: 'Five units. Eighteen lessons.',
  },
  units: [
    {
      id: 1,
      slug: 'unit-1-1-vowels',
      title: 'The Five Vowels',
      tagline: 'Five sounds. Forever.',
      order: 1,
      reward_badge_key: 'the_decoder',
      reward_feature_key: 'pronunciation_hints',
      lessons: [
        {
          id: 1,
          slug: 'l-1-1-1-vowel-lock-in',
          title: 'Vowel Lock-In',
          order: 1,
          skill_key: 'vowels',
          lock_state: 'available',
          best_score: null,
          completed_at: null,
        },
        {
          id: 2,
          slug: 'l-1-1-2-consonant-essentials',
          title: 'Consonant Essentials',
          order: 2,
          skill_key: 'consonants',
          lock_state: 'locked',
          best_score: null,
          completed_at: null,
        },
        {
          id: 3,
          slug: 'l-1-1-3-stress-and-accents',
          title: 'Stress & the Accent Mark',
          order: 3,
          skill_key: 'stress_rules',
          lock_state: 'completed',
          best_score: 92,
          completed_at: '2026-06-10T17:00:00Z',
        },
      ],
    },
  ],
}

const stubProfile: MeProfile = {
  id: 99,
  first_name: 'Ana',
  last_name: 'Reyes',
  email: 'ana@example.com',
  display_name: 'Ana',
  hometown: 'Guadalajara',
  xp: 220,
  vocab_counter: 11,
  streak: { count: 0, last_active_date: null },
  badges: [],
  feature_flags: {},
}

function mountDash() {
  const pinia = createTestingPinia({ createSpy: vi.fn })
  const wrapper = mount(DashboardPage, {
    global: {
      plugins: [pinia],
      stubs: { Transition: false },
    },
  })

  const levelMap = useLevelMapStore()
  const me = useUserStore()

  // Seed deterministic store state — no real fetch.
  levelMap.map = stubMap
  levelMap.isLoading = false
  me.profile = stubProfile

  return { wrapper, levelMap, me }
}

describe('DashboardPage', () => {
  it('renders the hero stats from the user store', async () => {
    const { wrapper } = mountDash()
    await wrapper.vm.$nextTick()

    const text = wrapper.text()
    expect(text).toContain('220') // xp
    expect(text).toContain('11') // vocab_counter
    expect(text).toContain('¡Hola, Ana!')
  })

  it('renders each lesson with its lock state', async () => {
    const { wrapper } = mountDash()
    await wrapper.vm.$nextTick()

    const text = wrapper.text()
    expect(text).toContain('Vowel Lock-In')
    expect(text).toContain('Consonant Essentials')
    expect(text).toContain('Stress & the Accent Mark')
    expect(text).toContain('92%') // best_score on completed lesson
  })

  it('routes to the lesson player when an available lesson is tapped', async () => {
    push.mockClear()
    const { wrapper } = mountDash()
    await wrapper.vm.$nextTick()

    const lessonButtons = wrapper
      .findAll('button[type="button"]')
      .filter((b) => b.text().includes('Vowel Lock-In'))

    await lessonButtons[0].trigger('click')

    expect(push).toHaveBeenCalledWith({
      name: 'lesson-play',
      params: { slug: 'l-1-1-1-vowel-lock-in' },
    })
  })

  it('does NOT route when a locked lesson is tapped', async () => {
    push.mockClear()
    const { wrapper } = mountDash()
    await wrapper.vm.$nextTick()

    const lockedBtn = wrapper
      .findAll('button[type="button"]')
      .find((b) => b.text().includes('Consonant Essentials'))!

    await lockedBtn.trigger('click')

    expect(push).not.toHaveBeenCalled()
  })
})
