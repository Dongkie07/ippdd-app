<script setup>
import { computed, ref, watch } from 'vue'
import SectionCard from '@/Components/SectionCard.vue'
import { useFormatters } from '@/composables/useFormatters'
import { FUNDS } from '@/constants/wfp'

const props = defineProps({
  rows: { type: Array, default: () => [] },
  years: { type: Array, default: () => [] },
  latestYear: { type: [Number, String, null], default: null },
})

const { phpM } = useFormatters()
const selectedYear = ref(props.latestYear)

watch(
  () => props.latestYear,
  (year) => {
    if (!selectedYear.value) selectedYear.value = year
  },
  { immediate: true },
)

watch(
  () => props.years,
  (years) => {
    if (!years?.length) return
    if (!years.includes(selectedYear.value)) selectedYear.value = props.latestYear ?? years.at(-1)
  },
  { immediate: true },
)

const yearOptions = computed(() => [...(props.years ?? [])].sort((a, b) => b - a))
const numberValue = (value) => Number(value ?? 0) || 0
const totalFor = (row) => numberValue(row[`budget_${selectedYear.value}`])
const amountFor = (row, fund) => numberValue(row[`${fund.key}_${selectedYear.value}`])

const displayedRows = computed(() =>
  [...(props.rows ?? [])]
    .filter((row) =>
      totalFor(row) > 0 || FUNDS.some((fund) => amountFor(row, fund) > 0),
    )
    .sort((a, b) => totalFor(b) - totalFor(a)),
)
</script>

<template>
  <div class="space-y-4">
    <SectionCard
      title="Fund Breakdown Detail"
      :subtitle="`FY ${selectedYear ?? '—'} · all fund clusters per department`"
      :noPad="true"
    >
      <template #actions>
        <div class="flex items-center gap-2 rounded-2xl border border-[#DDEDE3] bg-white p-1.5 shadow-sm">
          <span class="pl-2 text-[10px] font-black uppercase tracking-[0.16em] text-[#8FA79B]">
            Fiscal Year
          </span>
          <select
            v-model="selectedYear"
            class="rounded-xl border border-[#DDEDE3] bg-[#F8FCF9] px-3 py-2 text-[12px] font-black text-[#064E3B]
                   focus:outline-none focus:ring-2 focus:ring-[#168A4A]/20"
          >
            <option v-for="year in yearOptions" :key="year" :value="year">
              FY {{ year }}
            </option>
          </select>
        </div>
      </template>

      <div class="overflow-x-auto">
        <table class="w-full text-[12px]">
          <thead class="bg-[#F8FCF9]">
            <tr class="border-b-2 border-[#E6F2EA]">
              <th class="px-5 py-3 text-left text-[10px] font-black uppercase tracking-[0.14em] text-[#8FA79B]">Department</th>
              <th class="px-4 py-3 text-right text-[10px] font-black uppercase tracking-[0.14em] text-[#8FA79B]">Total</th>
              <th
                v-for="fund in FUNDS"
                :key="fund.key"
                class="px-4 py-3 text-right text-[10px] font-black uppercase tracking-[0.14em] text-[#8FA79B]"
              >
                {{ fund.key.toUpperCase() }}
              </th>
              <th class="px-4 py-3 text-right text-[10px] font-black uppercase tracking-[0.14em] text-[#8FA79B]">Mix</th>
            </tr>
          </thead>

          <tbody>
            <tr
              v-for="row in displayedRows"
              :key="`${row.sheet_code || row.department}_${selectedYear}`"
              class="group border-b border-[#E6F2EA]/70 transition-colors hover:bg-[#ECFDF3]/70"
            >
              <td class="px-5 py-3 text-[12px] font-bold text-[#064E3B]">
                {{ row.department }}
                <span class="ml-1 rounded-full bg-[#ECFDF3] px-1.5 py-0.5 text-[9px] font-black text-[#168A4A]">FY {{ selectedYear }}</span>
              </td>

              <td class="px-4 py-3 text-right font-mono font-black text-[#064E3B]">
                {{ phpM(totalFor(row)) }}
              </td>

              <td
                v-for="fund in FUNDS"
                :key="fund.key"
                class="px-4 py-3 text-right font-mono font-semibold text-[#64746B]"
              >
                {{ amountFor(row, fund) > 0 ? phpM(amountFor(row, fund)) : '—' }}
              </td>

              <td class="px-4 py-3">
                <div class="flex h-2.5 overflow-hidden rounded-full bg-[#E6F2EA] shadow-inner">
                  <div
                    v-for="fund in FUNDS"
                    v-show="amountFor(row, fund) > 0"
                    :key="fund.key"
                    :style="{ flex: amountFor(row, fund), background: fund.color }"
                    class="transition-all duration-300"
                  />
                </div>
              </td>
            </tr>

            <tr v-if="displayedRows.length === 0">
              <td :colspan="FUNDS.length + 3" class="px-5 py-12 text-center text-sm font-semibold text-[#8FA79B]">
                No fund data found for FY {{ selectedYear ?? '—' }}.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </SectionCard>
  </div>
</template>
