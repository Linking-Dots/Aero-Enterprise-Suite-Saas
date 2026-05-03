# aeos365 SaaS - AI Agent Instructions

## Project Overview
This is a **multi-tenant, multi-module SaaS ERP system** built with Laravel 11 + Inertia.js v2 + React 18 + Tailwind CSS v4 + HeroUI. The application supports multiple tenant isolation strategies (subdomain-based) with a modular architecture where features are gradually migrated, extended, or newly developed.

**Key Principle:** When a feature exists, **merge or extend** it instead of duplicating. Always integrate cleanly with the current architecture.

---

## CRITICAL: Monorepo Development Rules

### All Code Changes MUST Be in Packages
This is a **monorepo architecture**. The host applications (`apps/saas-host`, `apps/standalone-host`, or external apps like `aeos365`) are thin wrappers that consume packages via Composer symlinks.

**DO:**
- ✅ Make ALL code changes in `packages/aero-*` directories
- ✅ Update migrations, models, controllers, views in packages
- ✅ Add new features to the appropriate package

**DO NOT:**
- ❌ Never modify code in `app/`, `resources/`, `routes/` of host apps
- ❌ Never create new files in host app directories
- ❌ Never copy package code into host apps

### Package Structure
```
packages/
├── aero-core/          # Core functionality (auth, users, base models, middleware)
├── aero-platform/      # SaaS platform (tenancy, billing, subscriptions)
├── aero-hrm/           # Human Resource Management module
├── aero-crm/           # Customer Relationship Management module
├── aero-finance/       # Finance & Accounting module
├── aero-project/       # Project Management module
├── aero-ims/           # Inventory Management module
├── aero-pos/           # Point of Sale module
├── aero-scm/           # Supply Chain Management module
├── aero-quality/       # Quality Management module
├── aero-dms/           # Document Management module
└── aero-compliance/    # Compliance Management module
```

### Host App Configuration Only
Host apps should ONLY contain:
- `.env` file (environment-specific configuration)
- `composer.json` (package dependencies with path repositories)
- `vite.config.js` / build configuration
- `public/` directory for compiled assets
- Database files if using SQLite for testing

---

## Architecture Overview

### Multi-Tenancy Structure
- **Central Database (`eos365`):** Stores `tenants`, `domains`, `plans`, `subscriptions`, `landlord_users`, `platform_settings`
- **Tenant Databases:** Each tenant has isolated database (`tenant{id}`) with full user/module data
- **Domain Resolution:** Uses `stancl/tenancy` with subdomain identification (`{tenant}.domain.com`)
- **Two Auth Guards:** `landlord` (platform admin) and `web` (tenant users)

### Package Directory Structure
Each package follows this structure:
```
packages/aero-{module}/
├── composer.json           # Package metadata & dependencies
├── config/                 # Package configuration files
├── database/
│   ├── migrations/         # Database migrations
│   ├── factories/          # Model factories
│   └── seeders/            # Database seeders
├── resources/
│   ├── js/                 # React components & pages
│   │   ├── Pages/          # Inertia pages
│   │   ├── Components/     # Reusable components
│   │   └── Forms/          # Form components
│   └── views/              # Blade templates (if needed)
├── routes/
│   ├── web.php             # Web routes
│   ├── api.php             # API routes
│   ├── tenant.php          # Tenant-scoped routes (SaaS mode)
│   └── admin.php           # Admin routes (landlord)
├── src/
│   ├── Http/
│   │   ├── Controllers/    # Controllers
│   │   ├── Middleware/     # Middleware
│   │   └── Requests/       # Form requests
│   ├── Models/             # Eloquent models
│   ├── Providers/          # Service providers
│   ├── Services/           # Business logic services
│   └── Policies/           # Authorization policies
└── tests/                  # Package tests
```

### Module System
Modules are defined in `config/modules.php` with hierarchy: **modules → submodules → components → actions**. Access is controlled by: `Plan Access (subscription) ∩ Permission Match (RBAC)`.

---

## Frontend UI/UX Standards (CRITICAL)

### Component Library
- **Use HeroUI components exclusively** (`@heroui/react`): `Table`, `Button`, `Card`, `Modal`, `Input`, `Select`, `Chip`, `Tooltip`, `Dropdown`, `Pagination`, `Spinner`, `Switch`, `Badge`, `Skeleton`
- **Icons:** Use `@heroicons/react/24/outline` consistently

### Card Styling Pattern (CRITICAL)
All HeroUI Card components **MUST** use the themed card styling for consistency across the application. Use one of these approaches:

**Option 1: Use ThemedCard component (Recommended)**
```jsx
import { ThemedCard, ThemedCardHeader, ThemedCardBody } from '@/Components/UI/ThemedCard';

<Card>
  <ThemedCardHeader>Title</ThemedCardHeader>
  <ThemedCardBody>Content</ThemedCardBody>
</Card>
```

**Option 2: Use getThemedCardStyle utility**
```jsx
import { getThemedCardStyle } from '@/Components/UI/ThemedCard';

<Card className="transition-all duration-200" style={getThemedCardStyle()}>
  <CardHeader className="border-b border-divider p-4">Title</CardHeader>
  <CardBody className="p-4">Content</CardBody>
</Card>
```

