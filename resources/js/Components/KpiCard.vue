<script setup>
defineProps({
  label:    { type: String,  required: true },
  value:    { type: String,  required: true },
  sub:      { type: String,  default: '' },
  trend:    { type: Number,  default: null },   // positive = up, negative = down
  color:    { type: String,  default: 'blue' }, // blue | green | amber | rose
  icon:     { type: String,  default: 'peso' }, // peso | target | building | chart
})
</script>

<template>
  <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm hover:shadow-md transition-shadow relative overflow-hidden group">

    <!-- Accent strip -->
    <div :class="[
      'absolute top-0 left-0 w-full h-0.5',
      color === 'blue'  ? 'bg-gradient-to-r from-[#1E90FF] to-[#60B0FF]' : '',
      color === 'green' ? 'bg-gradient-to-r from-emerald-400 to-teal-400' : '',
      color === 'amber' ? 'bg-gradient-to-r from-amber-400 to-yellow-300' : '',
      color === 'rose'  ? 'bg-gradient-to-r from-rose-500 to-pink-400'    : '',
    ]" />

    <div class="flex items-start justify-between">
      <div class="flex-1">
        <p class="text-xs font-medium text-gray-400 uppercase tracking-widest">{{ label }}</p>
        <p class="text-2xl font-display font-bold text-gray-800 mt-2 leading-none">{{ value }}</p>

        <div class="flex items-center gap-2 mt-2">
          <span v-if="trend !== null" :class="[
            'inline-flex items-center gap-0.5 text-xs font-semibold px-1.5 py-0.5 rounded-md',
            trend >= 0 ? 'bg-emerald-50 text-emerald-600' : 'bg-red-50 text-red-500'
          ]">
            {{ trend >= 0 ? '▲' : '▼' }} {{ Math.abs(trend) }}%
          </span>
          <span v-if="sub" class="text-xs text-gray-400">{{ sub }}</span>
        </div>
      </div>

      <!-- Icon box -->
      <div :class="[
        'w-10 h-10 rounded-xl flex items-center justify-center shrink-0 ml-3',
        color === 'blue'  ? 'bg-blue-50 text-[#1E90FF]' : '',
        color === 'green' ? 'bg-emerald-50 text-emerald-500' : '',
        color === 'amber' ? 'bg-amber-50 text-amber-500' : '',
        color === 'rose'  ? 'bg-rose-50 text-rose-500'   : '',
      ]">
        <!-- peso -->
        <svg v-if="icon === 'peso'" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path d="M6 3h8a4 4 0 010 8H6M6 11h8M6 3v18M6 7h10"/>
        </svg>
        <!-- target -->
        <svg v-if="icon === 'target'" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1" fill="currentColor"/>
        </svg>
        <!-- building -->
        <svg v-if="icon === 'building'" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/>
          <rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/>
        </svg>
        <!-- chart -->
        <svg v-if="icon === 'chart'" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path d="M3 18V10M8 18V6M13 18v-4M18 18V8"/><line x1="3" y1="18" x2="21" y2="18"/>
        </svg>
      </div>
    </div>
  </div>
</template>