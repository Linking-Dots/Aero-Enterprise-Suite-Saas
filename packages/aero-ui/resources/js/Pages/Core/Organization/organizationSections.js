/**
 * organizationSections — the CORE (tenant) Organization section config consumed
 * by the shared settings shell (components/settings/*). Permission codes are the
 * DECLARED config/module.php .view actions per component. Icons are heroicon
 * component refs (the @aero/ui string-name Icon registry does not include these),
 * mirroring Core/Settings/settingsSections.js.
 */
import {
  BuildingOffice2Icon,
  IdentificationIcon,
  CalendarDaysIcon,
  MapPinIcon,
  UsersIcon,
} from '@heroicons/react/24/outline';

export const ORG_GROUPS = [
  {
    group: 'Company',
    items: [
      { key: 'profile',  label: 'Profile',              routeName: 'core.organization.profile',     icon: BuildingOffice2Icon, permission: 'core.organization.org_profile.view' },
      { key: 'identity', label: 'Tax / Legal Identity', routeName: 'core.organization.identity',     icon: IdentificationIcon,  permission: 'core.organization.org_identity.view' },
    ],
  },
  {
    group: 'Operations',
    items: [
      { key: 'fiscal',   label: 'Fiscal Year',          routeName: 'core.organization.fiscal-year', icon: CalendarDaysIcon,    permission: 'core.organization.fiscal_year.view' },
    ],
  },
  {
    group: 'Directory',
    items: [
      { key: 'addresses', label: 'Addresses',           routeName: 'core.organization.addresses',   icon: MapPinIcon,          permission: 'core.organization.org_addresses.view' },
      { key: 'contacts',  label: 'Contacts',            routeName: 'core.organization.contacts',     icon: UsersIcon,           permission: 'core.organization.org_contacts.view' },
    ],
  },
];

// Filtering/href-resolution is provided generically by
// components/settings/useSettingsGroups.js (shared with Settings + Platform).
