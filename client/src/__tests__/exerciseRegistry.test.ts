import { describe, expect, it } from 'vitest'
import {
  isRegistered,
  registeredComponentNames,
  resolveExerciseComponent,
} from '@/lib/exerciseRegistry'

const UNIT_1_1_COMPONENT_NAMES = [
  'EarTraining',
  'MinimalPairs',
  'ShadowRecord',
  'SoundMatch',
  'SilentLetterTap',
  'StressTap',
  'AccentDetective',
  'RuleSort',
] as const

describe('exerciseRegistry', () => {
  it.each([...UNIT_1_1_COMPONENT_NAMES])(
    'resolves Unit 1.1 component "%s" to a non-null component',
    (name) => {
      expect(isRegistered(name)).toBe(true)
      expect(resolveExerciseComponent(name)).not.toBeNull()
    }
  )

  it('returns null (does NOT throw) for an unknown component name', () => {
    expect(resolveExerciseComponent('NotARealComponent')).toBeNull()
    expect(isRegistered('NotARealComponent')).toBe(false)
  })

  it('exposes every registered name through registeredComponentNames', () => {
    const names = registeredComponentNames()
    for (const expected of UNIT_1_1_COMPONENT_NAMES) {
      expect(names).toContain(expected)
    }
  })

  // Locked behind `it.skip` until Phase 4 ships the remaining components from
  // ExerciseSeeder.php. Enables the full coverage check then.
  it.skip('(future) resolves every component name found in ExerciseSeeder.php', () => {
    // Phase 4 task: import the full list of seeded component names and
    // assert each one resolves. Until then, scope is Unit 1.1 only.
  })
})
