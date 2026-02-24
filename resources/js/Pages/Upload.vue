<script setup>
import { ref } from 'vue'
import AppLayout   from '@/Layouts/AppLayout.vue'
import SectionCard from '@/Components/SectionCard.vue'
import StatusBadge from '@/Components/StatusBadge.vue'

const dragging  = ref(false)
const file      = ref(null)
const uploading = ref(false)
const uploaded  = ref(false)

const onDrop = (e) => {
  dragging.value = false
  const f = e.dataTransfer?.files[0] ?? e.target.files[0]
  if (f && (f.name.endsWith('.xlsx') || f.name.endsWith('.xls'))) {
    file.value = f
  }
}

const handleSubmit = async () => {
  if (!file.value) return
  uploading.value = true
  // TODO: POST to /upload with FormData containing the file
  // const form = new FormData()
  // form.append('file', file.value)
  // await axios.post('/upload', form)
  await new Promise(r => setTimeout(r, 1800)) // simulate
  uploading.value = false
  uploaded.value = true
}

const reset = () => { file.value = null; uploaded.value = false }

const recentUploads = [
  { name: 'WORK_AND_FINANCIAL_PLAN__2026_.xlsx', year: 2026, by: 'Admin',  date: 'Feb 1, 2026',  status: 'completed' },
  { name: 'WORK_AND_FINANCIAL_PLAN__2025_.xlsx', year: 2025, by: 'Admin',  date: 'Jan 5, 2025',  status: 'completed' },
  { name: 'WORK_AND_FINANCIAL_PLAN__2024_.xlsx', year: 2024, by: 'Admin',  date: 'Jan 3, 2024',  status: 'completed' },
]
</script>

<template>
  <AppLayout>
    <template #header-title>Upload WFP Data</template>
    <template #header-subtitle>Add new Work & Financial Plan Excel files</template>

    <div class="space-y-6 max-w-3xl">

      <div>
        <h2 class="text-xl font-display font-bold text-gray-800">Upload New WFP</h2>
        <p class="text-sm text-gray-400 mt-0.5">Supports .xlsx and .xls files exported from the WFP template</p>
      </div>

      <!-- Drop Zone -->
      <SectionCard title="Upload File" subtitle="Drag & drop or click to browse">
        <div
          v-if="!file && !uploaded"
          @dragover.prevent="dragging = true"
          @dragleave="dragging = false"
          @drop.prevent="onDrop"
          @click="$refs.fileInput.click()"
          :class="[
            'border-2 border-dashed rounded-2xl p-12 text-center cursor-pointer transition-all',
            dragging ? 'border-[#1E90FF] bg-blue-50' : 'border-gray-200 hover:border-[#1E90FF] hover:bg-blue-50/30'
          ]"
        >
          <input ref="fileInput" type="file" accept=".xlsx,.xls" class="hidden" @change="onDrop" />
          <div class="w-14 h-14 rounded-2xl bg-blue-50 flex items-center justify-center mx-auto mb-4">
            <svg class="w-7 h-7 text-[#1E90FF]" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
              <path d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M16 8l-4-4-4 4M12 4v12"/>
            </svg>
          </div>
          <p class="font-semibold text-gray-600 text-sm">Drop your WFP Excel file here</p>
          <p class="text-xs text-gray-400 mt-1">or click to browse · .xlsx / .xls supported</p>
        </div>

        <!-- File selected -->
        <div v-else-if="file && !uploaded" class="space-y-4">
          <div class="flex items-center gap-4 bg-blue-50 rounded-xl p-4 border border-blue-100">
            <div class="w-10 h-10 rounded-xl bg-[#1E90FF]/10 flex items-center justify-center shrink-0">
              <svg class="w-5 h-5 text-[#1E90FF]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/>
              </svg>
            </div>
            <div class="flex-1 min-w-0">
              <p class="font-semibold text-gray-700 text-sm truncate">{{ file.name }}</p>
              <p class="text-xs text-gray-400 mt-0.5">{{ (file.size / 1024).toFixed(0) }} KB</p>
            </div>
            <button @click="reset" class="text-gray-400 hover:text-red-500 transition-colors">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
              </svg>
            </button>
          </div>
          <button
            @click="handleSubmit"
            :disabled="uploading"
            class="w-full py-2.5 rounded-xl bg-[#0B1F3A] text-white font-semibold text-sm hover:bg-[#1E90FF] transition-colors disabled:opacity-50 flex items-center justify-center gap-2"
          >
            <svg v-if="uploading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
            </svg>
            {{ uploading ? 'Processing…' : 'Upload & Process File' }}
          </button>
        </div>

        <!-- Success -->
        <div v-else-if="uploaded" class="text-center py-8">
          <div class="w-14 h-14 rounded-full bg-emerald-100 flex items-center justify-center mx-auto mb-3">
            <svg class="w-7 h-7 text-emerald-500" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
              <polyline points="20 6 9 17 4 12"/>
            </svg>
          </div>
          <p class="font-bold text-gray-700">File uploaded successfully!</p>
          <p class="text-xs text-gray-400 mt-1">Dashboard data will refresh shortly.</p>
          <button @click="reset" class="mt-4 text-sm text-[#1E90FF] hover:underline">Upload another file</button>
        </div>
      </SectionCard>

      <!-- Upload history -->
      <SectionCard title="Upload History" subtitle="Previous WFP files processed" :noPad="true">
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b border-gray-100 text-xs text-gray-400 uppercase tracking-widest">
              <th class="text-left px-6 py-3 font-medium">File</th>
              <th class="text-left px-6 py-3 font-medium">Year</th>
              <th class="text-left px-6 py-3 font-medium">Uploaded by</th>
              <th class="text-left px-6 py-3 font-medium">Date</th>
              <th class="text-left px-6 py-3 font-medium">Status</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="upload in recentUploads" :key="upload.name" class="border-b border-gray-50 hover:bg-[#F8FAFF] transition-colors">
              <td class="px-6 py-3 font-mono text-xs text-gray-600">{{ upload.name }}</td>
              <td class="px-6 py-3 font-semibold text-gray-700">{{ upload.year }}</td>
              <td class="px-6 py-3 text-gray-500">{{ upload.by }}</td>
              <td class="px-6 py-3 text-gray-400 text-xs">{{ upload.date }}</td>
              <td class="px-6 py-3"><StatusBadge :status="upload.status" :label="upload.status" /></td>
            </tr>
          </tbody>
        </table>
      </SectionCard>

    </div>
  </AppLayout>
</template>