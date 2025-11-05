<template>
  <div class="orangehrm-background-container">
    <!-- Filter Section -->
    <oxd-table-filter :filter-title="$t('payroll.employee_overview')">
      <oxd-form @submit-valid="filterItems">
        <oxd-form-row>
          <oxd-grid :cols="2" class="orangehrm-full-width-grid">
            <oxd-grid-item>
              <oxd-input-field
                v-model="filters.employeeName"
                :label="$t('payroll.employee_name')"
                :placeholder="$t('payroll.enter_employee_name')"
              />
            </oxd-grid-item>
            <oxd-grid-item>
              <oxd-select
                v-model="filters.department"
                :label="$t('payroll.department')"
                :options="departments"
                :placeholder="$t('payroll.select_department')"
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

    <br />

    <!-- Employee Payroll Table -->
    <div class="orangehrm-paper-container">
      <div class="orangehrm-header-container">
        <oxd-button
          :label="$t('general.add')"
          icon-name="plus"
          display-type="secondary"
          @click="onClickAdd"
        />
      </div>

      <table-header
        :selected="checkedItems.length"
        :total="total"
        :loading="isLoading"
        @delete="onClickDeleteSelected"
      ></table-header>

      <div class="orangehrm-container">
        <oxd-card-table
          v-model:selected="checkedItems"
          v-model:order="sortDefinition"
          :headers="headers"
          :items="items?.data"
          :selectable="true"
          :clickable="false"
          :loading="isLoading"
          row-decorator="oxd-table-decorator-card"
        />
      </div>

      <div class="orangehrm-bottom-container">
        <oxd-pagination
          v-if="showPaginator"
          v-model:current="currentPage"
          :length="pages"
        />
      </div>
    </div>

    <delete-confirmation ref="deleteDialog"></delete-confirmation>
  </div>
</template>

<script>
import {ref, computed} from 'vue';
import {APIService} from '@/core/util/services/api.service';
import useSort from '@ohrm/core/util/composable/useSort';
import usePaginate from '@ohrm/core/util/composable/usePaginate';
import {navigate} from '@ohrm/core/util/helper/navigation';
import DeleteConfirmationDialog from '@ohrm/components/dialogs/DeleteConfirmationDialog';

const defaultFilters = {
  employeeName: '',
  department: null,
};

const defaultSortOrder = {
  'employee.name': 'ASC',
};

export default {
  name: 'EmployeePayrollOverview',

  components: {
    'delete-confirmation': DeleteConfirmationDialog,
  },

  setup() {
    const {sortDefinition, sortField, sortOrder, onSort} = useSort({
      sortDefinition: defaultSortOrder,
    });

    const filters = ref({...defaultFilters});
    const departments = ref([
      {id: 'hr', label: 'HR'},
      {id: 'finance', label: 'Finance'},
      {id: 'it', label: 'IT'},
      {id: 'operations', label: 'Operations'},
    ]);

    const serializedFilters = computed(() => ({
      employeeName: filters.value.employeeName,
      department: filters.value.department?.id,
      sortField: sortField.value,
      sortOrder: sortOrder.value,
    }));

    const http = new APIService(
      window.appGlobal.baseUrl,
      '/api/v2/payroll/employees',
    );

    const {
      showPaginator,
      currentPage,
      total,
      pages,
      response,
      isLoading,
      execQuery,
    } = usePaginate(http, {query: serializedFilters});

    onSort(execQuery);

    return {
      http,
      filters,
      departments,
      sortDefinition,
      showPaginator,
      currentPage,
      total,
      pages,
      isLoading,
      execQuery,
      items: response,
    };
  },

  data() {
    return {
      headers: [
        {
          name: 'name',
          slot: 'title',
          title: this.$t('payroll.employee_name'),
          style: {flex: '25%'},
          sortField: 'employee.name',
        },
        {
          name: 'jobCategory',
          title: this.$t('payroll.job_category'),
          style: {flex: '15%'},
        },
        {
          name: 'subUnit',
          title: this.$t('payroll.sub_unit'),
          style: {flex: '15%'},
        },
        {
          name: 'employmentStatus',
          title: this.$t('payroll.employment_status'),
          style: {flex: '15%'},
        },
        {
          name: 'components',
          title: this.$t('payroll.components'),
          style: {flex: '15%'},
          sortField: 'employee.components',
        },
        {
          name: 'grossSalary',
          title: this.$t('payroll.gross_salary'),
          style: {flex: '15%'},
          sortField: 'employee.grossSalary',
        },
        {
          name: 'actions',
          title: this.$t('general.actions'),
          slot: 'action',
          style: {flex: '15%'},
          cellType: 'oxd-table-cell-actions',
          cellConfig: {
            view: {onClick: this.onClickView, props: {name: 'file-text-fill'}},
            edit: {onClick: this.onClickEdit, props: {name: 'pencil-fill'}},
          },
        },
      ],
      checkedItems: [],
    };
  },

  methods: {
    onClickAdd() {
      navigate('/pim/addEmployee');
    },
    onClickEdit(item) {
      navigate('/pim/viewSalaryList/empNumber/{id}', {id: item.empNumber});
    },
    onClickView(item) {
      navigate('/payroll/employees/view/{id}', {id: item.empNumber});
    },
    async deleteItems(ids) {
      if (Array.isArray(ids)) {
        this.isLoading = true;
        await this.http.deleteAll({ids});
        this.$toast.deleteSuccess();
        this.isLoading = false;
        await this.execQuery();
      }
    },
    async filterItems() {
      await this.execQuery();
    },
    onClickReset() {
      this.filters = {...defaultFilters};
      this.filterItems();
    },
  },
};
</script>
