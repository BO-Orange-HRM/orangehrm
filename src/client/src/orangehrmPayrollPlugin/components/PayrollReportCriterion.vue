<template>
  <div class="orangehrm-report-criterion orangehrm-card">
    <oxd-grid :cols="4" class="orangehrm-full-width-grid">
      <!-- Criterion Label -->
      <oxd-grid-item>
        <oxd-input-field
          :label="$t('payroll.criterion')"
          :value="criterion.name"
          disabled
        />
      </oxd-grid-item>

      <!-- Operator (e.g. Equal, Between, etc.) -->
      <oxd-grid-item>
        <oxd-input-field
          v-model="localOperator"
          type="select"
          :label="$t('payroll.operator')"
          :options="operators"
        />
      </oxd-grid-item>

      <!-- Value X -->
      <oxd-grid-item>
        <oxd-input-field
          v-if="showValueX"
          v-model="localValueX"
          :label="$t('payroll.value')"
          :placeholder="$t('general.type_here_message')"
          :rules="[required]"
          required
        />
      </oxd-grid-item>

      <!-- Value Y (for ranges) -->
      <oxd-grid-item v-if="showValueY">
        <oxd-input-field
          v-model="localValueY"
          :label="$t('payroll.to_value')"
          :placeholder="$t('general.type_here_message')"
        />
      </oxd-grid-item>

      <!-- Delete button -->
      <oxd-grid-item>
        <oxd-icon-button
          name="trash"
          class="orangehrm-report-icon orangehrm-margin-top"
          @click="$emit('delete')"
        />
      </oxd-grid-item>
    </oxd-grid>
  </div>
</template>

<script>
import {required} from '@ohrm/core/util/validation/rules';

export default {
  name: 'PayrollReportCriterion',

  props: {
    criterion: {
      type: Object,
      required: true,
    },
    modelValue: {
      type: Object,
      default: () => ({}),
    },
  },

  emits: ['update:operator', 'update:valueX', 'update:valueY', 'delete'],

  data() {
    return {
      required,
      localOperator: this.modelValue.operator || null,
      localValueX: this.modelValue.valueX || null,
      localValueY: this.modelValue.valueY || null,
      operators: [
        {id: 'eq', label: this.$t('payroll.equal')},
        {id: 'lt', label: this.$t('payroll.less_than')},
        {id: 'gt', label: this.$t('payroll.greater_than')},
        {id: 'between', label: this.$t('payroll.between')},
      ],
    };
  },

  computed: {
    showValueX() {
      return this.localOperator !== null;
    },
    showValueY() {
      return this.localOperator?.id === 'between';
    },
  },

  watch: {
    localOperator(val) {
      this.$emit('update:operator', val);
    },
    localValueX(val) {
      this.$emit('update:valueX', val);
    },
    localValueY(val) {
      this.$emit('update:valueY', val);
    },
  },
};
</script>

<style lang="scss" scoped>
.orangehrm-report-criterion {
  margin-bottom: 1rem;
  padding: 1rem;
  border: 1px solid var(--oxd-interface-gray-lighten-2);
  border-radius: var(--oxd-border-radius-md);
  background: var(--oxd-background-white);
}

.orangehrm-report-icon {
  margin-top: 1.8rem;
  color: var(--oxd-interface-gray-darken-1);
}

.orangehrm-margin-top {
  margin-top: 2.2rem;
}
</style>
