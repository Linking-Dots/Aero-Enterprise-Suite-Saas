import { ENV } from '../support/env.ts';

export type Role = 'superadmin' | 'hr' | 'employee' | 'landlord';
export type Mode = 'saas' | 'standalone';

export const ROLE_EMAIL: Record<Role, string> = {
  superadmin: ENV.superAdminEmail,
  hr: ENV.hrEmail,
  employee: ENV.employeeEmail,
  landlord: ENV.landlordEmail,
};

export function statePath(role: Role, mode: Mode): string {
  return `.auth/${role}.${mode}.json`;
}
