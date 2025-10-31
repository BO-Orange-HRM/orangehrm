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
            <oxd-text>{{ payrollData.period.name }}</oxd-text>
          </oxd-grid-item>

          <oxd-grid-item>
            <oxd-text class="orangehrm-label">{{
              $t('payroll.date_range')
            }}</oxd-text>
            <oxd-text
              >{{ payrollData.period.startDate }} →
              {{ payrollData.period.endDate }}</oxd-text
            >
          </oxd-grid-item>

          <oxd-grid-item>
            <oxd-text class="orangehrm-label">{{
              $t('payroll.payment_date')
            }}</oxd-text>
            <oxd-text>{{ payrollData.period.paymentDate }}</oxd-text>
          </oxd-grid-item>
        </oxd-grid>

        <br />

        <oxd-grid :cols="3" class="orangehrm-full-width-grid">
          <oxd-grid-item>
            <oxd-text class="orangehrm-label">{{
              $t('payroll.frequency')
            }}</oxd-text>
            <oxd-text>{{ payrollData.period.frequency }}</oxd-text>
          </oxd-grid-item>
          <oxd-grid-item>
            <oxd-text class="orangehrm-label">{{
              $t('payroll.status')
            }}</oxd-text>
            <oxd-badge :type="statusType(payrollData.period.status)">
              {{ payrollData.period.status }}
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
          v-for="metric in payrollData.overview"
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
    <!-- Actions -->
    <div class="orangehrm-paper-container orangehrm-action-container">
      <oxd-button
        v-if="payrollData.period.status === 'Open'"
        display-type="secondary"
        :label="$t('payroll.close_period')"
        @click="closePeriod"
      />
      <oxd-button
        v-if="payrollData.period.status === 'Closed'"
        display-type="secondary"
        :label="$t('payroll.generate_payslips')"
        @click="generatePayslips"
      />
      <oxd-button
        v-if="payrollData.period.status === 'Draft'"
        display-type="secondary"
        :label="$t('payroll.start_processing')"
        @click="generatePayslips"
      />
    </div>

    <br />

    <!-- Employee Breakdown Table -->
    <div class="orangehrm-paper-container">
      <oxd-text tag="h6">{{ $t('payroll.employee_breakdown') }}</oxd-text>
      <oxd-divider />

      <oxd-card-table
        v-model:order="sortDefinition"
        :headers="headers"
        :items="payrollData.employees"
        :loading="false"
        row-decorator="oxd-table-decorator-card"
      />
    </div>

    <br />

    <!-- Audit Logs -->
    <div class="orangehrm-paper-container">
      <oxd-text tag="h6">{{ $t('payroll.audit_logs') }}</oxd-text>
      <oxd-divider />

      <div v-if="payrollData.auditLogs.length === 0">
        <oxd-text>{{ $t('general.no_records_found') }}</oxd-text>
      </div>
      <ul v-else class="orangehrm-audit-list">
        <li v-for="log in payrollData.auditLogs" :key="log.id">
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
import useSort from '@ohrm/core/util/composable/useSort';

const defaultSortOrder = {
  employeeName: 'ASC',
  grossPay: 'DEFAULT',
  netPay: 'DEFAULT',
  status: 'DEFAULT',
};
export default {
  name: 'PayrollPeriodView',
  props: {
    payrollData: {
      type: Object,
      required: true,
    },
    pPId: {
      type: Number,
      required: true,
    },
  },
  setup(props) {
    const {sortDefinition} = useSort({
      sortDefinition: defaultSortOrder,
    });

    const closePeriod = () => {
      window.location.href = `/payroll/close/${props.pPId}`;
    };

    const generatePayslips = () => {
      window.location.href = `/payroll/payslips/${props.pPId}`;
    };

    return {
      sortDefinition,
      closePeriod,
      generatePayslips,
    };
  },

  data() {
    return {
      headers: [
        {
          name: 'employeeName',
          title: 'Employee',
          sortField: 'employeeName',
          style: {flex: '25%'},
        },
        {
          name: 'grossPay',
          title: 'Gross Pay',
          sortField: 'grossPay',
          style: {flex: '15%'},
        },
        {
          name: 'deductions',
          title: 'Deductions',
          style: {flex: '15%'},
        },
        {
          name: 'netPay',
          title: 'Net Pay',
          sortField: 'netPay',
          style: {flex: '15%'},
        },
        {
          name: 'department',
          title: 'Department',
          style: {flex: '20%'},
        },
        {
          name: 'status',
          title: 'Status',
          sortField: 'status',
          style: {flex: '10%'},
        },
      ],
    };
  },

  methods: {
    statusType(status) {
      switch (status) {
        case 'Closed':
          return 'success';
        case 'Open':
          return 'warning';
        default:
          return 'info';
      }
    },
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
