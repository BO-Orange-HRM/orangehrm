<template>
  <oxd-modal :title="$t('payroll.add_payroll_period')" @close="handleClose">
    <template #body>
      <oxd-form class="orangehrm-form" @submit.prevent="handleSubmit">
        <oxd-input-field
          v-model="period.name"
          :label="$t('payroll.period_name')"
          :placeholder="$t('payroll.enter_period_name')"
          :error="errors.name"
          required
        />

        <oxd-date-input
          v-model="period.start_date"
          :label="$t('payroll.start_date')"
          :error="errors.start_date"
          required
        />

        <oxd-date-input
          v-model="period.end_date"
          :label="$t('payroll.end_date')"
          :error="errors.end_date"
          required
        />

        <oxd-select
          v-model="period.status"
          :label="$t('general.status')"
          :options="statusOptions"
        />
      </oxd-form>
    </template>

    <template #footer>
      <oxd-button
        display-type="secondary"
        :label="$t('general.cancel')"
        @click="handleClose"
      />
      <oxd-button
        display-type="primary"
        :label="$t('general.save')"
        icon-name="save"
        @click="handleSubmit"
      />
    </template>
  </oxd-modal>
</template>

<script lang="ts">
import {defineComponent, reactive} from 'vue';
import {PayrollPeriod} from '@/orangehrmPayrollPlugin/types';

export default defineComponent({
  name: 'PayrollPeriodModal',
  emits: ['close', 'save'],

  setup(_, {emit}) {
    const period = reactive<PayrollPeriod>({
      id: null,
      name: '',
      start_date: null,
      end_date: null,
      status: 'open',
    });

    const errors = reactive({
      name: '',
      start_date: '',
      end_date: '',
    });

    const statusOptions = [
      {label: 'Open', value: 'Open'},
      {label: 'Closed', value: 'Closed'},
      {label: 'Processed', value: 'Processed'},
    ];

    const validate = (): boolean => {
      let valid = true;
      errors.name = '';
      errors.start_date = '';
      errors.end_date = '';

      if (!period.name) {
        errors.name = 'Period name is required';
        valid = false;
      }
      if (!period.start_date) {
        errors.start_date = 'Start date is required';
        valid = false;
      }
      if (!period.end_date) {
        errors.end_date = 'End date is required';
        valid = false;
      }
      return valid;
    };

    const handleSubmit = () => {
      if (!validate()) return;
      emit('save', {...period});
      handleClose();
    };

    const handleClose = () => {
      emit('close');
    };

    return {
      period,
      errors,
      statusOptions,
      handleSubmit,
      handleClose,
    };
  },
});
</script>

<style scoped lang="scss">
.orangehrm-form {
  display: flex;
  flex-direction: column;
  gap: 1.2rem;
}
</style>
