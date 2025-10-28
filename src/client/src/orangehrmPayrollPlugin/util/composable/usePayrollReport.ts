/**
 * OrangeHRM Payroll Module - Payroll Report Composable (TypeScript Version)
 * -------------------------------------------------------------------------
 * Provides reusable logic for creating, editing, and managing payroll reports.
 * Mirrors the structure of useEmployeeReport but adapted for payroll-specific data.
 */

import {ref, Ref} from 'vue';
import {
  Criterion,
  Field,
  FieldGroup,
  PayrollReport,
} from '@/orangehrmPayrollPlugin/types';

export default function usePayrollReport(
  selectionCriteria: Criterion[],
  displayFields: Field[],
  displayFieldGroups: FieldGroup[],
) {
  const report: Ref<PayrollReport> = ref({
    name: '',
    criterion: null,
    includeEmployees: null,
    criteriaSelected: [],
    criteriaFieldValues: {},
    fieldGroup: null,
    displayField: null,
    fieldGroupSelected: [],
    displayFieldSelected: {},
  });

  const availableCriteria = ref<Criterion[]>(selectionCriteria);
  const availableFieldGroups = ref<FieldGroup[]>(displayFieldGroups);
  const availableDisplyFields = ref<Field[]>(displayFields);
  const addCriterion = (): void => {
    if (!report.value.criterion) return;

    const alreadySelected = report.value.criteriaSelected.find(
      (c) => c.id === report.value.criterion?.id,
    );

    if (!alreadySelected) {
      report.value.criteriaSelected.push(report.value.criterion);
      report.value.criteriaFieldValues[report.value.criterion.id] = {
        valueX: null,
        valueY: null,
        operator: null,
      };
    }

    report.value.criterion = null;
  };

  const removeCriterion = (index: number): void => {
    const criterion = report.value.criteriaSelected[index];
    delete report.value.criteriaFieldValues[criterion.id];
    report.value.criteriaSelected.splice(index, 1);
  };

  const addDisplayField = (): void => {
    const fieldGroup = report.value.fieldGroup;
    const displayField = report.value.displayField;

    if (!fieldGroup || !displayField) return;

    const groupId = fieldGroup.id;

    if (!report.value.displayFieldSelected[groupId]) {
      report.value.fieldGroupSelected.push(fieldGroup);
      report.value.displayFieldSelected[groupId] = {
        fields: [],
        includeHeader: true,
      };
    }

    const alreadyAdded = report.value.displayFieldSelected[groupId].fields.find(
      (f) => f.id === displayField.id,
    );

    if (!alreadyAdded) {
      report.value.displayFieldSelected[groupId].fields.push(displayField);
    }

    report.value.fieldGroup = null;
    report.value.displayField = null;
  };

  const removeDisplayFieldGroup = (index: number): void => {
    const fieldGroup = report.value.fieldGroupSelected[index];
    delete report.value.displayFieldSelected[fieldGroup.id];
    report.value.fieldGroupSelected.splice(index, 1);
  };

  const removeDisplayField = (event: Field, groupIndex: number): void => {
    const fieldGroup = report.value.fieldGroupSelected[groupIndex];
    const groupId = fieldGroup.id;
    const groupFields = report.value.displayFieldSelected[groupId].fields;

    const idx = groupFields.findIndex((f) => f.id === event.id);
    if (idx !== -1) groupFields.splice(idx, 1);
  };

  const serializeBody = (reportObj: PayrollReport): Record<string, any> => {
    const payload: Record<string, any> = {
      name: reportObj.name,
      include: reportObj.includeEmployees?.key || 'onlyCurrent',
      criteria: {},
      fieldGroup: {},
    };

    // Criteria
    for (const c of reportObj.criteriaSelected) {
      const {valueX, valueY, operator} =
        reportObj.criteriaFieldValues[c.id] || {};
      payload.criteria[c.id] = {
        x: valueX,
        y: valueY,
        operator: operator?.id,
      };
    }

    // Display fields
    for (const group of reportObj.fieldGroupSelected) {
      const groupId = group.id;
      const selection = reportObj.displayFieldSelected[groupId];
      payload.fieldGroup[groupId] = {
        fields: selection.fields.map((f) => f.id),
        includeHeader: selection.includeHeader,
      };
    }

    return payload;
  };

  return {
    report,
    addCriterion,
    removeCriterion,
    addDisplayField,
    removeDisplayFieldGroup,
    removeDisplayField,
    serializeBody,
    availableCriteria,
    availableFieldGroups,
    availableDisplyFields,
  };
}
