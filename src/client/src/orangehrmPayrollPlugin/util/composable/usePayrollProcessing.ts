import {ref, computed} from 'vue';
import {PayrollPeriod, PayrollRecord} from '@/orangehrmPayrollPlugin/types';
import axios from 'axios'; // adjust if you have a different axios instance

export function usePayrollProcessing() {
  const payrollPeriods = ref<PayrollPeriod[]>([]);
  const selectedPeriod = ref<PayrollPeriod | null>(null);
  const payrollRecords = ref<PayrollRecord[]>([]);
  const isLoading = ref(false);
  const error = ref<string | null>(null);
  const successMessage = ref<string | null>(null);

  const loadPayrollPeriods = async () => {
    try {
      isLoading.value = true;
      const {data} = await axios.get('/api/payroll/periods');
      payrollPeriods.value = data;
    } catch (err: any) {
      error.value = err.message || 'Failed to load payroll periods';
    } finally {
      isLoading.value = false;
    }
  };

  const createPayrollPeriod = async (
    name: string,
    startDate: string,
    endDate: string,
  ) => {
    try {
      isLoading.value = true;
      const {data} = await axios.post('/api/payroll/periods', {
        name,
        startDate,
        endDate,
      });
      payrollPeriods.value.push(data);
      successMessage.value = 'Payroll period created successfully';
    } catch (err: any) {
      error.value = err.message || 'Failed to create payroll period';
    } finally {
      isLoading.value = false;
    }
  };

  const selectPeriod = (periodId: number) => {
    selectedPeriod.value =
      payrollPeriods.value.find((p) => p.id === periodId) || null;
  };

  const processPayroll = async () => {
    if (!selectedPeriod.value) return;
    try {
      isLoading.value = true;
      const {data} = await axios.post(
        `/api/payroll/${selectedPeriod.value.id}/process`,
      );
      payrollRecords.value = data.records;
      successMessage.value = 'Payroll processed successfully';
    } catch (err: any) {
      error.value = err.message || 'Payroll processing failed';
    } finally {
      isLoading.value = false;
    }
  };

  const reviewPayroll = computed(() => payrollRecords.value);

  const finalizePayroll = async () => {
    if (!selectedPeriod.value) return;
    try {
      isLoading.value = true;
      await axios.post(`/api/payroll/${selectedPeriod.value.id}/finalize`);
      selectedPeriod.value.status = 'closed';
      successMessage.value = 'Payroll finalized successfully';
    } catch (err: any) {
      error.value = err.message || 'Failed to finalize payroll';
    } finally {
      isLoading.value = false;
    }
  };

  const generateBulkPayslips = async () => {
    if (!selectedPeriod.value) return;
    try {
      isLoading.value = true;
      await axios.post(
        `/api/payroll/${selectedPeriod.value.id}/generate-payslips`,
      );
      successMessage.value = 'Payslips generated for all employees';
    } catch (err: any) {
      error.value = err.message || 'Failed to generate payslips';
    } finally {
      isLoading.value = false;
    }
  };

  return {
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
  };
}
