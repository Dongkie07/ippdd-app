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
      { label: 'Executive Dashboard', href: '/', icon: 'grid' },
    ]
  },
  {
    group: 'Management',
    items: [
      { label: 'Upload WFP Data', href: '/upload', icon: 'upload' },
      { label: 'Dept. Breakdown', href: '/budget', icon: 'chart-bar' },
      { label: 'Manual Entry', href: '/departments', icon: 'edit' },
    ]
  },
]

const isActive = (href) => {
  if (href === '/') return currentPath.value === '/'
  return currentPath.value.startsWith(href)
}
</script>

<template>
  <div class="flex h-screen overflow-hidden bg-[#F4F8F5] font-body text-slate-800">
    <!-- ── Sidebar ──────────────────────────────────────────── -->
    <aside :class="['relative z-20 flex shrink-0 flex-col overflow-hidden bg-gradient-to-b from-[#064E3B] via-[#075D3F] to-[#022C22] text-white shadow-2xl shadow-[#064E3B]/20 transition-all duration-300', sidebarOpen ? 'w-72' : 'w-20']">
      <div class="pointer-events-none absolute inset-0 opacity-25">
        <div class="absolute -left-24 top-16 h-56 w-56 rounded-full border border-[#53D28C]/30" />
        <div class="absolute -right-20 bottom-10 h-72 w-72 rounded-full border border-[#53D28C]/20" />
        <div class="absolute inset-0 bg-[linear-gradient(135deg,rgba(255,255,255,.08)_0,rgba(255,255,255,0)_38%)]" />
      </div>

      <!-- Logo -->
      <div class="relative flex items-center gap-3 border-b border-white/10 px-4 py-5">
        <div class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-white shadow-lg shadow-black/10 ring-1 ring-white/40">
          <div class="grid h-9 w-9 place-items-center rounded-xl bg-gradient-to-br from-[#064E3B] to-[#168A4A] text-[11px] font-black tracking-tight text-white">
            IP
          </div>
        </div>
        <div v-if="sidebarOpen" class="min-w-0 overflow-hidden">
          <p class="truncate font-display text-[15px] font-black leading-tight tracking-tight text-white">IPPDD</p>
          <p class="mt-0.5 truncate text-[11px] font-semibold text-[#B7F4CE]/80">Executive Dashboard</p>
        </div>
      </div>

      <!-- Navigation -->
      <nav class="relative flex-1 space-y-6 overflow-y-auto px-3 py-5">
        <div v-for="group in navItems" :key="group.group">
          <p v-if="sidebarOpen" class="mb-2 px-3 text-[9px] font-black uppercase tracking-[0.18em] text-[#B7F4CE]/55">
            {{ group.group }}
          </p>
          <div v-else class="mb-3 border-t border-white/10" />

          <Link
            v-for="item in group.items"
            :key="item.href"
            :href="item.href"
            :class="[
              'group flex items-center gap-3 rounded-2xl px-3 py-2.5 transition-all duration-150',
              isActive(item.href)
                ? 'bg-white text-[#064E3B] shadow-lg shadow-black/10'
                : 'text-[#DDFBE8]/70 hover:bg-white/10 hover:text-white'
            ]"
          >
            <span :class="[
              'grid h-8 w-8 shrink-0 place-items-center rounded-xl transition-colors',
              isActive(item.href) ? 'bg-[#ECFDF3] text-[#064E3B]' : 'bg-white/10 text-[#B7F4CE] group-hover:bg-white/15 group-hover:text-white'
            ]">
              <svg v-if="item.icon === 'grid'" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/>
                <rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>
              </svg>
              <svg v-if="item.icon === 'chart-bar'" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <rect x="3" y="12" width="4" height="8" rx="1"/><rect x="10" y="7" width="4" height="13" rx="1"/><rect x="17" y="3" width="4" height="17" rx="1"/>
              </svg>
              <svg v-if="item.icon === 'upload'" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <path d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M16 8l-4-4-4 4M12 4v12"/>
              </svg>
              <svg v-if="item.icon === 'edit'" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 013 3L7 19l-4 1 1-4 12.5-12.5z"/>
              </svg>
            </span>
            <span v-if="sidebarOpen" class="truncate text-[13px] font-bold">{{ item.label }}</span>
            <span v-if="sidebarOpen && isActive(item.href)" class="ml-auto h-2 w-2 rounded-full bg-[#168A4A]" />
          </Link>
        </div>
      </nav>

      <!-- Collapse toggle -->
      <button
        @click="sidebarOpen = !sidebarOpen"
        class="relative m-3 flex items-center justify-center rounded-2xl border border-white/10 bg-white/5 p-2 text-[#B7F4CE]/70 transition-all hover:bg-white/10 hover:text-white"
      >
        <svg class="h-4 w-4 transition-transform" :class="sidebarOpen ? '' : 'rotate-180'" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path d="M15 18l-6-6 6-6"/>
        </svg>
      </button>
    </aside>

    <!-- ── Main Area ─────────────────────────────────────────── -->
    <div class="relative flex flex-1 flex-col overflow-hidden">
      <div class="pointer-events-none absolute inset-0 overflow-hidden">
        <div class="absolute -top-28 right-10 h-80 w-80 rounded-full bg-[#53D28C]/[0.18] blur-3xl" />
        <div class="absolute bottom-0 left-10 h-72 w-72 rounded-full bg-[#064E3B]/[0.08] blur-3xl" />
      </div>

      <!-- Top Bar -->
      <header class="relative shrink-0 border-b border-[#DDEDE3] bg-white/90 px-6 py-4 shadow-sm backdrop-blur-xl">
        <div class="flex items-center justify-between gap-4">
          <div class="min-w-0">
            <slot name="breadcrumb">
              <p class="text-[10px] font-black uppercase tracking-[0.2em] text-[#168A4A]">IPPDD</p>
            </slot>
            <slot name="header-title">
              <h1 class="mt-1 truncate font-display text-[18px] font-black tracking-tight text-[#064E3B]">
                <slot name="title">Executive Dashboard</slot>
              </h1>
            </slot>
            <slot name="header-subtitle">
              <p class="mt-1 text-[12px] font-medium text-[#64746B]">
                <slot name="subtitle">Davao del Norte State College · Work & Financial Plan</slot>
              </p>
            </slot>
          </div>

          <div class="flex items-center gap-3">
            <button class="relative grid h-10 w-10 place-items-center rounded-2xl border border-[#DDEDE3] bg-[#F8FCF9] text-[#064E3B] transition-colors hover:bg-[#ECFDF3]">
              <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9M13.73 21a2 2 0 01-3.46 0"/>
              </svg>
              <span class="absolute right-2 top-2 h-2 w-2 rounded-full bg-[#D6B74A] ring-2 ring-white" />
            </button>

            <div class="flex items-center gap-3 rounded-2xl border border-[#DDEDE3] bg-white px-3 py-2 shadow-sm">
              <div class="grid h-8 w-8 place-items-center rounded-xl bg-gradient-to-br from-[#064E3B] to-[#168A4A] text-xs font-black text-white">A</div>
              <div class="hidden sm:block">
                <p class="text-[12px] font-black leading-none text-[#064E3B]">Admin</p>
                <p class="mt-1 text-[10px] font-semibold text-[#8FA79B]">DNSC</p>
              </div>
            </div>
          </div>
        </div>
      </header>

      <!-- Page Content -->
      <main class="relative flex-1 overflow-y-auto p-6">
        <slot />
      </main>
    </div>
  </div>
</template>
