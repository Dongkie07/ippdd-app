<script setup>
import { ref } from 'vue'

const props = defineProps({
  modelValue: { type: Number, required: true },
})
const emit = defineEmits(['update:modelValue'])

const defaultYears = [2024, 2025, 2026, 2027]
const extraYears   = ref([])
const allYears     = ref([...defaultYears])

// ── Add custom year ────────────────────────────────────────
const showInput  = ref(false)
const inputYear  = ref('')
const inputError = ref('')
const inputRef   = ref(null)

const openInput = async () => {
  showInput.value  = true
  inputYear.value  = ''
  inputError.value = ''
  await nextTick()
  inputRef.value?.focus()
}

const addYear = () => {
  const y = parseInt(inputYear.value)
  if (!y || y < 2000 || y > 2100) {
    inputError.value = 'Enter a valid year (2000–2100)'
    return
  }
  if (allYears.value.includes(y)) {
    inputError.value = 'Year already exists'
    return
  }
  allYears.value = [...allYears.value, y].sort((a, b) => a - b)
  extraYears.value.push(y)
  emit('update:modelValue', y)
  showInput.value = false
}

const cancelInput = () => {
  showInput.value  = false
  inputError.value = ''
}

const removeYear = (y) => {
  allYears.value   = allYears.value.filter(x => x !== y)
  extraYears.value = extraYears.value.filter(x => x !== y)
  if (props.modelValue === y)
    emit('update:modelValue', allYears.value.at(-1) ?? defaultYears.at(-1))
}

import { nextTick } from 'vue'
</script>

<template>
  <div class="flex items-center gap-3 flex-wrap mb-5">
    <label class="text-[11px] font-extrabold uppercase tracking-widest text-gray-400 shrink-0">
      Fiscal Year
    </label>

    <div class="flex items-center gap-1.5 flex-wrap">
      <!-- Year tabs -->
      <div v-for="y in allYears" :key="y" class="relative group">
        <button
          @click="emit('update:modelValue', y)"
          :class="['px-3.5 py-1.5 rounded-xl text-[13px] font-bold border transition-all pr-5',
            modelValue === y
              ? 'bg-[#0D2137] text-white border-[#0D2137] shadow-sm'
              : 'bg-white text-gray-400 border-gray-200 hover:border-[#0D2137]/40']">
          {{ y }}
        </button>

        <!-- Remove button (only on custom years) -->
        <button
          v-if="extraYears.includes(y)"
          @click.stop="removeYear(y)"
          class="absolute -top-1.5 -right-1.5 w-4 h-4 rounded-full bg-red-400 text-white
                 text-[9px] font-bold hidden group-hover:flex items-center justify-center
                 hover:bg-red-500 transition-colors shadow-sm z-10">
          ✕
        </button>
      </div>

      <!-- Add year button -->
      <button
        v-if="!showInput"
        @click="openInput"
        class="w-8 h-8 rounded-xl border-2 border-dashed border-gray-300 text-gray-400
               hover:border-[#0D2137]/50 hover:text-[#0D2137] hover:bg-[#0D2137]/5
               transition-all flex items-center justify-center font-bold text-lg"
        title="Add custom fiscal year">
        +
      </button>

      <!-- Inline year input -->
      <Transition name="slide">
        <div v-if="showInput" class="flex items-center gap-1.5">
          <div class="relative">
            <input
              ref="inputRef"
              v-model="inputYear"
              type="number"
              placeholder="e.g. 2028"
              @keyup.enter="addYear"
              @keyup.escape="cancelInput"
              :class="['w-28 px-2.5 py-1.5 rounded-xl text-[13px] font-bold border-2 outline-none transition-all',
                inputError
                  ? 'border-red-400 bg-red-50 text-red-700'
                  : 'border-[#0D2137]/40 bg-white text-[#0D2137] focus:border-[#0D2137]']"
            />
            <p v-if="inputError" class="absolute top-9 left-0 text-[10px] text-red-500 font-semibold whitespace-nowrap">
              {{ inputError }}
            </p>
          </div>

          <!-- Confirm -->
          <button
            @click="addYear"
            class="w-7 h-7 rounded-lg bg-[#0D2137] text-white flex items-center justify-center
                   hover:bg-[#1A5276] transition-colors text-sm font-bold">
            ✓
          </button>

          <!-- Cancel -->
          <button
            @click="cancelInput"
            class="w-7 h-7 rounded-lg border border-gray-200 text-gray-400 flex items-center justify-center
                   hover:text-red-400 hover:border-red-200 transition-colors text-sm">
            ✕
          </button>
        </div>
      </Transition>
    </div>
  </div>
</template>

<style scoped>
.slide-enter-active, .slide-leave-active { transition: all 0.15s ease; }
.slide-enter-from, .slide-leave-to { opacity: 0; transform: translateX(-6px); }
</style>
