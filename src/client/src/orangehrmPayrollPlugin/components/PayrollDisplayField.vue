<template>
  <div class="orangehrm-report-display orangehrm-card">
    <oxd-grid
      :cols="3"
      class="orangehrm-full-width-grid orangehrm-report-display__header"
    >
      <!-- Display Field Group -->
      <oxd-grid-item>
        <oxd-input-field
          :label="$t('payroll.display_group')"
          :value="fieldGroup.name"
          disabled
        />
      </oxd-grid-item>

      <!-- Include Header Checkbox -->
      <oxd-grid-item>
        <oxd-input-field
          v-model="localIncludeHeader"
          type="checkbox"
          :label="$t('payroll.include_header')"
        />
      </oxd-grid-item>

      <!-- Delete Button -->
      <oxd-grid-item class="orangehrm-report-display__delete">
        <oxd-icon-button
          name="trash"
          class="orangehrm-report-icon"
          @click="$emit('delete')"
        />
      </oxd-grid-item>
    </oxd-grid>

    <oxd-divider />

    <!-- Field Chips for selected display fields -->
    <div class="orangehrm-report-display__fields">
      <oxd-chip
        v-for="(field, index) in selectedFields"
        :key="field.id"
        :label="field.name"
        @remove="$emit('delete-chip', field)"
      />
    </div>

    <oxd-divider />

    <!-- Add Field Selector -->
    <oxd-form-row>
      <oxd-grid :cols="2" class="orangehrm-full-width-grid">
        <oxd-grid-item>
          <oxd-input-field
            v-model="selectedField"
            type="select"
            :label="$t('payroll.add_display_field')"
            :options="availableFields"
            placeholder="Select field to add"
          />
        </oxd-grid-item>
        <oxd-grid-item>
          <oxd-icon-button
            name="plus"
            class="orangehrm-report-icon orangehrm-margin-top"
            @click="onAddField"
          />
        </oxd-grid-item>
      </oxd-grid>
    </oxd-form-row>
  </div>
</template>

<script>
export default {
  name: 'PayrollDisplayField',

  props: {
    fieldGroup: {
      type: Object,
      required: true,
    },
    selectedFields: {
      type: Array,
      default: () => [],
    },
    includeHeader: {
      type: Boolean,
      default: true,
    },
    availableFields: {
      type: Array,
      default: () => [],
    },
  },

  emits: ['delete', 'delete-chip', 'update:includeHeader', 'add-field'],

  data() {
    return {
      localIncludeHeader: this.includeHeader,
      selectedField: null,
    };
  },

  watch: {
    localIncludeHeader(val) {
      this.$emit('update:includeHeader', val);
    },
  },

  methods: {
    onAddField() {
      if (this.selectedField) {
        this.$emit('add-field', this.selectedField);
        this.selectedField = null;
      } else {
        this.$toast.warn({
          title: this.$t('general.warning'),
          message: this.$t('payroll.select_field_first'),
        });
      }
    },
  },
};
</script>

<style lang="scss" scoped>
.orangehrm-report-display {
  margin-bottom: 1rem;
  padding: 1rem;
  border: 1px solid var(--oxd-interface-gray-lighten-2);
  border-radius: var(--oxd-border-radius-md);
  background: var(--oxd-background-white);
}

.orangehrm-report-display__header {
  margin-bottom: 0.5rem;
}

.orangehrm-report-display__fields {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.orangehrm-report-display__delete {
  display: flex;
  justify-content: flex-end;
  align-items: center;
}

.orangehrm-report-icon {
  margin-top: 1.2rem;
  color: var(--oxd-interface-gray-darken-1);
}

.orangehrm-margin-top {
  margin-top: 2.2rem;
}
</style>
