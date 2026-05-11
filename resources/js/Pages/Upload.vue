<script setup>
import { computed } from 'vue'
import AppLayout      from '@/Layouts/AppLayout.vue'
import PageHero       from '@/Components/PageHero.vue'
import SectionCard    from '@/Components/SectionCard.vue'
import MiniMetric     from '@/Components/MiniMetric.vue'
import Banners        from '@/Components/Upload/Banners.vue'
import FiscalYearTabs from '@/Components/Upload/FiscalYearTabs.vue'
import DropZone       from '@/Components/Upload/DropZone.vue'
import ImportHistory  from '@/Components/Upload/ImportHistory.vue'
import PreviewModal   from '@/Components/Upload/PreviewModal.vue'
import { useUploadLogic } from '@/composables/useUploadLogic'

const props = defineProps({ history: { type: Array, default: () => [] } })

const {
  stage, file, year, dragging, error,
  showModal, previewRows, filename, parseStats, savedMessage,
  selectedRows, totalBudget, groupedRows, parentOptions,
  onPick, uploadAndParse,
  toggleRow, toggleEdit, selectAll, previewIndex,
  showAddOffice, newOffice, openAddOffice, saveNewOffice,
  moveTarget, moveTargetIdx, showMovePopup, openMovePopup, applyMove, removeFromParent,
  dragRow, dragOver, dragOverPos,
  onDragStart, onDragOver, onDragLeave, onDrop, onDragEnd,
  confirmImport, reset,
  php, phpM,
} = useUploadLogic()

const importCount = computed(() => props.history?.length ?? 0)
const stageLabel = computed(() => stage.value ? stage.value.replace(/_/g, ' ') : 'Ready')
</script>

