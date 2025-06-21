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
  FolderOpenIcon,
  ChartBarIcon,
  CreditCardIcon,
  ShoppingBagIcon,
  BuildingOffice2Icon,
  BanknotesIcon,
  WrenchScrewdriverIcon,
  ClipboardDocumentCheckIcon,
  DocumentDuplicateIcon,
  ShieldCheckIcon
} from '@heroicons/react/24/outline';

export const getPages = (permissions) => [
  // 1. Dashboard & Analytics (ISO 9000 - Information Management)
  ...(permissions.includes('dashboard.view') ? [{
    name: 'Dashboard', 
    icon: <HomeIcon className="h-6 w-6" />, 
    route: 'dashboard',
    priority: 1,
    module: 'core'
  }] : []),
  
  // 2. Workspace & Self-Service (ISO 27001 - Personal Information Management)
  ...((permissions.includes('attendance.own.view') || permissions.includes('leave.own.view') || permissions.includes('communications.own.view')) ? [{
    name: 'My Workspace',
    icon: <UserGroupIcon className="h-6 w-6" />,
    priority: 2,
    module: 'self-service',
    subMenu: [
      ...(permissions.includes('attendance.own.view') ? [
        { name: 'My Attendance', icon: <CalendarDaysIcon className="ml-2 h-5 w-5" />, route: 'attendance-employee' }
      ] : []),
      ...(permissions.includes('leave.own.view') ? [
        { name: 'My Leave Requests', icon: <ArrowRightOnRectangleIcon className="ml-2 h-5 w-5" />, route: 'leaves-employee' }
      ] : []),
      ...(permissions.includes('communications.own.view') ? [
        { name: 'My Communications', icon: <EnvelopeIcon className="ml-2 h-5 w-5" />, route: 'emails' },
      ] : []),
    ]
  }] : []),// 3. Human Resource Management (ISO 30414 - Human Capital Reporting)
  ...(permissions.includes('employees.view') ? [{
    name: 'Human Resource Management',
    icon: <UserGroupIcon className="h-6 w-6" />,
    priority: 3,
    module: 'hrm',
    subMenu: [
      ...(permissions.includes('employees.view') ? [{ name: 'Employee Management', icon: <UserGroupIcon className="ml-2 h-5 w-5" />, route: 'employees' }] : []),
      ...(permissions.includes('departments.view') ? [{ name: 'Organizational Structure', icon: <HomeIcon className="ml-2 h-5 w-5" />, route: 'departments' }] : []),
      ...(permissions.includes('designations.view') ? [{ name: 'Position Management', icon: <BriefcaseIcon className="ml-2 h-5 w-5" />, route: 'designations' }] : []),
      ...(permissions.includes('attendance.view') ? [{ name: 'Time & Attendance', icon: <CalendarDaysIcon className="ml-2 h-5 w-5" />, route: 'attendances' }] : []),
      ...(permissions.includes('holidays.view') ? [{ name: 'Calendar Management', icon: <CalendarIcon className="ml-2 h-5 w-5" />, route: 'holidays' }] : []),
      ...(permissions.includes('leaves.view') ? [
        { name: 'Leave Management', icon: <ArrowRightOnRectangleIcon className="ml-2 h-5 w-5" />, route: 'leaves' },
        { name: 'Leave Analytics', icon: <ChartBarIcon className="ml-2 h-5 w-5" />, route: 'leave-summary' },
        { name: 'Leave Policy Configuration', icon: <Cog6ToothIcon className="ml-2 h-5 w-5" />, route: 'leave-settings' },
      ] : []),
    ]
  }] : []),
  // 4. Project & Portfolio Management (ISO 21500 - Project Management)
  ...(permissions.includes('daily-works.view') ? [{
    name: 'Project & Portfolio Management',
    icon: <BriefcaseIcon className="h-6 w-6" />,
    priority: 4,
    module: 'ppm',
    subMenu: [
      ...(permissions.includes('daily-works.view') ? [
        { name: 'Work Log Management', icon: <DocumentTextIcon className="ml-2 h-5 w-5" />, route: 'daily-works' }
      ] : []),
      ...(permissions.includes('daily-works.view') ? [
        { name: 'Project Analytics', icon: <ChartBarIcon className="ml-2 h-5 w-5" />, route: 'daily-works-summary' }
      ] : []),
      // Note: Project planner route not implemented yet
      // ...(permissions.includes('projects.view') ? [
      //   { name: 'Project Planning', icon: <CalendarIcon className="ml-2 h-5 w-5" />, route: 'project-planner' }
      // ] : [])
    ]
  }] : []),

  // 5. Customer Relationship Management (ISO 27500 - Customer Experience Management)  // Note: CRM routes not implemented yet - placeholder for future development
  // ...(permissions.includes('customers.view') ? [{
  //   name: 'Customer Relationship Management',
  //   icon: <UsersIcon className="h-6 w-6" />,
  //   priority: 5,
  //   module: 'crm',
  //   subMenu: [
  //     { name: 'Customer Directory', icon: <UserGroupIcon className="ml-2 h-5 w-5" />, route: 'customers' },
  //     { name: 'Lead & Opportunity Management', icon: <DocumentTextIcon className="ml-2 h-5 w-5" />, route: 'leads' },
  //     { name: 'Customer Experience Management', icon: <ClipboardDocumentCheckIcon className="ml-2 h-5 w-5" />, route: 'feedback' },
  //   ]
  // }] : []),

  // // 6. Supply Chain & Inventory Management (ISO 28000 - Supply Chain Security)  // Note: Inventory routes not implemented yet - placeholder for future development
  // ...(permissions.includes('inventory.view') ? [{
  //   name: 'Supply Chain & Inventory Management',
  //   icon: <FolderOpenIcon className="h-6 w-6" />,
  //   priority: 6,
  //   module: 'scm',
  //   subMenu: [
  //     { name: 'Inventory Control', icon: <ClipboardDocumentCheckIcon className="ml-2 h-5 w-5" />, route: 'inventory-overview' },
  //     { name: 'Supplier Management', icon: <BuildingOffice2Icon className="ml-2 h-5 w-5" />, route: 'suppliers' },
  //     { name: 'Procurement Management', icon: <DocumentDuplicateIcon className="ml-2 h-5 w-5" />, route: 'purchase-orders' },
  //     { name: 'Warehouse Operations', icon: <WrenchScrewdriverIcon className="ml-2 h-5 w-5" />, route: 'warehousing' }
  //   ]
  // }] : []),

  // // 7. Point of Sale & Retail Operations (ISO 12912 - Financial Services)  // Note: POS routes not implemented yet - placeholder for future development
  // ...(permissions.includes('pos.view') ? [{
  //   name: 'Retail & Sales Operations',
  //   icon: <ShoppingBagIcon className="h-6 w-6" />,
  //   priority: 7,
  //   module: 'retail',
  //   subMenu: [
  //     { name: 'Point of Sale Terminal', icon: <CreditCardIcon className="ml-2 h-5 w-5" />, route: 'sales-terminal' },
  //     { name: 'Sales Transaction History', icon: <ChartBarIcon className="ml-2 h-5 w-5" />, route: 'sales-history' }
  //   ]
  // }] : []),
  // // 8. Financial Management & Accounting (ISO 19011 - Financial Management)  // Note: Finance routes not implemented yet - placeholder for future development
  // ...(permissions.includes('financial-reports.view') ? [{
  //   name: 'Financial Management & Accounting',
  //   icon: <BanknotesIcon className="h-6 w-6" />,
  //   priority: 8,
  //   module: 'finance',
  //   subMenu: [
  //     { name: 'Payables Management', icon: <ArrowRightOnRectangleIcon className="ml-2 h-5 w-5" />, route: 'accounts-payable' },
  //     { name: 'Receivables Management', icon: <ArrowRightOnRectangleIcon className="ml-2 h-5 w-5" />, route: 'accounts-receivable' },
  //     { name: 'General Ledger', icon: <DocumentTextIcon className="ml-2 h-5 w-5" />, route: 'ledger' },
  //     { name: 'Financial Reporting & Analytics', icon: <ChartBarIcon className="ml-2 h-5 w-5" />, route: 'financial-reports' }
  //   ]
  // }] : []),
  // 9. Document & Knowledge Management (ISO 15489 - Records Management)
  ...(permissions.includes('letters.view') ? [{
    name: 'Document & Knowledge Management',
    icon: <FolderOpenIcon className="h-6 w-6" />,
    priority: 9,
    module: 'dms',
    subMenu: [
      { name: 'Official Correspondence', icon: <EnvelopeIcon className="ml-2 h-5 w-5" />, route: 'letters' },
    ]
  }] : []),  // 10. System Administration & Governance (ISO 38500 - IT Governance)
  ...(permissions.includes('users.view') || permissions.includes('settings.view') || permissions.includes('roles.view') ? [{
    name: 'System Administration & Governance',
    icon: <Cog6ToothIcon className="h-6 w-6" />,
    priority: 10,
    module: 'admin',
    subMenu: [
      // Note: Admin dashboard routes not implemented yet
      // ...(permissions.includes('dashboard.view') ? [{ name: 'Executive Dashboard', icon: <HomeIcon className="ml-2 h-5 w-5" />, route: 'admin-dashboard' }] : []),
      // ...(permissions.includes('dashboard.view') ? [{ name: 'Performance Intelligence', icon: <ChartBarIcon className="ml-2 h-5 w-5" />, route: 'performance-dashboard' }] : []),
      ...(permissions.includes('users.view') ? [{ name: 'User Account Management', icon: <UsersIcon className="ml-2 h-5 w-5" />, route: 'users' }] : []),
      ...(permissions.includes('roles.view') ? [
        { name: 'Role & Permission Management', icon: <UserGroupIcon className="ml-2 h-5 w-5" />, route: 'admin.roles-management' }
      ] : []),
      ...(permissions.includes('settings.view') ? [{ name: 'System Configuration', icon: <Cog6ToothIcon className="ml-2 h-5 w-5" />, route: 'admin.settings.company' }] : []),
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

// Check if user has access to a specific page
export const hasPageAccess = (pageName, permissions) => {
  const pages = getPages(permissions);
  return pages.some(page => 
    page.name === pageName || 
    (page.subMenu && page.subMenu.some(subItem => subItem.name === pageName))
  );
};