**Option 3: Use CSS class (for simple cases)**
```jsx
<Card className="aero-card">
  <CardBody>Content</CardBody>
</Card>
```

The themed card style applies:
- Theme-aware border, radius, and font from CSS variables
- Gradient background using `--theme-content1/2/3` colors
- Dark mode support
- Smooth transitions

### Reference Components for UI Patterns
When creating new pages, **study and follow these existing patterns exactly:**

| Pattern | Reference Files |
|---------|-----------------|
| **Full Page Layout** | `packages/aero-ui/resources/js/Pages/HRM/LeavesAdmin.jsx` (**PRIMARY PAGE REFERENCE**) |
| **Themed Cards** | `packages/aero-ui/resources/js/Components/UI/ThemedCard.jsx` (**primary card reference**) |
| List pages with tables | `resources/js/Tenant/Pages/UsersList.jsx`, `resources/js/Tables/UsersTable.jsx` |
| Employee list with filters | `resources/js/Tenant/Pages/Employees/EmployeeList.jsx` (**primary filter reference**) |
| Data tables | `resources/js/Tables/EmployeeTable.jsx`, `resources/js/Tables/TimeSheetTable.jsx` |
| Stats/Dashboard cards | `resources/js/Components/StatsCards.jsx` |
| Page headers | `resources/js/Components/PageHeader.jsx` |
| Add/Edit User forms | `resources/js/Forms/AddEditUserForm.jsx` (modals, validation, precognition) |
| Invite User forms | `resources/js/Forms/InviteUserForm.jsx` (email invite modal) |
| Leave forms | `resources/js/Forms/LeaveForm.jsx` (date pickers, selectors) |
| Bulk operations | `resources/js/Components/BulkLeave/BulkLeaveModal.jsx` (multi-select, calendars) |
| Modals | `resources/js/Components/EnhancedModal.jsx` |
| Employee/Department Selector | `resources/js/Components/DepartmentEmployeeSelector.jsx` |

### Full Page Layout Pattern (CRITICAL - Follow LeavesAdmin.jsx)
**All admin/management pages MUST follow this exact structure from `Pages/HRM/LeavesAdmin.jsx`:**

