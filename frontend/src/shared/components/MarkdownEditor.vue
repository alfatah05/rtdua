<template>
  <div class="rounded-[12px] border border-[var(--line)] bg-[var(--search)] overflow-hidden">
    <div class="flex flex-wrap items-center gap-0.5 px-1.5 py-1.5 border-b border-[var(--line)] bg-[var(--card)]">
      <button
        v-for="b in tools"
        :key="b.title"
        type="button"
        class="min-h-[36px] min-w-[36px] px-2 rounded-lg text-[13px] font-bold text-[var(--text)] hover:bg-[var(--search)] active:scale-95 transition"
        :title="b.title"
        @click="b.run"
      >
        <component :is="b.icon" :size="16" />
      </button>
      <span class="flex-1"></span>
      <button
        type="button"
        class="min-h-[36px] px-3 rounded-full text-[12px] font-bold"
        :class="tab === 'edit' ? 'bg-[var(--g)] text-white' : 'bg-[var(--search)] text-[var(--mut)]'"
        @click="tab = 'edit'"
      >
        Edit
      </button>
      <button
        type="button"
        class="min-h-[36px] px-3 rounded-full text-[12px] font-bold ml-1"
        :class="tab === 'preview' ? 'bg-[var(--g)] text-white' : 'bg-[var(--search)] text-[var(--mut)]'"
        @click="tab = 'preview'"
      >
        Pratinjau
      </button>
    </div>

    <textarea
      v-show="tab === 'edit'"
      ref="ta"
      :value="modelValue"
      :rows="rows"
      :placeholder="placeholder"
      class="w-full px-3 py-3 bg-transparent outline-none resize-y min-h-[140px] text-[14px] leading-relaxed font-mono"
      @input="onInput"
    />

    <div
      v-show="tab === 'preview'"
      class="prose-rt px-3 py-3 min-h-[140px] text-[14px] leading-relaxed"
      v-html="previewHtml"
    />

    <p class="text-[11px] text-[var(--mut)] px-3 pb-2 m-0">
      Markdown: **tebal**, *miring*, # judul, - list, tabel, [link](url)
    </p>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import {
  Bold, Italic, Heading2, Heading3, List, ListOrdered,
  Link2, Image, Table2, Quote, Code2, Minus,
} from 'lucide-vue-next'
import { renderMarkdown } from '@shared/utils/markdown.js'

const props = defineProps({
  modelValue: { type: String, default: '' },
  rows: { type: Number, default: 8 },
  placeholder: { type: String, default: 'Tulis isi dengan Markdown…' },
})
const emit = defineEmits(['update:modelValue'])

const ta = ref(null)
const tab = ref('edit')

const previewHtml = computed(() => renderMarkdown(props.modelValue))

function onInput(e) {
  emit('update:modelValue', e.target.value)
}

function insert(before, after = '', placeholder = '') {
  const el = ta.value
  if (!el) {
    emit('update:modelValue', (props.modelValue || '') + before + placeholder + after)
    return
  }
  const start = el.selectionStart ?? props.modelValue.length
  const end = el.selectionEnd ?? start
  const val = props.modelValue || ''
  const selected = val.slice(start, end) || placeholder
  const next = val.slice(0, start) + before + selected + after + val.slice(end)
  emit('update:modelValue', next)
  requestAnimationFrame(() => {
    el.focus()
    const pos = start + before.length + selected.length
    el.setSelectionRange(start + before.length, pos)
  })
}

function insertBlock(block) {
  const el = ta.value
  const val = props.modelValue || ''
  const start = el?.selectionStart ?? val.length
  const needsNl = start > 0 && val[start - 1] !== '\n'
  const text = (needsNl ? '\n' : '') + block
  insert(text, '', '')
}

const tools = [
  { title: 'Tebal', icon: Bold, run: () => insert('**', '**', 'teks') },
  { title: 'Miring', icon: Italic, run: () => insert('*', '*', 'teks') },
  { title: 'Judul 2', icon: Heading2, run: () => insertBlock('## ') },
  { title: 'Judul 3', icon: Heading3, run: () => insertBlock('### ') },
  { title: 'List', icon: List, run: () => insertBlock('- item\n- item\n') },
  { title: 'List nomor', icon: ListOrdered, run: () => insertBlock('1. item\n2. item\n') },
  { title: 'Kutipan', icon: Quote, run: () => insertBlock('> kutipan\n') },
  { title: 'Kode', icon: Code2, run: () => insert('`', '`', 'kode') },
  { title: 'Garis', icon: Minus, run: () => insertBlock('---\n') },
  { title: 'Tautan', icon: Link2, run: () => insert('[', '](https://)', 'teks') },
  { title: 'Gambar', icon: Image, run: () => insert('![', '](https://)', 'alt') },
  {
    title: 'Tabel',
    icon: Table2,
    run: () =>
      insertBlock(
        '| Kolom 1 | Kolom 2 | Kolom 3 |\n| --- | --- | --- |\n| isi | isi | isi |\n| isi | isi | isi |\n'
      ),
  },
]
</script>
