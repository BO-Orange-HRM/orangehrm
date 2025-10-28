<template>
  <div class="orangehrm-background-container">
    <!-- Payroll Period Header -->
    <div class="orangehrm-header-container orangehrm-card-container">
      <oxd-text tag="h5">{{ $t('payroll.pay_period_details') }}</oxd-text>

      <div class="orangehrm-header-info">
        <oxd-grid :cols="3" class="orangehrm-full-width-grid">
          <oxd-grid-item>
            <oxd-text class="orangehrm-label">{{
              $t('payroll.period_name')
            }}</oxd-text>
            <oxd-text>{{ payrollPeriod?.name }}</oxd-text>
          </oxd-grid-item>

          <oxd-grid-item>
            <oxd-text class="orangehrm-label">{{
              $t('payroll.date_range')
            }}</oxd-text>
            <oxd-text>
              {{ payrollPeriod?.startDate }} → {{ payrollPeriod?.endDate }}
            </oxd-text>
          </oxd-grid-item>

          <oxd-grid-item>
            <oxd-text class="orangehrm-label">{{
              $t('payroll.payment_date')
            }}</oxd-text>
            <oxd-text>{{ payrollPeriod?.paymentDate }}</oxd-text>
          </oxd-grid-item>
        </oxd-grid>

        <br />

        <oxd-grid :cols="3" class="orangehrm-full-width-grid">
          <oxd-grid-item>
            <oxd-text class="orangehrm-label">{{
              $t('payroll.frequency')
            }}</oxd-text>
            <oxd-text>{{ payrollPeriod?.frequency }}</oxd-text>
          </oxd-grid-item>
          <oxd-grid-item>
            <oxd-text class="orangehrm-label">{{
              $t('payroll.status')
            }}</oxd-text>
            <oxd-badge :type="statusType(payrollPeriod?.status)">
              {{ payrollPeriod?.status }}
            </oxd-badge>
          </oxd-grid-item>
        </oxd-grid>
      </div>
    </div>

    <br />

    <!-- Overview Summary -->
    <div class="orangehrm-paper-container">
      <oxd-text tag="h6">{{ $t('payroll.overview_summary') }}</oxd-text>
      <oxd-divider />

      <div class="orangehrm-metric-grid">
        <oxd-card
          v-for="metric in overviewMetrics"
          :key="metric.label"
          class="orangehrm-dashboard-card"
        >
          <oxd-text tag="p" class="orangehrm-card-title">{{
            $t(metric.label)
          }}</oxd-text>
          <oxd-text tag="h5">{{ metric.value }}</oxd-text>
        </oxd-card>
      </div>
    </div>

    <br />

    <!-- Employee Breakdown Table -->
    <div class="orangehrm-paper-container">
      <oxd-text tag="h6">{{ $t('payroll.employee_breakdown') }}</oxd-text>
      <oxd-divider />

      <oxd-card-table
        v-model:order="sortDefinition"
        :headers="headers"
        :items="employees"
        :loading="isLoading"
        row-decorator="oxd-table-decorator-card"
      />
    </div>

    <br />

    <!-- Actions -->
    <div class="orangehrm-paper-container orangehrm-action-container">
      <oxd-button
        v-if="payrollPeriod?.status === 'Open'"
        display-type="secondary"
        :label="$t('payroll.close_period')"
        @click="closePeriod"
      />
      <oxd-button
        v-if="payrollPeriod?.status === 'Closed'"
        display-type="secondary"
        :label="$t('payroll.generate_payslips')"
        @click="generatePayslips"
      />
    </div>

    <br />

    <!-- Audit Logs -->
    <div class="orangehrm-paper-container">
      <oxd-text tag="h6">{{ $t('payroll.audit_logs') }}</oxd-text>
      <oxd-divider />

      <div v-if="auditLogs.length === 0">
        <oxd-text>{{ $t('general.no_records_found') }}</oxd-text>
      </div>
      <ul v-else class="orangehrm-audit-list">
        <li v-for="log in auditLogs" :key="log.id">
          <oxd-text tag="p">{{ log.message }}</oxd-text>
          <oxd-text tag="span" class="orangehrm-muted">{{
            log.timestamp
          }}</oxd-text>
        </li>
      </ul>
    </div>
  </div>
</template>

<script>
import {ref, onMounted} from 'vue';
import {APIService} from '@ohrm/core/util/services/api.service';
import useSort from '@ohrm/core/util/composable/useSort';
import {navigate} from '@ohrm/core/util/helper/navigation';

export default {
  name: 'PayrollPeriodView',
  setup() {
    const http = new APIService(
      window.appGlobal.baseUrl,
      '/api/v2/payroll/periods',
    );
    const {sortDefinition} = useSort();

    const payrollPeriod = ref(null);
    const overviewMetrics = ref([]);
    const employees = ref([]);
    const auditLogs = ref([]);
    const isLoading = ref(false);

    const id = window.location.pathname.split('/').pop(); // expects /payroll/periods/view/{id}

    const loadPeriod = async () => {
      isLoading.value = true;
      const {data} = await http.get(id);
      payrollPeriod.value = data?.period;
      overviewMetrics.value = data?.overview || [];
      employees.value = data?.employees || [];
      auditLogs.value = data?.auditLogs || [];
      isLoading.value = false;
    };

    const statusType = (status) => {
      switch (status) {
        case 'Closed':
          return 'success';
        case 'Open':
          return 'warning';
        default:
          return 'info';
      }
    };

    const closePeriod = async () => {
      await http.request('POST', `/${id}/close`);
      await loadPeriod();
    };

    const generatePayslips = async () => {
      await http.request('POST', `/${id}/payslips`);
      navigate(`/payroll/payslips/${id}`);
    };

    const headers = [
      {name: 'employeeName', title: 'Employee', style: {flex: '25%'}},
      {name: 'grossPay', title: 'Gross Pay', style: {flex: '15%'}},
      {name: 'deductions', title: 'Deductions', style: {flex: '15%'}},
      {name: 'netPay', title: 'Net Pay', style: {flex: '15%'}},
      {name: 'department', title: 'Department', style: {flex: '20%'}},
      {name: 'status', title: 'Status', style: {flex: '10%'}},
    ];

    onMounted(loadPeriod);

    return {
      payrollPeriod,
      overviewMetrics,
      employees,
      auditLogs,
      headers,
      isLoading,
      statusType,
      closePeriod,
      generatePayslips,
      sortDefinition,
    };
  },
};
</script>

<style scoped>
.orangehrm-header-info {
  margin-top: 1rem;
}
.orangehrm-metric-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
  gap: 1rem;
}
.orangehrm-dashboard-card {
  text-align: center;
  padding: 1rem;
}
.orangehrm-action-container {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
}
.orangehrm-audit-list {
  list-style: none;
  padding: 0;
}
.orangehrm-audit-list li {
  margin-bottom: 0.75rem;
}
</style>
