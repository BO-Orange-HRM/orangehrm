<template>
  <div class="orangehrm-background-container">
    <div class="orangehrm-card-container">
      <oxd-text tag="h6" class="orangehrm-main-title">
        {{ $t('payroll.edit_report') }}
      </oxd-text>
      <oxd-divider />

      <oxd-form :loading="isLoading" @submit-valid="onSave">
        <!-- Report Name -->
        <oxd-form-row>
          <oxd-grid :cols="2" class="orangehrm-full-width-grid">
            <oxd-grid-item>
              <oxd-input-field
                v-model="report.name"
                :label="$t('general.report_name')"
                :placeholder="$t('general.type_here_message')"
                :rules="rules.name"
                required
              />
            </oxd-grid-item>
          </oxd-grid>
        </oxd-form-row>

        <oxd-divider />

        <!-- Filter Criteria -->
        <oxd-form-row>
          <oxd-text class="orangehrm-sub-title" tag="h6">
            {{ $t('payroll.selection_criteria') }}
          </oxd-text>
          <oxd-grid :cols="4" class="orangehrm-full-width-grid">
            <oxd-grid-item class="orangehrm-report-criteria --span-column-2">
              <oxd-input-field
                v-model="report.criterion"
                type="select"
                :label="$t('payroll.select_criterion')"
                :options="availableCriteria"
              />
              <oxd-input-group>
                <oxd-icon-button
                  class="orangehrm-report-icon"
                  name="plus"
                  @click="addCriterion"
                />
              </oxd-input-group>
            </oxd-grid-item>

            <!-- Render selected criteria -->
            <payroll-report-criterion
              v-for="(criterion, index) in report.criteriaSelected"
              :key="criterion.id"
              v-model:operator="
                report.criteriaFieldValues[criterion.id].operator
              "
              v-model:valueX="report.criteriaFieldValues[criterion.id].valueX"
              v-model:valueY="report.criteriaFieldValues[criterion.id].valueY"
              :criterion="criterion"
              @delete="removeCriterion(index)"
            />
          </oxd-grid>
        </oxd-form-row>

        <oxd-divider />

        <!-- Display Fields -->
        <oxd-form-row>
          <oxd-text class="orangehrm-sub-title" tag="h6">
            {{ $t('payroll.display_fields') }}
          </oxd-text>
          <oxd-grid :cols="4" class="orangehrm-full-width-grid">
            <oxd-grid-item>
              <oxd-input-field
                v-model="report.fieldGroup"
                type="select"
                :label="$t('payroll.select_display_field_group')"
                :options="availableFieldGroups"
              />
            </oxd-grid-item>
            <oxd-grid-item class="orangehrm-report-criteria --span-column-2">
              <oxd-input-field
                v-model="report.displayField"
                type="select"
                :label="$t('payroll.select_display_field')"
                :options="availableDisplayFields"
              />
              <oxd-input-group>
                <oxd-icon-button
                  class="orangehrm-report-icon"
                  name="plus"
                  @click="addDisplayField"
                />
              </oxd-input-group>
            </oxd-grid-item>

            <!-- Selected field groups -->
            <payroll-display-field
              v-for="(fieldGroup, index) in report.fieldGroupSelected"
              :key="fieldGroup.id"
              v-model:includeHeader="
                report.displayFieldSelected[fieldGroup.id].includeHeader
              "
              :field-group="fieldGroup"
              :selected-fields="
                report.displayFieldSelected[fieldGroup.id].fields
              "
              @delete="removeDisplayFieldGroup(index)"
              @delete-chip="removeDisplayField($event, index)"
            />
          </oxd-grid>
        </oxd-form-row>

        <oxd-divider />

        <!-- Form Actions -->
        <oxd-form-actions>
          <required-text />
          <oxd-button
            type="button"
            display-type="ghost"
            :label="$t('general.cancel')"
            @click="onCancel"
          />
          <submit-button />
        </oxd-form-actions>
      </oxd-form>
    </div>
  </div>
</template>

<script>
import {navigate} from '@ohrm/core/util/helper/navigation';
import {
  required,
  shouldNotExceedCharLength,
} from '@ohrm/core/util/validation/rules';
import {APIService} from '@ohrm/core/util/services/api.service';
import PayrollReportCriterion from '@/orangehrmPayrollPlugin/components/PayrollReportCriterion';
import PayrollDisplayField from '@/orangehrmPayrollPlugin/components/PayrollDisplayField';
import usePayrollReport from '@/orangehrmPayrollPlugin/util/composable/usePayrollReport';

export default {
  components: {
    'payroll-report-criterion': PayrollReportCriterion,
    'payroll-display-field': PayrollDisplayField,
  },

  props: {
    reportId: {type: Number, required: true},
    selectionCriteria: {type: Array, required: true},
    displayFieldGroups: {type: Array, required: true},
    displayFields: {type: Array, required: true},
  },

  setup(props) {
    const http = new APIService(
      window.appGlobal.baseUrl,
      '/api/v2/payroll/reports/defined',
    );
    const {
      report,
      addCriterion,
      addDisplayField,
      removeCriterion,
      removeDisplayField,
      removeDisplayFieldGroup,
      availableCriteria,
      availableFieldGroups,
      availableDisplayFields,
      serializeBody,
    } = usePayrollReport(
      props.selectionCriteria,
      props.displayFields,
      props.displayFieldGroups,
    );

    return {
      http,
      report,
      addCriterion,
      addDisplayField,
      removeCriterion,
      removeDisplayField,
      removeDisplayFieldGroup,
      availableCriteria,
      availableFieldGroups,
      availableDisplayFields,
      serializeBody,
    };
  },

  data() {
    return {
      isLoading: false,
      rules: {
        name: [required, shouldNotExceedCharLength(250)],
      },
    };
  },

  beforeMount() {
    this.isLoading = true;
    this.http
      .get(this.reportId)
      .then(({data}) => {
        const reportData = data.data;
        this.report.name = reportData.name;
        // Map saved field groups and criteria from API to UI model
      })
      .finally(() => (this.isLoading = false));
  },

  methods: {
    onCancel() {
      navigate('/payroll/viewDefinedReports');
    },
    onSave() {
      if (Object.keys(this.report.displayFieldSelected).length === 0) {
        return this.$toast.warn({
          title: this.$t('general.warning'),
          message: this.$t(
            'payroll.at_least_one_display_field_should_be_added',
          ),
        });
      }
      this.isLoading = true;
      const payload = this.serializeBody(this.report);
      this.http
        .update(this.reportId, payload)
        .then(() => this.$toast.updateSuccess())
        .then(() =>
          navigate('/payroll/displayReport/{id}', {id: this.reportId}),
        )
        .finally(() => (this.isLoading = false));
    },
  },
};
</script>
