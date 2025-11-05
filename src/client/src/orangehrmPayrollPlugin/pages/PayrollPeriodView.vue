<template>
  <div class="orangehrm-background-container">
    <!-- Header -->
    <div class="orangehrm-header-container orangehrm-card-container">
      <oxd-text tag="h5" class="orangehrm-title">
        {{ $t('Pay Period Details') }}
      </oxd-text>

      <div class="orangehrm-header-info">
        <oxd-grid :cols="3" class="orangehrm-full-width-grid">
          <oxd-grid-item>
            <oxd-text class="orangehrm-label"
              >{{ $t('Period Name') }}
            </oxd-text>
            <oxd-text class="orangehrm-value"
              >{{ payrollData.period.name }}
            </oxd-text>
          </oxd-grid-item>

          <oxd-grid-item>
            <oxd-text class="orangehrm-label"
              >{{ $t('Dates Range') }}
            </oxd-text>
            <oxd-text class="orangehrm-value">
              {{ formatDate(payrollData.period.startDate) }} →
              {{ formatDate(payrollData.period.endDate) }}
            </oxd-text>
          </oxd-grid-item>

          <oxd-grid-item>
            <oxd-text class="orangehrm-label"
              >{{ $t('Payment Date') }}
            </oxd-text>
            <oxd-text class="orangehrm-value">
              {{ formatDate(payrollData.period.paymentDate) || '-' }}
            </oxd-text>
          </oxd-grid-item>
        </oxd-grid>

        <oxd-grid :cols="3" class="orangehrm-full-width-grid mt-3">
          <oxd-grid-item>
            <oxd-text class="orangehrm-label"
              >{{ $t('Payment Frequency') }}
            </oxd-text>
            <oxd-text class="orangehrm-value"
              >{{ payrollData.period.frequency || '-' }}
            </oxd-text>
          </oxd-grid-item>
          <oxd-grid-item>
            <oxd-text class="orangehrm-label"
              >{{ $t('general.status') }}
            </oxd-text>
            <oxd-badge :type="statusType(payrollData.period.status)">
              {{ payrollData.period.status }}
            </oxd-badge>
          </oxd-grid-item>
        </oxd-grid>
      </div>
    </div>

    <br />

    <!-- Overview Summary -->
    <div class="orangehrm-paper-container payroll-overview">
      <div class="overview-header">
        <oxd-text tag="h6">{{ $t('Overview') }}</oxd-text>
      </div>
      <oxd-divider />

      <div class="overview-grid">
        <div
          v-for="metric in payrollData.overview"
          :key="metric.label"
          class="overview-card"
          :class="metricColor(metric.label)"
        >
          <div class="metric-icon">
            <i :class="metricIcon(metric.label)"></i>
          </div>
          <div class="metric-info">
            <oxd-text tag="p" class="metric-label"
              >{{ $t(metric.label) }}
            </oxd-text>
            <oxd-text tag="h4" class="metric-value"
              >{{ metric.value }}
            </oxd-text>
          </div>
        </div>
      </div>
    </div>

    <br />

    <!-- Employee Breakdown Table -->
    <div class="orangehrm-paper-container payroll-overview">
      <div class="orangehrm-action-container">
        <oxd-button
          v-if="payrollData.period.status === 'Open'"
          display-type="secondary"
          :label="$t('payroll.close_period')"
          @click="closePeriod"
        />
        <oxd-button
          v-else-if="payrollData.period.status === 'Closed'"
          display-type="secondary"
          :label="$t('payroll.generate_payslips')"
          @click="generatePayslips"
        />
        <oxd-button
          v-else-if="payrollData.period.status === 'Draft'"
          display-type="secondary"
          :label="$t('payroll.start_processing')"
          @click="generatePayslips"
        />
      </div>
      <oxd-divider />
      <div class="orangehrm-container">
        <oxd-card-table
          v-model:order="sortDefinition"
          :headers="headers"
          :items="sortedEmployees"
          :loading="false"
          row-decorator="oxd-table-decorator-card"
        />
      </div>
    </div>

    <br />

    <!-- Audit Logs -->
    <div class="orangehrm-paper-container">
      <oxd-text tag="h6" class="orangehrm-section-title">
        {{ $t('payroll.audit_logs') }}
      </oxd-text>
      <oxd-divider />

      <div v-if="!payrollData.auditLogs.length">
        <oxd-text class="orangehrm-muted"
          >{{ $t('general.no_records_found') }}
        </oxd-text>
      </div>

      <ul v-else class="orangehrm-audit-list">
        <li v-for="log in payrollData.auditLogs" :key="log.id">
          <oxd-text tag="p">{{ log.message }}</oxd-text>
          <oxd-text tag="span" class="orangehrm-muted"
            >{{ log.timestamp }}
          </oxd-text>
        </li>
      </ul>
    </div>
  </div>
