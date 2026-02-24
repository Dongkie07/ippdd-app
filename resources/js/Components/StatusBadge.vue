<script setup>
defineProps({
  status: { type: String, default: 'active' }, // active | pending | completed | warning
  label:  { type: String, default: '' },
})

const styles = {
  active:    'bg-emerald-50 text-emerald-600 border-emerald-200',
  pending:   'bg-amber-50 text-amber-600 border-amber-200',
  completed: 'bg-blue-50 text-blue-600 border-blue-200',
  warning:   'bg-rose-50 text-rose-600 border-rose-200',
}
const dots = {
  active:    'bg-emerald-500',
  pending:   'bg-amber-500',
  completed: 'bg-blue-500',
  warning:   'bg-rose-500',
}
</script>

<template>
  <span :class="['inline-flex items-center gap-1.5 text-xs font-medium px-2.5 py-1 rounded-full border', styles[status]]">
    <span :class="['w-1.5 h-1.5 rounded-full', dots[status]]" />
    {{ label || status }}
  </span>
</template>