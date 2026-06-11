import { defineAsyncComponent, type Component } from 'vue'

/**
 * Maps the seeder's `exercises.component` string to a Vue component.
 * Unknown names return `null` — the lesson player handles that with a
 * "Coming soon" placeholder. (DECISIONS.md Conflict #10, scoped to Unit 1.1
 * in Phase 2.)
 */
const registry: Record<string, () => Promise<Component>> = {
  // Unit 1.1 — fully wired in Phase 2.
  EarTraining: () => import('@/components/exercises/EarTraining.vue'),
  MinimalPairs: () => import('@/components/exercises/MinimalPairs.vue'),
  ShadowRecord: () => import('@/components/exercises/ShadowRecord.vue'),
  SoundMatch: () => import('@/components/exercises/SoundMatch.vue'),
  SilentLetterTap: () => import('@/components/exercises/SilentLetterTap.vue'),
  StressTap: () => import('@/components/exercises/StressTap.vue'),
  AccentDetective: () => import('@/components/exercises/AccentDetective.vue'),
  RuleSort: () => import('@/components/exercises/RuleSort.vue'),
}

export function resolveExerciseComponent(name: string): Component | null {
  const loader = registry[name]
  if (!loader) return null
  return defineAsyncComponent(loader)
}

export function isRegistered(name: string): boolean {
  return name in registry
}

export function registeredComponentNames(): string[] {
  return Object.keys(registry)
}
