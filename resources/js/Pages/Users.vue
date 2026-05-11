<script setup>
import { computed, ref } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import PageHero from '@/Components/PageHero.vue'
import SectionCard from '@/Components/SectionCard.vue'
import MiniMetric from '@/Components/MiniMetric.vue'
import StatusBadge from '@/Components/StatusBadge.vue'

const users = [
  { name: 'System Admin', email: 'admin@dnsc.edu.ph', role: 'Admin', status: 'active', last: 'Today', access: 100 },
  { name: 'VP Academic', email: 'vpaa@dnsc.edu.ph', role: 'Viewer', status: 'active', last: 'Yesterday', access: 65 },
  { name: 'Finance Office', email: 'finance@dnsc.edu.ph', role: 'Viewer', status: 'pending', last: 'Never', access: 35 },
]

const roleFilter = ref('All')
const search = ref('')

const filteredUsers = computed(() => users.filter((user) => {
  const matchesRole = roleFilter.value === 'All' || user.role === roleFilter.value
  const searchText = `${user.name} ${user.email} ${user.role}`.toLowerCase()
  return matchesRole && searchText.includes(search.value.toLowerCase())
}))

const activeUsers = computed(() => users.filter((user) => user.status === 'active').length)
const pendingUsers = computed(() => users.filter((user) => user.status === 'pending').length)
const adminUsers = computed(() => users.filter((user) => user.role === 'Admin').length)

const permissions = [
  { feature: 'View dashboard and charts', admin: true, viewer: true },
  { feature: 'Upload WFP files', admin: true, viewer: false },
  { feature: 'Manual data entry', admin: true, viewer: false },
  { feature: 'Office registry changes', admin: true, viewer: false },
  { feature: 'Generate reports', admin: true, viewer: true },
]
</script>

