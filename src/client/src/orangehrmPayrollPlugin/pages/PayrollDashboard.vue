<template>
  <div class="orangehrm-background-container">
    <!-- === TOP HEADER: Current Payroll Period === -->
    <div class="orangehrm-header-section orangehrm-card-container">
      <div class="header-left">
        <oxd-text tag="h6" class="orangehrm-main-title">
          {{ $t('payroll.current_period') }}
        </oxd-text>
        <div v-if="activePeriod" class="period-info">
          <p>
            <strong>{{ activePeriod.name }}</strong>
          </p>
          <p>
            {{ $t('payroll.period') }}: {{ activePeriod.start_date }} -
            {{ activePeriod.end_date }}
          </p>
          <p>
            {{ $t('general.status') }}:
            <span :class="['status-badge', activePeriod.status.toLowerCase()]">
              {{ activePeriod.status }}
            </span>
          </p>
        </div>
        <div v-else>
          <p>{{ $t('payroll.no_active_period') }}</p>
        </div>
      </div>

      <div class="header-right">
        <oxd-button
          v-if="$can.create('payroll_period')"
          display-type="secondary"
          icon-name="plus"
          :label="$t('payroll.create_payroll_period')"
          @click="onCreatePayrollPeriod"
        />
      </div>
    </div>

    <!-- === KPI CARDS === -->
    <div class="orangehrm-dashboard-cards orangehrm-grid-4 orangehrm-gap-20">
      <oxd-card>
        <oxd-text tag="h6">{{ $t('payroll.total_employees') }}</oxd-text>
        <oxd-text tag="p" class="kpi-value">{{ kpis.totalEmployees }}</oxd-text>
      </oxd-card>
      <oxd-card>
        <oxd-text tag="h6">{{ $t('payroll.total_amount') }}</oxd-text>
        <oxd-text tag="p" class="kpi-value">{{
          formatCurrency(kpis.totalAmount)
        }}</oxd-text>
      </oxd-card>
      <oxd-card>
        <oxd-text tag="h6">{{ $t('payroll.pending_approvals') }}</oxd-text>
        <oxd-text tag="p" class="kpi-value">{{
          kpis.pendingApprovals
        }}</oxd-text>
      </oxd-card>
      <oxd-card>
        <oxd-text tag="h6">{{ $t('payroll.last_run_date') }}</oxd-text>
        <oxd-text tag="p" class="kpi-value">{{
          kpis.lastRunDate || '-'
        }}</oxd-text>
      </oxd-card>
    </div>

    <!-- === RECENT ACTIVITY / ALERTS === -->
    <div class="orangehrm-card-container orangehrm-margin-top">
      <oxd-text tag="h6" class="orangehrm-main-title">
        {{ $t('payroll.recent_activity') }}
      </oxd-text>
      <ul class="activity-list">
        <li v-for="(alert, index) in alerts" :key="index" class="alert-item">
          <oxd-icon name="alert-triangle" class="alert-icon" />
          <span>{{ alert.message }}</span>
        </li>
      </ul>
      <p v-if="alerts.length === 0" class="no-alerts">
        {{ $t('payroll.no_alerts') }}
      </p>
    </div>

    <!-- === RECENT PAYROLL PERIODS === -->
    <div class="orangehrm-card-container orangehrm-margin-top">
      <oxd-text tag="h6" class="orangehrm-main-title">
        {{ $t('payroll.recent_periods') }}
      </oxd-text>

      <oxd-card-table
        :headers="headers"
        :items="recentPeriods"
        :clickable="true"
        @click="onOpenPeriod"
      />
    </div>
  </div>
</template>

<script setup>
import {onMounted, ref} from 'vue';
import {APIService} from '@/core/util/services/api.service';
import {navigate} from '@/core/util/helper/navigation';
import usei18n from '@/core/util/composable/usei18n';

const {$t} = usei18n();
const http = new APIService(
  window.appGlobal.baseUrl,
  '/api/v2/payroll/dashboard',
);

const activePeriod = ref(null);
const kpis = ref({
  totalEmployees: 0,
  totalAmount: 0,
  pendingApprovals: 0,
  lastRunDate: '',
});
const alerts = ref([]);
const recentPeriods = ref([]);

onMounted(async () => {
  const data = await http.getAll();
  activePeriod.value = data?.data?.activePeriod;
  kpis.value = data?.data?.kpis;
  alerts.value = data?.data?.alerts;
  recentPeriods.value = data?.data?.recentPeriods;
});

const headers = [
  {name: 'name', title: $t('payroll.name'), style: {flex: 1}},
  {name: 'period', title: $t('payroll.period'), style: {flex: 1}},
  {name: 'status', title: $t('general.status'), style: {flex: 1}},
  {name: 'totalAmount', title: $t('payroll.total_amount'), style: {flex: 1}},
];

function onCreatePayrollPeriod() {
  navigate('/payroll/create-period');
}

function onOpenPeriod(row) {
  navigate(`/payroll/period/${row.id}`);
}

function formatCurrency(value) {
  if (!value) return '-';
  return new Intl.NumberFormat('en-ZA', {
    style: 'currency',
    currency: 'BWP',
  }).format(value);
}
</script>

<style scoped>
.orangehrm-header-section {
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.period-info p {
  margin: 0;
  font-size: 0.9rem;
}
.status-badge {
  padding: 2px 6px;
  border-radius: 4px;
  color: white;
  font-size: 0.8rem;
}
.status-badge.draft {
  background: #9e9e9e;
}
.status-badge.open {
  background: #0288d1;
}
.status-badge.processed {
  background: #ff9800;
}
.status-badge.approved {
  background: #4caf50;
}
.kpi-value {
  font-size: 1.4rem;
  font-weight: 600;
}
.activity-list {
  list-style: none;
  padding: 0;
  margin: 0;
}
.alert-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 6px;
}
.alert-icon {
  color: #ff9800;
}
.no-alerts {
  color: #9e9e9e;
  font-style: italic;
}

.orangehrm-dashboard-cards {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 1rem;
  margin-top: 1rem;
}

.orangehrm-card-container {
  background: var(--oxd-background-card-default);
  border-radius: var(--oxd-border-radius-md);
  padding: 1rem;
  box-shadow: var(--oxd-shadow-xs);
}

.orangehrm-main-title {
  font-weight: 600;
  margin-bottom: 0.5rem;
}
</style>
