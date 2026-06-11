<script setup lang="ts">
import { computed, inject, onBeforeUnmount, onMounted, ref } from 'vue'
import { CheckCircle2, Mic, MicOff, Play, Square, Volume2 } from 'lucide-vue-next'
import { cn } from '@/lib/utils'
import type { ShadowRecordPayload } from '@/types/exercises'
import type { ExerciseResult } from '@/types/domain'
import { audioKey, type AudioApi } from '@/composables/audioInjection'
import { useVoiceActivity } from '@/composables/useVoiceActivity'
import { useSpeechTranscript } from '@/composables/useSpeechTranscript'
import { useFuzzyMatch, type FuzzyMatchResult } from '@/composables/useFuzzyMatch'

const props = defineProps<{ payload: ShadowRecordPayload }>()
const emit = defineEmits<{ complete: [result: ExerciseResult] }>()

const audio = inject<AudioApi | null>(audioKey, null)

type MicState = 'unknown' | 'allowed' | 'denied'
const micState = ref<MicState>('unknown')
const isRecording = ref(false)
const recordingUrl = ref<string | null>(null)

let mediaRecorder: MediaRecorder | null = null
let chunks: Blob[] = []
let stream: MediaStream | null = null

const phraseIndex = ref(0)
const total = computed(() => props.payload.phrases.length)
const phrase = computed(() => props.payload.phrases[phraseIndex.value])

// Voice-activity auto-stop. Calls back when the user has gone quiet long enough.
const vad = useVoiceActivity()
vad.onSilence(() => {
  if (isRecording.value) stopRecord()
})

// Browser SpeechRecognition transcript (Chrome/Edge/Safari yes, Firefox no).
const speech = useSpeechTranscript()

// Comparison result from the latest recording, if recognition produced anything.
const comparison = ref<FuzzyMatchResult | null>(null)
const heardTranscript = ref('')

async function requestMic() {
  try {
    stream = await navigator.mediaDevices.getUserMedia({ audio: true })
    micState.value = 'allowed'
  } catch {
    micState.value = 'denied'
  }
}

function playReference() {
  audio?.play(phrase.value.audio_key, { es: phrase.value.es })
}

async function startRecord() {
  if (micState.value === 'unknown') await requestMic()
  if (micState.value === 'denied' || !stream) return

  chunks = []
  recordingUrl.value = null
  comparison.value = null
  heardTranscript.value = ''

  mediaRecorder = new MediaRecorder(stream)
  mediaRecorder.ondataavailable = (e) => {
    if (e.data.size > 0) chunks.push(e.data)
  }
  mediaRecorder.onstop = () => {
    const blob = new Blob(chunks, { type: 'audio/webm' })
    recordingUrl.value = URL.createObjectURL(blob)
    // Try every recognized alternative; keep the highest-scoring one.
    pickBestAlternative()
  }

  mediaRecorder.start()
  vad.start(stream)
  if (speech.isSupported.value) speech.start('es-MX')
  isRecording.value = true
}

function stopRecord() {
  vad.stop()
  speech.stop()
  if (mediaRecorder && mediaRecorder.state !== 'inactive') {
    mediaRecorder.stop()
  }
  isRecording.value = false
}

/**
 * SpeechRecognition can return up to 3 alternatives. Score each against the
 * expected phrase and keep the one that matches (or comes closest to matching).
 */
function pickBestAlternative() {
  if (!speech.isSupported.value) return
  const alts = speech.alternatives.value
  if (alts.length === 0) return

  let best: { text: string; result: FuzzyMatchResult } | null = null
  for (const alt of alts) {
    const result = useFuzzyMatch(phrase.value.es, alt)
    if (!best) {
      best = { text: alt, result }
      continue
    }
    if (result.correct && !best.result.correct) {
      best = { text: alt, result }
    } else if (result.retry && !best.result.correct && !best.result.retry) {
      best = { text: alt, result }
    }
  }
  if (best) {
    heardTranscript.value = best.text
    comparison.value = best.result
  }
}

function nextOrComplete() {
  // Tear down any in-flight session before advancing.
  stopRecord()

  if (phraseIndex.value < total.value - 1) {
    phraseIndex.value++
    recordingUrl.value = null
    comparison.value = null
    heardTranscript.value = ''
  } else {
    emit('complete', {
      correct: true,
      score: 100,
      meta:
        micState.value === 'denied'
          ? { skipped: 'no_mic', phrases: total.value }
          : { practiced: total.value },
    })
  }
}

