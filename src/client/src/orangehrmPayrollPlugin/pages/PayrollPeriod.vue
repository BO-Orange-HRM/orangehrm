<template>
  <div class="orangehrm-background-container">
    <div class="orangehrm-card-container">
      <oxd-text tag="h6" class="orangehrm-main-title">
        {{ $t('Create Payroll Period') }}
      </oxd-text>

      <oxd-divider />

      <oxd-form @submit.prevent="savePayrollPeriod">
        <oxd-input-field
          v-model="form.startDate"
          label="Start Date"
          type="date"
          required
        />
        <oxd-input-field
          v-model="form.endDate"
          label="End Date"
          type="date"
          required
        />
        <oxd-input-field
          v-model="form.paymentDate"
          label="Payment Date"
          type="date"
          required
        />
        <oxd-select
          v-model="form.frequency"
          label="Frequency"
          :options="frequencies"
        />

        <div class="oxd-form-actions">
          <oxd-button
            type="submit"
            label="Save"
            class="oxd-button--secondary"
          />
        </div>
      </oxd-form>

      <oxd-divider />
      <oxd-table :columns="columns" :data="periods" />
    </div>
  </div>
</template>

<script setup>
import {ref, onMounted} from 'vue';
import {APIService} from '@/core/util/services/api.service';

const api = new APIService(window.appGlobal.baseUrl, '/api/v2/payroll/periods');

const form = ref({
  startDate: '',
  endDate: '',
  paymentDate: '',
  frequency: 'Monthly',
});

const frequencies = ['Monthly', 'Bi-Weekly', 'Weekly'];

const periods = ref([]);
const columns = [
  {key: 'startDate', label: 'Start Date'},
  {key: 'endDate', label: 'End Date'},
  {key: 'paymentDate', label: 'Payment Date'},
  {key: 'frequency', label: 'Frequency'},
  {key: 'status', label: 'Status'},
];

async function loadPeriods() {
  const {data} = await api.getAll();
  periods.value = data?.data || [];
}

async function savePayrollPeriod() {
  await api.create(form.value);
  await loadPeriods();
}

onMounted(loadPeriods);
</script>
