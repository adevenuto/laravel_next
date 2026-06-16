<script setup lang="ts">
import { computed, inject } from 'vue'
import { Volume2 } from 'lucide-vue-next'
import { audioKey as lessonAudioInjectionKey, type AudioApi } from '@/composables/audioInjection'

const props = withDefaults(
  defineProps<{
    es: string
    audioKey?: string
    speakEs?: string
  }>(),
  { audioKey: undefined, speakEs: undefined }
)

const audio = inject<AudioApi | null>(lessonAudioInjectionKey, null)

const resolvedKey = computed(() => props.audioKey ?? deriveKey(props.es))
const resolvedSpeech = computed(() => props.speakEs ?? props.es)

function deriveKey(es: string): string {
  const slug = es
    .toLowerCase()
    .normalize('NFD')
    .replace(/[̀-ͯ]/g, '')
    .replace(/[^a-zñ]+/g, '_')
    .replace(/^_|_$/g, '')
  return `tts_chunk_${slug}`
}

function onClick() {
  audio?.play(resolvedKey.value, { es: resolvedSpeech.value })
}
</script>

<template>
  <button type="button" class="es es-audio-word" :aria-label="`Hear ${es}`" @click="onClick">
    <span>{{ es }}</span>
    <Volume2 class="es-audio-word__icon" aria-hidden="true" />
  </button>
</template>

<style scoped>
.es-audio-word {
  display: inline-flex;
  align-items: baseline;
  gap: 0.15em;
  padding: 0 0.1em;
  margin: 0 0.05em;
  border: 0;
  background: transparent;
  color: inherit;
  font: inherit;
  cursor: pointer;
  border-bottom: 1.5px dotted hsl(var(--primary) / 0.55);
  border-radius: 2px;
  transition:
    color var(--motion-duration-quick) var(--motion-ease-quick),
    border-color var(--motion-duration-quick) var(--motion-ease-quick),
    transform var(--motion-duration-quick) var(--motion-ease-quick);
}

.es-audio-word:hover,
.es-audio-word:focus-visible {
  color: hsl(var(--primary));
  border-bottom-color: hsl(var(--primary));
  border-bottom-style: solid;
  transform: translateY(-1px);
}

.es-audio-word:active {
  transform: translateY(0);
}

.es-audio-word:focus-visible {
  outline: 2px solid hsl(var(--ring));
  outline-offset: 2px;
}

.es-audio-word__icon {
  width: 0.85em;
  height: 0.85em;
  opacity: 0.55;
  transform: translateY(0.05em);
  flex-shrink: 0;
}

.es-audio-word:hover .es-audio-word__icon,
.es-audio-word:focus-visible .es-audio-word__icon {
  opacity: 1;
}
</style>
