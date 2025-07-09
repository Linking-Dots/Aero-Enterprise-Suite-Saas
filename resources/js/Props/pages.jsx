import {
  HomeIcon,
  UserGroupIcon,
  CalendarDaysIcon,
  Cog6ToothIcon,
  CalendarIcon,
  ArrowRightOnRectangleIcon,
  EnvelopeIcon,
  DocumentTextIcon,
  BriefcaseIcon,
  UsersIcon,
  FolderIcon, // Changed from FolderOpenIcon
  ChartBarSquareIcon, // Changed from ChartBarIcon
  CreditCardIcon,
  ShoppingBagIcon,
  BuildingOffice2Icon,
  BanknotesIcon,
  WrenchScrewdriverIcon,
  ClipboardDocumentCheckIcon,
  DocumentDuplicateIcon,
  ShieldCheckIcon,
  ComputerDesktopIcon,
  PhoneIcon,
  UserIcon,
  ArchiveBoxIcon,
  AcademicCapIcon,
  TruckIcon,
  ShoppingCartIcon,
  TicketIcon,
  BeakerIcon,
  CubeIcon,
  ScaleIcon
} from '@heroicons/react/24/outline';

export const getPages = (permissions, auth = null) => [
  // 1. Dashboard (ISO 9000 - Information Management)
  ...(permissions.includes('core.dashboard.view') ? [{
    name: 'Dashboard',
    icon: <HomeIcon className="" />, 
    route: 'dashboard',
    priority: 1,
    module: 'core'
  }] : []),
  // 2. Workspace (Self-Service)
  ...((permissions.includes('attendance.own.view') || permissions.includes('leave.own.view') || permissions.includes('communications.own.view')) ? [{
    name: 'Workspace',
    icon: <UserGroupIcon className="" />,
    priority: 2,
    module: 'self-service',
    subMenu: [
      ...(permissions.includes('attendance.own.view') ? [
        { name: 'Attendance', icon: <CalendarDaysIcon  />, route: 'attendance-employee' }
      ] : []),
      ...(permissions.includes('leave.own.view') ? [
        { name: 'Leaves', icon: <ArrowRightOnRectangleIcon  />, route: 'leaves-employee' }
      ] : []),
      ...(permissions.includes('communications.own.view') ? [
        { name: 'Communications', icon: <EnvelopeIcon  />, route: 'emails' },
      ] : []),
    ]
  }] : []),
  // 3. HR (Human Resources) - Reorganized with submodule groups
  ...((permissions.includes('employees.view') || 
       permissions.includes('hr.onboarding.view') || 
       permissions.includes('hr.skills.view') || 
       permissions.includes('hr.benefits.view') || 
       permissions.includes('hr.safety.view') || 
       permissions.includes('hr.analytics.view') || 
       permissions.includes('hr.documents.view') || 
       permissions.includes('hr.selfservice.view')) ? [{
    name: 'HR',
    icon: <UserGroupIcon className="" />,
    priority: 3,
    module: 'hrm',
    subMenu: [
      // Core Employee Management
      ...((permissions.includes('employees.view') || permissions.includes('departments.view') || permissions.includes('designations.view')) ? [{
        name: 'Employees',
        icon: <UserGroupIcon  />,
        category: 'core',
        subMenu: [
          ...(permissions.includes('employees.view') ? [{ name: 'Staff', icon: <UserGroupIcon  />, route: 'employees' }] : []),
          ...(permissions.includes('departments.view') ? [{ name: 'Depts', icon: <BuildingOffice2Icon  />, route: 'departments' }] : []),
          ...(permissions.includes('designations.view') ? [{ name: 'Roles', icon: <BriefcaseIcon  />, route: 'designations' }] : []),
        ]
      }] : []),
      
      // Time & Attendance Management
      ...((permissions.includes('attendance.view') || permissions.includes('holidays.view') || permissions.includes('leaves.view')) ? [{
        name: 'Time',
        icon: <CalendarDaysIcon  />,
        category: 'time',
        subMenu: [
          ...(permissions.includes('attendance.view') ? [{ name: 'Punch', icon: <CalendarDaysIcon  />, route: 'attendances' }] : []),
          ...(permissions.includes('holidays.view') ? [{ name: 'Calendar', icon: <CalendarIcon  />, route: 'holidays' }] : []),
          ...(permissions.includes('leaves.view') ? [
            { name: 'Leaves', icon: <ArrowRightOnRectangleIcon  />, route: 'leaves' },
            { name: 'Analytics', icon: <ChartBarSquareIcon  />, route: 'leave-summary' },
            { name: 'Policy', icon: <Cog6ToothIcon  />, route: 'leave-settings' },
          ] : []),
        ]
      }] : []),
      
      // Employee Lifecycle Management
      ...((permissions.includes('hr.onboarding.view') || permissions.includes('hr.offboarding.view') || permissions.includes('hr.checklists.view') || permissions.includes('jobs.view')) ? [{
        name: 'Lifecycle',
        icon: <UserIcon  />,
        category: 'lifecycle',
        subMenu: [
          ...(permissions.includes('jobs.view') ? [{ name: 'Recruit', icon: <BriefcaseIcon  />, route: 'hr.recruitment.index' }] : []),
          ...(permissions.includes('hr.onboarding.view') ? [{ name: 'Onboard', icon: <UserIcon  />, route: 'hr.onboarding.index' }] : []),
          ...(permissions.includes('hr.offboarding.view') ? [{ name: 'Offboard', icon: <ArrowRightOnRectangleIcon  />, route: 'hr.offboarding.index' }] : []),
          ...(permissions.includes('hr.checklists.view') ? [{ name: 'Checklists', icon: <ClipboardDocumentCheckIcon  />, route: 'hr.checklists.index' }] : []),
        ]
      }] : []),
      
      // Performance & Development
      ...((permissions.includes('performance-reviews.view') || permissions.includes('training-sessions.view') || permissions.includes('hr.skills.view') || permissions.includes('hr.competencies.view')) ? [{
        name: 'Dev',
        icon: <AcademicCapIcon  />,
        category: 'development',
        subMenu: [
          ...(permissions.includes('performance-reviews.view') ? [{ name: 'Reviews', icon: <ChartBarSquareIcon  />, route: 'hr.performance.index' }] : []),
          ...(permissions.includes('training-sessions.view') ? [{ name: 'Training', icon: <AcademicCapIcon  />, route: 'hr.training.index' }] : []),
          ...(permissions.includes('hr.skills.view') ? [{ name: 'Skills', icon: <AcademicCapIcon  />, route: 'hr.skills.index' }] : []),
          ...(permissions.includes('hr.competencies.view') ? [{ name: 'Framework', icon: <ScaleIcon  />, route: 'hr.competencies.index' }] : []),
        ]
      }] : []),
      
      // Benefits & Compensation
      ...(permissions.includes('hr.benefits.view') ? [{
        name: 'Benefits',
        icon: <CreditCardIcon  />,
        category: 'benefits',
        subMenu: [
          { name: 'Plans', icon: <CreditCardIcon  />, route: 'hr.benefits.index' },
          { name: 'Time-off', icon: <CalendarIcon  />, route: 'hr.timeoff.index' },
        ]
      }] : []),
      
      // Workplace Safety & Compliance
      ...((permissions.includes('hr.safety.view') || permissions.includes('hr.safety.incidents.view') || permissions.includes('hr.safety.training.view')) ? [{
        name: 'Safety',
        icon: <ShieldCheckIcon  />,
        category: 'safety',
        subMenu: [
          ...(permissions.includes('hr.safety.view') ? [{ name: 'Dashboard', icon: <ShieldCheckIcon  />, route: 'hr.safety.index' }] : []),
          ...(permissions.includes('hr.safety.incidents.view') ? [{ name: 'Incidents', icon: <DocumentTextIcon  />, route: 'hr.safety.incidents.index' }] : []),
          ...(permissions.includes('hr.safety.training.view') ? [{ name: 'Training', icon: <AcademicCapIcon  />, route: 'hr.safety.training.index' }] : []),
        ]
      }] : []),
      
      // Document Management
      ...(permissions.includes('hr.documents.view') ? [{
        name: 'Docs',
        icon: <DocumentDuplicateIcon  />,
        category: 'documents',
        subMenu: [
          { name: 'Files', icon: <DocumentDuplicateIcon  />, route: 'hr.documents.index' },
          { name: 'Categories', icon: <FolderIcon  />, route: 'hr.documents.categories.index' },
        ]
      }] : []),
      
      // Employee Self-Service
      ...(permissions.includes('hr.selfservice.view') ? [{
        name: 'Self-Service',
        icon: <UserIcon  />,
        category: 'selfservice',
        subMenu: [
          { name: 'Portal', icon: <UserIcon  />, route: 'hr.selfservice.index' },
          { name: 'Profile', icon: <UserIcon  />, route: 'hr.selfservice.profile' },
          { name: 'Access', icon: <DocumentTextIcon  />, route: 'hr.selfservice.documents' },
        ]
      }] : []),
      
      // HR Analytics & Reporting
      ...(permissions.includes('hr.analytics.view') ? [{
        name: 'Analytics',
        icon: <ChartBarSquareIcon  />,
        category: 'analytics',
        subMenu: [
          { name: 'Dashboard', icon: <ChartBarSquareIcon  />, route: 'hr.analytics.index' },
          { name: 'Attendance', icon: <CalendarDaysIcon  />, route: 'hr.analytics.attendance' },
          { name: 'Performance', icon: <ChartBarSquareIcon  />, route: 'hr.analytics.performance' },
          { name: 'Recruitment', icon: <UserGroupIcon  />, route: 'hr.analytics.recruitment' },
          { name: 'Turnover', icon: <ArrowRightOnRectangleIcon  />, route: 'hr.analytics.turnover' },
        ]
      }] : []),
    ]
  }] : []),
  // 4. Projects (Project Management)
  ...(permissions.includes('daily-works.view') ? [{
    name: 'Projects',
    icon: <BriefcaseIcon className="" />,
    priority: 4,
    module: 'ppm',
    subMenu: [
      ...(permissions.includes('daily-works.view') ? [
        { name: 'Worklog', icon: <DocumentTextIcon  />, route: 'daily-works' }
      ] : []),
      ...(permissions.includes('daily-works.view') ? [
        { name: 'Analytics', icon: <ChartBarSquareIcon  />, route: 'daily-works-summary' }
      ] : []),
    ]
  }] : []),
  // 5. DMS (Document Management System)
  ...(permissions.includes('dms.view') ? [{
    name: 'DMS',
    icon: <FolderIcon className="" />,
    priority: 5,
    module: 'dms',
    subMenu: [
      { name: 'Dashboard', icon: <HomeIcon  />, route: 'dms.index' },
      { name: 'Documents', icon: <DocumentTextIcon  />, route: 'dms.documents' },
      { name: 'Upload', icon: <DocumentDuplicateIcon  />, route: 'dms.documents.create' },
      { name: 'Categories', icon: <FolderIcon  />, route: 'dms.categories' },
      { name: 'Shared', icon: <UserGroupIcon  />, route: 'dms.shared' },
      { name: 'Analytics', icon: <ChartBarSquareIcon  />, route: 'dms.analytics' },
      // Legacy document routes
      ...(permissions.includes('letters.view') ? [
        { name: 'Correspondence', icon: <EnvelopeIcon  />, route: 'letters' },
      ] : []),
    ]
  }] : []),
  // 6. CRM (Customer Relationship Management)
  ...(permissions.includes('crm.view') ? [{
    name: 'CRM',
    icon: <UserIcon className="" />,
    priority: 6,
    module: 'crm',
    subMenu: [
      ...(permissions.includes('crm.customers.view') ? [{ name: 'Customers', icon: <UserGroupIcon  />, route: 'crm.customers.index' }] : []),
      ...(permissions.includes('crm.opportunities.view') ? [{ name: 'Opportunities', icon: <BriefcaseIcon  />, route: 'crm.opportunities.index' }] : []),
      ...(permissions.includes('crm.interactions.view') ? [{ name: 'Interactions', icon: <PhoneIcon  />, route: 'crm.interactions.index' }] : []),
      ...(permissions.includes('crm.dashboard.view') ? [{ name: 'Analytics', icon: <ChartBarSquareIcon  />, route: 'crm.dashboard' }] : []),
    ]
  }] : []),
  // 7. FMS (Financial Management System)
  ...(permissions.includes('fms.view') ? [{
    name: 'Finance',
    icon: <BanknotesIcon className="" />,
    priority: 7,
    module: 'fms',
    subMenu: [
      ...(permissions.includes('fms.transactions.view') ? [{ name: 'Transactions', icon: <CreditCardIcon  />, route: 'fms.transactions.index' }] : []),
      ...(permissions.includes('fms.accounts.view') ? [{ name: 'Accounts', icon: <BanknotesIcon  />, route: 'fms.accounts.index' }] : []),
      ...(permissions.includes('fms.reports.view') ? [{ name: 'Reports', icon: <DocumentTextIcon  />, route: 'fms.reports.index' }] : []),
      ...(permissions.includes('fms.budgets.view') ? [{ name: 'Budgets', icon: <ScaleIcon  />, route: 'fms.budgets.index' }] : []),
      ...(permissions.includes('fms.dashboard.view') ? [{ name: 'Dashboard', icon: <ChartBarSquareIcon  />, route: 'fms.dashboard' }] : []),
    ]
  }] : []),
  // 8. POS (Point of Sale)
  ...(permissions.includes('pos.view') ? [{
    name: 'POS',
    icon: <ShoppingCartIcon className="" />,
    priority: 8,
    module: 'pos',
    subMenu: [
      ...(permissions.includes('pos.sales.view') ? [{ name: 'Sales', icon: <ShoppingCartIcon  />, route: 'pos.sales.index' }] : []),
      ...(permissions.includes('pos.orders.view') ? [{ name: 'Orders', icon: <ClipboardDocumentCheckIcon  />, route: 'pos.orders.index' }] : []),
      ...(permissions.includes('pos.cashier.view') ? [{ name: 'Cashier', icon: <CreditCardIcon  />, route: 'pos.cashier' }] : []),
      ...(permissions.includes('pos.dashboard.view') ? [{ name: 'Reports', icon: <ChartBarSquareIcon  />, route: 'pos.dashboard' }] : []),
    ]
  }] : []),
  // 9. IMS (Inventory Management System)
  ...(permissions.includes('inventory.view') ? [{
    name: 'Inventory',
    icon: <ArchiveBoxIcon className="" />,
    priority: 9,
    module: 'ims',
    subMenu: [
      ...(permissions.includes('inventory.view') ? [{ name: 'Dashboard', icon: <HomeIcon  />, route: 'ims.index' }] : []),
      ...(permissions.includes('inventory.view') ? [{ name: 'Products', icon: <CubeIcon  />, route: 'ims.products' }] : []),
      ...(permissions.includes('inventory.view') ? [{ name: 'Warehouse', icon: <BuildingStorefrontIcon  />, route: 'ims.warehouse' }] : []),
      ...(permissions.includes('inventory.view') ? [{ name: 'Stock Movements', icon: <ArrowPathIcon  />, route: 'ims.stock-movements' }] : []),
      ...(permissions.includes('suppliers.view') ? [{ name: 'Suppliers', icon: <TruckIcon  />, route: 'ims.suppliers' }] : []),
      ...(permissions.includes('purchase-orders.view') ? [{ name: 'Purchase Orders', icon: <DocumentTextIcon  />, route: 'ims.purchase-orders' }] : []),
      ...(permissions.includes('inventory.view') ? [{ name: 'Reports', icon: <ChartBarSquareIcon  />, route: 'ims.reports' }] : []),
    ]
  }] : []),
  // 10. LMS (Learning Management System)
  ...(permissions.includes('lms.view') ? [{
    name: 'Learning',
    icon: <AcademicCapIcon className="" />,
    priority: 10,
    module: 'lms',
    subMenu: [
      ...(permissions.includes('lms.view') ? [{ name: 'Dashboard', icon: <HomeIcon  />, route: 'lms.index' }] : []),
      ...(permissions.includes('lms.courses.view') ? [{ name: 'Courses', icon: <AcademicCapIcon  />, route: 'lms.courses' }] : []),
      ...(permissions.includes('lms.students.view') ? [{ name: 'Students', icon: <UserGroupIcon  />, route: 'lms.students' }] : []),
      ...(permissions.includes('lms.instructors.view') ? [{ name: 'Instructors', icon: <UserIcon  />, route: 'lms.instructors' }] : []),
      ...(permissions.includes('lms.assessments.view') ? [{ name: 'Assessments', icon: <ClipboardDocumentCheckIcon  />, route: 'lms.assessments' }] : []),
      ...(permissions.includes('lms.certificates.view') ? [{ name: 'Certificates', icon: <DocumentTextIcon  />, route: 'lms.certificates' }] : []),
      ...(permissions.includes('lms.reports.view') ? [{ name: 'Reports', icon: <ChartBarSquareIcon  />, route: 'lms.reports' }] : []),
    ]
  }] : []),
  // 11. SCM (Supply Chain Management)
  ...(permissions.includes('scm.view') ? [{
    name: 'SCM',
    icon: <TruckIcon className="" />,
    priority: 11,
    module: 'scm',
    subMenu: [
      ...(permissions.includes('scm.suppliers.view') ? [{ name: 'Suppliers', icon: <UserGroupIcon  />, route: 'scm.suppliers.index' }] : []),
      ...(permissions.includes('scm.purchases.view') ? [{ name: 'Purchases', icon: <ShoppingBagIcon  />, route: 'scm.purchases.index' }] : []),
      ...(permissions.includes('scm.logistics.view') ? [{ name: 'Logistics', icon: <TruckIcon  />, route: 'scm.logistics.index' }] : []),
      ...(permissions.includes('scm.dashboard.view') ? [{ name: 'Analytics', icon: <ChartBarSquareIcon  />, route: 'scm.dashboard' }] : []),
    ]
  }] : []),
  // 12. Sales/Retail
  ...(permissions.includes('sales.view') ? [{
    name: 'Sales',
    icon: <ShoppingBagIcon className="" />,
    priority: 12,
    module: 'sales',
    subMenu: [
      ...(permissions.includes('sales.orders.view') ? [{ name: 'Orders', icon: <ClipboardDocumentCheckIcon  />, route: 'sales.orders.index' }] : []),
      ...(permissions.includes('sales.invoices.view') ? [{ name: 'Invoices', icon: <DocumentTextIcon  />, route: 'sales.invoices.index' }] : []),
      ...(permissions.includes('sales.quotes.view') ? [{ name: 'Quotes', icon: <DocumentDuplicateIcon  />, route: 'sales.quotes.index' }] : []),
      ...(permissions.includes('sales.dashboard.view') ? [{ name: 'Analytics', icon: <ChartBarSquareIcon  />, route: 'sales.dashboard' }] : []),
    ]
  }] : []),
  // 13. Helpdesk
  ...(permissions.includes('helpdesk.view') ? [{
    name: 'Helpdesk',
    icon: <TicketIcon className="" />,
    priority: 13,
    module: 'helpdesk',
    subMenu: [
      ...(permissions.includes('helpdesk.tickets.view') ? [{ name: 'Tickets', icon: <TicketIcon  />, route: 'helpdesk.tickets.index' }] : []),
      ...(permissions.includes('helpdesk.knowledge.view') ? [{ name: 'KB', icon: <FolderIcon  />, route: 'helpdesk.knowledge.index' }] : []),
      ...(permissions.includes('helpdesk.dashboard.view') ? [{ name: 'Analytics', icon: <ChartBarSquareIcon  />, route: 'helpdesk.dashboard' }] : []),
    ]
  }] : []),
  // 14. Assets
  ...(permissions.includes('assets.view') ? [{
    name: 'Assets',
    icon: <ComputerDesktopIcon className="" />,
    priority: 14,
    module: 'assets',
    subMenu: [
      ...(permissions.includes('assets.items.view') ? [{ name: 'Items', icon: <ComputerDesktopIcon  />, route: 'assets.items.index' }] : []),
      ...(permissions.includes('assets.maintenance.view') ? [{ name: 'Maintenance', icon: <WrenchScrewdriverIcon  />, route: 'assets.maintenance.index' }] : []),
      ...(permissions.includes('assets.dashboard.view') ? [{ name: 'Reports', icon: <ChartBarSquareIcon  />, route: 'assets.dashboard' }] : []),
    ]
  }] : []),
  // 15. Compliance
  ...(permissions.includes('compliance.view') ? [{
    name: 'Compliance',
    icon: <ShieldCheckIcon className="" />,
    priority: 15,
    module: 'compliance',
    subMenu: [
      ...(permissions.includes('compliance.dashboard.view') ? [{ name: 'Dashboard', icon: <ChartBarSquareIcon  />, route: 'compliance.dashboard' }] : []),
      ...(permissions.includes('compliance.policies.view') ? [{ name: 'Policies', icon: <DocumentTextIcon  />, route: 'compliance.policies.index' }] : []),
      ...(permissions.includes('compliance.regulatory_requirements.view') ? [{ name: 'Regulatory Requirements', icon: <ScaleIcon  />, route: 'compliance.regulatory-requirements.index' }] : []),
      ...(permissions.includes('compliance.risks.view') ? [{ name: 'Risk Assessments', icon: <ShieldCheckIcon  />, route: 'compliance.risks.index' }] : []),
      ...(permissions.includes('compliance.audits.view') ? [{ name: 'Audits', icon: <ClipboardDocumentCheckIcon  />, route: 'compliance.audits.index' }] : []),
      ...(permissions.includes('compliance.training_records.view') ? [{ name: 'Training Records', icon: <AcademicCapIcon  />, route: 'compliance.training-records.index' }] : []),
      ...(permissions.includes('compliance.controlled_documents.view') ? [{ name: 'Controlled Documents', icon: <DocumentDuplicateIcon  />, route: 'compliance.controlled-documents.index' }] : []),
    ]
  }] : []),
  // 16. Procurement
  ...(permissions.includes('procurement.view') ? [{
    name: 'Procurement',
    icon: <ShoppingBagIcon className="" />,
    priority: 16,
    module: 'procurement',
    subMenu: [
      ...(permissions.includes('procurement.purchase-orders.view') ? [{ name: 'POs', icon: <ShoppingCartIcon  />, route: 'procurement.purchase-orders.index' }] : []),
      ...(permissions.includes('procurement.vendors.view') ? [{ name: 'Vendors', icon: <UserGroupIcon  />, route: 'procurement.vendors.index' }] : []),
      ...(permissions.includes('procurement.rfq.view') ? [{ name: 'RFQs', icon: <DocumentDuplicateIcon  />, route: 'procurement.rfq.index' }] : []),
      ...(permissions.includes('procurement.dashboard.view') ? [{ name: 'Analytics', icon: <ChartBarSquareIcon  />, route: 'procurement.dashboard' }] : []),
    ]
  }] : []),
  // 17. Quality Management
  ...(permissions.includes('quality.view') ? [{
    name: 'Quality',
    icon: <BeakerIcon className="" />,
    priority: 17,
    module: 'quality',
    subMenu: [
      ...(permissions.includes('quality.inspections.view') ? [{ name: 'Inspections', icon: <ClipboardDocumentCheckIcon  />, route: 'quality.inspections.index' }] : []),
      ...(permissions.includes('quality.ncr.view') ? [{ name: 'NCRs', icon: <DocumentTextIcon  />, route: 'quality.ncrs.index' }] : []),
      ...(permissions.includes('quality.calibrations.view') ? [{ name: 'Calibrations', icon: <ScaleIcon  />, route: 'quality.calibrations.index' }] : []),
      ...(permissions.includes('quality.dashboard.view') ? [{ name: 'Analytics', icon: <ChartBarSquareIcon  />, route: 'quality.dashboard' }] : []),
    ]
  }] : []),
  // 18. Analytics
  ...(permissions.includes('analytics.view') ? [{
    name: 'Analytics',
    icon: <ChartBarSquareIcon className="" />,
    priority: 18,
    module: 'analytics',
    subMenu: [
      ...(permissions.includes('analytics.reports.view') ? [{ name: 'Reports', icon: <DocumentTextIcon  />, route: 'analytics.reports.index' }] : []),
      ...(permissions.includes('analytics.dashboards.view') ? [{ name: 'Dashboards', icon: <ChartBarSquareIcon  />, route: 'analytics.dashboards.index' }] : []),
      ...(permissions.includes('analytics.kpi.view') ? [{ name: 'KPIs', icon: <ChartBarSquareIcon  />, route: 'analytics.kpi.index' }] : []),
    ]
  }] : []),
  // 19. Admin (System Administration)
  ...(permissions.includes('users.view') || permissions.includes('settings.view') || permissions.includes('roles.view') ? [{
    name: 'Admin',
    icon: <Cog6ToothIcon className="" />,
    priority: 19,
    module: 'admin',
    subMenu: [
      ...(permissions.includes('users.view') ? [{ name: 'Users', icon: <UsersIcon  />, route: 'users' }] : []),
      ...(permissions.includes('roles.view') ? [
        { name: 'Roles', icon: <UserGroupIcon  />, route: 'admin.roles-management' }
      ] : []),
      ...(auth?.user && auth?.roles?.includes('Super Administrator') ? [
        { name: 'Monitoring', icon: <ComputerDesktopIcon  />, route: 'admin.system-monitoring' }
      ] : []),
      ...(permissions.includes('settings.view') ? [{ name: 'Settings', icon: <Cog6ToothIcon  />, route: 'admin.settings.company' }] : []),
    ]
  }] : []),
];

// Utility functions for navigation management

// Get pages by module for better organization
export const getPagesByModule = (permissions) => {
  const pages = getPages(permissions);
  const modules = {};
  
  pages.forEach(page => {
    const module = page.module || 'core';
    if (!modules[module]) {
      modules[module] = [];
    }
    modules[module].push(page);
  });
  
  return modules;
};

// Get pages sorted by priority
export const getPagesByPriority = (permissions) => {
  return getPages(permissions).sort((a, b) => (a.priority || 999) - (b.priority || 999));
};

// Get navigation breadcrumb path
export const getNavigationPath = (currentRoute, permissions) => {
  const pages = getPages(permissions);
  const path = [];
  // Find the current page in the navigation structure
  const findPageInMenu = (menuItems, targetRoute, currentPath = []) => {
    for (const item of menuItems) {
      const newPath = [...currentPath, item];
      if (item.route === targetRoute) {
        return newPath;
      }
      if (item.subMenu) {
        const result = findPageInMenu(item.subMenu, targetRoute, newPath);
        if (result) return result;
      }
    }
    return null;
  };
  return findPageInMenu(pages, currentRoute) || [];
};
