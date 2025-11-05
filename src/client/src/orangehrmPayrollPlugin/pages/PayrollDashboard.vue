<template>
  <div class="orangehrm-background-container">
    <div class="orangehrm-header-section orangehrm-card-container">
      <div class="header-left">
        <oxd-text tag="h6" class="orangehrm-main-title">
          {{ $t('Current Period') }}
        </oxd-text>
        <div v-if="activePeriod" class="period-info">
          <p>
            <strong>{{ activePeriod.name }}</strong>
          </p>
          <p>
            {{ $t('Period') }}: {{ activePeriod.start_date }} -
            {{ activePeriod.end_date }}
          </p>
          <p>
            {{ $t('Status') }}:
            <span :class="['status-badge', activePeriod.status.toLowerCase()]">
              {{ activePeriod.status }}
            </span>
          </p>
        </div>
        <div v-else>
          <p>{{ $t('No active period') }}</p>
        </div>
      </div>

      <div class="header-right">
        <oxd-button
          display-type="secondary"
          icon-name="plus"
          :label="$t('Create Payroll Period')"
          @click="onCreatePayrollPeriod"
        />
      </div>
    </div>

    <!-- === KPI CARDS === -->
    <div class="orangehrm-kpi-container">
      <oxd-card class="orangehrm-kpi-card">
        <oxd-text tag="p" class="orangehrm-kpi-label">
          {{ $t('Total Employees') }}
        </oxd-text>
        <oxd-text tag="h5" class="orangehrm-kpi-value">
          {{ kpis.totalEmployees }}
        </oxd-text>
      </oxd-card>

      <oxd-card class="orangehrm-kpi-card">
        <oxd-text tag="p" class="orangehrm-kpi-label">
          {{ $t('Total Amount(BPW)') }}
        </oxd-text>
        <oxd-text tag="h5" class="orangehrm-kpi-value">
          {{ formatCurrency(kpis.totalAmount) }}
        </oxd-text>
      </oxd-card>

      <oxd-card class="orangehrm-kpi-card">
        <oxd-text tag="p" class="orangehrm-kpi-label">
          {{ $t('Pending Approvals') }}
        </oxd-text>
        <oxd-text tag="h5" class="orangehrm-kpi-value orangehrm-kpi-warning">
          {{ kpis.pendingApprovals }}
        </oxd-text>
      </oxd-card>

      <oxd-card class="orangehrm-kpi-card">
        <oxd-text tag="p" class="orangehrm-kpi-label">
          {{ $t('Last Run') }}
        </oxd-text>
        <oxd-text tag="h5" class="orangehrm-kpi-value">
          {{ kpis.lastRunDate || '-' }}
        </oxd-text>
      </oxd-card>
    </div>

    <!-- === FILTER SECTION === -->
    <oxd-table-filter :filter-title="$t('Filter Payroll Periods')">
      <oxd-form @submit-valid="filterItems">
        <oxd-form-row>
          <oxd-grid :cols="3" class="orangehrm-full-width-grid">
            <oxd-grid-item>
              <oxd-input-field
                v-model="filters.periodName"
                :label="$t('Period Name')"
                :placeholder="$t('enter period name')"
              />
            </oxd-grid-item>
            <oxd-grid-item>
              <oxd-input-field
                v-model="filters.status"
                type="select"
                :label="$t('general.select_status')"
                :options="statusOptions"
              />
            </oxd-grid-item>
            <oxd-grid-item>
              <date-input
                v-model="filters.startDate"
                :label="$t('Start Date')"
              />
            </oxd-grid-item>
          </oxd-grid>
        </oxd-form-row>

        <oxd-divider />

        <oxd-form-actions>
          <oxd-button
            display-type="ghost"
            :label="$t('general.reset')"
            @click="onClickReset"
          />
          <oxd-button
            class="orangehrm-left-space"
            display-type="secondary"
            :label="$t('general.search')"
            type="submit"
          />
        </oxd-form-actions>
      </oxd-form>
    </oxd-table-filter>

    <!-- === RECENT PAYROLL PERIODS === -->
    <div class="orangehrm-paper-container orangehrm-margin-top">
      <table-header
        :selected="checkedItems.length"
        :total="total"
        :loading="isLoading"
      ></table-header>

      <div class="orangehrm-container">
        <oxd-card-table
          v-model:selected="checkedItems"
          v-model:order="sortDefinition"
          :headers="headers"
          :items="paginatedItems"
          :selectable="true"
          :clickable="true"
          :loading="isLoading"
        />
      </div>

      <div class="orangehrm-bottom-container">
        <oxd-pagination
          v-if="pages > 1"
          v-model:current="currentPage"
          :length="pages"
        />
      </div>
    </div>

    <edit-payroll
      v-if="showEditModal"
      :payroll="selectedPayroll"
      @close="closeEditModal"
    />
  </div>
</template>

<script>
import {computed, onMounted, ref} from 'vue';
import {APIService} from '@/core/util/services/api.service';
import usei18n from '@/core/util/composable/usei18n';
import useSort from '@ohrm/core/util/composable/useSort';
import {navigate} from '@ohrm/core/util/helper/navigation';
import EditPayrollModal from '@/orangehrmPayrollPlugin/components/EditPayrollModal.vue';

