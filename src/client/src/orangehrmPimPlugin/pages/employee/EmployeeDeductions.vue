<!--
/**
 * OrangeHRM is a comprehensive Human Resource Management (HRM) System that captures
 * all the essential functionalities required for any enterprise.
 * Copyright (C) 2006 OrangeHRM Inc., http://www.orangehrm.com
 *
 * OrangeHRM is free software: you can redistribute it and/or modify it under the terms of
 * the GNU General Public License as published by the Free Software Foundation, either
 * version 3 of the License, or (at your option) any later version.
 *
 * OrangeHRM is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with OrangeHRM.
 * If not, see <https://www.gnu.org/licenses/>.
 */
 -->

<template>
  <edit-employee-layout :employee-id="empNumber" screen="deduction">
    <save-deduction-component
      v-if="showSaveModal"
      :http="http"
      @close="onSaveModalClose"
    ></save-deduction-component>
    <edit-deduction-component
      v-if="showEditModal"
      :http="http"
      :data="editModalState"
      @close="onEditModalClose"
    ></edit-deduction-component>
    <div class="orangehrm-horizontal-padding orangehrm-vertical-padding">
      <profile-action-header
        :action-button-shown="$can.update(`deduction_details`)"
        @click="onClickAdd"
      >
        {{ $t('pim.employee_deductions') }}
      </profile-action-header>
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
        :headers="tableHeaders"
        :items="items?.data"
        :selectable="$can.delete(`deduction_details`)"
        :disabled="isDisabled"
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
    <delete-confirmation ref="deleteDialog"></delete-confirmation>
  </edit-employee-layout>
</template>

<script>
import usePaginate from '@ohrm/core/util/composable/usePaginate';
import {APIService} from '@ohrm/core/util/services/api.service';
import ProfileActionHeader from '@/orangehrmPimPlugin/components/ProfileActionHeader';
import EditEmployeeLayout from '@/orangehrmPimPlugin/components/EditEmployeeLayout';
import SaveDeductionComponent from '@/orangehrmPimPlugin/components/SaveDeductionComponent';
import EditDeductionComponent from '@/orangehrmPimPlugin/components/EditDeductionComponent';
import DeleteConfirmationDialog from '@ohrm/components/dialogs/DeleteConfirmationDialog';

const deductionNormalizer = (data) => {
  return data.map((item) => {
    return {
      id: item.id,
      name: item.name,
      amount: item.amount,
      effectiveDate: item.effectiveDate,
      comment: item.comment,
    };
  });
};

export default {
  name: 'EmployeeDeductions',
  components: {
    'edit-employee-layout': EditEmployeeLayout,
    'profile-action-header': ProfileActionHeader,
    'save-deduction-component': SaveDeductionComponent,
    'edit-deduction-component': EditDeductionComponent,
    'delete-confirmation': DeleteConfirmationDialog,
  },

  props: {
    empNumber: {
      type: String,
      required: true,
    },
  },

  setup(props) {
    const http = new APIService(
      window.appGlobal.baseUrl,
      `/api/v2/pim/employees/${props.empNumber}/deductions`,
    );

    const {
      showPaginator,
      currentPage,
      total,
      pages,
      pageSize,
      response,
      isLoading,
      execQuery,
    } = usePaginate(http, {
      normalizer: deductionNormalizer,
      toastNoRecords: false,
    });

    return {
      http,
      showPaginator,
      currentPage,
      isLoading,
      total,
      pages,
      pageSize,
      execQuery,
      items: response,
    };
  },

  data() {
    return {
      checkedItems: [],
      showSaveModal: false,
      showEditModal: false,
      editModalState: null,
    };
  },

  computed: {
    isDisabled() {
      return false;
    },

    tableHeaders() {
      return [
        {
          name: 'name',
          title: this.$t('general.name'),
          field: 'name',
          width: 200,
          style: {flex: '1'},
        },
        {
          name: 'amount',
          title: this.$t('general.amount'),
          field: 'amount',
          style: {flex: '1'},
        },
        {
          name: 'effectiveDate',
          title: this.$t('pim.effective_date'),
          field: 'effectiveDate',
          style: {flex: '1'},
        },
        {
          name: 'comment',
          title: this.$t('general.comment'),
          field: 'comment',
          style: {flex: '2'},
        },
      ];
    },
  },

  methods: {
    onClickDeleteSelected() {
      const ids = this.checkedItems.map((index) => {
        return this.items?.data[index].id;
      });
      this.$refs.deleteDialog.showDialog().then((confirmation) => {
        if (confirmation === 'ok') {
          this.deleteItems(ids);
        }
      });
    },

    onClickDelete(item) {
      this.$refs.deleteDialog.showDialog().then((confirmation) => {
        if (confirmation === 'ok') {
          this.deleteItems([item.id]);
        }
      });
    },

    async deleteItems(items) {
      if (items instanceof Array) {
        this.isDeleting = true;
        await this.http
          .delete({
            ids: items,
          })
          .then(() => {
            return this.$toast.deleteSuccess();
          })
          .then(() => {
            this.resetDataTable();
          });
      }
    },

    resetDataTable() {
      this.checkedItems = [];
      this.execQuery();
    },

    onClickAdd() {
      this.showSaveModal = true;
    },

    onClickEdit(item) {
      this.editModalState = item;
      this.showEditModal = true;
    },

    onSaveModalClose() {
      this.showSaveModal = false;
      this.resetDataTable();
    },

    onEditModalClose() {
      this.showEditModal = false;
      this.editModalState = null;
      this.resetDataTable();
    },
  },
};
</script>