```jsx
import React, { useCallback, useEffect, useMemo, useState } from 'react';
import { Head, usePage } from '@inertiajs/react';
import { motion } from 'framer-motion';
import { Button, Card, CardBody, CardHeader, Input, Select, SelectItem } from "@heroui/react";
import { PlusIcon, MagnifyingGlassIcon } from "@heroicons/react/24/outline";
import App from '@/Layouts/App.jsx';
import StatsCards from '@/Components/StatsCards.jsx';
import axios from 'axios';
import { showToast } from '@/utils/toastUtils.jsx';

const PageName = ({ title }) => {
    const { auth } = usePage().props;
    
    // 1. Theme radius helper (REQUIRED)
    const getThemeRadius = () => {
        if (typeof window === 'undefined') return 'lg';
        const rootStyles = getComputedStyle(document.documentElement);
        const borderRadius = rootStyles.getPropertyValue('--borderRadius')?.trim() || '12px';
        const radiusValue = parseInt(borderRadius);
        if (radiusValue === 0) return 'none';
        if (radiusValue <= 4) return 'sm';
        if (radiusValue <= 8) return 'md';
        if (radiusValue <= 16) return 'lg';
        return 'full';
    };
    
    // 2. Responsive breakpoints (REQUIRED)
    const [isMobile, setIsMobile] = useState(false);
    const [isTablet, setIsTablet] = useState(false);
    
    useEffect(() => {
        const checkScreenSize = () => {
            setIsMobile(window.innerWidth < 640);
            setIsTablet(window.innerWidth < 768);
        };
        checkScreenSize();
        window.addEventListener('resize', checkScreenSize);
        return () => window.removeEventListener('resize', checkScreenSize);
    }, []);

    // 3. State management
    const [loading, setLoading] = useState(false);
    const [data, setData] = useState([]);
    const [filters, setFilters] = useState({ search: '', status: [] });
    const [pagination, setPagination] = useState({ perPage: 30, currentPage: 1 });
    const [stats, setStats] = useState({ total: 0, active: 0, pending: 0 });
    const [modalStates, setModalStates] = useState({ add: false, edit: false, delete: false });

    // 4. Stats data for StatsCards component (REQUIRED)
    const statsData = useMemo(() => [
        { title: "Total", value: stats.total, icon: <DocumentIcon />, color: "text-primary", iconBg: "bg-primary/20" },
        { title: "Active", value: stats.active, icon: <CheckIcon />, color: "text-success", iconBg: "bg-success/20" },
        // ... more stats
    ], [stats]);

    // 5. Permission checks (REQUIRED)
    const canCreate = auth.permissions?.includes('resource.create') || false;
    const canEdit = auth.permissions?.includes('resource.update') || false;

    // 6. Data fetching with axios
    const fetchData = useCallback(async () => {
        setLoading(true);
        try {
            const response = await axios.get(route('resource.paginate'), {
                params: { page: pagination.currentPage, perPage: pagination.perPage, ...filters }
            });
            if (response.status === 200) {
                setData(response.data.items);
            }
        } catch (error) {
            showToast.promise(Promise.reject(error), { error: 'Failed to fetch data' });
        } finally {
            setLoading(false);
        }
    }, [filters, pagination]);

    useEffect(() => { fetchData(); }, [fetchData]);

    // RENDER STRUCTURE (CRITICAL - Follow exactly)
    return (
        <>
            <Head title={title} />
            
            {/* Modals go BEFORE main content */}
            {modalStates.add && <AddModal open={modalStates.add} onClose={() => closeModal('add')} />}
            {modalStates.edit && <EditModal open={modalStates.edit} onClose={() => closeModal('edit')} />}
            
            {/* Main content wrapper */}
            <div className="flex flex-col w-full h-full p-4" role="main" aria-label="Resource Management">
                <div className="space-y-4">
                    <div className="w-full">
                        {/* Animated Card wrapper */}
                        <motion.div
                            initial={{ scale: 0.9, opacity: 0 }}
                            animate={{ scale: 1, opacity: 1 }}
                            transition={{ duration: 0.5 }}
                        >
                            {/* Main Card with theme styling */}
                            <Card 
                                className="transition-all duration-200"
                                style={{
                                    border: `var(--borderWidth, 2px) solid transparent`,
                                    borderRadius: `var(--borderRadius, 12px)`,
                                    fontFamily: `var(--fontFamily, "Inter")`,
                                    transform: `scale(var(--scale, 1))`,
                                    background: `linear-gradient(135deg, 
                                        var(--theme-content1, #FAFAFA) 20%, 
                                        var(--theme-content2, #F4F4F5) 10%, 
                                        var(--theme-content3, #F1F3F4) 20%)`,
                                }}
                            >
                                {/* Card Header with title + action buttons */}
                                <CardHeader 
                                    className="border-b p-0"
                                    style={{
                                        borderColor: `var(--theme-divider, #E4E4E7)`,
                                        background: `linear-gradient(135deg, 
                                            color-mix(in srgb, var(--theme-content1) 50%, transparent) 20%, 
                                            color-mix(in srgb, var(--theme-content2) 30%, transparent) 10%)`,
                                    }}
                                >
                                    <div className={`${!isMobile ? 'p-6' : 'p-4'} w-full`}>
                                        <div className="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                                            {/* Title Section with icon */}
                                            <div className="flex items-center gap-3 lg:gap-4">
                                                <div className={`${!isMobile ? 'p-3' : 'p-2'} rounded-xl`}
                                                    style={{
                                                        background: `color-mix(in srgb, var(--theme-primary) 15%, transparent)`,
                                                        borderRadius: `var(--borderRadius, 12px)`,
                                                    }}
                                                >
                                                    <PageIcon className={`${!isMobile ? 'w-8 h-8' : 'w-6 h-6'}`} 
                                                        style={{ color: 'var(--theme-primary)' }} />
                                                </div>
                                                <div>
                                                    <h4 className={`${!isMobile ? 'text-2xl' : 'text-xl'} font-bold`}>
                                                        Page Title
                                                    </h4>
                                                    <p className={`${!isMobile ? 'text-sm' : 'text-xs'} text-default-500`}>
                                                        Page description
                                                    </p>
                                                </div>
                                            </div>
                                            
                                            {/* Action Buttons */}
                                            <div className="flex gap-2 flex-wrap">
                                                {canCreate && (
                                                    <Button color="primary" variant="shadow"
                                                        startContent={<PlusIcon className="w-4 h-4" />}
                                                        onPress={() => openModal('add')}
                                                        size={isMobile ? "sm" : "md"}
                                                    >
                                                        Add New
                                                    </Button>
                                                )}
                                            </div>
                                        </div>
                                    </div>
                                </CardHeader>

                                <CardBody className="p-6">
                                    {/* 1. Stats Cards (REQUIRED at top) */}
                                    <StatsCards stats={statsData} className="mb-6" />
                                    
                                    {/* 2. Filter Section */}
                                    <div className="flex flex-col sm:flex-row gap-4 mb-6">
                                        <Input
                                            label="Search"
                                            placeholder="Search..."
                                            value={filters.search}
                                            onChange={(e) => handleFilterChange('search', e.target.value)}
                                            startContent={<MagnifyingGlassIcon className="w-4 h-4" />}
                                            variant="bordered"
                                            size="sm"
                                            radius={getThemeRadius()}
                                        />
                                        {/* Additional filters... */}
                                    </div>
                                    
                                    {/* 3. Data Table */}
                                    <DataTable 
                                        data={data}
                                        loading={loading}
                                        onEdit={handleEdit}
                                        onDelete={handleDelete}
                                    />
                                    
                                    {/* 4. Pagination */}
                                    <Pagination ... />
                                </CardBody>
                            </Card>
                        </motion.div>
                    </div>
                </div>
            </div>
        </>
    );
};