export default {
  name: 'PayrollDashboard',
  components: {
    'edit-payroll': EditPayrollModal,
  },

  setup() {
    const {$t} = usei18n();
    const http = new APIService(
      window.appGlobal.baseUrl,
      '/api/v2/payroll/dashboard',
    );
    const {sortDefinition, sortField, sortOrder} = useSort({
      sortDefinition: {period: 'DESC'},
    });

    const filters = ref({
      periodName: '',
      status: null,
      startDate: null,
    });

    const statusOptions = ref([
      {id: 'Draft', label: $t('payroll.draft')},
      {id: 'Approved', label: $t('payroll.approved')},
      {id: 'Processed', label: $t('payroll.processed')},
    ]);

    const activePeriod = ref(null);
    const kpis = ref({});
    const items = ref([]);
    const isLoading = ref(false);

    const currentPage = ref(1);
    const itemsPerPage = 10;

    const showEditModal = ref(false);
    const selectedPayroll = ref(null);

    const total = computed(() => items.value.length);
    const pages = computed(() => Math.ceil(total.value / itemsPerPage));

    const paginatedItems = computed(() => {
      const start = (currentPage.value - 1) * itemsPerPage;
      return items.value.slice(start, start + itemsPerPage);
    });

    onMounted(async () => {
      isLoading.value = true;
      const {data} = await http.getAll();
      activePeriod.value = data.activePeriod;
      kpis.value = data.kpis;
      items.value = data.recentPeriods || [];
      isLoading.value = false;
    });

    // === Filtering ===
    function filterItems() {
      const query = filters.value;
      currentPage.value = 1;
      items.value = items.value.filter((item) => {
        const matchName = query.periodName
          ? item.period.toLowerCase().includes(query.periodName.toLowerCase())
          : true;
        const matchStatus = query.status
          ? item.status === query.status.id
          : true;
        return matchName && matchStatus;
      });
    }

    function onClickReset() {
      filters.value = {periodName: '', status: null, startDate: null};
      onMounted();
    }

    function onOpenPeriod(row) {
      navigate(`/payroll/viewPayrollDetails/pPNumber/${row.item.id}`);
    }

    const headers = [
      {
        name: 'period_name',
        title: $t('Period Name'),
        style: {flex: 2},
        sortField: 'name',
      },
      {
        name: 'period',
        title: $t('Period Dates'),
        style: {flex: 2},
        sortField: 'period',
      },
      {
        name: 'status',
        title: $t('general.status'),
        style: {flex: 1},
      },
      {
        name: 'totalAmount',
        title: $t('Total Amount(Bwp)'),
        style: {flex: 1},
      },
      {
        name: 'actions',
        title: $t('general.actions'),
        slot: 'action',
        style: {flex: '15%'},
        cellType: 'oxd-table-cell-actions',
        cellConfig: {
          view: {
            onClick: (item) =>
              navigate(`/payroll/viewPayrollDetails/pPNumber/${item.id}`),
            props: {name: 'file-text-fill'},
          },
          edit: {
            onClick: onClickEdit,
            props: {name: 'pencil-fill'},
          },
        },
      },
    ];

    function formatCurrency(value) {
      if (!value) return '-';
      return new Intl.NumberFormat('en-ZA', {
        style: 'currency',
        currency: 'BWP',
      }).format(value);
    }

    function onClickEdit(item) {
      selectedPayroll.value = {...item};
      showEditModal.value = true;
    }

    function closeEditModal(reload) {
      showEditModal.value = false;
      if (reload) {
        location.reload();
      }
    }

    function onCreatePayrollPeriod() {
      navigate('/payroll/viewPayrollDashboard');
    }

    return {
      $t,
      activePeriod,
      kpis,
      headers,
      filters,
      statusOptions,
      currentPage,
      pages,
      total,
      paginatedItems,
      isLoading,
      sortDefinition,
      checkedItems: ref([]),
      filterItems,
      onClickReset,
      formatCurrency,
      onClickEdit,
      showEditModal,
      selectedPayroll,
      closeEditModal,
      onCreatePayrollPeriod,
      http,
    };
  },
};
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

.orangehrm-kpi-container {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1.5rem;
  margin-top: 1.5rem;
  margin-bottom: 2rem;
}

.orangehrm-kpi-card {
  text-align: center;
  padding: 1.5rem 1rem;
  border-radius: 1rem;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
  background: white;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.orangehrm-kpi-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
}

.orangehrm-kpi-label {
  font-size: 0.85rem;
  color: var(--oxd-interface-gray-color);
  margin-bottom: 0.25rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.orangehrm-kpi-value {
  font-size: 1.6rem;
  font-weight: 600;
  color: var(--oxd-interface-primary-color);
}

.orangehrm-kpi-warning {
  color: #ff9800;
}

.orangehrm-margin-top {
  margin-top: 2.2rem;
}
</style>
