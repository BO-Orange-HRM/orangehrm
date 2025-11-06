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
  <oxd-dialog
    :title="$t('pim.add_deduction')"
    :with-fixed-footer="true"
    :show="showDialog"
    @update:show="onCancel"
  >
    <oxd-form @submit-valid="onSave" @reset="onCancel">
      <oxd-form-row>
        <oxd-grid :cols="2" class="orangehrm-full-width-grid">
          <oxd-grid-item>
            <oxd-input-field
              v-model="deduction.name"
              :label="$t('general.name')"
              :rules="rules.name"
              required
            />
          </oxd-grid-item>
          <oxd-grid-item>
            <oxd-input-field
              v-model="deduction.amount"
              :label="$t('general.amount')"
              type="number"
              :rules="rules.amount"
              required
            />
          </oxd-grid-item>
        </oxd-grid>
      </oxd-form-row>

      <oxd-form-row>
        <oxd-grid :cols="2" class="orangehrm-full-width-grid">
          <oxd-grid-item>
            <oxd-input-field
              v-model="deduction.effectiveDate"
              :label="$t('pim.effective_date')"
              type="date"
              :rules="rules.effectiveDate"
              required
            />
          </oxd-grid-item>
        </oxd-grid>
      </oxd-form-row>

      <oxd-form-row>
        <oxd-input-field
          v-model="deduction.comment"
          type="textarea"
          :label="$t('general.comment')"
          :rules="rules.comment"
        />
      </oxd-form-row>

      <oxd-divider />

      <oxd-form-actions>
        <oxd-button
          type="button"
          display-type="text"
          :label="$t('general.cancel')"
          @click="onCancel"
        />
        <submit-button :label="$t('general.save')" />
      </oxd-form-actions>
    </oxd-form>
  </oxd-dialog>
</template>

<script>
import {APIService} from '@ohrm/core/util/services/api.service';

export default {
  name: 'SaveDeductionComponent',
  props: {
    http: {
      type: Object,
      required: true,
    },
  },

  emits: ['close'],

  data() {
    return {
      showDialog: true,
      deduction: {
        name: '',
        amount: '',
        effectiveDate: '',
        comment: '',
      },
      rules: {
        name: [
          {
            required: true,
            message: this.$t('general.required'),
          },
          {
            max: 100,
            message: this.$t('general.should_not_exceed_n_characters', {
              n: 100,
            }),
          },
        ],
        amount: [
          {
            required: true,
            message: this.$t('general.required'),
          },
          {
            min: 0,
            message: this.$t('pim.should_be_zero_or_more'),
          },
          {
            max: 999999999.99,
            message: this.$t('pim.should_be_less_than_max', {
              max: '999,999,999.99',
            }),
          },
        ],
        effectiveDate: [
          {
            required: true,
            message: this.$t('general.required'),
          },
        ],
        comment: [
          {
            max: 1000,
            message: this.$t('general.should_not_exceed_n_characters', {
              n: 1000,
            }),
          },
        ],
      },
    };
  },

  methods: {
    onSave() {
      this.$refs.form.hide();
      this.http
        .create({
          ...this.deduction,
          amount: parseFloat(this.deduction.amount),
        })
        .then(() => {
          this.$toast.saveSuccess();
          this.onCancel();
        });
    },

    onCancel() {
      this.$emit('close');
    },
  },
};
</script>