// REQUIRED: Use App layout wrapper
PageName.layout = (page) => <App children={page} />;
export default PageName;
```

**Key Layout Rules:**
1. **Modals BEFORE main content** - All modal components render before the main `<div>`
2. **Single Card wrapper** - Entire page content in one themed Card
3. **CardHeader = Title + Actions** - Icon, title, description on left; buttons on right
4. **CardBody order**: StatsCards → Filters → Table → Pagination
5. **Use motion.div** for entry animation on the main Card
6. **Theme-aware styling** - Use CSS variables for all colors/borders/fonts
7. **Responsive classes** - Use `isMobile` state for size adjustments
8. **Permission guards** - Check permissions before rendering action buttons

### Modal Pattern (CRITICAL)
Always use HeroUI's Modal components with consistent structure:
```jsx
<Modal 
  isOpen={open} 
  onOpenChange={closeModal}
  size="2xl"
  scrollBehavior="inside"
  classNames={{
    base: "bg-content1",
    header: "border-b border-divider",
    body: "py-6",
    footer: "border-t border-divider"
  }}
>
  <ModalContent>
    <ModalHeader className="flex flex-col gap-1">
      <h2 className="text-lg font-semibold">Title</h2>
    </ModalHeader>
    <ModalBody>
      {/* Form content */}
    </ModalBody>
    <ModalFooter>
      <Button variant="flat" onPress={closeModal}>Cancel</Button>
      <Button color="primary" onPress={handleSubmit}>Submit</Button>
    </ModalFooter>
  </ModalContent>
</Modal>
```

### Filter Section Pattern (Follow EmployeeList.jsx)
```jsx
// Filter bar structure - always include search + dropdowns
<div className="flex flex-col sm:flex-row gap-3">
  {/* Search Input */}
  <Input
    placeholder="Search..."
    value={filters.search}
    onValueChange={handleSearchChange}
    startContent={<MagnifyingGlassIcon className="w-4 h-4 text-default-400" />}
    classNames={{
      inputWrapper: "bg-default-100"
    }}
  />
  
  {/* Department Filter */}
  <Select
    placeholder="All Departments"
    selectedKeys={filters.department !== 'all' ? [filters.department] : []}
    onSelectionChange={(keys) => handleDepartmentFilterChange(Array.from(keys)[0] || 'all')}
    classNames={{ trigger: "bg-default-100" }}
  >
    <SelectItem key="all">All Departments</SelectItem>
    {departments.map(dept => (
      <SelectItem key={dept.id}>{dept.name}</SelectItem>
    ))}
  </Select>
  
  {/* Additional filters following same pattern */}
</div>
```

### Form Input Pattern (Follow AddEditUserForm.jsx)
```jsx
// Form inputs with validation and theme radius
<Input
  label="Email"
  placeholder="Enter email"
  value={form.data.email}
  onValueChange={(value) => form.setData('email', value)}
  onBlur={() => form.validate('email')}
  isInvalid={!!form.errors.email}
  errorMessage={form.errors.email}
  isRequired
  radius={themeRadius}
  classNames={{
    inputWrapper: "bg-default-100"
  }}
/>

// Select with department/designation cascade
<Select
  label="Department"
  placeholder="Select department"
  selectedKeys={form.data.department_id ? [String(form.data.department_id)] : []}
  onSelectionChange={(keys) => handleChange('department_id', Array.from(keys)[0])}
  isInvalid={!!form.errors.department_id}
  errorMessage={form.errors.department_id}
  radius={themeRadius}
>
  {departments?.map(dept => (
    <SelectItem key={String(dept.id)}>{dept.name}</SelectItem>
  ))}
</Select>
```

### Dropdown Actions Pattern
```jsx
<Dropdown>
  <DropdownTrigger>
    <Button isIconOnly size="sm" variant="light">
      <EllipsisVerticalIcon className="w-5 h-5" />
    </Button>
  </DropdownTrigger>
  <DropdownMenu aria-label="Actions">
    <DropdownItem key="edit" startContent={<PencilIcon className="w-4 h-4" />}>
      Edit
    </DropdownItem>
    <DropdownItem key="delete" className="text-danger" color="danger" startContent={<TrashIcon className="w-4 h-4" />}>
      Delete
    </DropdownItem>
  </DropdownMenu>
</Dropdown>
```

### Theme System
```javascript
// Always use theme-aware styling via CSS variables:
const getCardStyle = () => ({
  background: `var(--theme-content1, #FAFAFA)`,
  borderColor: `var(--theme-divider, #E4E4E7)`,
  borderWidth: `var(--borderWidth, 2px)`,
  borderRadius: `var(--borderRadius, 12px)`,
  fontFamily: `var(--fontFamily, "Inter")`,
});

// Border radius helper (used throughout codebase):
const getThemeRadius = () => {
  const rootStyles = getComputedStyle(document.documentElement);
  const borderRadius = rootStyles.getPropertyValue('--borderRadius')?.trim() || '12px';
  const radiusValue = parseInt(borderRadius);
  if (radiusValue === 0) return 'none';
  if (radiusValue <= 4) return 'sm';
  if (radiusValue <= 8) return 'md';
  if (radiusValue <= 12) return 'lg';
  return 'xl';
};
```

### Toast Notifications (CRITICAL - Use Promise Pattern Only)
**Always use `showToast.promise()` from `@/utils/toastUtils.jsx`** for async operations:
```jsx
import { showToast } from '@/utils/toastUtils';

