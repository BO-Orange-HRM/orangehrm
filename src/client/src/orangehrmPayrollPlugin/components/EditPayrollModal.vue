<template>
  <oxd-dialog class="orangehrm-dialog-modal" @update:show="onCancel(false)">
    <div class="orangehrm-modal-header">
      <oxd-text type="card-title">
        {{ $t('Edit Payroll Period') }}
      </oxd-text>
    </div>

    <oxd-divider />

    <oxd-form :loading="isLoading" @submit-valid="onSave">
      <oxd-form-row>
        <oxd-input-field
          v-model="form.status"
          type="select"
          :label="$t('general.status')"
          :options="statusOptions"
          :rules="rules.status"
          required
        />
      </oxd-form-row>

      <oxd-form-row>
        <date-input
          v-model="form.start_date"
          :label="$t('Start Date')"
          :rules="rules.start_date"
          required
        />
      </oxd-form-row>

      <oxd-form-row>
        <date-input
          v-model="form.end_date"
          :label="$t('End Date')"
          :rules="rules.end_date"
          required
        />
      </oxd-form-row>

      <oxd-divider />

      <oxd-form-actions>
        <required-text />
        <oxd-button
          type="button"
          display-type="ghost"
          :label="$t('general.cancel')"
          @click="onCancel(false)"
        />
        <submit-button :label="$t('general.save')" />
      </oxd-form-actions>
    </oxd-form>
  </oxd-dialog>
</template>

<script>
import {APIService} from '@/core/util/services/api.service';
import {OxdDialog} from '@ohrm/oxd';
import {
  required,
  shouldNotExceedCharLength,
  validDateFormat,
} from '@ohrm/core/util/validation/rules';
import useDateFormat from '@/core/util/composable/useDateFormat';

const defaultPayrollModel = {
  id: null,
  name: '',
  start_date: '',
  end_date: '',
};

export default {
  name: 'EditPayrollModal',

  components: {
    'oxd-dialog': OxdDialog,
  },

  props: {
    payroll: {
      type: Object,
      required: false,
      default: () => ({...defaultPayrollModel}),
    },
  },

  emits: ['close', 'updated'],

  setup() {
    const {userDateFormat} = useDateFormat();
    const http = new APIService(
      window.appGlobal.baseUrl,
      '/api/v2/payroll/periods',
    );

    return {
      http,
      userDateFormat,
    };
  },

  data() {
    return {
      isLoading: false,
      form: {...defaultPayrollModel},
      statusOptions: [
        {id: 'Draft', label: this.$t('payroll.status_draft')},
        {id: 'Pending', label: this.$t('payroll.status_pending')},
        {id: 'Approved', label: this.$t('payroll.status_approved')},
        {id: 'Closed', label: this.$t('payroll.status_closed')},
      ],
      rules: {
        name: [required, shouldNotExceedCharLength(100)],
        start_date: [required, validDateFormat(this.userDateFormat)],
        end_date: [required, validDateFormat(this.userDateFormat)],
      },
    };
  },

  watch: {
    payroll: {
      immediate: true,
      handler(newVal) {
        if (newVal && Object.keys(newVal).length) {
          const [start, end] = newVal.period
            ? newVal.period.split(' - ')
            : ['', ''];

          this.form = {
            id: newVal.id || null,
            status: newVal.status || 'Draft',
            start_date: start,
            end_date: end,
          };
        } else {
          // reset form when empty
          this.form = {...defaultPayrollModel};
        }
      },
    },
  },

  methods: {
    onSave() {
      this.isLoading = true;
      const payload = {
        id: this.form.id,
        status: this.form.status.id,
        start_date: this.form.start_date,
        end_date: this.form.end_date,
      };

      this.submitData(payload, this.form.id)
        .then(() => this.$toast.updateSuccess())
        .then(() => {
          this.$emit('updated');
          this.onCancel(true);
        })
        .finally(() => {
          this.isLoading = false;
        });
    },

    async submitData(payload, id) {
      return !id ? this.http.create(payload) : this.http.update(id, payload);
    },

    onCancel(reload) {
      this.$emit('close', reload);
    },
  },
};
</script>