<template>
  <AppLayout>
    <template #breadcrumb>
      <p class="text-[10px] font-black uppercase tracking-[0.2em] text-[#168A4A]">Access Control</p>
    </template>
    <template #title>Users & Roles</template>
    <template #subtitle>Manage dashboard access, permissions, and account visibility</template>

    <div class="space-y-5">
      <PageHero
        eyebrow="Role Governance"
        title="Cleaner access control for the people allowed to touch the data"
        subtitle="This page presents account status, role levels, and permission boundaries in a way that is easier to defend than saying ‘sir, admin lang po yan’ and hoping no one asks follow-up questions."
        tone="slate"
      >
        <template #actions>
          <button class="inline-flex items-center justify-center gap-2 rounded-2xl bg-white px-4 py-2.5 text-[13px] font-black text-[#064E3B] shadow-lg shadow-black/10 hover:-translate-y-0.5 hover:bg-[#ECFDF3]">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
              <path d="M12 5v14M5 12h14" />
            </svg>
            Add User
          </button>
        </template>
        <template #stats>
          <div class="grid gap-3 sm:grid-cols-3 lg:grid-cols-1">
            <div class="rounded-2xl border border-white/15 bg-white/10 p-4 backdrop-blur">
              <p class="text-[10px] font-black uppercase tracking-[0.16em] text-[#DDFBE8]/80">Active Users</p>
              <p class="mt-1 text-2xl font-black text-white">{{ activeUsers }}</p>
            </div>
            <div class="rounded-2xl border border-white/15 bg-white/10 p-4 backdrop-blur">
              <p class="text-[10px] font-black uppercase tracking-[0.16em] text-[#DDFBE8]/80">Pending</p>
              <p class="mt-1 text-2xl font-black text-white">{{ pendingUsers }}</p>
            </div>
            <div class="rounded-2xl border border-white/15 bg-white/10 p-4 backdrop-blur">
              <p class="text-[10px] font-black uppercase tracking-[0.16em] text-[#DDFBE8]/80">Admin Accounts</p>
              <p class="mt-1 text-2xl font-black text-white">{{ adminUsers }}</p>
            </div>
          </div>
        </template>
      </PageHero>

      <div class="grid gap-4 md:grid-cols-3">
        <MiniMetric label="Total Accounts" :value="users.length" note="Tracked users" />
        <MiniMetric label="Active" :value="activeUsers" note="Can access dashboard" />
        <MiniMetric label="Pending" :value="pendingUsers" note="Needs review" />
      </div>

      <SectionCard title="User Directory" subtitle="Search and filter users by role" :noPad="true">
        <div class="border-b border-[#E6F2EA] bg-[#F8FCF9] p-4">
          <div class="grid gap-3 lg:grid-cols-[1fr_auto]">
            <div class="relative">
              <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-[#8FA79B]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <circle cx="11" cy="11" r="8" /><path d="m21 21-4.3-4.3" />
              </svg>
              <input
                v-model="search"
                type="search"
                placeholder="Search by name, email, or role..."
                class="w-full rounded-2xl border border-[#DDEDE3] bg-white py-3 pl-10 pr-4 text-sm font-semibold text-[#064E3B] placeholder:text-[#8FA79B]"
              />
            </div>

            <div class="inline-flex rounded-2xl border border-[#DDEDE3] bg-white p-1">
              <button
                v-for="role in ['All', 'Admin', 'Viewer']"
                :key="role"
                @click="roleFilter = role"
                :class="[
                  'rounded-xl px-4 py-2 text-[12px] font-black transition-all',
                  roleFilter === role ? 'bg-[#064E3B] text-white shadow-md shadow-[#064E3B]/15' : 'text-[#64746B] hover:bg-[#ECFDF3] hover:text-[#064E3B]',
                ]"
              >
                {{ role }}
              </button>
            </div>
          </div>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-[#E6F2EA] bg-white text-[10px] uppercase tracking-[0.16em] text-[#8FA79B]">
                <th class="px-6 py-3 text-left font-black">User</th>
                <th class="px-6 py-3 text-left font-black">Role</th>
                <th class="px-6 py-3 text-left font-black">Status</th>
                <th class="px-6 py-3 text-left font-black">Access Level</th>
                <th class="px-6 py-3 text-left font-black">Last Login</th>
                <th class="px-6 py-3" />
              </tr>
            </thead>
            <tbody>
              <tr v-for="user in filteredUsers" :key="user.email" class="border-b border-[#F0F7F2] transition-colors hover:bg-[#F8FCF9]">
                <td class="px-6 py-4">
                  <div class="flex items-center gap-3">
                    <div class="grid h-10 w-10 shrink-0 place-items-center rounded-2xl bg-gradient-to-br from-[#168A4A] to-[#047857] text-xs font-black text-white shadow-sm">
                      {{ user.name[0] }}
                    </div>
                    <div>
                      <p class="font-black text-[#064E3B]">{{ user.name }}</p>
                      <p class="text-xs font-semibold text-[#8FA79B]">{{ user.email }}</p>
                    </div>
                  </div>
                </td>
                <td class="px-6 py-4">
                  <span :class="[
                    'rounded-full px-3 py-1 text-[11px] font-black ring-1',
                    user.role === 'Admin' ? 'bg-[#064E3B] text-white ring-[#064E3B]' : 'bg-[#ECFDF3] text-[#168A4A] ring-[#B7F4CE]',
                  ]">
                    {{ user.role }}
                  </span>
                </td>
                <td class="px-6 py-4"><StatusBadge :status="user.status" :label="user.status" /></td>
                <td class="px-6 py-4 min-w-44">
                  <div class="flex items-center gap-3">
                    <div class="h-2 flex-1 overflow-hidden rounded-full bg-[#E6F2EA]">
                      <div class="h-full rounded-full bg-[#168A4A]" :style="{ width: `${user.access}%` }" />
                    </div>
                    <span class="text-xs font-black text-[#064E3B]">{{ user.access }}%</span>
                  </div>
                </td>
                <td class="px-6 py-4 text-xs font-bold text-[#64746B]">{{ user.last }}</td>
                <td class="px-6 py-4 text-right">
                  <button class="rounded-xl px-3 py-1.5 text-[11px] font-black text-[#168A4A] hover:bg-[#ECFDF3] hover:text-[#064E3B]">
                    Edit
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </SectionCard>

      <SectionCard title="Role Permission Matrix" subtitle="Clear separation of admin and viewer actions">
        <div class="overflow-hidden rounded-2xl border border-[#DDEDE3]">
          <table class="w-full text-sm">
            <thead>
              <tr class="bg-[#F8FCF9] text-[10px] uppercase tracking-[0.16em] text-[#8FA79B]">
                <th class="px-5 py-3 text-left font-black">Feature</th>
                <th class="px-5 py-3 text-center font-black">Admin</th>
                <th class="px-5 py-3 text-center font-black">Viewer</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="permission in permissions" :key="permission.feature" class="border-t border-[#E6F2EA]">
                <td class="px-5 py-3 font-bold text-[#064E3B]">{{ permission.feature }}</td>
                <td class="px-5 py-3 text-center">
                  <span :class="permission.admin ? 'bg-emerald-50 text-emerald-600 ring-emerald-100' : 'bg-slate-50 text-slate-400 ring-slate-100'" class="inline-flex rounded-full px-2.5 py-1 text-[11px] font-black ring-1">
                    {{ permission.admin ? 'Allowed' : 'Blocked' }}
                  </span>
                </td>
                <td class="px-5 py-3 text-center">
                  <span :class="permission.viewer ? 'bg-emerald-50 text-emerald-600 ring-emerald-100' : 'bg-slate-50 text-slate-400 ring-slate-100'" class="inline-flex rounded-full px-2.5 py-1 text-[11px] font-black ring-1">
                    {{ permission.viewer ? 'Allowed' : 'Blocked' }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </SectionCard>
    </div>
  </AppLayout>
</template>