// Promise-based toast (REQUIRED pattern for all async operations)
const handleSubmit = async () => {
  const promise = new Promise(async (resolve, reject) => {
    try {
      const response = await axios.post(route('entity.store'), formData);
      if (response.status === 200) {
        resolve([response.data.message || 'Success']);
      }
    } catch (error) {
      reject(error.response?.data?.errors || ['An error occurred']);
    }
  });

  showToast.promise(promise, {
    loading: 'Saving changes...',
    success: (data) => data.join(', '),
    error: (data) => Array.isArray(data) ? data.join(', ') : data,
  });
};

// With dynamic messages based on entity
showToast.promise(apiCall(), {
  loading: `Creating ${entityName}...`,
  success: (data) => data.message || `${entityName} created!`,
  error: (err) => err.response?.data?.message || `Failed to create ${entityName}`
});
```

**Available action message templates in `toastUtils.jsx`:**
- `create`, `update`, `delete`, `save` - CRUD operations
- `approve`, `reject`, `submit`, `cancel` - Status operations
- `assign`, `unassign` - Assignment operations
- `export`, `import`, `fetch`, `refresh` - Data operations

### Loading States & Skeleton Pattern (Apply to Specific Sections Only)
**NEVER show full-page loading.** Apply skeletons only to the sections loading data:
```jsx
import { Skeleton } from "@heroui/react";

// Stats section loading - use StatsCards with isLoading prop
<StatsCards stats={statsData} isLoading={statsLoading} />

// Table section loading - show skeleton rows
{tableLoading ? (
  <div className="space-y-3">
    {Array.from({ length: 5 }).map((_, i) => (
      <div key={i} className="flex gap-4">
        <Skeleton className="h-12 w-12 rounded-lg" />
        <div className="flex-1 space-y-2">
          <Skeleton className="h-4 w-3/4 rounded" />
          <Skeleton className="h-3 w-1/2 rounded" />
        </div>
      </div>
    ))}
  </div>
) : (
  <Table>{/* actual table content */}</Table>
)}

// Card content loading
<Card>
  <CardBody>
    {cardLoading ? (
      <div className="space-y-3">
        <Skeleton className="h-6 w-32 rounded" />
        <Skeleton className="h-4 w-full rounded" />
        <Skeleton className="h-4 w-2/3 rounded" />
      </div>
    ) : (
      <ActualContent />
    )}
  </CardBody>
</Card>
```

### Date Picker Pattern (Follow LeaveForm.jsx / BulkLeaveModal.jsx)
```jsx
// Use HeroUI Input with type="date" for simple date inputs
<Input
  type="date"
  label="Start Date"
  value={fromDate}
  onChange={(e) => setFromDate(e.target.value)}
  isRequired
  radius={themeRadius}
  classNames={{ inputWrapper: "bg-default-100" }}
/>

// For date range validation
useEffect(() => {
  if (fromDate && toDate) {
    const start = new Date(fromDate);
    const end = new Date(toDate);
    if (end < start) {
      setErrors(prev => ({ ...prev, toDate: 'End date must be after start date' }));
    }
  }
}, [fromDate, toDate]);
```

### Page Structure Pattern
Every list/table page should follow this structure:
```jsx
// 1. Responsive breakpoint detection
const [isMobile, setIsMobile] = useState(false);
const [isTablet, setIsTablet] = useState(false);
useEffect(() => {
  const checkScreenSize = () => {
    setIsMobile(window.innerWidth < 640);
    setIsTablet(window.innerWidth < 768);
  };
  checkScreenSize();
  window.addEventListener('resize', checkScreenSize);
  return () => window.removeEventListener('resize', checkScreenSize);
}, []);

// 2. Server-side pagination state
const [pagination, setPagination] = useState({ currentPage: 1, perPage: 10, total: 0 });

// 3. Loading states with Skeleton components
if (loading) return <StatsLoadingSkeleton />;

// 4. Stats cards at top (using StatsCards component)
// 5. Filter bar with search, dropdowns
// 6. Data table with actions column
// 7. Pagination at bottom
```

### Table Component Pattern
```jsx
<Table
  aria-label="Data table"
  isHeaderSticky
  classNames={{
    wrapper: "shadow-none border border-divider rounded-lg",
    th: "bg-default-100 text-default-600 font-semibold",
    td: "py-3"
  }}
>
  <TableHeader columns={columns}>
    {(column) => <TableColumn key={column.uid}>{column.name}</TableColumn>}
  </TableHeader>
  <TableBody items={data} emptyContent="No data found">
    {(item) => (
      <TableRow key={item.id}>
        {(columnKey) => <TableCell>{renderCell(item, columnKey)}</TableCell>}
      </TableRow>
    )}
  </TableBody>