onMounted(async () => {
  // Pre-flight: feature-detect MediaRecorder. Then plays prompt audio on mount
  // after a brief beat so it doesn't slam in with the screen transition.
  if (typeof navigator === 'undefined' || !navigator.mediaDevices?.getUserMedia) {
    micState.value = 'denied'
  }
  setTimeout(() => audio?.play(phrase.value.audio_key, { es: phrase.value.es }), 450)
})

onBeforeUnmount(() => {
  vad.teardown()
  speech.stop()
  if (mediaRecorder && mediaRecorder.state !== 'inactive') {
    try {
      mediaRecorder.stop()
    } catch {
      /* ignore */
    }
  }
  if (stream) {
    for (const track of stream.getTracks()) track.stop()
    stream = null
  }
})
</script>

<template>
  <div class="space-y-6">
    <header class="space-y-2">
      <p class="text-sm font-medium text-muted-foreground uppercase tracking-wider">
        Shadow · {{ phraseIndex + 1 }} of {{ total }}
      </p>
      <h2 class="text-2xl font-display font-semibold text-foreground leading-tight">
        {{ props.payload.prompt }}
      </h2>
    </header>

    <!-- The phrase + reference audio. -->
    <div class="sunlit-card p-6 space-y-3">
      <p class="es text-3xl font-display font-semibold leading-tight">{{ phrase.es }}</p>
      <p class="text-sm text-muted-foreground italic">{{ phrase.en }}</p>
      <button
        type="button"
        class="mt-3 inline-flex items-center gap-2 rounded-pill bg-secondary px-4 py-2 text-secondary-foreground font-semibold text-sm transition-transform duration-quick ease-quick hover:-translate-y-0.5 active:translate-y-0 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background"
        @click="playReference"
      >
        <Volume2 class="h-4 w-4" />
        Listen
      </button>
    </div>

    <!-- Mic-denied fallback. Auto-passable per §2/§9. -->
    <div v-if="micState === 'denied'" class="coach-feedback text-sm flex items-start gap-3">
      <MicOff class="h-5 w-5 flex-shrink-0 mt-0.5" />
      <p>
        No microphone available — that's OK. Read the phrase aloud once, then tap continue. We'll
        mark it practiced.
      </p>
    </div>

    <!-- Record controls. -->
    <div v-else class="flex flex-col sm:flex-row gap-3 items-stretch">
      <button
        v-if="!isRecording"
        type="button"
        :class="
          cn(
            'inline-flex h-12 flex-1 items-center justify-center gap-2 rounded-soft border-2 border-primary bg-primary text-primary-foreground font-semibold transition-transform duration-quick ease-quick hover:-translate-y-0.5 active:translate-y-0',
            'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background'
          )
        "
        @click="startRecord"
      >
        <Mic class="h-5 w-5" />
        Record yourself
      </button>
      <button
        v-else
        type="button"
        class="inline-flex h-12 flex-1 items-center justify-center gap-2 rounded-soft border-2 border-coach bg-coach text-coach-foreground font-semibold animate-pulse"
        @click="stopRecord"
      >
        <Square class="h-5 w-5 fill-current" />
        Stop recording
      </button>

      <audio
        v-if="recordingUrl"
        :src="recordingUrl"
        controls
        class="rounded-soft border border-border h-12 flex-1"
      >
        <Play class="h-4 w-4" />
      </audio>
    </div>

    <!-- Transcript + comparison badge. Only renders when recognition produced
         text; Firefox / unsupported browsers silently fall back to playback. -->
    <div
      v-if="speech.isSupported.value && heardTranscript"
      class="rounded-soft border border-border bg-card/60 p-4 space-y-3"
    >
      <p class="text-xs uppercase tracking-wider text-muted-foreground font-semibold">Heard</p>
      <p class="es text-xl font-display font-semibold leading-snug">
        &ldquo;{{ heardTranscript }}&rdquo;
      </p>

      <div
        v-if="comparison && comparison.correct"
        class="flex items-center gap-2 text-sm font-semibold"
        style="color: hsl(var(--success))"
      >
        <CheckCircle2 class="h-4 w-4" />
        ¡Eso es! Nice shadow.
      </div>
      <p v-else-if="comparison && comparison.retry" class="coach-feedback text-sm">
        Almost — check the accent and try once more.
      </p>
      <p v-else-if="comparison && !comparison.correct" class="coach-feedback text-sm">
        Not quite — give the reference another listen and try again.
      </p>
    </div>

    <button
      type="button"
      class="inline-flex h-12 w-full items-center justify-center rounded-soft bg-success px-6 font-semibold text-success-foreground transition-transform duration-quick ease-quick hover:-translate-y-0.5 active:translate-y-0 disabled:opacity-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background"
      @click="nextOrComplete"
    >
      {{ phraseIndex >= total - 1 ? 'Done' : 'Next phrase' }}
    </button>
  </div>
</template>
