<script setup lang="ts">
import EsAudioWord from './EsAudioWord.vue'
import type { TeachBlock, TeachInline } from '@/lib/parseTeachMarkdown'

defineProps<{ blocks: TeachBlock[] }>()

function inlineKey(token: TeachInline, i: number): string {
  if (token.type === 'es') return `es-${i}-${token.es}`
  if (token.type === 'text') return `t-${i}`
  return `${token.type}-${i}-${(token as { value: string }).value}`
}
</script>

<template>
  <div>
    <template v-for="(block, bi) in blocks" :key="`b-${bi}`">
      <p v-if="block.type === 'p'" class="my-2">
        <template v-for="(token, ti) in block.inline" :key="inlineKey(token, ti)">
          <EsAudioWord
            v-if="token.type === 'es'"
            :es="token.es"
            :audio-key="token.audioKey"
            :speak-es="token.speakEs"
          />
          <strong v-else-if="token.type === 'bold'">{{ token.value }}</strong>
          <em v-else-if="token.type === 'italic'">{{ token.value }}</em>
          <template v-else>{{ token.value }}</template>
        </template>
      </p>
      <ul v-else-if="block.type === 'ul'" class="list-disc pl-6 space-y-1 my-2">
        <li v-for="(item, ii) in block.items" :key="`li-${bi}-${ii}`">
          <template v-for="(token, ti) in item" :key="inlineKey(token, ti)">
            <EsAudioWord
              v-if="token.type === 'es'"
              :es="token.es"
              :audio-key="token.audioKey"
              :speak-es="token.speakEs"
            />
            <strong v-else-if="token.type === 'bold'">{{ token.value }}</strong>
            <em v-else-if="token.type === 'italic'">{{ token.value }}</em>
            <template v-else>{{ token.value }}</template>
          </template>
        </li>
      </ul>
    </template>
  </div>
</template>
