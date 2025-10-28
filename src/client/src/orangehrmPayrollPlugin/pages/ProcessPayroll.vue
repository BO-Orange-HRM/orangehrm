<!--
/**
 * OrangeHRM Payroll Module - Process Payroll
 * Handles payroll period creation, processing, review, and payslip generation.
 */
-->

<template>
  <div class="orangehrm-background-container">
    <div class="orangehrm-card-container">
      <oxd-text tag="h6" class="orangehrm-main-title">
        {{ $t('payroll.process_payroll') }}
      </oxd-text>
      <oxd-divider />

      <!-- Payroll Period Management -->
      <section class="orangehrm-section">
        <oxd-text tag="h6" class="orangehrm-sub-title">
          {{ $t('payroll.payroll_period_management') }}
        </oxd-text>

        <oxd-button
          icon-name="plus"
          :label="$t('general.add_new_period')"
          display-type="secondary"
          @click="showPeriodModal = true"
        />
        <oxd-divider />

        <oxd-table
          :columns="columns"
          :data="periods"
          :loading="isLoading"
          :empty-message="$t('payroll.no_periods_found')"
        >
          <template #cell(actions)="{row}">
            <oxd-icon-button
              icon-name="trash"
              :title="$t('general.delete')"
              @click="deletePeriod(row.id)"
            />
            <oxd-icon-button
              icon-name="play"
              :title="$t('payroll.process_this_period')"
              @click="startProcessing(row)"
            />
          </template>
        </oxd-table>
      </section>

      <oxd-divider />

      <!-- Payroll Processing Interface -->
      <section v-if="selectedPeriod" class="orangehrm-section">
        <oxd-text tag="h6" class="orangehrm-sub-title">
          {{ $t('payroll.payroll_processing') }}
        </oxd-text>

        <oxd-text
          >{{ $t('payroll.processing_for') }}:
          {{ selectedPeriod.name }}</oxd-text
        >
        <oxd-button
          :disabled="isProcessing"
          icon-name="refresh"
          :label="
            isProcessing
              ? $t('payroll.processing')
              : $t('payroll.run_calculation')
          "
          @click="runPayroll"
        />
        <payroll-processing-progress v-if="isProcessing" />
      </section>

      <oxd-divider />

      <!-- Payroll Review -->
      <section v-if="processedData.length > 0" class="orangehrm-section">
        <oxd-text tag="h6" class="orangehrm-sub-title">
          {{ $t('payroll.review_calculated_payroll') }}
        </oxd-text>
        <payroll-review-table :data="processedData" />
        <oxd-button
          display-type="secondary"
          :label="$t('payroll.finalize_and_generate')"
          icon-name="file"
          @click="generatePayslips"
        />
      </section>
    </div>

    <payroll-period-modal
      v-if="showPeriodModal"
      @close="showPeriodModal = false"
      @save="addNewPeriod"
    />
  </div>
</template>

<script lang="ts">
import {onMounted, ref} from 'vue';
import PayrollPeriodModal from '../components/PayrollPeriodModal.vue';
import PayrollReviewTable from '../components/PayrollReviewTable.vue';
import PayrollProcessingProgress from '../components/PayrollProcessingProgress.vue';
import {usePayrollProcessing} from '../util/composable/usePayrollProcessing';

export default {
  name: 'ProcessPayroll',
  components: {
    PayrollPeriodModal,
    PayrollReviewTable,
    PayrollProcessingProgress,
  },
  setup() {
    const {
      payrollPeriods,
      selectedPeriod,
      payrollRecords,
      isLoading,
      error,
      successMessage,
      loadPayrollPeriods,
      createPayrollPeriod,
      selectPeriod,
      processPayroll,
      reviewPayroll,
      finalizePayroll,
      generateBulkPayslips,
    } = usePayrollProcessing();

    const showPeriodModal = ref(false);

    const columns = [
      {name: 'name', label: 'Period Name'},
      {name: 'start_date', label: 'Start Date'},
      {name: 'end_date', label: 'End Date'},
      {name: 'status', label: 'Status'},
      {name: 'actions', label: 'Actions', sortable: false},
    ];

    onMounted(loadPayrollPeriods);

    return {
      columns,
      payrollPeriods,
      selectedPeriod,
      payrollRecords,
      isLoading,
      error,
      showPeriodModal,
      createPayrollPeriod,
      selectPeriod,
      processPayroll,
      reviewPayroll,
      finalizePayroll,
      generateBulkPayslips,
    };
  },
};
</script>

<style lang="scss" scoped>
.orangehrm-section {
  margin-bottom: 2rem;
}
</style>
