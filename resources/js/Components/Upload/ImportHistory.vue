<script setup>
import SectionCard from '@/Components/SectionCard.vue'
import StatusBadge from '@/Components/StatusBadge.vue'

defineProps({
  history: { type: Array, default: () => [] },
})

const phpM = v => '₱' + (parseFloat(v || 0) / 1e6).toFixed(2) + 'M'
</script>

<template>
  <SectionCard v-if="history?.length" title="Import History" :noPad="true">
    <table class="w-full text-[13px]">
      <thead>
        <tr class="border-b-2 border-gray-100">
          <th class="text-left px-6 py-3 text-[10px] font-extrabold uppercase tracking-[0.12em] text-gray-400">File</th>
          <th class="text-left px-6 py-3 text-[10px] font-extrabold uppercase tracking-[0.12em] text-gray-400">Year</th>
          <th class="text-right px-6 py-3 text-[10px] font-extrabold uppercase tracking-[0.12em] text-gray-400">Depts</th>
          <th class="text-right px-6 py-3 text-[10px] font-extrabold uppercase tracking-[0.12em] text-gray-400">Total Budget</th>
          <th class="text-left px-6 py-3 text-[10px] font-extrabold uppercase tracking-[0.12em] text-gray-400">Date</th>
          <th class="text-left px-6 py-3 text-[10px] font-extrabold uppercase tracking-[0.12em] text-gray-400">Status</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="h in history" :key="h.id" class="border-b border-gray-50 hover:bg-[#0D2137]/[0.02]">
          <td class="px-6 py-3.5 font-mono text-[11px] text-gray-500 max-w-[180px] truncate">{{ h.filename }}</td>
          <td class="px-6 py-3.5 font-bold text-[#0D2137]">FY {{ h.year }}</td>
          <td class="px-6 py-3.5 text-right text-gray-500">{{ h.dept_count }}</td>
          <td class="px-6 py-3.5 text-right font-mono font-bold text-[#0D2137] text-[12px]">{{ phpM(h.total_budget) }}</td>
          <td class="px-6 py-3.5 text-[11px] text-gray-400">
            {{ new Date(h.created_at).toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' }) }}
          </td>
          <td class="px-6 py-3.5"><StatusBadge status="completed" label="Imported" /></td>
        </tr>
      </tbody>
    </table>
  </SectionCard>
</template>