</template>

<script>
import useSort from '@ohrm/core/util/composable/useSort';
import {APIService} from '@/core/util/services/api.service';
import html2canvas from 'html2canvas';
import jsPDF from 'jspdf';

const defaultSortOrder = {
  employeeName: 'ASC',
  grossPay: 'DEFAULT',
  netPay: 'DEFAULT',
  status: 'DEFAULT',
};

const http = new APIService(
  window.appGlobal.baseUrl,
  '/api/v2/payroll/dashboard',
);

export default {
  name: 'PayrollPeriodView',
  props: {
    payrollData: {type: Object, required: true},
    pPId: {type: Number, required: true},
  },

  setup(props) {
    const {sortDefinition, sortByField} = useSort({
      sortDefinition: defaultSortOrder,
    });

    const closePeriod = () =>
      (window.location.href = `/payroll/close/${props.pPId}`);
    const generatePayslips = () =>
      (window.location.href = `/payroll/payslips/${props.pPId}`);

    const formatDate = (date) =>
      date
        ? new Date(date).toLocaleDateString(undefined, {
            year: 'numeric',
            month: 'short',
            day: '2-digit',
          })
        : '-';

    return {
      sortDefinition,
      sortByField,
      closePeriod,
      generatePayslips,
      formatDate,
    };
  },

  data() {
    async function onGeneratePayslip(object) {
      try {
        // Validate required fields and provide defaults
        const netPay = object.netPay ?? object.netAmount ?? 0;
        const grossAmount = object.grossAmount ?? object.grossPay ?? 0;
        const basicSalary = object.basicSalary ?? 0;
        const allowances = object.allowances ?? 0;
        const totalDeductions =
          object.totalDeductions ?? object.deductions ?? 0;
        const deductionsList = object.deductionsList ?? [];

        // Create hidden container
        const container = document.createElement('div');
        container.style.position = 'absolute';
        container.style.left = '-9999px';
        container.style.width = '800px';
        container.innerHTML = `
      <div style="font-family: DejaVu Sans, sans-serif; color: #333; padding: 20px; border: 1px solid #ddd; border-radius: 10px;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
          <div>
            <img src="${
              object.companyLogo || 'web/images/logo.png'
            }" alt="Company Logo" style="height:50px;">
          </div>
          <div style="text-align:right;">
            <h2 style="margin:0; color:#004aad;">${
              object.companyName || 'Blackoak Consulting Payroll'
            }</h2>
            <p style="margin:0; font-size:12px;">${
              object.companyAddress || 'Plot 1234, Gaborone, Botswana'
            }</p>
          </div>
        </div>

        <h3 style="text-align:center; color:#004aad; margin-bottom:5px;">Employee Payslip</h3>
        <p style="text-align:center; font-size:12px; margin-top:0;">Payroll Period: <strong>${
          object.period || 'N/A'
        }</strong></p>

        <h4 style="background-color:#f5f7fa; padding:5px 10px; border-left:4px solid #004aad;">Employee Information</h4>
        <table style="width:100%; border-collapse:collapse; margin-bottom:15px;">
          <tr><th style="text-align:left; padding:5px;">Employee Name</th><td style="padding:5px;">${
            object.employeeName || 'N/A'
          }</td></tr>
          <tr><th style="text-align:left; padding:5px;">Employee ID</th><td style="padding:5px;">${
            object.employeeId || 'N/A'
          }</td></tr>
          <tr><th style="text-align:left; padding:5px;">Department</th><td style="padding:5px;">${
            object.department || '-'
          }</td></tr>
          <tr><th style="text-align:left; padding:5px;">Designation</th><td style="padding:5px;">${
            object.designation || '-'
          }</td></tr>
        </table>

        <h4 style="background-color:#f5f7fa; padding:5px 10px; border-left:4px solid #004aad;">Earnings</h4>
        <table style="width:100%; border-collapse:collapse; margin-bottom:15px;">
          <tr><th style="text-align:left; padding:5px;">Description</th><th style="text-align:right; padding:5px;">Amount (${
            object.currency || 'BWP'
          })</th></tr>
          <tr><td style="padding:5px;">Basic Salary</td><td style="padding:5px; text-align:right;">${basicSalary.toFixed(
            2,
          )}</td></tr>
          <tr><td style="padding:5px;">Allowances</td><td style="padding:5px; text-align:right;">${allowances.toFixed(
            2,
          )}</td></tr>
          <tr><th style="padding:5px; text-align:left;">Gross Pay</th><th style="padding:5px; text-align:right;">${grossAmount.toFixed(
            2,
          )}</th></tr>
        </table>

        <h4 style="background-color:#f5f7fa; padding:5px 10px; border-left:4px solid #004aad;">Deductions</h4>
        <table style="width:100%; border-collapse:collapse; margin-bottom:15px;">
          <tr><th style="text-align:left; padding:5px;">Description</th><th style="text-align:right; padding:5px;">Amount (${
            object.currency || 'BWP'
          })</th></tr>
          ${deductionsList
            .map(
              (d) => `
            <tr><td style="padding:5px;">${
              d.name || 'Deduction'
            }</td><td style="padding:5px; text-align:right;">${(
                d.amount ?? 0
              ).toFixed(2)}</td></tr>
          `,
            )
            .join('')}
          <tr><th style="padding:5px; text-align:left;">Total Deductions</th><th style="padding:5px; text-align:right;">${totalDeductions.toFixed(
            2,
          )}</th></tr>
        </table>

        <table style="width:100%; margin-top:15px;">
          <tr>
            <td style="text-align:right; font-weight:bold; font-size:16px; color:#004aad;">Net Pay: ${netPay.toFixed(
              2,
            )} ${object.currency || 'BWP'}</td>
          </tr>
        </table>

        <div style="text-align:center; font-size:11px; color:#777; margin-top:20px;">
          Generated on ${new Date().toLocaleString()} | Confidential Payroll Document<br>
          &copy; ${
            object.companyName || 'Blackoak Consulting Payroll'
          } — Powered by OrangeHRM Payroll
        </div>
      </div>
    `;
        document.body.appendChild(container);

        // Convert to canvas
        const canvas = await html2canvas(container, {scale: 2});
        const imgData = canvas.toDataURL('image/png');

        // Create PDF
        const pdf = new jsPDF('p', 'mm', 'a4');
        const pdfWidth = pdf.internal.pageSize.getWidth();
        const pdfHeight = (canvas.height * pdfWidth) / canvas.width;

        pdf.addImage(imgData, 'PNG', 0, 0, pdfWidth, pdfHeight);
        pdf.save(`Payslip_${object.employeeName || 'Employee'}.pdf`);

        // Clean up
        document.body.removeChild(container);
      } catch (error) {
        console.error('Error generating PDF:', error);
        alert('Failed to generate PDF. Please try again.');
      }
    }

    function onPayEmployees(item) {
      http
        .request({
          url: `/payroll/pay-employee/${item.id}`,
          method: 'POST',
          params: {
            employeeId: item.id,
          },
        })
        .then(() => {
          this.$toast.updateSuccess();
        })
        .catch((error) => {
          this.$toast.error(error.response.data.message || error.message);
        });
    }
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
        {name: 'deductions', title: 'Deductions', style: {flex: '15%'}},
        {
          name: 'netPay',
          title: 'Net Pay',
          sortField: 'netPay',
          style: {flex: '15%'},
        },
        {name: 'department', title: 'Department', style: {flex: '20%'}},
        {
          name: 'status',
          title: 'Status',
          sortField: 'status',
          style: {flex: '10%'},
        },
        {
          name: 'actions',
          title: 'Actions',
          slot: 'action',
          style: {flex: '15%'},
          cellType: 'oxd-table-cell-actions',
          cellConfig: {},
        },
        {
          name: 'actions',
          title: 'Actions',
          slot: 'action',
          style: {flex: '15%'},
          cellType: 'oxd-table-cell-actions',
          cellConfig: {
            generatePayslip: {
              onClick: (item) => onGeneratePayslip(item),
              props: {name: 'file-plus'},
              tooltip: 'Generate Payslip',
            },
            payEmployees: {
              onClick: (item) => onPayEmployees(item),
              props: {name: 'credit-card'},
              tooltip: 'Pay Employees',
            },
          },
        },
      ],
    };
  },

  computed: {
    sortedEmployees() {
      if (!this.sortDefinition) return this.payrollData.employees;
      const [field, direction] =
        Object.entries(this.sortDefinition).find(
          ([, dir]) => dir !== 'DEFAULT',
        ) || [];
      if (!field) return this.payrollData.employees;

      return [...this.payrollData.employees].sort((a, b) => {
        if (direction === 'ASC') return a[field] > b[field] ? 1 : -1;
        if (direction === 'DESC') return a[field] < b[field] ? 1 : -1;
        return 0;
      });
    },
  },

  methods: {
    statusType(status) {
      switch (status) {
        case 'Closed':
          return 'success';
        case 'Open':
          return 'warning';
        case 'Draft':
          return 'info';
        default:
          return 'default';
      }
    },
    metricColor(label) {
      if (label.includes('total_employees')) return 'metric-blue';
      if (label.includes('gross_pay')) return 'metric-green';
      if (label.includes('deductions')) return 'metric-red';
      if (label.includes('net_pay')) return 'metric-orange';
      return '';
    },
    metricIcon(label) {
      if (label.includes('employees')) return 'ri-group-line';
      if (label.includes('gross_pay')) return 'ri-bar-chart-fill';
      if (label.includes('deductions')) return 'ri-subtract-line';
      if (label.includes('net_pay')) return 'ri-wallet-3-fill';
      return 'ri-information-line';
    },
  },
};
</script>

