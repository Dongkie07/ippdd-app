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
      { label: 'Dashboard',         href: '/',                  icon: 'grid' },
    ]
  },
  {
     group: 'Financial',
     items: [
       { label: 'Budget by Department', href: '/budget',         icon: 'chart-bar' },
      // { label: 'Performance Indicators', href: '/indicators',   icon: 'target' },
     ]
   },
  {
  group: 'Management',
   items: [
   { label: 'Upload WFP Data',   href: '/upload',            icon: 'upload' },
   // { label: 'Reports & Export',  href: '/reports',           icon: 'document' },
    ]
   },
  // {
  //   group: 'System',
  //   items: [
  //     { label: 'Users & Roles',     href: '/users',             icon: 'users' },
  //     { label: 'Settings',          href: '/settings',          icon: 'settings' },
  //   ]// },
]

const isActive = (href) => {
  if (href === '/') return currentPath.value === '/'
  return currentPath.value.startsWith(href)
}
</script>

<template>
  <div class="flex h-screen bg-[#F0F2F5] font-body overflow-hidden">

    <!-- ── Sidebar ──────────────────────────────────────────── -->
    <aside
      :class="[
        'flex flex-col bg-[#0B1F3A] transition-all duration-300 shrink-0 relative z-20',
        sidebarOpen ? 'w-60' : 'w-16'
      ]"
    >
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
            <!-- Icon -->
            <span class="shrink-0 w-5 h-5 flex items-center justify-center">
              <!-- grid -->
              <svg v-if="item.icon === 'grid'" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/>
                <rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>
              </svg>
              <!-- chart-bar -->
              <svg v-if="item.icon === 'chart-bar'" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <path d="M3 18V10M8 18V6M13 18v-4M18 18V8"/><line x1="3" y1="18" x2="21" y2="18"/>
              </svg>
              <!-- target -->
              <svg v-if="item.icon === 'target'" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1" fill="currentColor"/>
              </svg>
              <!-- upload -->
              <svg v-if="item.icon === 'upload'" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <path d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M16 8l-4-4-4 4M12 4v12"/>
              </svg>
              <!-- document -->
              <svg v-if="item.icon === 'document'" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/>
                <line x1="8" y1="13" x2="16" y2="13"/><line x1="8" y1="17" x2="12" y2="17"/>
              </svg>
              <!-- users -->
              <svg v-if="item.icon === 'users'" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/>
                <path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/>
              </svg>
              <!-- settings -->
              <svg v-if="item.icon === 'settings'" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <circle cx="12" cy="12" r="3"/>
                <path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-2 2 2 2 0 01-2-2v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 01-2-2 2 2 0 012-2h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 010-2.83 2 2 0 012.83 0l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 012-2 2 2 0 012 2v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 012 2 2 2 0 01-2 2h-.09a1.65 1.65 0 00-1.51 1z"/>
              </svg>
            </span>

            <span v-if="sidebarOpen" class="text-sm font-medium whitespace-nowrap">{{ item.label }}</span>

            <!-- Active indicator -->
            <span
              v-if="isActive(item.href)"
              class="ml-auto w-1.5 h-1.5 rounded-full bg-[#1E90FF] shrink-0"
            />
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
            <h1 class="text-base font-display font-semibold text-gray-800">Dashboard</h1>
          </slot>
          <slot name="header-subtitle">
            <p class="text-xs text-gray-400 mt-0.5">Davao del Norte State College · Work & Financial Plan</p>
          </slot>
        </div>

        <div class="flex items-center gap-3">
          <!-- Notification bell -->
          <button class="relative w-8 h-8 rounded-lg hover:bg-gray-100 flex items-center justify-center text-gray-500 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
              <path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9M13.73 21a2 2 0 01-3.46 0"/>
            </svg>
            <span class="absolute top-1 right-1 w-1.5 h-1.5 bg-red-500 rounded-full"></span>
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