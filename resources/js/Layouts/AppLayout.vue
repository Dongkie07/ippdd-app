<script setup>
import { ref, computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'

const page = usePage()
const sidebarOpen = ref(true)
const currentPath = computed(() => page.url)

const navItems = [
  {
    group: 'Overview',
    items: [
      { label: 'Dashboard',       href: '/',       icon: 'grid'   },
    ]
  },
  {
    group: 'Management',
    items: [
      { label: 'Upload WFP Data',     href: '/upload',  icon: 'upload' },
      { label: 'Dept. Breakdown',      href: '/budget',  icon: 'chart-bar' },
    ]
  },
]

const isActive = (href) => {
  if (href === '/') return currentPath.value === '/'
  return currentPath.value.startsWith(href)
}
</script>

<template>
  <div class="flex h-screen bg-[#F0F2F5] font-body overflow-hidden">

    <!-- ── Sidebar ──────────────────────────────────────────── -->
    <aside :class="['flex flex-col bg-[#0B1F3A] transition-all duration-300 shrink-0 relative z-20', sidebarOpen ? 'w-60' : 'w-16']">

      <!-- Logo -->
      <div class="flex items-center gap-3 px-4 py-5 border-b border-white/10">
        <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-[#1E90FF] to-[#0057B7] flex items-center justify-center shrink-0 shadow-lg">
          <span class="text-white font-display font-bold text-sm">D</span>
        </div>
        <div v-if="sidebarOpen" class="overflow-hidden">
          <p class="text-white font-display font-semibold text-sm leading-tight whitespace-nowrap">DNSC</p>
          <p class="text-blue-300/60 text-[10px] whitespace-nowrap">Executive Dashboard</p>
        </div>
      </div>

      <!-- Navigation -->
      <nav class="flex-1 overflow-y-auto py-4 px-2 space-y-5">
        <div v-for="group in navItems" :key="group.group">
          <p v-if="sidebarOpen" class="text-[9px] font-semibold uppercase tracking-[0.15em] text-blue-300/40 px-3 mb-1">
            {{ group.group }}
          </p>
          <div v-else class="border-t border-white/10 mb-2" />

          <Link
            v-for="item in group.items"
            :key="item.href"
            :href="item.href"
            :class="[
              'flex items-center gap-3 px-3 py-2 rounded-lg transition-all duration-150 group',
              isActive(item.href)
                ? 'bg-[#1E90FF]/20 text-white'
                : 'text-blue-100/50 hover:text-white hover:bg-white/5'
            ]"
          >
            <span class="shrink-0 w-5 h-5 flex items-center justify-center">
              <svg v-if="item.icon === 'grid'" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/>
                <rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>
              </svg>
              <svg v-if="item.icon === 'upload'" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <path d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M16 8l-4-4-4 4M12 4v12"/>
              </svg>
            </span>
            <span v-if="sidebarOpen" class="text-sm font-medium whitespace-nowrap">{{ item.label }}</span>
            <span v-if="isActive(item.href)" class="ml-auto w-1.5 h-1.5 rounded-full bg-[#1E90FF] shrink-0" />
          </Link>
        </div>
      </nav>

      <!-- Collapse toggle -->
      <button
        @click="sidebarOpen = !sidebarOpen"
        class="m-3 p-2 rounded-lg text-blue-300/40 hover:text-white hover:bg-white/10 transition-all flex items-center justify-center"
      >
        <svg class="w-4 h-4 transition-transform" :class="sidebarOpen ? '' : 'rotate-180'" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path d="M15 18l-6-6 6-6"/>
        </svg>
      </button>
    </aside>

    <!-- ── Main Area ─────────────────────────────────────────── -->
    <div class="flex-1 flex flex-col overflow-hidden">

      <!-- Top Bar -->
      <header class="bg-white border-b border-gray-200 px-6 py-3.5 flex items-center justify-between shrink-0">
        <div>
          <slot name="header-title">
            <h1 class="text-base font-display font-semibold text-gray-800">
              <slot name="title">Dashboard</slot>
            </h1>
          </slot>
          <slot name="header-subtitle">
            <p class="text-xs text-gray-400 mt-0.5">
              <slot name="subtitle">Davao del Norte State College · Work & Financial Plan</slot>
            </p>
          </slot>
        </div>

        <div class="flex items-center gap-3">
          <!-- Notification bell -->
          <button class="relative w-8 h-8 rounded-lg hover:bg-gray-100 flex items-center justify-center text-gray-500 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
              <path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9M13.73 21a2 2 0 01-3.46 0"/>
            </svg>
            <span class="absolute top-1 right-1 w-1.5 h-1.5 bg-red-500 rounded-full" />
          </button>

          <!-- User avatar -->
          <div class="flex items-center gap-2 pl-3 border-l border-gray-200">
            <div class="w-7 h-7 rounded-full bg-gradient-to-br from-[#1E90FF] to-[#0057B7] flex items-center justify-center text-white text-xs font-bold">A</div>
            <div class="hidden sm:block">
              <p class="text-xs font-semibold text-gray-700 leading-none">Admin</p>
              <p class="text-[10px] text-gray-400 mt-0.5">DNSC</p>
            </div>
          </div>
        </div>
      </header>

      <!-- Page Content -->
      <main class="flex-1 overflow-y-auto p-6">
        <slot />
      </main>
    </div>
  </div>
</template>