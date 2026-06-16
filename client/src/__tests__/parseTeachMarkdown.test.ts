import { describe, expect, it } from 'vitest'
import { parseTeachMarkdown } from '@/lib/parseTeachMarkdown'

describe('parseTeachMarkdown', () => {
  it('returns an empty array for empty input', () => {
    expect(parseTeachMarkdown('')).toEqual([])
  })

  it('parses a single plain paragraph as a `p` block of text', () => {
    const blocks = parseTeachMarkdown('Just plain prose.')
    expect(blocks).toEqual([{ type: 'p', inline: [{ type: 'text', value: 'Just plain prose.' }] }])
  })

  it('parses bold and italic inline tokens', () => {
    const blocks = parseTeachMarkdown('A **bold** and *italic* word.')
    expect(blocks).toHaveLength(1)
    expect(blocks[0]).toEqual({
      type: 'p',
      inline: [
        { type: 'text', value: 'A ' },
        { type: 'bold', value: 'bold' },
        { type: 'text', value: ' and ' },
        { type: 'italic', value: 'italic' },
        { type: 'text', value: ' word.' },
      ],
    })
  })

  it('parses a bulleted list as a `ul` block with one item per line', () => {
    const blocks = parseTeachMarkdown('- one\n- two\n- three')
    expect(blocks).toHaveLength(1)
    expect(blocks[0]).toMatchObject({
      type: 'ul',
      items: [
        [{ type: 'text', value: 'one' }],
        [{ type: 'text', value: 'two' }],
        [{ type: 'text', value: 'three' }],
      ],
    })
  })

  it('splits double-newlines into separate blocks', () => {
    const blocks = parseTeachMarkdown('First paragraph.\n\nSecond paragraph.')
    expect(blocks).toHaveLength(2)
    expect(blocks[0]).toMatchObject({ type: 'p' })
    expect(blocks[1]).toMatchObject({ type: 'p' })
  })

  it('parses [[es]] without an audio key', () => {
    const blocks = parseTeachMarkdown('Say [[hola]] back.')
    expect(blocks[0]).toEqual({
      type: 'p',
      inline: [
        { type: 'text', value: 'Say ' },
        { type: 'es', es: 'hola', audioKey: undefined, speakEs: undefined },
        { type: 'text', value: ' back.' },
      ],
    })
  })

  it('parses [[es|audio_key]] with an explicit audio key', () => {
    const blocks = parseTeachMarkdown('Say [[buenos días|tts_chunk_buenos_dias]] now.')
    expect(blocks[0]).toMatchObject({
      type: 'p',
      inline: [
        { type: 'text', value: 'Say ' },
        {
          type: 'es',
          es: 'buenos días',
          audioKey: 'tts_chunk_buenos_dias',
          speakEs: undefined,
        },
        { type: 'text', value: ' now.' },
      ],
    })
  })

  it('parses [[display|audio_key|speech_es]] with an override for the speech fallback', () => {
    const blocks = parseTeachMarkdown('Stress hint: [[CA-sa|tts_word_casa|casa]].')
    expect(blocks[0]).toMatchObject({
      type: 'p',
      inline: [
        { type: 'text', value: 'Stress hint: ' },
        { type: 'es', es: 'CA-sa', audioKey: 'tts_word_casa', speakEs: 'casa' },
        { type: 'text', value: '.' },
      ],
    })
  })

  it('does not let [[...]] consume neighboring **bold** tokens', () => {
    const blocks = parseTeachMarkdown('See [[hola]] and **bold**.')
    expect(blocks[0]).toMatchObject({
      type: 'p',
      inline: [
        { type: 'text', value: 'See ' },
        { type: 'es', es: 'hola' },
        { type: 'text', value: ' and ' },
        { type: 'bold', value: 'bold' },
        { type: 'text', value: '.' },
      ],
    })
  })

  it('keeps raw HTML chars verbatim in text tokens (TeachBody uses {{ }} interpolation, which Vue auto-escapes)', () => {
    const blocks = parseTeachMarkdown('Use <div> & friends.')
    expect(blocks[0]).toEqual({
      type: 'p',
      inline: [{ type: 'text', value: 'Use <div> & friends.' }],
    })
  })

  it('treats list items as independent inline streams (each gets its own es token)', () => {
    const blocks = parseTeachMarkdown('- [[hola]] — hello\n- [[adiós]] — bye')
    expect(blocks[0]).toMatchObject({
      type: 'ul',
      items: [
        [
          { type: 'es', es: 'hola' },
          { type: 'text', value: ' — hello' },
        ],
        [
          { type: 'es', es: 'adiós' },
          { type: 'text', value: ' — bye' },
        ],
      ],
    })
  })
})