<template>
  <AppLayout>
    <template #breadcrumb></template>
    <template #title>Upload WFP Data</template>
    <template #subtitle>Import Work & Financial Plan Excel files · Preview before saving</template>

    <div class="space-y-5">
      <PageHero
        eyebrow="WFP Import Flow"
        title="Upload, preview, correct, then save your WFP data"
        subtitle="The import page now behaves like a proper review workflow: choose fiscal year, upload the Excel file, preview parsed rows, then confirm only after checking the data. Revolutionary concept: not blindly trusting a spreadsheet."
      >
        <template #stats>
          <div class="grid gap-3 sm:grid-cols-3 lg:grid-cols-1">
            <div class="rounded-2xl border border-white/15 bg-white/10 p-4 backdrop-blur">
              <p class="text-[10px] font-black uppercase tracking-[0.16em] text-[#DDFBE8]/80">Selected Year</p>
              <p class="mt-1 text-2xl font-black text-white">FY {{ year }}</p>
            </div>
            <div class="rounded-2xl border border-white/15 bg-white/10 p-4 backdrop-blur">
              <p class="text-[10px] font-black uppercase tracking-[0.16em] text-[#DDFBE8]/80">Import Stage</p>
              <p class="mt-1 text-2xl font-black capitalize text-white">{{ stageLabel }}</p>
            </div>
            <div class="rounded-2xl border border-white/15 bg-white/10 p-4 backdrop-blur">
              <p class="text-[10px] font-black uppercase tracking-[0.16em] text-[#DDFBE8]/80">Past Imports</p>
              <p class="mt-1 text-2xl font-black text-white">{{ importCount }}</p>
            </div>
          </div>
        </template>
      </PageHero>

      <div class="grid gap-4 md:grid-cols-3">
        <MiniMetric label="Step 1" value="Upload" note="Select official Excel file" />
        <MiniMetric label="Step 2" value="Preview" note="Review offices and amounts" />
        <MiniMetric label="Step 3" value="Confirm" note="Save validated rows" />
      </div>

      <div class="grid gap-5 xl:grid-cols-[minmax(0,42rem)_1fr]">
        <div class="space-y-5">

      <!-- Banners (success / error) -->
      <Banners :stage="stage" :saved-message="savedMessage" :error="error" @reset="reset" />

      <!-- Upload card -->
      <SectionCard
        title="Select WFP Excel File"
        subtitle="Upload the official DNSC Work & Financial Plan file · parsed data will appear for review before saving">

        <!-- Year selector with + button -->
        <FiscalYearTabs v-model="year" />

        <!-- Drop zone + upload button -->
        <DropZone
          :file="file"
          :dragging="dragging"
          :year="year"
          :stage="stage"
          @pick="onPick"
          @clear="file = null; error = null"
          @upload="uploadAndParse"
          @dragging="v => dragging = v"
        />

        <!-- How it works steps -->
        <div class="mt-5 grid grid-cols-3 gap-3">
          <div v-for="(step, i) in [
            { n:'1', label:'Upload',  desc:'Select your WFP Excel file' },
            { n:'2', label:'Preview', desc:'Review & edit parsed data' },
            { n:'3', label:'Save',    desc:'Confirm to update database' },
          ]" :key="i"
            class="flex items-start gap-2.5 p-3 rounded-xl bg-gray-50 border border-gray-100">
            <span class="w-5 h-5 rounded-full bg-[#064E3B] text-white text-[10px] font-extrabold flex items-center justify-center shrink-0 mt-0.5">
              {{ step.n }}
            </span>
            <div>
              <p class="text-[11px] font-bold text-[#064E3B]">{{ step.label }}</p>
              <p class="text-[10px] text-gray-400 mt-0.5">{{ step.desc }}</p>
            </div>
          </div>
        </div>
      </SectionCard>

      <!-- Import history table -->
      <ImportHistory :history="props.history" />
        </div>

        <SectionCard title="Import Readiness" subtitle="What the system checks before data becomes official">
          <div class="space-y-3">
            <div v-for="item in [
              ['Fiscal year selected', `FY ${year}`],
              ['Excel file attached', file ? file.name : 'No file yet'],
              ['Preview required', 'Rows must be reviewed before saving'],
              ['Office identity support', 'Renamed offices can stay connected'],
            ]" :key="item[0]" class="flex items-start gap-3 rounded-2xl border border-[#DDEDE3] bg-[#F8FCF9] p-4">
              <div class="grid h-8 w-8 shrink-0 place-items-center rounded-xl bg-[#ECFDF3] text-[#168A4A]">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.3" viewBox="0 0 24 24">
                  <path d="M20 6 9 17l-5-5" />
                </svg>
              </div>
              <div class="min-w-0">
                <p class="text-[12px] font-black text-[#064E3B]">{{ item[0] }}</p>
                <p class="mt-1 truncate text-[11px] font-semibold text-[#64746B]">{{ item[1] }}</p>
              </div>
            </div>
          </div>
        </SectionCard>
      </div>
    </div>

    <!-- Preview modal (teleported to body) -->
    <PreviewModal
      v-if="showModal"
      :show="showModal"
      :filename="filename"
      :parse-stats="parseStats"
      :preview-rows="previewRows"
      :grouped-rows="groupedRows"
      :selected-rows="selectedRows"
      :total-budget="totalBudget"
      :parent-options="parentOptions"
      :stage="stage"
      :show-add-office="showAddOffice"
      :new-office="newOffice"
      :show-move-popup="showMovePopup"
      :move-target="moveTarget"
      :drag-row="dragRow"
      :drag-over="dragOver"
      :drag-over-pos="dragOverPos"
      :php="php"
      :php-m="phpM"
      @close="showModal = false"
      @confirm="confirmImport"
      @select-all="selectAll"
      @toggle-row="toggleRow"
      @toggle-edit="toggleEdit"
      @preview-index="previewIndex"
      @open-add-office="openAddOffice"
      @save-new-office="saveNewOffice"
      @close-add-office="showAddOffice = false"
      @update-new-office="v => newOffice = v"
      @open-move-popup="openMovePopup"
      @apply-move="applyMove"
      @close-move-popup="showMovePopup = false"
      @remove-from-parent="removeFromParent"
      @drag-start="onDragStart"
      @drag-over="onDragOver"
      @drag-leave="onDragLeave"
      @drop="onDrop"
      @drag-end="onDragEnd"
    />
  </AppLayout>
</template>