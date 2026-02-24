<script setup>
import AppLayout   from '@/Layouts/AppLayout.vue'
import SectionCard from '@/Components/SectionCard.vue'
import StatusBadge from '@/Components/StatusBadge.vue'

const users = [
  { name: 'System Admin',    email: 'admin@dnsc.edu.ph',   role: 'Admin',  status: 'active',  last: 'Today' },
  { name: 'VP Academic',     email: 'vpaa@dnsc.edu.ph',    role: 'Viewer', status: 'active',  last: 'Yesterday' },
  { name: 'Finance Office',  email: 'finance@dnsc.edu.ph', role: 'Viewer', status: 'pending', last: 'Never' },
]
</script>

<template>
  <AppLayout>
    <template #header-title>Users & Roles</template>
    <template #header-subtitle>Manage dashboard access and permissions</template>

    <div class="space-y-6 max-w-3xl">
      <div class="flex items-center justify-between">
        <div>
          <h2 class="text-xl font-display font-bold text-gray-800">User Management</h2>
          <p class="text-sm text-gray-400 mt-0.5">Control who can access and modify the dashboard</p>
        </div>
        <button class="bg-[#0B1F3A] text-white text-sm font-semibold px-4 py-2 rounded-xl hover:bg-[#1E90FF] transition-colors flex items-center gap-2">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          Add User
        </button>
      </div>

      <SectionCard title="All Users" :noPad="true">
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b border-gray-100 text-xs text-gray-400 uppercase tracking-widest">
              <th class="text-left px-6 py-3 font-medium">User</th>
              <th class="text-left px-6 py-3 font-medium">Role</th>
              <th class="text-left px-6 py-3 font-medium">Status</th>
              <th class="text-left px-6 py-3 font-medium">Last Login</th>
              <th class="px-6 py-3" />
            </tr>
          </thead>
          <tbody>
            <tr v-for="user in users" :key="user.email" class="border-b border-gray-50 hover:bg-[#F8FAFF] transition-colors">
              <td class="px-6 py-3.5">
                <div class="flex items-center gap-3">
                  <div class="w-8 h-8 rounded-full bg-gradient-to-br from-[#1E90FF] to-[#0057B7] flex items-center justify-center text-white text-xs font-bold shrink-0">
                    {{ user.name[0] }}
                  </div>
                  <div>
                    <p class="font-medium text-gray-700">{{ user.name }}</p>
                    <p class="text-xs text-gray-400">{{ user.email }}</p>
                  </div>
                </div>
              </td>
              <td class="px-6 py-3.5">
                <span :class="[
                  'text-xs font-semibold px-2.5 py-1 rounded-full',
                  user.role === 'Admin' ? 'bg-[#0B1F3A] text-white' : 'bg-gray-100 text-gray-600'
                ]">{{ user.role }}</span>
              </td>
              <td class="px-6 py-3.5"><StatusBadge :status="user.status" :label="user.status" /></td>
              <td class="px-6 py-3.5 text-xs text-gray-400">{{ user.last }}</td>
              <td class="px-6 py-3.5 text-right">
                <button class="text-xs text-[#1E90FF] hover:underline">Edit</button>
              </td>
            </tr>
          </tbody>
        </table>
      </SectionCard>

      <!-- Role legend -->
      <SectionCard title="Role Permissions">
        <div class="grid grid-cols-2 gap-4">
          <div class="bg-gray-50 rounded-xl p-4 border border-gray-200">
            <p class="font-semibold text-gray-700 text-sm flex items-center gap-2">
              <span class="w-5 h-5 rounded bg-[#0B1F3A] inline-flex items-center justify-center text-white text-[10px]">A</span>
              Admin
            </p>
            <ul class="mt-2 space-y-1 text-xs text-gray-500">
              <li>✅ View all data</li>
              <li>✅ Upload WFP files</li>
              <li>✅ Manage users</li>
              <li>✅ Export reports</li>
            </ul>
          </div>
          <div class="bg-gray-50 rounded-xl p-4 border border-gray-200">
            <p class="font-semibold text-gray-700 text-sm flex items-center gap-2">
              <span class="w-5 h-5 rounded bg-gray-400 inline-flex items-center justify-center text-white text-[10px]">V</span>
              Viewer
            </p>
            <ul class="mt-2 space-y-1 text-xs text-gray-500">
              <li>✅ View all data</li>
              <li>✅ Export reports</li>
              <li>❌ Upload WFP files</li>
              <li>❌ Manage users</li>
            </ul>
          </div>
        </div>
      </SectionCard>
    </div>
  </AppLayout>
</template>