/**
 * Bespoke teach-screen markdown parser.
 *
 * Replaces the v-html string renderer that used to live in LessonPlayPage. The
 * scope is intentionally tiny — just enough to support the curriculum's
 * teach_screens body_md content:
 *
 *   **bold**, *italic*, "- " bulleted lists, paragraph breaks ("\n\n"),
 *   and the new [[es|audio_key]] / [[es]] syntax for inline tap-to-hear.
 *
 * Output is a typed block tree consumed by <TeachBody>. Consumers render text
 * tokens via Vue text interpolation (`{{ }}`) which handles HTML escaping
 * natively — the parser doesn't need to pre-escape.
 */

export type TeachInline =
  | { type: 'text'; value: string }
  | { type: 'bold'; value: string }
  | { type: 'italic'; value: string }
  | { type: 'es'; es: string; audioKey?: string; speakEs?: string }

export type TeachBlock =
  | { type: 'p'; inline: TeachInline[] }
  | { type: 'ul'; items: TeachInline[][] }

export function parseTeachMarkdown(input: string): TeachBlock[] {
  if (!input) return []

  const paragraphs = input.split(/\n\n+/)
  const blocks: TeachBlock[] = []

  for (const para of paragraphs) {
    const lines = para.split('\n')
    const isList = lines.length > 0 && lines.every((l) => /^\s*-\s+/.test(l))

    if (isList) {
      const items = lines.map((l) => parseInline(l.replace(/^\s*-\s+/, '')))
      blocks.push({ type: 'ul', items })
    } else {
      blocks.push({ type: 'p', inline: parseInline(para.replace(/\n/g, ' ')) })
    }
  }

  return blocks
}

function parseInline(raw: string): TeachInline[] {
  if (!raw) return []
  const tokens: TeachInline[] = []
  let i = 0
  let textBuf = ''

  const flushText = () => {
    if (textBuf) {
      tokens.push({ type: 'text', value: textBuf })
      textBuf = ''
    }
  }

  while (i < raw.length) {
    // [[es]] · [[es|audio_key]] · [[es|audio_key|speech_es]]
    if (raw.startsWith('[[', i)) {
      const end = raw.indexOf(']]', i + 2)
      if (end !== -1) {
        const inner = raw.slice(i + 2, end)
        const parts = inner.split('|')
        const es = parts[0].trim()
        const audioKey = parts[1]?.trim() || undefined
        const speakEs = parts[2]?.trim() || undefined
        flushText()
        tokens.push({ type: 'es', es, audioKey, speakEs })
        i = end + 2
        continue
      }
    }

    // **bold**
    if (raw.startsWith('**', i)) {
      const end = raw.indexOf('**', i + 2)
      if (end !== -1) {
        flushText()
        tokens.push({ type: 'bold', value: raw.slice(i + 2, end) })
        i = end + 2
        continue
      }
    }

    // *italic* — must not match the second asterisk of **bold** (handled by the
    // preceding ** branch consuming both before we reach a single *).
    if (raw[i] === '*') {
      const end = raw.indexOf('*', i + 1)
      if (end !== -1 && raw[end + 1] !== '*' && end > i + 1) {
        flushText()
        tokens.push({ type: 'italic', value: raw.slice(i + 1, end) })
        i = end + 1
        continue
      }
    }

    textBuf += raw[i]
    i++
  }

  flushText()
  return tokens
}
