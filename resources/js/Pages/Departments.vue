<script setup>
/**
 * Pages/Departments.vue — Manual Data Entry
 * Thin page only: layout + component wiring.
 * Business logic lives in Pages/Departments/useDepartments.js.
 */
import AppLayout from '@/Layouts/AppLayout.vue'
import PageHero from '@/Components/PageHero.vue'
import MiniMetric from '@/Components/MiniMetric.vue'
import ConfirmDeleteModal from './Departments/ConfirmDeleteModal.vue'
import DepartmentEditorPanel from './Departments/DepartmentEditorPanel.vue'
import DepartmentSummary from './Departments/DepartmentSummary.vue'
import DepartmentTable from './Departments/DepartmentTable.vue'
import DepartmentToolbar from './Departments/DepartmentToolbar.vue'
import EmptyState from './Departments/EmptyState.vue'
import FeedbackToast from './Departments/FeedbackToast.vue'
import NewYearModal from './Departments/NewYearModal.vue'
import { useDepartments } from './Departments/useDepartments'

const props = defineProps({
  years: { type: Array, default: () => [] },
  deptsByYear: { type: Object, default: () => ({}) },
  offices: { type: Array, default: () => [] },
})

const {
  activeYear,
  saving,
  feedback,
  confirmDelete,
  form,
  showForm,
  formMode,
  showYearForm,
  newYearForm,
  years,
  rows,
  parentOptions,
  offices,
  selectedOffice,
  selectedOfficeHistoricalName,
  totalBudget,
  fundTotals,
  formTotal,
  funds,
  openAdd,
  openEdit,
  selectOffice,
  closeForm,
  openYearForm,
  closeYearForm,
  askDeleteYear,
  askDeleteDepartment,
  cancelDelete,
  submitForm,
  submitNewYear,
  deleteDepartment,
  deleteYear,
} = useDepartments(props)

const confirmDeleteAction = () => {
  confirmDelete.value?.year ? deleteYear() : deleteDepartment()
}
</script>

<template>
  <AppLayout>
    <template #breadcrumb></template>
    <template #title>Manual Data Entry</template>
    <template #subtitle>Add, edit, or delete department budget data for any fiscal year</template>

    <div class="space-y-5">
      <FeedbackToast :feedback="feedback" />

      <PageHero
        eyebrow="Manual Encoding"
        title="Add and maintain WFP records without breaking office history"
        subtitle="Use the office identity dropdown for stable grouping, while the department name field can preserve the exact name used in that fiscal year. Tiny miracle: historical accuracy and clean charts can coexist."
      >
        <template #stats>
          <div class="grid gap-3 sm:grid-cols-3 lg:grid-cols-1">
            <div class="rounded-2xl border border-white/15 bg-white/10 p-4 backdrop-blur">
              <p class="text-[10px] font-black uppercase tracking-[0.16em] text-[#DDFBE8]/80">Active Year</p>
              <p class="mt-1 text-2xl font-black text-white">FY {{ activeYear ?? '—' }}</p>
            </div>
            <div class="rounded-2xl border border-white/15 bg-white/10 p-4 backdrop-blur">
              <p class="text-[10px] font-black uppercase tracking-[0.16em] text-[#DDFBE8]/80">Rows</p>
              <p class="mt-1 text-2xl font-black text-white">{{ rows.length }}</p>
            </div>
            <div class="rounded-2xl border border-white/15 bg-white/10 p-4 backdrop-blur">
              <p class="text-[10px] font-black uppercase tracking-[0.16em] text-[#DDFBE8]/80">Office Registry</p>
              <p class="mt-1 text-2xl font-black text-white">{{ offices.length }}</p>
            </div>
          </div>
        </template>
      </PageHero>

      <div class="grid gap-4 md:grid-cols-3">
        <MiniMetric label="Fiscal Years" :value="years.length" note="Manual entry periods" />
        <MiniMetric label="Current Total" :value="`₱${Number(totalBudget || 0).toLocaleString()}`" note="Selected fiscal year" />
        <MiniMetric label="Registry Link" value="Enabled" note="Uses stable office ID" />
      </div>

      <DepartmentToolbar
        v-model:activeYear="activeYear"
        :years="years"
        @new-year="openYearForm"
        @delete-year="askDeleteYear"
        @add-department="openAdd"
      />

      <EmptyState
        v-if="!activeYear || rows.length === 0"
        :active-year="activeYear"
        @create-year="openYearForm"
        @add-department="openAdd"
      />

      <template v-else>
        <DepartmentSummary
          :active-year="activeYear"
          :total-budget="totalBudget"
          :fund-totals="fundTotals"
          :top-level-count="rows.filter((row) => !row.parent_dept).length"
        />

        <DepartmentTable
          :rows="rows"
          :funds="funds"
          :total-budget="totalBudget"
          @edit="openEdit"
          @delete="askDeleteDepartment"
        />
      </template>
    </div>

    <DepartmentEditorPanel
      :show="showForm"
      :form="form"
      :form-mode="formMode"
      :parent-options="parentOptions"
      :office-options="offices"
      :selected-office="selectedOffice"
      :selected-office-historical-name="selectedOfficeHistoricalName"
      :funds="funds"
      :form-total="formTotal"
      :saving="saving"
      @close="closeForm"
      @submit="submitForm"
      @select-office="selectOffice"
    />

    <NewYearModal
      :show="showYearForm"
      :form="newYearForm"
      :years="years"
      :depts-by-year="props.deptsByYear"
      :saving="saving"
      @close="closeYearForm"
      @submit="submitNewYear"
    />

    <ConfirmDeleteModal
      :item="confirmDelete"
      :record-count="rows.length"
      :saving="saving"
      @cancel="cancelDelete"
      @confirm="confirmDeleteAction"
    />
  </AppLayout>
</template>

<style scoped>
.panel-enter-active,
.panel-leave-active { transition: transform 0.25s ease, opacity 0.25s; }
.panel-enter-from,
.panel-leave-to { transform: translateX(100%); opacity: 0; }

.modal-enter-active,
.modal-leave-active { transition: opacity 0.2s, transform 0.2s; }
.modal-enter-from,
.modal-leave-to { opacity: 0; transform: scale(0.96); }

.toast-enter-active,
.toast-leave-active { transition: opacity 0.3s, transform 0.3s; }
.toast-enter-from,
.toast-leave-to { opacity: 0; transform: translateY(-10px); }
</style>
