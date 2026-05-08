<script setup>
/**
 * Pages/Departments.vue — Manual Data Entry
 * Thin page only: layout + component wiring.
 * Business logic lives in Pages/Departments/useDepartments.js.
 */
import AppLayout from '@/Layouts/AppLayout.vue'
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
  totalBudget,
  fundTotals,
  formTotal,
  funds,
  openAdd,
  openEdit,
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
    <template #breadcrumb>Manual Entry</template>
    <template #title>Manual Data Entry</template>
    <template #subtitle>Add, edit, or delete department budget data for any fiscal year</template>

    <div class="space-y-5">
      <FeedbackToast :feedback="feedback" />

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
      :funds="funds"
      :form-total="formTotal"
      :saving="saving"
      @close="closeForm"
      @submit="submitForm"
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
