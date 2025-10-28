export interface PayrollResponse {
  message: string;
  timestamp: string;
  status: string;
  pluginName: string;
  version: string;
}

export interface PayrollEmployee {
  id: number;
  employeeId: string;
  firstName: string;
  lastName: string;
  baseSalary: number;
  currency: string;
}

export interface Option {
  id: number | string;
  key?: string;
  label: string;
}

export interface Criterion {
  id: number;
  label: string;
  name?: string;
}

export interface Operator {
  id: string;
  label: string;
}

export interface Field {
  id: number;
  label: string;
  name?: string;
}

export interface FieldGroup {
  id: number;
  label: string;
  name?: string;
}

export interface CriterionValue {
  valueX: string | number | null;
  valueY: string | number | null;
  operator: Operator | null;
}

export interface DisplayFieldSelection {
  fields: Field[];
  includeHeader: boolean;
}

export interface PayrollReport {
  name: string;
  criterion: Criterion | null;
  includeEmployees: Option | null;
  criteriaSelected: Criterion[];
  criteriaFieldValues: Record<number, CriterionValue>;
  fieldGroup: FieldGroup | null;
  displayField: Field | null;
  fieldGroupSelected: FieldGroup[];
  displayFieldSelected: Record<number, DisplayFieldSelection>;
}

export interface PayrollPeriod {
  id: number | null;
  name: string;
  start_date: string | null;
  end_date: string | null;
  status: 'open' | 'processing' | 'closed';
}

export interface PayrollRecord {
  employeeId: number;
  employeeName: string;
  grossPay: number;
  deductions: number;
  netPay: number;
}