<style scoped>
.orangehrm-header-info {
  margin-top: 1rem;
}

.orangehrm-title {
  font-weight: 600;
}

.orangehrm-value {
  font-weight: 500;
}

.orangehrm-action-container {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
}

.orangehrm-audit-list {
  list-style: none;
  padding: 0;
  margin-top: 0.5rem;
}

.orangehrm-audit-list li {
  margin-bottom: 0.75rem;
}

.mt-3 {
  margin-top: 1rem;
}

.payroll-overview {
  padding: 1.5rem;
}

.overview-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.overview-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
  gap: 1rem;
  margin-top: 1rem;
}

.overview-card {
  display: flex;
  align-items: center;
  gap: 1rem;
  background: var(--oxd-bg-white);
  border-radius: 1rem;
  padding: 1rem 1.25rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
  transition: all 0.25s ease-in-out;
}

.overview-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
}

.metric-icon {
  font-size: 2rem;
  display: flex;
  align-items: center;
  justify-content: center;
}

.metric-info {
  display: flex;
  flex-direction: column;
}

.metric-label {
  font-size: 0.875rem;
  color: var(--oxd-text-muted);
}

.metric-value {
  font-weight: 700;
  margin-top: 0.25rem;
}

.metric-blue .metric-icon {
  color: #3b82f6;
}

.metric-green .metric-icon {
  color: #10b981;
}

.metric-red .metric-icon {
  color: #ef4444;
}

.metric-orange .metric-icon {
  color: #f97316;
}
</style>
