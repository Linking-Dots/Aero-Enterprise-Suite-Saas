# Aero Enterprise Suite SaaS

**A Modern, Modular ERP System Built with Laravel, React, and Inertia.js**

[![Laravel](https://img.shields.io/badge/Laravel-12.x-red.svg)](https://laravel.com)
[![React](https://img.shields.io/badge/React-18.x-blue.svg)](https://reactjs.org)
[![License](https://img.shields.io/badge/License-Proprietary-yellow.svg)]()

---

## 🚀 What is Aero?

Aero Enterprise Suite is a **comprehensive, modular ERP (Enterprise Resource Planning) system** designed for modern businesses. It features:

- **🏢 Multi-Tenant SaaS Architecture** - Serve multiple companies from one installation
- **📦 Modular Design** - Independent modules that work together seamlessly
- **🔄 Flexible Deployment** - Deploy as full SaaS or standalone applications
- **⚡ Modern Tech Stack** - Laravel 12, React 18, Inertia.js v2, Tailwind CSS v4
- **🎨 Beautiful UI** - HeroUI components with dark mode support

### Modules Available

- 👥 **HRM** - Human Resource Management
- 🤝 **CRM** - Customer Relationship Management
- 💰 **Finance** - Accounting & Financial Management
- 📦 **SCM** - Supply Chain Management
- 📊 **Project** - Project Management
- 🏪 **POS** - Point of Sale
- 📝 **DMS** - Document Management System
- ✅ **Compliance** - Regulatory Compliance Tracking
- 🏭 **IMS** - Inventory Management System
- 🎯 **Quality** - Quality Assurance & Control

---

## 📚 Documentation

### For New Developers

**👉 [START HERE: Complete Welcome Guide](WELCOME_GUIDE.md)** - Comprehensive guide for developers new to the project or software development. Includes:
- Project overview and architecture explained in simple terms
- Step-by-step setup instructions
- Technology stack breakdown with beginner-friendly explanations
- Development workflow tutorials
- Frontend and backend development guides
- Module system deep dive
- Troubleshooting and best practices
- Learning path from beginner to advanced

### Quick References

- **[Quick Reference Monorepo](QUICK_REFERENCE_MONOREPO.md)** - Fast lookup for common commands and patterns
- **[Development Workflow](docs/DEVELOPMENT_WORKFLOW.md)** - Detailed workflow guide
- **[Quick Start](docs/QUICK_START.md)** - Get up and running in 5 minutes

### Technical Documentation

- **[Foundation Implementation](FOUNDATION_IMPLEMENTATION_COMPLETE.md)** - Complete foundation architecture
- **[Compliance Verification](COMPLIANCE_VERIFICATION.md)** - Architecture compliance status
- **[Integration Pillars](docs/INTEGRATION_PILLARS_IMPLEMENTATION.md)** - Module integration architecture
- **[Navigation Implementation](NAVIGATION_IMPLEMENTATION_CHECKLIST.md)** - Navigation system checklist

---

## 🏗️ Architecture Overview

Aero uses a **monorepo structure** with clear separation between applications and packages:

```
Aero-Enterprise-Suite-Saas/
├── apps/                    # Runnable Applications
│   ├── saas-host/          # Full SaaS platform with all modules
│   └── standalone-host/    # Standalone deployment (e.g., HRM only)
│
├── packages/                # Source Code Packages
│   ├── aero-core/          # Foundation (auth, users, RBAC)
│   ├── aero-platform/      # Multi-tenancy engine
│   ├── aero-hrm/           # Human Resource Management
│   ├── aero-crm/           # Customer Relationship Management
│   └── ... (other modules)
│
└── scripts/                 # Build & Deployment Scripts
    ├── build-release.sh    # Linux/Mac production build
    └── build-release.ps1   # Windows production build
```

### Key Features

- **Symlinked Packages**: Changes in `packages/` are instantly reflected in `apps/`
- **Dual Build System**: Core (host) + Modules (guests) with React externalization
- **Multi-Tenancy**: Complete tenant isolation with Stancl/Tenancy
- **Modular Routes**: Context-aware routing for SaaS vs Standalone modes
- **Dynamic Loading**: Runtime module discovery and registration

---

## ⚡ Quick Start

### Prerequisites

- PHP 8.2+
- Composer
- Node.js 18+
- MySQL or PostgreSQL
- Git

### Installation

```bash
# 1. Clone the repository
git clone https://github.com/Linking-Dots/Aero-Enterprise-Suite-Saas.git
cd Aero-Enterprise-Suite-Saas

# 2. Choose your host application
cd apps/saas-host  # or apps/standalone-host

# 3. Install PHP dependencies
composer install

# 4. Install JavaScript dependencies
npm install

# 5. Configure environment
cp .env.example .env
php artisan key:generate

# 6. Set up database (edit .env first)
php artisan migrate
php artisan db:seed

# 7. Start development servers
# Terminal 1: Core watcher
cd packages/aero-core && npm run dev

# Terminal 2: Module watcher (optional)
cd packages/aero-hrm && npm run build -- --watch

# Terminal 3: Laravel server
cd apps/saas-host && php artisan serve
```

Visit: `http://localhost:8000`

**For detailed instructions, see the [Complete Welcome Guide](WELCOME_GUIDE.md)**

---

## 🛠️ Development

### Development Workflow

We use a **hybrid dual-watcher system**:

1. **Core Watcher** (packages/aero-core): Serves the React host application
2. **Module Watchers** (packages/aero-hrm, etc.): Compile modules in library mode

**Learn more**: [Development Workflow Guide](docs/DEVELOPMENT_WORKFLOW.md)

### Common Commands

```bash
# Development
npm run dev                      # Start development server (core)
npm run build -- --watch         # Watch mode (modules)
php artisan serve                # Laravel development server

# Testing
php artisan test                 # Run PHPUnit tests
php artisan test --filter=Name   # Run specific test

# Database
php artisan migrate              # Run migrations
php artisan db:seed              # Seed database
php artisan migrate:fresh --seed # Fresh install with data

# Building
./scripts/build-release.sh 1.0.0        # Build release (Linux/Mac)
.\scripts\build-release.ps1 -Version "1.0.0"  # Build release (Windows)

# Cache
php artisan config:clear         # Clear config cache
php artisan cache:clear          # Clear application cache
php artisan route:clear          # Clear route cache
```

---

## 🧪 Testing

```bash
# Run all tests
php artisan test

# Run specific test file
php artisan test --filter=EmployeeTest

# Run tests with coverage
php artisan test --coverage
```

---

## 📦 Building for Production

### Build All Modules

```bash
# Linux/Mac
./scripts/build-release.sh 1.0.0

# Windows PowerShell
.\scripts\build-release.ps1 -Version "1.0.0"
```

This creates:
- **Installers** (~50-80 MB): Complete packages with vendor/ for new installations
- **Add-ons** (~300-500 KB): Lightweight module packages for existing installations

### Build Individual Module

```bash
# Linux/Mac
./scripts/build-module.sh aero-hrm

# Windows
.\scripts\build-module.ps1 -ModuleName "aero-hrm"
```

---

## 🎯 Project Structure

### Applications (apps/)

- **saas-host**: Full SaaS platform with Platform package + Core + All modules
- **standalone-host**: Standalone deployment with Core + Selected module(s)

### Packages (packages/)

| Package | Purpose | Dependencies |
|---------|---------|--------------|
| **aero-core** | Foundation, Auth, Users, RBAC | Laravel, React |
| **aero-platform** | Multi-tenancy, Billing, Plans | aero-core, stancl/tenancy |
| **aero-hrm** | Human Resources Management | aero-core |
| **aero-crm** | Customer Relationship Management | aero-core |
| **aero-finance** | Financial Management | aero-core |
| **aero-project** | Project Management | aero-core |
| **aero-pos** | Point of Sale | aero-core |
| **aero-scm** | Supply Chain Management | aero-core |
| **aero-ims** | Inventory Management | aero-core |
| **aero-compliance** | Compliance Tracking | aero-core |
| **aero-dms** | Document Management | aero-core |
| **aero-quality** | Quality Management | aero-core |

---

## 🤝 Contributing

We welcome contributions! Please follow these guidelines:

1. **Read the documentation** first (especially [WELCOME_GUIDE.md](WELCOME_GUIDE.md))
2. **Follow coding standards**: Use Laravel Pint for PHP, ESLint for JavaScript
3. **Write tests** for new features
4. **Use clear commit messages**: `feat:`, `fix:`, `docs:`, `refactor:`, etc.
5. **Create feature branches**: `feature/your-feature-name`
6. **Submit pull requests** with clear descriptions

### Coding Standards

- **PHP**: PSR-12 (enforced by Laravel Pint)
- **JavaScript**: ESLint with React rules
- **Components**: Follow HeroUI patterns
- **Styling**: Tailwind CSS utility classes

---

## 📄 License

Proprietary - Aero Enterprise Suite  
Copyright © 2024 Linking Dots

---

## 🆘 Support

- **Documentation**: See [docs/](docs/) folder
- **Issues**: Create a GitHub issue
- **Questions**: Ask in team discussions
- **New to project?**: Start with [WELCOME_GUIDE.md](WELCOME_GUIDE.md)

---

## 🎓 Learning Resources

### For New Developers

1. **[Complete Welcome Guide](WELCOME_GUIDE.md)** - Start here!
2. **[Quick Start Guide](docs/QUICK_START.md)** - Setup in 5 minutes
3. **[Development Workflow](docs/DEVELOPMENT_WORKFLOW.md)** - How we develop

### External Resources

- [Laravel Documentation](https://laravel.com/docs/12.x)
- [React Documentation](https://react.dev/)
- [Inertia.js Documentation](https://inertiajs.com/)
- [Tailwind CSS Documentation](https://tailwindcss.com/)
- [HeroUI Documentation](https://heroui.com/)

---

## 🌟 Key Technologies

- **Backend**: Laravel 12, PHP 8.2+
- **Frontend**: React 18, Inertia.js v2
- **UI Library**: HeroUI (NextUI v2)
- **Styling**: Tailwind CSS v4
- **Build Tool**: Vite 6
- **Database**: MySQL/PostgreSQL
- **Multi-Tenancy**: Stancl/Tenancy
- **Authentication**: Laravel Sanctum
- **Icons**: Heroicons

---

## 📊 Project Status

- ✅ Foundation architecture complete
- ✅ Core module implemented
- ✅ Platform (multi-tenancy) implemented
- ✅ HRM module implemented
- ✅ CRM module implemented
- 🚧 Finance module in progress
- 🚧 Additional modules in development
- ✅ Build pipeline complete
- ✅ Documentation complete

---

**Ready to start?** 👉 [Read the Complete Welcome Guide](WELCOME_GUIDE.md)

**Quick reference needed?** 👉 [Check Quick Reference](QUICK_REFERENCE_MONOREPO.md)

**Have questions?** Don't hesitate to ask the team!

---

*Last Updated: December 2024*  
*Repository: https://github.com/Linking-Dots/Aero-Enterprise-Suite-Saas*
