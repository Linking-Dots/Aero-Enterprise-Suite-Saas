# 🎉 Welcome to Aero Enterprise Suite SaaS!

**A Comprehensive Guide for New Developers**

---

## 📖 Table of Contents

1. [What is Aero Enterprise Suite?](#what-is-aero-enterprise-suite)
2. [Project Overview](#project-overview)
3. [Understanding the Architecture](#understanding-the-architecture)
4. [Technology Stack](#technology-stack)
5. [Getting Started](#getting-started)
6. [Development Workflow](#development-workflow)
7. [Frontend Development](#frontend-development)
8. [Backend Development](#backend-development)
9. [Module System](#module-system)
10. [Building and Deployment](#building-and-deployment)
11. [Common Tasks](#common-tasks)
12. [Troubleshooting](#troubleshooting)
13. [Best Practices](#best-practices)
14. [Resources and Next Steps](#resources-and-next-steps)
15. [Glossary](#glossary)

---

## What is Aero Enterprise Suite?

**Aero Enterprise Suite** is a comprehensive, **modular ERP (Enterprise Resource Planning) system** designed to help businesses manage various aspects of their operations including:

- 👥 **Human Resources (HRM)** - Employee management, attendance, leave, payroll
- 🤝 **Customer Relationship Management (CRM)** - Lead, contact, and customer management
- 💰 **Finance** - Accounting, invoicing, expense tracking
- 📦 **Supply Chain Management (SCM)** - Inventory, orders, suppliers
- 📊 **Project Management** - Tasks, milestones, team collaboration
- 🏪 **Point of Sale (POS)** - Retail and sales management
- 📝 **Document Management (DMS)** - File storage and organization
- ✅ **Compliance** - Regulatory compliance tracking
- 🏭 **Inventory Management (IMS)** - Stock and warehouse management
- 🎯 **Quality Management** - Quality assurance and control

### What Makes It Special?

#### 1. **Multi-Tenant SaaS Platform**
Think of it like this: One installation can serve multiple companies (called "tenants"). Each company has completely isolated data - Company A cannot see Company B's data, even though they're using the same application.

#### 2. **Modular Architecture**
Like building blocks! Each feature (HRM, CRM, Finance) is a separate module that can:
- Work independently (standalone mode)
- Work together in the full SaaS platform
- Be added or removed as needed
- Be developed and deployed separately

#### 3. **Flexible Deployment**
- **SaaS Mode**: Full multi-tenant platform with all modules
- **Standalone Mode**: Single module (like just HRM) for one company


---

## Project Overview

### The Big Picture

Imagine you're building a LEGO city where:
- **Base plates** = Core foundation (authentication, user management)
- **Buildings** = Modules (HRM, CRM, Finance)
- **Power source** = Platform (multi-tenancy engine)
- **Construction site** = Development workspace (monorepo)

Our project is structured as a **monorepo** (one repository containing multiple related packages) that:
1. **Shares code** between modules (no duplication)
2. **Maintains separation** (each module is independent)
3. **Enables flexibility** (deploy together or separately)

### Key Concepts

#### Monorepo
**Simple Explanation**: Instead of having 10 separate repositories for 10 modules, we have ONE repository with 10 folders. This makes it easier to:
- Share common code
- Make changes across modules
- Keep everything in sync
- Test integrations locally

#### Multi-Tenancy
**Simple Explanation**: Like an apartment building:
- One building (application) with many apartments (tenants)
- Each tenant has their own space and data
- Shared infrastructure (plumbing, electricity = database, servers)
- Complete privacy between tenants

**Technical Implementation**:
- Each tenant gets their own subdomain: `company1.aerosuite.com`, `company2.aerosuite.com`
- Data is isolated using tenant-specific database tables or schemas
- Middleware ensures users only access their tenant's data

#### Module System
**Simple Explanation**: Think of modules like apps on your phone:
- Each module is self-contained
- Modules can communicate with each other
- You can install/uninstall modules
- Core system provides shared services (like your phone's OS)

---

## Understanding the Architecture

### Directory Structure

```
Aero-Enterprise-Suite-Saas/
│
├── apps/                           ← Applications (What you run)
│   ├── saas-host/                 ← Full SaaS platform
│   │   ├── app/                   ← Laravel application logic
│   │   ├── config/                ← Configuration files
│   │   ├── database/              ← Migrations, seeders
│   │   ├── routes/                ← API and web routes
│   │   ├── public/                ← Public web assets
│   │   ├── composer.json          ← PHP dependencies
│   │   └── package.json           ← JavaScript dependencies
│   │
│   └── standalone-host/           ← Single module deployment
│       └── ... (similar structure)
│
├── packages/                       ← Source Code (What you edit)
│   ├── aero-core/                 ← Foundation package
│   │   ├── src/                   ← PHP source code
│   │   │   ├── Models/            ← Database models
│   │   │   ├── Http/Controllers/  ← Request handlers
│   │   │   ├── Services/          ← Business logic
│   │   │   ├── Traits/            ← Reusable code
│   │   │   └── Providers/         ← Laravel service providers
│   │   ├── resources/js/          ← React/JavaScript code
│   │   │   ├── Pages/             ← Full page components
│   │   │   ├── Components/        ← Reusable UI components
│   │   │   ├── Layouts/           ← Page layouts
│   │   │   └── theme/             ← Theme configuration
│   │   ├── config/                ← Package configuration
│   │   ├── routes/                ← Package routes
│   │   ├── composer.json          ← PHP package definition
│   │   ├── package.json           ← JS package definition
│   │   └── vite.config.js         ← Build configuration
│   │
│   ├── aero-platform/             ← Multi-tenancy engine
│   ├── aero-hrm/                  ← HRM module
│   ├── aero-crm/                  ← CRM module
│   ├── aero-finance/              ← Finance module
│   └── ... (other modules)
│
├── scripts/                        ← Build & Deployment Scripts
│   ├── build-release.sh           ← Linux/Mac build script
│   ├── build-release.ps1          ← Windows build script
│   └── validate-build.ps1         ← Build verification
│
├── docs/                           ← Documentation
│   ├── DEVELOPMENT_WORKFLOW.md    ← Development guide
│   ├── QUICK_START.md             ← Quick setup guide
│   └── ...
│
└── tests/                          ← Test files
    ├── Feature/                    ← Feature tests
    └── Unit/                       ← Unit tests
```

### Component Relationships

```
┌─────────────────────────────────────────────────────────┐
│                    AERO ARCHITECTURE                     │
└─────────────────────────────────────────────────────────┘

         ┌──────────────┐         ┌──────────────┐
         │  saas-host   │         │ standalone-  │
         │ (Full SaaS)  │         │     host     │
         └──────┬───────┘         └───────┬──────┘
                │                         │
                └─────────┬───────────────┘
                          ↓
              ┌───────────────────────┐
              │    aero-platform      │ ← Multi-tenancy
              │  (Tenancy Engine)     │
              └───────────┬───────────┘
                          ↓
              ┌───────────────────────┐
              │      aero-core        │ ← Foundation
              │  (Auth, Users, RBAC)  │
              └───────────┬───────────┘
                          ↓
       ┌──────────────────┴──────────────────┐
       │                                      │
   ┌───▼──────┐  ┌──────────┐  ┌──────────┐ │
   │ aero-hrm │  │ aero-crm │  │ aero-    │ │
   │          │  │          │  │ finance  │ ...
   └──────────┘  └──────────┘  └──────────┘

   Each module is:
   • Independent (can work alone)
   • Connected (shares core services)
   • Pluggable (can be added/removed)
```

### Data Flow

```
User Request (Browser)
        ↓
    Web Server (Laravel)
        ↓
    Middleware (Authentication, Tenancy)
        ↓
    Router (Determines which controller)
        ↓
    Controller (Handles request)
        ↓
    Service Layer (Business logic)
        ↓
    Model (Database interaction)
        ↓
    Database (MySQL/PostgreSQL)
        ↓
    Response (JSON or Inertia.js page)
        ↓
    React Component (Renders UI)
        ↓
    User sees result
```

---

## Technology Stack

### Backend Technologies

#### 1. **PHP 8.2+**
**What it is**: Server-side programming language  
**Why we use it**: Fast, mature, great for web applications  
**Your role**: Write controllers, services, models

#### 2. **Laravel 12**
**What it is**: PHP web framework (think of it as a toolkit)  
**Why we use it**: Provides routing, database, authentication out of the box  
**Key features we use**:
- **Eloquent ORM**: Database interactions using PHP objects
- **Migrations**: Version control for database structure
- **Middleware**: Request filtering and processing
- **Service Providers**: Bootstrap application services
- **Artisan**: Command-line tool for common tasks

#### 3. **Composer**
**What it is**: PHP package manager (like npm for PHP)  
**Why we use it**: Manages PHP dependencies  
**Common commands**:
```bash
composer install      # Install dependencies
composer require pkg  # Add new package
composer update       # Update packages
```

#### 4. **MySQL/PostgreSQL**
**What it is**: Database management systems  
**Why we use it**: Store all application data  
**Your role**: Design tables, write migrations, query data

#### 5. **Stancl/Tenancy**
**What it is**: Laravel package for multi-tenancy  
**Why we use it**: Manages tenant isolation, databases, domains  
**Key concepts**:
- Automatic tenant identification (from subdomain)
- Database per tenant or shared database with scoping
- Tenant-specific middleware

### Frontend Technologies

#### 1. **React 18**
**What it is**: JavaScript library for building user interfaces  
**Why we use it**: Component-based, reusable, performant  
**Key concepts**:
- **Components**: Reusable UI pieces (like LEGO blocks)
- **Props**: Data passed to components
- **State**: Component memory that changes over time
- **Hooks**: Special functions (useState, useEffect, etc.)

#### 2. **Inertia.js v2**
**What it is**: Glue between Laravel and React  
**Why we use it**: Build single-page apps without building an API  
**How it works**:
```
Laravel Controller → Inertia::render('Page', $data)
                              ↓
                     React Component receives $data as props
                              ↓
                     User interacts, submits form
                              ↓
                     Inertia sends request to Laravel
                              ↓
                     Controller processes, returns new page/data
```

#### 3. **HeroUI (NextUI v2)**
**What it is**: React component library  
**Why we use it**: Pre-built, beautiful, accessible components  
**Components we use**:
- Table, Button, Card, Modal, Input, Select, Dropdown, Chip, Badge, etc.

#### 4. **Tailwind CSS v4**
**What it is**: Utility-first CSS framework  
**Why we use it**: Rapid UI development with utility classes  
**Example**:
```jsx
<div className="bg-white dark:bg-gray-800 rounded-lg shadow-md p-4">
  <h1 className="text-2xl font-bold text-gray-900">Title</h1>
</div>
```

#### 5. **Vite**
**What it is**: Modern frontend build tool  
**Why we use it**: Fast development server, optimized production builds  
**Modes**:
- **Dev mode**: Hot module replacement (instant updates)
- **Build mode**: Optimized production bundles

#### 6. **Heroicons**
**What it is**: Icon library  
**Why we use it**: Consistent, beautiful SVG icons  
**Usage**: Import from `@heroicons/react/24/outline`

### Build & Development Tools

#### 1. **NPM (Node Package Manager)**
**What it is**: JavaScript package manager  
**Common commands**:
```bash
npm install           # Install dependencies
npm run dev           # Start development server
npm run build         # Build for production
```

#### 2. **Git**
**What it is**: Version control system  
**Why we use it**: Track changes, collaborate, manage versions  
**Common commands**:
```bash
git status            # Check status
git add .             # Stage changes
git commit -m "msg"   # Commit changes
git push              # Push to remote
git pull              # Pull from remote
```

#### 3. **PHPUnit**
**What it is**: PHP testing framework  
**Why we use it**: Automated testing  
**Usage**: `php artisan test`

---

## Getting Started

### Prerequisites

Before you begin, ensure you have installed:

1. **PHP 8.2 or higher**
   ```bash
   php -v  # Check version
   ```

2. **Composer**
   ```bash
   composer -V  # Check version
   ```

3. **Node.js 18+ and NPM**
   ```bash
   node -v  # Check version
   npm -v   # Check version
   ```

4. **MySQL or PostgreSQL**
   ```bash
   mysql --version  # Check MySQL version
   ```

5. **Git**
   ```bash
   git --version  # Check version
   ```

### Initial Setup

#### Step 1: Clone the Repository

```bash
git clone https://github.com/Linking-Dots/Aero-Enterprise-Suite-Saas.git
cd Aero-Enterprise-Suite-Saas
```

#### Step 2: Choose Your Host Application

You have two options:

**Option A: Full SaaS Platform (Recommended for learning)**
```bash
cd apps/saas-host
```

**Option B: Standalone HRM Only**
```bash
cd apps/standalone-host
```

#### Step 3: Install PHP Dependencies

```bash
composer install
```

**What this does**:
- Downloads all PHP packages
- Creates symlinks to `packages/` folder
- Sets up autoloading

**You should see**:
```
- Installing aero/core (dev-main)
  Symlinking from ../../packages/aero-core
```

#### Step 4: Configure Environment

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` file and set database credentials:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=aero_saas
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

#### Step 5: Create Database

```bash
# In MySQL
mysql -u root -p
CREATE DATABASE aero_saas;
EXIT;
```

#### Step 6: Run Migrations

```bash
php artisan migrate
```

**What this does**:
- Creates all database tables
- Sets up initial structure

#### Step 7: Seed Database (Optional)

```bash
php artisan db:seed
```

**What this does**:
- Creates sample data
- Creates admin user
- Sets up roles and permissions

#### Step 8: Install Frontend Dependencies

```bash
npm install
```

#### Step 9: Build Frontend Assets

For development:
```bash
npm run dev
```

For production:
```bash
npm run build
```

#### Step 10: Start the Application

```bash
php artisan serve
```

Visit: `http://localhost:8000`

**Default Credentials** (if seeded):
- Email: `admin@example.com`
- Password: `password`

---

## Development Workflow

### The Hybrid Development Approach

Our project uses a **unique dual-watcher system** because:
- **Core** is the React "host" (serves the app)
- **Modules** are React "guests" (loaded dynamically)

### Setting Up Your Development Environment

You need **TWO terminal windows** running simultaneously:

#### Terminal 1: Core Development Server (The Host)

```bash
cd packages/aero-core
npm run dev
```

**What this does**:
- Starts Vite development server on `http://localhost:5173`
- Provides React, ReactDOM, Inertia.js to modules
- Enables Hot Module Replacement (HMR)
- Watches for changes in core files

**You'll see**:
```
VITE v6.3.5  ready in 423 ms

➜  Local:   http://localhost:5173/
➜  Network: use --host to expose
```

#### Terminal 2: Module Watcher (The Guests)

```bash
# For HRM Module
cd packages/aero-hrm
npm run build -- --watch

# For CRM Module (in another terminal if needed)
cd packages/aero-crm
npm run build -- --watch
```

**What this does**:
- Compiles module to `dist/aero-hrm.umd.js`
- Externalizes React dependencies (doesn't bundle React, uses core's React)
- Auto-recompiles on file changes
- Module is picked up by Laravel on page refresh

**You'll see**:
```
watching for file changes...

built in 234ms
```

#### Terminal 3: Laravel Server (Optional)

```bash
cd apps/saas-host
php artisan serve
```

**Alternative**: Use the built-in dev command:
```bash
cd apps/saas-host
composer run dev
```

This runs Laravel server + Queue worker + Vite in one command.

### Understanding the Build Process

```
┌─────────────────────────────────────────────────────────┐
│                  DEVELOPMENT FLOW                        │
└─────────────────────────────────────────────────────────┘

You edit:
  packages/aero-core/resources/js/Pages/Dashboard.jsx
       ↓
  Vite (Terminal 1) detects change
       ↓
  Hot Module Replacement (HMR)
       ↓
  Browser updates INSTANTLY (no refresh needed)

You edit:
  packages/aero-hrm/resources/js/Pages/EmployeeList.jsx
       ↓
  Vite (Terminal 2) detects change
       ↓
  Recompiles to dist/aero-hrm.umd.js
       ↓
  Refresh browser to see changes
```

### Package Linking (Symlinks)

**Key Concept**: When you run `composer install`, packages are **symlinked** not copied.

**What does this mean?**
```
apps/saas-host/vendor/aero/core → packages/aero-core (symlink)
apps/saas-host/vendor/aero/hrm  → packages/aero-hrm  (symlink)
```

**Benefits**:
- ✅ Edit once in `packages/`, changes reflect in all hosts immediately
- ✅ No need to run `composer update` after every change
- ✅ Faster development

**Verify symlinks**:
```bash
cd apps/saas-host/vendor/aero
ls -la
# You'll see arrows (→) indicating symlinks
```


### Making Your First Change

Let's make a simple change to see the workflow in action:

#### Example: Editing a Core Page

1. **Open the dashboard page**:
   ```bash
   code packages/aero-core/resources/js/Pages/Dashboard.jsx
   ```

2. **Make a change** (add a new heading):
   ```jsx
   export default function Dashboard() {
       return (
           <div>
               <h1>Dashboard</h1>
               <h2>Welcome to Aero! 🎉</h2>  {/* Add this line */}
               {/* ... rest of code */}
           </div>
       );
   }
   ```

3. **Save the file** (Ctrl+S)

4. **See the change**:
   - Your browser updates INSTANTLY (thanks to HMR!)
   - No refresh needed

#### Example: Editing a Module Page

1. **Open an HRM page**:
   ```bash
   code packages/aero-hrm/resources/js/Pages/EmployeeList.jsx
   ```

2. **Make a change**:
   ```jsx
   <h1>Employee List</h1>
   <p>Total Employees: {employees.length}</p>  {/* Add this line */}
   ```

3. **Save the file** (Ctrl+S)

4. **Wait for compilation**:
   - Watch Terminal 2 - you'll see "built in XXXms"
   
5. **Refresh browser** (F5 or Ctrl+R)

6. **See the change**!

#### Example: Adding a Backend Route

1. **Open module routes**:
   ```bash
   code packages/aero-hrm/routes/web.php
   ```

2. **Add a new route**:
   ```php
   Route::get('/employees/stats', [EmployeeController::class, 'stats']);
   ```

3. **Create the controller method**:
   ```bash
   code packages/aero-hrm/src/Http/Controllers/EmployeeController.php
   ```

   ```php
   public function stats()
   {
       return Inertia::render('HRM/EmployeeStats', [
           'total' => Employee::count(),
           'active' => Employee::where('status', 'active')->count(),
       ]);
   }
   ```

4. **Create the React page**:
   ```bash
   code packages/aero-hrm/resources/js/Pages/EmployeeStats.jsx
   ```

   ```jsx
   export default function EmployeeStats({ total, active }) {
       return (
           <div className="p-6">
               <h1 className="text-2xl font-bold">Employee Statistics</h1>
               <p>Total: {total}</p>
               <p>Active: {active}</p>
           </div>
       );
   }
   ```

5. **Export the component**:
   ```bash
   code packages/aero-hrm/resources/js/index.jsx
   ```

   ```jsx
   export { default as EmployeeStats } from './Pages/EmployeeStats';
   ```

6. **Rebuild the module**:
   - Terminal 2 automatically rebuilds

7. **Visit the route**: `http://localhost:8000/hrm/employees/stats`

---

## Frontend Development

### Component Structure

Our frontend follows a specific pattern. Here's what you need to know:

#### Page Components

Location: `packages/{module}/resources/js/Pages/`

**Structure**:
```jsx
import React, { useState } from 'react';
import { Head } from '@inertiajs/react';
import { Card, Button } from '@heroui/react';

export default function PageName({ propFromLaravel }) {
    const [state, setState] = useState('initial');
    
    return (
        <>
            <Head title="Page Title" />
            
            <div className="p-6">
                <h1 className="text-2xl font-bold mb-4">Page Title</h1>
                
                <Card>
                    {/* Your content */}
                </Card>
            </div>
        </>
    );
}
```

#### Reusable Components

Location: `packages/aero-core/resources/js/Components/`

**Example: Button Component**:
```jsx
import { Button as HeroButton } from '@heroui/react';

export default function CustomButton({ children, onClick, variant = "solid" }) {
    return (
        <Button 
            variant={variant}
            onClick={onClick}
            className="custom-button"
        >
            {children}
        </Button>
    );
}
```

### Using HeroUI Components

We use **HeroUI (NextUI v2)** as our component library. Here are the most common ones:

#### Table
```jsx
import { Table, TableHeader, TableColumn, TableBody, TableRow, TableCell } from '@heroui/react';

<Table aria-label="Example table">
    <TableHeader>
        <TableColumn>NAME</TableColumn>
        <TableColumn>EMAIL</TableColumn>
    </TableHeader>
    <TableBody>
        {users.map(user => (
            <TableRow key={user.id}>
                <TableCell>{user.name}</TableCell>
                <TableCell>{user.email}</TableCell>
            </TableRow>
        ))}
    </TableBody>
</Table>
```

#### Modal
```jsx
import { Modal, ModalContent, ModalHeader, ModalBody, ModalFooter, Button } from '@heroui/react';

const [isOpen, setIsOpen] = useState(false);

<Modal isOpen={isOpen} onOpenChange={setIsOpen}>
    <ModalContent>
        <ModalHeader>Modal Title</ModalHeader>
        <ModalBody>
            <p>Modal content goes here</p>
        </ModalBody>
        <ModalFooter>
            <Button variant="flat" onPress={() => setIsOpen(false)}>
                Close
            </Button>
        </ModalFooter>
    </ModalContent>
</Modal>
```

#### Form Inputs
```jsx
import { Input, Select, SelectItem } from '@heroui/react';

<Input
    label="Email"
    placeholder="Enter your email"
    type="email"
    value={email}
    onValueChange={setEmail}
/>

<Select
    label="Department"
    placeholder="Select department"
>
    <SelectItem key="hr" value="hr">Human Resources</SelectItem>
    <SelectItem key="it" value="it">IT</SelectItem>
</Select>
```

### Styling with Tailwind CSS

We use **utility-first CSS** with Tailwind. Here are common patterns:

#### Layout
```jsx
<div className="container mx-auto px-4">        {/* Centered container */}
    <div className="grid grid-cols-1 md:grid-cols-3 gap-4">  {/* Responsive grid */}
        <div className="col-span-1">Column 1</div>
        <div className="col-span-2">Column 2</div>
    </div>
</div>
```

#### Spacing
```jsx
<div className="p-4">      {/* Padding all sides */}
<div className="px-6">     {/* Padding left & right */}
<div className="py-3">     {/* Padding top & bottom */}
<div className="mt-4">     {/* Margin top */}
<div className="mb-6">     {/* Margin bottom */}
```

#### Colors & Background
```jsx
<div className="bg-white dark:bg-gray-800">           {/* Responsive to dark mode */}
<div className="text-gray-900 dark:text-white">
<div className="border border-gray-200">
```

#### Typography
```jsx
<h1 className="text-3xl font-bold">       {/* Large bold heading */}
<p className="text-sm text-gray-600">     {/* Small gray text */}
<span className="font-semibold">          {/* Semi-bold text */}
```

### Inertia.js Patterns

#### Receiving Props from Laravel
```jsx
export default function Dashboard({ users, stats }) {
    // props.users comes from Laravel controller
    // props.stats comes from Laravel controller
    
    return (
        <div>
            <p>Total Users: {users.length}</p>
            <p>Total Revenue: ${stats.revenue}</p>
        </div>
    );
}
```

#### Making Requests to Laravel
```jsx
import { router } from '@inertiajs/react';

// GET request
router.get('/users');

// POST request
router.post('/users', {
    name: 'John',
    email: 'john@example.com'
});

// PUT request
router.put(`/users/${userId}`, {
    name: 'John Updated'
});

// DELETE request
router.delete(`/users/${userId}`);
```

#### Using Forms with Inertia
```jsx
import { useForm } from '@inertiajs/react';

export default function UserForm() {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        email: '',
    });

    function submit(e) {
        e.preventDefault();
        post('/users');
    }

    return (
        <form onSubmit={submit}>
            <Input
                label="Name"
                value={data.name}
                onValueChange={value => setData('name', value)}
                errorMessage={errors.name}
            />
            
            <Input
                label="Email"
                type="email"
                value={data.email}
                onValueChange={value => setData('email', value)}
                errorMessage={errors.email}
            />
            
            <Button type="submit" isLoading={processing}>
                Submit
            </Button>
        </form>
    );
}
```

### State Management

#### Local State (useState)
```jsx
const [count, setCount] = useState(0);
const [user, setUser] = useState({ name: '', email: '' });

// Update state
setCount(count + 1);
setUser({ ...user, name: 'John' });
```

#### Effects (useEffect)
```jsx
import { useEffect } from 'react';

useEffect(() => {
    // Runs after component mounts
    console.log('Component mounted');
    
    // Cleanup function (runs before unmount)
    return () => {
        console.log('Component will unmount');
    };
}, []); // Empty array = run once

useEffect(() => {
    // Runs when 'userId' changes
    fetchUserData(userId);
}, [userId]); // Dependency array
```

### Toast Notifications

We use `react-hot-toast` for notifications:

```jsx
import { showToast } from '@/utils/toastUtils';

// Success toast
showToast.success('User created successfully!');

// Error toast
showToast.error('Failed to create user');

// Promise-based toast (for async operations)
const promise = new Promise((resolve, reject) => {
    axios.post('/users', data)
        .then(response => resolve(response.data.message))
        .catch(error => reject(error.response.data.message));
});

showToast.promise(promise, {
    loading: 'Creating user...',
    success: (data) => data,
    error: (err) => err,
});
```

---

## Backend Development

### Laravel Basics

#### Controllers

Location: `packages/{module}/src/Http/Controllers/`

**Structure**:
```php
<?php

namespace Aero\Hrm\Http\Controllers;

use Aero\Hrm\Models\Employee;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EmployeeController extends Controller
{
    public function index()
    {
        $employees = Employee::with('department')->paginate(10);
        
        return Inertia::render('HRM/EmployeeList', [
            'employees' => $employees
        ]);
    }
    
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:employees',
            'department_id' => 'required|exists:departments,id',
        ]);
        
        $employee = Employee::create($validated);
        
        return back()->with('success', 'Employee created successfully');
    }
    
    public function update(Request $request, Employee $employee)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:employees,email,' . $employee->id,
        ]);
        
        $employee->update($validated);
        
        return back()->with('success', 'Employee updated successfully');
    }
    
    public function destroy(Employee $employee)
    {
        $employee->delete();
        
        return back()->with('success', 'Employee deleted successfully');
    }
}
```

#### Models

Location: `packages/{module}/src/Models/`

**Structure**:
```php
<?php

namespace Aero\Hrm\Models;

use Illuminate\Database\Eloquent\Model;
use Aero\Core\Traits\AeroTenantable;

class Employee extends Model
{
    use AeroTenantable; // Enables multi-tenancy
    
    protected $fillable = [
        'name',
        'email',
        'department_id',
        'hire_date',
        'salary',
    ];
    
    protected $casts = [
        'hire_date' => 'date',
        'salary' => 'decimal:2',
    ];
    
    // Relationships
    public function department()
    {
        return $this->belongsTo(Department::class);
    }
    
    public function leaves()
    {
        return $this->hasMany(Leave::class);
    }
    
    // Accessors
    public function getFullNameAttribute()
    {
        return $this->name;
    }
    
    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
```

#### Routes

Location: `packages/{module}/routes/web.php`

**Structure**:
```php
<?php

use Illuminate\Support\Facades\Route;
use Aero\Hrm\Http\Controllers\EmployeeController;

Route::prefix('hrm')->middleware(['auth'])->group(function () {
    // Employee routes
    Route::get('/employees', [EmployeeController::class, 'index'])->name('hrm.employees.index');
    Route::get('/employees/create', [EmployeeController::class, 'create'])->name('hrm.employees.create');
    Route::post('/employees', [EmployeeController::class, 'store'])->name('hrm.employees.store');
    Route::get('/employees/{employee}', [EmployeeController::class, 'show'])->name('hrm.employees.show');
    Route::get('/employees/{employee}/edit', [EmployeeController::class, 'edit'])->name('hrm.employees.edit');
    Route::put('/employees/{employee}', [EmployeeController::class, 'update'])->name('hrm.employees.update');
    Route::delete('/employees/{employee}', [EmployeeController::class, 'destroy'])->name('hrm.employees.destroy');
});

// Or use resource routing (does the same as above)
Route::resource('hrm/employees', EmployeeController::class);
```

#### Migrations

Location: `packages/{module}/database/migrations/`

**Example**:
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->foreignId('department_id')->constrained()->onDelete('cascade');
            $table->date('hire_date');
            $table->decimal('salary', 10, 2);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->integer('tenant_id')->default(1); // For multi-tenancy
            $table->timestamps();
            
            $table->index(['tenant_id', 'status']);
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('employees');
    }
};
```

**Running migrations**:
```bash
php artisan migrate                    # Run all pending migrations
php artisan migrate:rollback           # Rollback last batch
php artisan migrate:fresh              # Drop all tables and re-run
php artisan migrate:fresh --seed       # Drop, re-run, and seed
```

#### Services

Location: `packages/{module}/src/Services/`

**Purpose**: Business logic separate from controllers

**Example**:
```php
<?php

namespace Aero\Hrm\Services;

use Aero\Hrm\Models\Employee;
use Aero\Hrm\Models\Leave;

class LeaveService
{
    public function applyLeave(Employee $employee, array $data)
    {
        // Business logic
        $remainingDays = $this->calculateRemainingDays($employee);
        
        if ($remainingDays < $data['days']) {
            throw new \Exception('Insufficient leave balance');
        }
        
        $leave = Leave::create([
            'employee_id' => $employee->id,
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'days' => $data['days'],
            'reason' => $data['reason'],
            'status' => 'pending',
        ]);
        
        // Send notification
        $this->notifyManager($leave);
        
        return $leave;
    }
    
    private function calculateRemainingDays(Employee $employee)
    {
        $used = $employee->leaves()
            ->whereYear('start_date', now()->year)
            ->where('status', 'approved')
            ->sum('days');
            
        return $employee->annual_leave_days - $used;
    }
    
    private function notifyManager($leave)
    {
        // Send email/notification
    }
}
```

**Usage in controller**:
```php
use Aero\Hrm\Services\LeaveService;

public function applyLeave(Request $request, LeaveService $leaveService)
{
    $employee = auth()->user()->employee;
    
    try {
        $leave = $leaveService->applyLeave($employee, $request->validated());
        return back()->with('success', 'Leave applied successfully');
    } catch (\Exception $e) {
        return back()->with('error', $e->getMessage());
    }
}
```

### Database Queries

#### Basic Queries
```php
// Get all
$employees = Employee::all();

// Get with conditions
$employees = Employee::where('status', 'active')->get();

// Get one
$employee = Employee::find($id);
$employee = Employee::where('email', 'john@example.com')->first();

// Get or fail (throws 404 if not found)
$employee = Employee::findOrFail($id);

// Count
$count = Employee::count();
$activeCount = Employee::where('status', 'active')->count();
```

#### Relationships
```php
// Eager loading (prevents N+1 problem)
$employees = Employee::with('department', 'leaves')->get();

// Conditional eager loading
$employees = Employee::with(['leaves' => function ($query) {
    $query->where('status', 'approved');
}])->get();

// Load relationships after fetching
$employee = Employee::find($id);
$employee->load('department');
```

#### Pagination
```php
$employees = Employee::paginate(10);  // 10 per page
$employees = Employee::simplePaginate(10);  // Simple pagination (no page numbers)
```

#### Aggregates
```php
$total = Employee::sum('salary');
$average = Employee::avg('salary');
$max = Employee::max('salary');
$min = Employee::min('salary');
```

### Validation

```php
$request->validate([
    'name' => 'required|string|max:255',
    'email' => 'required|email|unique:users,email',
    'age' => 'required|integer|min:18|max:65',
    'hire_date' => 'required|date|after:today',
    'department_id' => 'required|exists:departments,id',
    'avatar' => 'nullable|image|max:2048',  // Max 2MB
]);
```

**Custom error messages**:
```php
$request->validate([
    'email' => 'required|email',
], [
    'email.required' => 'Please provide an email address',
    'email.email' => 'Email must be valid',
]);
```

### Authorization

#### Policies

Location: `packages/{module}/src/Policies/`

```php
<?php

namespace Aero\Hrm\Policies;

use Aero\Core\Models\User;
use Aero\Hrm\Models\Employee;

class EmployeePolicy
{
    public function viewAny(User $user)
    {
        return $user->hasPermission('view-employees');
    }
    
    public function view(User $user, Employee $employee)
    {
        return $user->hasPermission('view-employees') || $user->id === $employee->user_id;
    }
    
    public function create(User $user)
    {
        return $user->hasRole('manager|admin');
    }
    
    public function update(User $user, Employee $employee)
    {
        return $user->hasRole('manager|admin') || $user->id === $employee->user_id;
    }
    
    public function delete(User $user, Employee $employee)
    {
        return $user->hasRole('admin');
    }
}
```

**Usage in controller**:
```php
public function update(Request $request, Employee $employee)
{
    $this->authorize('update', $employee);
    
    // User is authorized, proceed with update
    $employee->update($request->validated());
    
    return back()->with('success', 'Updated successfully');
}
```


---

## Module System

### What is a Module?

A **module** in Aero is a self-contained feature package that:
- Has its own models, controllers, views, and routes
- Can be installed/uninstalled independently
- Works in both SaaS and Standalone modes
- Shares common services from Core

### Module Structure

Every module follows this structure:

```
packages/aero-hrm/
├── composer.json              # PHP package definition
├── package.json               # JavaScript package definition
├── module.json                # Module metadata
├── vite.config.js             # Build configuration
│
├── src/                       # PHP source code
│   ├── Models/
│   ├── Http/
│   │   ├── Controllers/
│   │   └── Middleware/
│   ├── Services/
│   ├── Policies/
│   └── Providers/
│       └── AeroHrmServiceProvider.php
│
├── resources/
│   └── js/
│       ├── Pages/             # React pages
│       ├── Components/        # React components
│       └── index.jsx          # Entry point
│
├── routes/
│   ├── web.php                # Web routes
│   └── api.php                # API routes
│
├── database/
│   ├── migrations/
│   └── seeders/
│
└── config/
    └── hrm.php                # Module configuration
```

### module.json File

Every module needs a `module.json` file:

```json
{
  "name": "aero-hrm",
  "short_name": "hrm",
  "version": "1.0.0",
  "description": "Human Resource Management Module",
  "namespace": "Aero\\Hrm",
  "providers": ["Aero\\Hrm\\AeroHrmServiceProvider"],
  "assets": {
    "js": "dist/aero-hrm.umd.js",
    "css": "dist/aero-hrm.css"
  },
  "dependencies": {
    "aero/core": "^1.0"
  },
  "config": {
    "enabled": true,
    "auto_register": true
  }
}
```

### Service Provider

Each module has a service provider that registers services:

```php
<?php

namespace Aero\Hrm;

use Illuminate\Support\ServiceProvider;

class AeroHrmServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Register services
        $this->app->singleton(LeaveService::class);
    }
    
    public function boot()
    {
        // Load routes
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        
        // Load migrations
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        
        // Load config
        $this->mergeConfigFrom(__DIR__.'/../config/hrm.php', 'hrm');
        
        // Publish config
        $this->publishes([
            __DIR__.'/../config/hrm.php' => config_path('hrm.php'),
        ], 'hrm-config');
    }
}
```

### Multi-Tenancy in Modules

All models in modules should use the `AeroTenantable` trait:

```php
use Aero\Core\Traits\AeroTenantable;

class Employee extends Model
{
    use AeroTenantable;  // This single line handles multi-tenancy!
}
```

**What it does**:
- In SaaS mode: Uses `stancl/tenancy` for automatic tenant scoping
- In Standalone mode: Falls back to `tenant_id = 1`
- Automatically filters all queries by tenant
- No code changes needed when switching modes

### Creating a New Module

#### Step 1: Create Package Structure

```bash
cd packages
mkdir aero-newmodule
cd aero-newmodule
```

#### Step 2: Create composer.json

```json
{
    "name": "aero/newmodule",
    "type": "library",
    "autoload": {
        "psr-4": {
            "Aero\\NewModule\\": "src/"
        }
    },
    "require": {
        "php": "^8.2",
        "aero/core": "*"
    },
    "extra": {
        "laravel": {
            "providers": [
                "Aero\\NewModule\\AeroNewModuleServiceProvider"
            ]
        }
    }
}
```

#### Step 3: Create module.json

```json
{
  "name": "aero-newmodule",
  "short_name": "newmodule",
  "version": "1.0.0",
  "namespace": "Aero\\NewModule",
  "providers": ["Aero\\NewModule\\AeroNewModuleServiceProvider"]
}
```

#### Step 4: Create Service Provider

```php
<?php

namespace Aero\NewModule;

use Illuminate\Support\ServiceProvider;

class AeroNewModuleServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
```

#### Step 5: Add to Host App

Edit `apps/saas-host/composer.json`:

```json
{
    "require": {
        "aero/newmodule": "*"
    }
}
```

Run:
```bash
cd apps/saas-host
composer install
```

---

## Building and Deployment

### Build Pipeline Overview

Our build system creates two types of packages:

1. **Installers** - Full packages with all dependencies (50-80 MB)
   - For new buyers/clients
   - Includes `vendor/` folder
   - Complete, ready-to-install

2. **Add-ons** - Lightweight module packages (300-500 KB)
   - For existing users
   - No `vendor/` folder
   - Just module code + compiled JS

### Building a Release

#### Linux/Mac

```bash
./scripts/build-release.sh 1.0.0
```

#### Windows (PowerShell)

```powershell
.\scripts\build-release.ps1 -Version "1.0.0"
```

**What this does**:
1. Validates environment
2. Builds all frontend assets
3. Runs tests
4. Creates installer packages
5. Creates module add-ons
6. Verifies builds
7. Creates ZIP files in `dist/` folder

### Build Output

After building, you'll find in `dist/` folder:

```
dist/
├── Aero_Core_Installer_v1.0.0.zip       # ~60 MB
├── Aero_HRM_Installer_v1.0.0.zip        # ~55 MB
├── Aero_CRM_Module_v1.0.0.zip           # ~400 KB
├── Aero_Finance_Module_v1.0.0.zip       # ~350 KB
└── ... (other modules)
```

### Building Individual Modules

If you only need to build one module:

```bash
# Linux/Mac
./scripts/build-module.sh aero-hrm

# Windows
.\scripts\build-module.ps1 -ModuleName "aero-hrm"
```

### Build Verification

The build scripts automatically verify:

✅ Installer has `vendor/` folder  
✅ Add-ons don't have `vendor/` folder  
✅ Compiled JavaScript exists  
✅ React is externalized (not bundled)  
✅ All required files present  

### Manual Verification

#### 1. Check Installer Package

```bash
unzip -l dist/Aero_HRM_Installer_v1.0.0.zip | grep vendor/
# Should show many files
```

#### 2. Check Module Add-on

```bash
unzip -l dist/Aero_CRM_Module_v1.0.0.zip | grep vendor/
# Should show nothing (no vendor folder)
```

#### 3. Check React Externalization

```bash
unzip -p dist/Aero_CRM_Module_v1.0.0.zip aero-crm/dist/aero-crm.umd.js | grep 'from "react"'
# Should find: import ... from "react"
```

### Deployment

#### SaaS Deployment

1. Extract installer on server
2. Configure `.env` file
3. Run migrations
4. Set up domains for tenants
5. Configure queue workers

```bash
unzip Aero_Core_Installer_v1.0.0.zip
cd installer
composer install --no-dev
php artisan migrate
php artisan db:seed
```

#### Standalone Deployment

1. Extract standalone installer
2. Configure `.env` (set `AERO_MODE=standalone`)
3. Run migrations
4. No multi-tenancy setup needed

```bash
unzip Aero_HRM_Installer_v1.0.0.zip
cd installer
echo "AERO_MODE=standalone" >> .env
composer install --no-dev
php artisan migrate
```

#### Adding Modules to Existing Installation

1. Extract module add-on
2. Copy to `modules/` folder
3. Clear cache
4. Module is automatically discovered

```bash
unzip Aero_CRM_Module_v1.0.0.zip
cp -r aero-crm /path/to/installation/modules/
php artisan config:clear
php artisan cache:clear
```

---

## Common Tasks

### Running Tests

```bash
# Run all tests
php artisan test

# Run specific test file
php artisan test --filter=EmployeeTest

# Run tests for a specific module
php artisan test tests/Feature/Hrm/

# Run with coverage
php artisan test --coverage
```

### Database Operations

```bash
# Create new migration
php artisan make:migration create_employees_table

# Run migrations
php artisan migrate

# Rollback last migration
php artisan migrate:rollback

# Fresh migration (drop all tables)
php artisan migrate:fresh

# Fresh + seed
php artisan migrate:fresh --seed

# Create seeder
php artisan make:seeder EmployeeSeeder

# Run specific seeder
php artisan db:seed --class=EmployeeSeeder
```

### Creating Files

```bash
# Create model
php artisan make:model Employee

# Create model with migration
php artisan make:model Employee -m

# Create controller
php artisan make:controller EmployeeController

# Create resource controller (with CRUD methods)
php artisan make:controller EmployeeController --resource

# Create request (form validation)
php artisan make:request StoreEmployeeRequest

# Create policy
php artisan make:policy EmployeePolicy

# Create service
# (No artisan command, create manually in src/Services/)
```

### Cache Management

```bash
# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Cache config for production
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Queue Management

```bash
# Run queue worker
php artisan queue:work

# Run queue worker with specific queue
php artisan queue:work --queue=emails,default

# Process one job
php artisan queue:work --once

# Restart all queue workers
php artisan queue:restart

# Check failed jobs
php artisan queue:failed

# Retry failed job
php artisan queue:retry {id}

# Retry all failed jobs
php artisan queue:retry all
```

### Maintenance Mode

```bash
# Enable maintenance mode
php artisan down

# Enable with message
php artisan down --message="Upgrading Database"

# Enable with secret (bypass)
php artisan down --secret="my-secret-token"
# Visit: https://example.com/my-secret-token to bypass

# Disable maintenance mode
php artisan up
```

---

## Troubleshooting

### Common Issues and Solutions

#### 1. "Module not found" or "Component not registered"

**Symptom**: Browser console shows module errors

**Solution**:
```bash
# Rebuild the module
cd packages/aero-hrm
npm install
npm run build

# Clear Laravel cache
cd apps/saas-host
php artisan config:clear
php artisan cache:clear

# Hard refresh browser
Ctrl+Shift+R (Windows/Linux)
Cmd+Shift+R (Mac)
```

#### 2. Changes Not Appearing

**Symptom**: You edit code but don't see changes

**Solution**:
```bash
# Check if watchers are running
# Terminal 1: packages/aero-core - npm run dev
# Terminal 2: packages/aero-hrm - npm run build -- --watch

# If not, start them
cd packages/aero-core && npm run dev
cd packages/aero-hrm && npm run build -- --watch

# Hard refresh browser
```

#### 3. Symlinks Not Working

**Symptom**: Changes in `packages/` don't reflect in `apps/`

**Solution**:
```bash
cd apps/saas-host
rm -rf vendor/aero
composer install

# Verify symlinks
ls -la vendor/aero/
# Should show arrows (→) for symlinks
```

#### 4. Vite Manifest Error

**Symptom**: `Unable to locate file in Vite manifest`

**Solution**:
```bash
cd packages/aero-core
npm run build

cd apps/saas-host
php artisan config:clear
```

#### 5. Database Connection Error

**Symptom**: `SQLSTATE[HY000] [2002] Connection refused`

**Solution**:
```bash
# Check database is running
mysql -u root -p

# Check .env configuration
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=aero_saas
DB_USERNAME=root
DB_PASSWORD=your_password

# Clear config cache
php artisan config:clear
```

#### 6. Permission Denied Errors

**Symptom**: Cannot write to storage or cache directories

**Solution**:
```bash
# Linux/Mac
sudo chown -R $USER:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

# Or grant full permissions (development only!)
chmod -R 777 storage bootstrap/cache
```

#### 7. Composer Install Fails

**Symptom**: Composer cannot find packages

**Solution**:
```bash
# Clear Composer cache
composer clear-cache

# Remove vendor and reinstall
rm -rf vendor composer.lock
composer install
```

#### 8. NPM Install Fails

**Symptom**: Node module errors

**Solution**:
```bash
# Clear npm cache
npm cache clean --force

# Remove node_modules and reinstall
rm -rf node_modules package-lock.json
npm install
```

#### 9. Class Not Found

**Symptom**: `Class 'Aero\Hrm\Models\Employee' not found`

**Solution**:
```bash
# Regenerate autoload files
composer dump-autoload

# Clear cached classes
php artisan clear-compiled
php artisan optimize:clear
```

#### 10. Route Not Found

**Symptom**: 404 error for valid routes

**Solution**:
```bash
# Clear route cache
php artisan route:clear

# Check if route exists
php artisan route:list | grep employees
```

### Getting Help

When you encounter an issue:

1. **Check Laravel logs**: `storage/logs/laravel.log`
2. **Check browser console**: F12 → Console tab
3. **Check network tab**: F12 → Network tab
4. **Enable debug mode**: Set `APP_DEBUG=true` in `.env`
5. **Check documentation**: Review relevant docs in `docs/` folder
6. **Ask the team**: Don't hesitate to ask experienced team members

### Debug Tools

#### Laravel Telescope (Development)

```bash
composer require laravel/telescope --dev
php artisan telescope:install
php artisan migrate
```

Visit: `http://localhost:8000/telescope`

#### Laravel Debugbar

```bash
composer require barryvdh/laravel-debugbar --dev
```

Automatically shows at bottom of page in development.

---

## Best Practices

### Code Organization

#### 1. Keep Controllers Thin

**Bad**:
```php
public function store(Request $request)
{
    // 100 lines of business logic
    // Database queries
    // External API calls
    // Email sending
    // ...
}
```

**Good**:
```php
public function store(Request $request, EmployeeService $service)
{
    $employee = $service->createEmployee($request->validated());
    return back()->with('success', 'Employee created');
}
```

#### 2. Use Form Requests for Validation

**Bad**:
```php
public function store(Request $request)
{
    $request->validate([
        // 50 lines of validation rules
    ]);
}
```

**Good**:
```php
public function store(StoreEmployeeRequest $request)
{
    // Validation handled by form request
}
```

#### 3. Use Resource Collections for API Responses

```php
return EmployeeResource::collection($employees);
```

#### 4. Use Scopes for Reusable Queries

```php
// In Model
public function scopeActive($query)
{
    return $query->where('status', 'active');
}

// Usage
Employee::active()->get();
```

### Frontend Best Practices

#### 1. Component Composition

Break large components into smaller ones:

```jsx
// EmployeeList.jsx
export default function EmployeeList() {
    return (
        <div>
            <EmployeeFilters />
            <EmployeeTable />
            <EmployeePagination />
        </div>
    );
}
```

#### 2. Custom Hooks

Extract reusable logic:

```jsx
// useEmployees.js
export function useEmployees() {
    const [employees, setEmployees] = useState([]);
    const [loading, setLoading] = useState(true);
    
    useEffect(() => {
        fetchEmployees();
    }, []);
    
    const fetchEmployees = async () => {
        // Fetch logic
    };
    
    return { employees, loading, fetchEmployees };
}

// Usage in component
const { employees, loading } = useEmployees();
```

#### 3. Avoid Prop Drilling

Use context or global state for deeply nested props.

#### 4. Optimize Re-renders

Use `React.memo`, `useMemo`, `useCallback` for expensive components.

### Database Best Practices

#### 1. Use Indexes

```php
$table->index(['tenant_id', 'status']);
$table->index('email');
```

#### 2. Eager Load Relationships

**Bad** (N+1 problem):
```php
$employees = Employee::all();
foreach ($employees as $employee) {
    echo $employee->department->name;  // Queries inside loop!
}
```

**Good**:
```php
$employees = Employee::with('department')->get();
foreach ($employees as $employee) {
    echo $employee->department->name;  // No extra queries!
}
```

#### 3. Use Chunking for Large Data

```php
Employee::chunk(100, function ($employees) {
    foreach ($employees as $employee) {
        // Process
    }
});
```

### Security Best Practices

#### 1. Always Validate Input

Never trust user input:

```php
$validated = $request->validate([
    'email' => 'required|email',
    'amount' => 'required|numeric|min:0',
]);
```

#### 2. Use Mass Assignment Protection

```php
class Employee extends Model
{
    protected $fillable = ['name', 'email']; // Whitelist
    // OR
    protected $guarded = ['id', 'tenant_id']; // Blacklist
}
```

#### 3. Sanitize Output

Blade templates automatically escape:
```php
{{ $user->name }}  // Safe
{!! $user->name !!}  // Dangerous! Use only for trusted HTML
```

#### 4. Use CSRF Protection

Forms automatically include CSRF token with Inertia.js.

#### 5. Authorize Actions

```php
$this->authorize('update', $employee);
```

### Testing Best Practices

#### 1. Write Tests for Critical Features

```php
public function test_employee_can_apply_leave()
{
    $employee = Employee::factory()->create();
    
    $response = $this->actingAs($employee->user)
        ->post('/hrm/leaves', [
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-05',
            'reason' => 'Vacation',
        ]);
    
    $response->assertStatus(200);
    $this->assertDatabaseHas('leaves', [
        'employee_id' => $employee->id,
        'status' => 'pending',
    ]);
}
```

#### 2. Use Factories

```php
Employee::factory()->count(10)->create();
```

#### 3. Test Edge Cases

- Empty data
- Invalid data
- Boundary values
- Permissions

### Git Best Practices

#### 1. Write Clear Commit Messages

**Bad**:
```
fix stuff
update file
changes
```

**Good**:
```
feat: Add employee leave application feature
fix: Resolve leave balance calculation bug
refactor: Extract leave service from controller
docs: Update employee module documentation
```

#### 2. Commit Frequently

Small, focused commits are better than large ones.

#### 3. Use Branches

```bash
git checkout -b feature/leave-management
git checkout -b fix/leave-calculation-bug
git checkout -b refactor/employee-service
```

#### 4. Pull Before Push

```bash
git pull origin main
git push origin feature/leave-management
```

---

## Resources and Next Steps

### Documentation

- **Quick Start**: `docs/QUICK_START.md`
- **Development Workflow**: `docs/DEVELOPMENT_WORKFLOW.md`
- **Integration Guide**: `docs/INTEGRATION_PILLARS_IMPLEMENTATION.md`
- **Quick Reference**: `QUICK_REFERENCE_MONOREPO.md`

### Framework Documentation

- **Laravel**: https://laravel.com/docs/11.x
- **Inertia.js**: https://inertiajs.com/
- **React**: https://react.dev/
- **Tailwind CSS**: https://tailwindcss.com/docs
- **HeroUI**: https://heroui.com/

### Learning Path for New Developers

#### Week 1: Setup and Basics
- [ ] Set up development environment
- [ ] Clone and install the project
- [ ] Explore the codebase structure
- [ ] Make a simple change to see the workflow
- [ ] Read Laravel basics documentation

#### Week 2: Backend Development
- [ ] Learn Laravel MVC pattern
- [ ] Create a simple CRUD controller
- [ ] Write database migrations
- [ ] Understand Eloquent ORM
- [ ] Learn about multi-tenancy

#### Week 3: Frontend Development
- [ ] Learn React basics
- [ ] Understand Inertia.js workflow
- [ ] Use HeroUI components
- [ ] Practice with Tailwind CSS
- [ ] Create a new page component

#### Week 4: Module System
- [ ] Understand module architecture
- [ ] Study existing modules (HRM, CRM)
- [ ] Create a simple module
- [ ] Learn build pipeline
- [ ] Practice deployment

#### Week 5: Advanced Topics
- [ ] Write tests
- [ ] Implement authorization
- [ ] Optimize queries
- [ ] Learn best practices
- [ ] Contribute to the project

### Your First Task

Here's a simple task to get started:

**Task**: Add a "Notes" field to employees

1. **Backend**:
   - Create migration: `php artisan make:migration add_notes_to_employees_table`
   - Add field: `$table->text('notes')->nullable();`
   - Run migration: `php artisan migrate`
   - Add to model's `$fillable`: `'notes'`

2. **Frontend**:
   - Edit: `packages/aero-hrm/resources/js/Pages/EmployeeForm.jsx`
   - Add textarea:
     ```jsx
     <Textarea
         label="Notes"
         value={data.notes}
         onValueChange={value => setData('notes', value)}
     />
     ```

3. **Test**:
   - Start watchers
   - Visit employee form
   - Add notes and save
   - Verify it's saved in database

### Getting Help

- **Documentation**: Check `docs/` folder first
- **Code Examples**: Look at existing modules
- **Team Members**: Don't hesitate to ask
- **Git History**: See how features were implemented

---

## Glossary

### General Terms

- **ERP**: Enterprise Resource Planning - software for managing business processes
- **SaaS**: Software as a Service - cloud-based software delivery model
- **Multi-Tenancy**: Architecture where one instance serves multiple customers (tenants)
- **Monorepo**: Single repository containing multiple related packages
- **CRUD**: Create, Read, Update, Delete - basic database operations

### Architecture Terms

- **Module**: Self-contained feature package
- **Package**: Reusable code library
- **Service Provider**: Laravel class that registers services
- **Middleware**: Filter for HTTP requests
- **Trait**: Reusable PHP code that can be included in classes

### Frontend Terms

- **Component**: Reusable UI piece in React
- **Props**: Data passed to React components
- **State**: Component memory that changes over time
- **Hook**: Special function in React (useState, useEffect, etc.)
- **HMR**: Hot Module Replacement - instant updates without refresh
- **SSR**: Server-Side Rendering
- **CSR**: Client-Side Rendering

### Backend Terms

- **Controller**: Handles HTTP requests
- **Model**: Represents database table
- **Migration**: Version control for database structure
- **Seeder**: Populates database with sample data
- **Eloquent**: Laravel's ORM (Object-Relational Mapping)
- **ORM**: Object-Relational Mapping - interact with database using objects
- **Query Builder**: Fluent interface for building database queries
- **Policy**: Authorization logic for models

### Build Terms

- **Vite**: Modern frontend build tool
- **Bundle**: Compiled JavaScript file
- **Externalize**: Exclude dependencies from bundle (use from host)
- **Library Mode**: Vite mode for building shared libraries
- **Host Mode**: Vite mode for serving applications
- **Symlink**: Symbolic link - shortcut to another location

### Development Terms

- **Hot Reload**: Automatic page refresh on code change
- **Watch Mode**: Continuously monitor files for changes
- **Development Mode**: Unoptimized, verbose, with debugging tools
- **Production Mode**: Optimized, minified, ready for deployment

---

## Conclusion

Congratulations! You now have a comprehensive understanding of the Aero Enterprise Suite SaaS project.

### Key Takeaways

1. **Aero is a modular ERP** with multiple deployment options
2. **Monorepo structure** keeps everything organized and connected
3. **Multi-tenancy** allows serving multiple companies from one installation
4. **Hybrid development** requires two watchers (core + modules)
5. **Symlinks** make development fast and efficient
6. **Follow patterns** established in existing code
7. **Build pipeline** creates both installers and add-ons
8. **Ask for help** when you need it!

### What's Next?

- Review the quick reference guide: `QUICK_REFERENCE_MONOREPO.md`
- Follow the learning path above
- Start with small tasks and gradually take on bigger challenges
- Read code from existing modules to learn patterns
- Ask questions and seek feedback

**Welcome to the team! Happy coding! 🚀**

---

*Last Updated: December 2024*  
*Version: 1.0.0*  
*Maintainers: Aero Development Team*