</Table>
```

### Dark Mode Support
All new components **must** support dark mode using `dark:` Tailwind variants:
```jsx
<div className="bg-white dark:bg-content1 border-divider">
  <span className="text-foreground dark:text-white">Content</span>
</div>
```

### Color Palette (Chip/Badge colors)
```javascript
const statusColorMap = {
  active: "success",    // Green
  inactive: "danger",   // Red
  pending: "warning",   // Yellow
  processing: "primary" // Blue
};
```

---

## Backend Patterns

### Controller Response Pattern
```php
// For Inertia pages:
return Inertia::render('Tenant/Pages/UsersList', [
    'title' => 'Users',
    'roles' => Role::all(),
    'departments' => Department::all(),
]);

// For API endpoints:
return response()->json([
    'data' => $resource,
    'message' => 'Success'
]);
```

### Service Layer
Business logic should be in `app/Services/`. Example: `TenantProvisioner.php`, `ModernAuthenticationService.php`

### Form Requests
Always create Form Request classes for validation. Check `app/Http/Requests/` for patterns.

---

## Development Workflow

### Host App Required Environment Variables
When setting up a host application (like `aeos365`), these environment variables MUST be configured in `.env`:

```env
# Application URL (must match actual domain)
APP_URL=http://aeos365.test

# Platform domain configuration (REQUIRED for SaaS mode)
PLATFORM_DOMAIN=aeos365.test
ADMIN_DOMAIN=admin.aeos365.test

# Database configuration
DB_CONNECTION=mysql
DB_DATABASE=eos365
```

### Commands
```bash
# Run tests (from host app directory)
php artisan test --filter=testName

# Format code (run from package directory or use full path)
php vendor/bin/pint --dirty "packages/aero-core/src/File.php"

# Build frontend (from host app directory)
npm run build  # or npm run dev for watch mode

# Clear caches after package changes
php artisan config:clear
php artisan route:clear  
php artisan cache:clear

# Tenant management
php artisan tenant:create
php artisan tenant:migrate
```

### Testing
- Use PHPUnit (not Pest)
- Create feature tests with `php artisan make:test --phpunit {name}`
- Use model factories for test data

---

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

## Foundational Context
This application uses Laravel 11 with these key packages:

- php - 8.2.12
- inertiajs/inertia-laravel (INERTIA) - v2
- laravel/cashier (CASHIER) - v15
- laravel/fortify (FORTIFY) - v1
- laravel/framework (LARAVEL) - v11
- laravel/sanctum (SANCTUM) - v4
- tightenco/ziggy (ZIGGY) - v2
- @inertiajs/react (INERTIA) - v2
- react (REACT) - v18
- tailwindcss (TAILWINDCSS) - v4
- @heroui/react - UI Component Library

## Conventions
- **Always check sibling files** for correct structure, approach, and naming before creating new files.
- Use descriptive names: `isRegisteredForDiscounts`, not `discount()`.
- **Reuse existing components** - check `resources/js/Components/` and `resources/js/Tables/` first.
- When extending existing features, **merge into existing files** rather than creating duplicates.

## Application Structure & Architecture
- Stick to existing directory structure - don't create new base folders without approval.
- Do not change dependencies without approval.

## Frontend Bundling
- If frontend changes aren't reflected, run `npm run build` or `npm run dev`.

## Documentation Files
- Only create documentation if explicitly requested.


=== boost rules ===

## Laravel Boost
- Laravel Boost is an MCP server that comes with powerful tools designed specifically for this application. Use them.

## Artisan
- Use the `list-artisan-commands` tool when you need to call an Artisan command to double check the available parameters.

## URLs
- Whenever you share a project URL with the user you should use the `get-absolute-url` tool to ensure you're using the correct scheme, domain / IP, and port.

## Tinker / Debugging
- You should use the `tinker` tool when you need to execute PHP to debug code or query Eloquent models directly.
- Use the `database-query` tool when you only need to read from the database.

## Reading Browser Logs With the `browser-logs` Tool
- You can read browser logs, errors, and exceptions using the `browser-logs` tool from Boost.
- Only recent browser logs will be useful - ignore old logs.

## Searching Documentation (Critically Important)
- Boost comes with a powerful `search-docs` tool you should use before any other approaches. This tool automatically passes a list of installed packages and their versions to the remote Boost API, so it returns only version-specific documentation specific for the user's circumstance. You should pass an array of packages to filter on if you know you need docs for particular packages.
- The 'search-docs' tool is perfect for all Laravel related packages, including Laravel, Inertia, Livewire, Filament, Tailwind, Pest, Nova, Nightwatch, etc.
- You must use this tool to search for Laravel-ecosystem documentation before falling back to other approaches.
- Search the documentation before making code changes to ensure we are taking the correct approach.
- Use multiple, broad, simple, topic based queries to start. For example: `['rate limiting', 'routing rate limiting', 'routing']`.
- Do not add package names to queries - package information is already shared. For example, use `test resource table`, not `filament 4 test resource table`.

### Available Search Syntax
- You can and should pass multiple queries at once. The most relevant results will be returned first.

1. Simple Word Searches with auto-stemming - query=authentication - finds 'authenticate' and 'auth'
2. Multiple Words (AND Logic) - query=rate limit - finds knowledge containing both "rate" AND "limit"
3. Quoted Phrases (Exact Position) - query="infinite scroll" - Words must be adjacent and in that order
4. Mixed Queries - query=middleware "rate limit" - "middleware" AND exact phrase "rate limit"
5. Multiple Queries - queries=["authentication", "middleware"] - ANY of these terms


=== php rules ===

## PHP

- Always use curly braces for control structures, even if it has one line.

### Constructors
- Use PHP 8 constructor property promotion in `__construct()`.
    - <code-snippet>public function __construct(public GitHub $github) { }</code-snippet>
- Do not allow empty `__construct()` methods with zero parameters.

### Type Declarations
- Always use explicit return type declarations for methods and functions.
- Use appropriate PHP type hints for method parameters.

<code-snippet name="Explicit Return Types and Method Params" lang="php">
protected function isAccessible(User $user, ?string $path = null): bool
{
    ...
}
</code-snippet>

## Comments
- Prefer PHPDoc blocks over comments. Never use comments within the code itself unless there is something _very_ complex going on.

## PHPDoc Blocks
- Add useful array shape type definitions for arrays when appropriate.

## Enums
- Typically, keys in an Enum should be TitleCase. For example: `FavoritePerson`, `BestLake`, `Monthly`.


=== tests rules ===

## Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test` with a specific filename or filter.


