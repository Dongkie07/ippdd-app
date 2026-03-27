<script setup>
import AppLayout      from '@/Layouts/AppLayout.vue'
import SectionCard    from '@/Components/SectionCard.vue'
import Banners        from '@/Components/Upload/Banners.vue'
import FiscalYearTabs from '@/Components/Upload/FiscalYearTabs.vue'
import DropZone       from '@/Components/Upload/DropZone.vue'
import ImportHistory  from '@/Components/Upload/ImportHistory.vue'
import PreviewModal   from '@/Components/Upload/PreviewModal.vue'
import { useUploadLogic } from '@/composables/useUploadLogic'

defineProps({ history: { type: Array, default: () => [] } })

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
</script>

<template>
  <AppLayout>
    <template #breadcrumb>Upload WFP Data</template>
    <template #title>Upload WFP Data</template>
    <template #subtitle>Import Work & Financial Plan Excel files · Preview before saving</template>

    <div class="space-y-5 max-w-2xl">

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
            <span class="w-5 h-5 rounded-full bg-[#0D2137] text-white text-[10px] font-extrabold flex items-center justify-center shrink-0 mt-0.5">
              {{ step.n }}
            </span>
            <div>
              <p class="text-[11px] font-bold text-[#0D2137]">{{ step.label }}</p>
              <p class="text-[10px] text-gray-400 mt-0.5">{{ step.desc }}</p>
            </div>
          </div>
        </div>
      </SectionCard>

      <!-- Import history table -->
      <ImportHistory :history="history" />
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