=== inertia-laravel/core rules ===

## Inertia Core

- Inertia.js components should be placed in the `resources/js/Pages` directory unless specified differently in the JS bundler (vite.config.js).
- Use `Inertia::render()` for server-side routing instead of traditional Blade views.
- Use `search-docs` for accurate guidance on all things Inertia.

<code-snippet lang="php" name="Inertia::render Example">
// routes/web.php example
Route::get('/users', function () {
    return Inertia::render('Users/Index', [
        'users' => User::all()
    ]);
});
</code-snippet>


=== inertia-laravel/v2 rules ===

## Inertia v2

- Make use of all Inertia features from v1 & v2. Check the documentation before making any changes to ensure we are taking the correct approach.

### Inertia v2 New Features
- Polling
- Prefetching
- Deferred props
- Infinite scrolling using merging props and `WhenVisible`
- Lazy loading data on scroll

### Deferred Props & Empty States
- When using deferred props on the frontend, you should add a nice empty state with pulsing / animated skeleton.

### Inertia Form General Guidance
- The recommended way to build forms when using Inertia is with the `<Form>` component - a useful example is below. Use `search-docs` with a query of `form component` for guidance.
- Forms can also be built using the `useForm` helper for more programmatic control, or to follow existing conventions. Use `search-docs` with a query of `useForm helper` for guidance.
- `resetOnError`, `resetOnSuccess`, and `setDefaultsOnSuccess` are available on the `<Form>` component. Use `search-docs` with a query of 'form component resetting' for guidance.


=== laravel/core rules ===

## Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using the `list-artisan-commands` tool.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Database
- Always use proper Eloquent relationship methods with return type hints. Prefer relationship methods over raw queries or manual joins.
- Use Eloquent models and relationships before suggesting raw database queries
- Avoid `DB::`; prefer `Model::query()`. Generate code that leverages Laravel's ORM capabilities rather than bypassing them.
- Generate code that prevents N+1 query problems by using eager loading.
- Use Laravel's query builder for very complex database operations.

### Model Creation
- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `list-artisan-commands` to check the available options to `php artisan make:model`.

### APIs & Eloquent Resources
- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

### Controllers & Validation
- Always create Form Request classes for validation rather than inline validation in controllers. Include both validation rules and custom error messages.
- Check sibling Form Requests to see if the application uses array or string based validation rules.

### Queues
- Use queued jobs for time-consuming operations with the `ShouldQueue` interface.

### Authentication & Authorization
- Use Laravel's built-in authentication and authorization features (gates, policies, Sanctum, etc.).

### URL Generation
- When generating links to other pages, prefer named routes and the `route()` function.

### Configuration
- Use environment variables only in configuration files - never use the `env()` function directly outside of config files. Always use `config('app.name')`, not `env('APP_NAME')`.

### Testing
- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

### Vite Error
- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.


=== laravel/v11 rules ===

## Laravel 11

- Use the `search-docs` tool to get version specific documentation.
- This project upgraded from Laravel 10 without migrating to the new streamlined Laravel 11 file structure.
- This is **perfectly fine** and recommended by Laravel. Follow the existing structure from Laravel 10. We do not to need migrate to the Laravel 11 structure unless the user explicitly requests that.

### Laravel 10 Structure
- Middleware typically live in `app/Http/Middleware/` and service providers in `app/Providers/`.
- There is no `bootstrap/app.php` application configuration in a Laravel 10 structure:
    - Middleware registration is in `app/Http/Kernel.php`
    - Exception handling is in `app/Exceptions/Handler.php`
    - Console commands and schedule registration is in `app/Console/Kernel.php`
    - Rate limits likely exist in `RouteServiceProvider` or `app/Http/Kernel.php`

### Database
- When modifying a column, the migration must include all of the attributes that were previously defined on the column. Otherwise, they will be dropped and lost.
- Laravel 11 allows limiting eagerly loaded records natively, without external packages: `$query->latest()->limit(10);`.

### Models
- Casts can and likely should be set in a `casts()` method on a model rather than the `$casts` property. Follow existing conventions from other models.

### New Artisan Commands
- List Artisan commands using Boost's MCP tool, if available. New commands available in Laravel 11:
    - `php artisan make:enum`
    - `php artisan make:class `
    - `php artisan make:interface `


=== pint/core rules ===

## Laravel Pint Code Formatter

- You must run `vendor/bin/pint --dirty` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test`, simply run `vendor/bin/pint` to fix any formatting issues.


=== phpunit/core rules ===

## PHPUnit Core

- This application uses PHPUnit for testing. All tests must be written as PHPUnit classes. Use `php artisan make:test --phpunit {name}` to create a new test.
- If you see a test using "Pest", convert it to PHPUnit.
- Every time a test has been updated, run that singular test.
- When the tests relating to your feature are passing, ask the user if they would like to also run the entire test suite to make sure everything is still passing.
- Tests should test all of the happy paths, failure paths, and weird paths.
- You must not remove any tests or test files from the tests directory without approval. These are not temporary or helper files, these are core to the application.

### Running Tests
- Run the minimal number of tests, using an appropriate filter, before finalizing.
- To run all tests: `php artisan test`.
- To run all tests in a file: `php artisan test tests/Feature/ExampleTest.php`.
- To filter on a particular test name: `php artisan test --filter=testName` (recommended after making a change to a related file).


=== inertia-react/core rules ===

## Inertia + React

- Use `router.visit()` or `<Link>` for navigation instead of traditional links.

<code-snippet name="Inertia Client Navigation" lang="react">

import { Link } from '@inertiajs/react'
<Link href="/">Home</Link>

</code-snippet>


=== inertia-react/v2/forms rules ===

## Inertia + React Forms

<code-snippet name="`<Form>` Component Example" lang="react">

import { Form } from '@inertiajs/react'

export default () => (
    <Form action="/users" method="post">
        {({
            errors,
            hasErrors,
            processing,
            wasSuccessful,
            recentlySuccessful,
            clearErrors,
            resetAndClearErrors,
            defaults
        }) => (
        <>
        <input type="text" name="name" />

        {errors.name && <div>{errors.name}</div>}

        <button type="submit" disabled={processing}>
            {processing ? 'Creating...' : 'Create User'}
        </button>

        {wasSuccessful && <div>User created successfully!</div>}
        </>
    )}
    </Form>
)

</code-snippet>


=== tailwindcss/core rules ===

## Tailwind Core

- Use Tailwind CSS classes to style HTML, check and use existing tailwind conventions within the project before writing your own.
- Offer to extract repeated patterns into components that match the project's conventions (i.e. Blade, JSX, Vue, etc..)
- Think through class placement, order, priority, and defaults - remove redundant classes, add classes to parent or child carefully to limit repetition, group elements logically
- You can use the `search-docs` tool to get exact examples from the official documentation when needed.

### Spacing
- When listing items, use gap utilities for spacing, don't use margins.

    <code-snippet name="Valid Flex Gap Spacing Example" lang="html">
        <div class="flex gap-8">
            <div>Superior</div>
            <div>Michigan</div>
            <div>Erie</div>
        </div>
    </code-snippet>


### Dark Mode
- If existing pages and components support dark mode, new pages and components must support dark mode in a similar way, typically using `dark:`.


=== tailwindcss/v4 rules ===

## Tailwind 4

- Always use Tailwind CSS v4 - do not use the deprecated utilities.
- `corePlugins` is not supported in Tailwind v4.
- In Tailwind v4, configuration is CSS-first using the `@theme` directive — no separate `tailwind.config.js` file is needed.
<code-snippet name="Extending Theme in CSS" lang="css">
@theme {
  --color-brand: oklch(0.72 0.11 178);
}
</code-snippet>

- In Tailwind v4, you import Tailwind using a regular CSS `@import` statement, not using the `@tailwind` directives used in v3:

<code-snippet name="Tailwind v4 Import Tailwind Diff" lang="diff">
   - @tailwind base;
   - @tailwind components;
   - @tailwind utilities;
   + @import "tailwindcss";
</code-snippet>


### Replaced Utilities
- Tailwind v4 removed deprecated utilities. Do not use the deprecated option - use the replacement.
- Opacity values are still numeric.

| Deprecated |	Replacement |
|------------+--------------|
| bg-opacity-* | bg-black/* |
| text-opacity-* | text-black/* |
| border-opacity-* | border-black/* |
| divide-opacity-* | divide-black/* |
| ring-opacity-* | ring-black/* |
| placeholder-opacity-* | placeholder-black/* |
| flex-shrink-* | shrink-* |
| flex-grow-* | grow-* |
| overflow-ellipsis | text-ellipsis |
| decoration-slice | box-decoration-slice |
| decoration-clone | box-decoration-clone |
</laravel-boost-guidelines>
