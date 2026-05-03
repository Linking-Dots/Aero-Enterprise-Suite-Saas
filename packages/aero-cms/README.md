# Aero CMS - Content Management System

A powerful, block-based CMS system for the aeos365 SaaS platform built with Laravel 11 and React 18, featuring visual page builder, HRMAC permission integration, and HeroUI components.

## Overview

Aero CMS transforms the way you manage public pages in your SaaS application. Instead of hardcoding page content in React components, manage everything through an intuitive visual editor with:

- **Block-Based Architecture**: Compose pages from pre-built, reusable blocks
- **HRMAC Integration**: Full permission control with role-based access
- **Visual Editor**: Drag-and-drop page builder with live preview
- **Version Control**: Track changes and rollback to previous versions
- **Media Library**: Organize and manage media files
- **Responsive Design**: Auto-preview on desktop, tablet, and mobile
- **HeroUI Components**: Beautiful, accessible component library

## Quick Start

### Installation

1. **Install the Package**
   ```bash
   composer require aero/cms
   ```

2. **Publish Configuration**
   ```bash
   php artisan vendor:publish --provider="Aero\Cms\CmsServiceProvider" --tag="config"
   ```

3. **Run Migrations**
   ```bash
   php artisan migrate
   ```

4. **Seed Module Configuration**
   ```bash
   php artisan db:seed --class="Aero\Cms\Database\Seeders\ModuleSeeder"
   ```

### Creating Your First Page

1. Navigate to **Admin → CMS → Pages**
2. Click **Create Page**
3. Enter page title (slug auto-generates)
4. Configure SEO settings
5. Click **Save**
6. Click **Edit** to open the visual page builder
7. Add blocks from the left palette
8. Configure each block's content
9. Click **Publish**

Your page is now live at `/{slug}`

## Architecture

### Package Structure

```
packages/aero-cms/
├── config/
│   ├── module.php           # HRMAC module configuration
│   └── cms.php              # CMS settings
├── database/
│   ├── migrations/          # Database schema
│   └── factories/           # Model factories for testing
├── src/
│   ├── Http/
│   │   ├── Controllers/Admin/
│   │   │   ├── PageController.php
│   │   │   ├── BlockController.php
│   │   │   └── MediaController.php
│   │   ├── PublicPageController.php
│   │   └── Requests/        # Form requests for validation
│   ├── Models/
│   │   ├── CmsPage.php
│   │   ├── CmsPageBlock.php
│   │   ├── CmsBlockTemplate.php
│   │   ├── CmsMedia.php
│   │   └── CmsPageVersion.php
│   ├── Services/
│   │   └── BlockRegistry.php # Block type registry
│   └── Providers/
│       └── CmsServiceProvider.php
├── routes/
│   ├── admin.php            # Admin routes with HRMAC
│   └── public.php           # Public page routes
└── tests/                   # PHPUnit tests
```

## Database Schema

### cms_pages
Stores page metadata and publishing information.

| Column | Type | Description |
|--------|------|-------------|
| id | id | Primary key |
| title | string | Page title |
| slug | string | URL slug (unique) |
| meta_title | string | SEO title (max 60 chars) |
| meta_description | string | SEO description (max 160 chars) |
| og_image | string | Open Graph image URL |
| layout | string | Layout type (default: 'default') |
| status | enum | draft \| published \| scheduled |
| published_at | timestamp | Publication date |
| scheduled_for | timestamp | Scheduled publish date |
| is_homepage | boolean | Set as homepage |
| show_in_nav | boolean | Show in navigation menu |
| nav_label | string | Navigation menu label |
| parent_id | id | Parent page (for hierarchy) |
| created_by | id | User who created page |
| updated_by | id | User who last updated page |
| deleted_at | timestamp | Soft delete timestamp |

### cms_page_blocks
Stores individual blocks within pages.

| Column | Type | Description |
|--------|------|-------------|
| id | id | Primary key |
| cms_page_id | id | FK to cms_pages |
| block_type | string | Block type (e.g., 'hero_standard') |
| content | json | Block content data |
| settings | json | Block display settings |
| order_index | integer | Block order on page |

### cms_page_versions
Tracks page history for version control and rollback.

| Column | Type | Description |
|--------|------|-------------|
| id | id | Primary key |
| cms_page_id | id | FK to cms_pages |
| version_number | integer | Auto-incrementing version |
| content | json | Full page content snapshot |
| note | string | Optional version note |
| created_by | id | User who created version |

### cms_block_templates
Stores reusable block templates.

| Column | Type | Description |
|--------|------|-------------|
| id | id | Primary key |
| name | string | Template name |
| block_type | string | Block type |
| content | json | Template content |
| is_global | boolean | Available to all users |
| created_by | id | Template creator |

### cms_media
Stores uploaded media files.

| Column | Type | Description |
|--------|------|-------------|
| id | id | Primary key |
| filename | string | File name |
| path | string | File path |
| mime_type | string | MIME type |
| size | integer | File size in bytes |
| folder | string | Folder organization |
| metadata | json | Image dimensions, etc. |
| uploaded_by | id | Uploader user |

## Models

### CmsPage
```php
// Create a page
$page = CmsPage::create([
    'title' => 'About Us',
    'slug' => 'about-us',
    'meta_title' => 'About Our Company',
    'meta_description' => 'Learn more about us',
    'layout' => 'default',
]);

// Get all published pages
$pages = CmsPage::where('status', 'published')->get();

// Get page with blocks
$page = CmsPage::with('blocks')->find($pageId);

// Publish a page
$page->update(['status' => 'published', 'published_at' => now()]);

// Create version snapshot
$page->createVersion('Updated pricing section', auth()->id());

// Restore version
$version = $page->versions()->latest()->first();
$page->restoreVersion($version);
```

### CmsPageBlock
```php
// Get all blocks for a page (ordered)
$blocks = $page->blocks()->orderBy('order_index')->get();

// Create a block
$block = $page->blocks()->create([
    'block_type' => 'hero_standard',
    'content' => [
        'title' => 'Welcome',
        'subtitle' => 'To our site',
        'button_text' => 'Get Started',
        'button_url' => '/signup',
    ],
    'settings' => [
        'padding' => 'lg',
        'bgColor' => '#ffffff',
    ],
    'order_index' => 0,
]);

// Update block
$block->update([
    'content' => array_merge($block->content, ['title' => 'New Title']),
]);
```

## Controllers

### PageController

**Routes:**
- `GET /admin/cms/pages` - List pages
- `GET /admin/cms/pages/create` - Show creation form
- `POST /admin/cms/pages` - Create page
- `GET /admin/cms/pages/{id}/edit` - Show editor
- `PUT /admin/cms/pages/{id}` - Update page
- `DELETE /admin/cms/pages/{id}` - Delete page
- `POST /admin/cms/pages/{id}/duplicate` - Duplicate page
- `POST /admin/cms/pages/{id}/publish` - Publish page
- `POST /admin/cms/pages/{id}/unpublish` - Unpublish page
- `POST /admin/cms/pages/{id}/preview` - Preview page
- `GET /admin/cms/pages/{id}/versions` - Get version history
- `POST /admin/cms/pages/{id}/versions/{version}/restore` - Restore version

### BlockController

**Routes:**
- `GET /admin/cms/blocks/types` - Get all block types
- `GET /admin/cms/blocks/{type}/schema` - Get block schema
- `GET /admin/cms/blocks/{type}/defaults` - Get default content
- `POST /admin/cms/blocks/validate` - Validate block content
- `PUT /admin/cms/blocks/reorder` - Reorder blocks
- `POST /admin/cms/blocks/{block}/duplicate` - Duplicate block
- `DELETE /admin/cms/blocks/{block}` - Delete block
- `POST /admin/cms/blocks/{block}/template` - Save as template

### MediaController

**Routes:**
- `GET /admin/cms/media` - List media
- `POST /admin/cms/media` - Upload media
- `PUT /admin/cms/media/{media}` - Update media
- `DELETE /admin/cms/media/{media}` - Delete media
- `POST /admin/cms/media/bulk-delete` - Delete multiple files
- `POST /admin/cms/media/folders` - Create folder

### PublicPageController

**Routes:**
- `GET /{slug}` - View published page
- `GET /sitemap.xml` - XML sitemap of pages

## Block System

### Available Blocks

#### HeroStandard
Full-width hero banner with text and optional image.
```
content:
  - title: string
  - subtitle: string
  - description: string
  - image: url
  - button_text: string
  - button_url: url
  - button_style: enum (primary|secondary|outline)
  - layout: enum (text-image|image-text|full-width)
```

#### FeatureGrid
Grid of feature cards with icons and descriptions.
```
content:
  - title: string
  - subtitle: string
  - items: array[{icon, title, description, link_text, link_url}]
  - columns: number (1-4)
```

#### PricingCards
Pricing plans with features and CTA buttons.
```
content:
  - title: string
  - subtitle: string
  - currency: string ($|€|£)
  - billingPeriod: string (/month|/year)
  - plans: array[{name, description, price, features[], button_text}]
  - highlightPlan: number (index of featured plan)
```

#### CTASection
Call-to-action section with optional background image.
```
content:
  - title: string
  - description: string
  - button_text: string
  - button_url: url
  - secondary_button_text: string
  - secondary_button_url: url
  - layout: enum (text|image)
  - image: url
```

#### FeatureGrid
Grid of features with cards.

#### TextBlock
Rich text or plain text content block.

### Creating Custom Blocks

1. **Create Component**
   ```jsx
   // resources/js/Blocks/MyCustomBlock.jsx
   const MyCustomBlock = ({ content = {}, settings = {} }) => {
     return (
       <div className="custom-block">
         {/* Your component */}
       </div>
     );
   };
   export default MyCustomBlock;
   ```

2. **Register Component**
   ```jsx
   // In BlockRenderer.jsx
   import MyCustomBlock from './MyCustomBlock';
   
   BLOCK_COMPONENTS.my_custom_block = MyCustomBlock;
   ```

3. **Define Schema** - In `BlockRegistry.php`:
   ```php
   [
       'type' => 'my_custom_block',
       'label' => 'My Custom Block',
       'description' => 'A custom block description',
       'category' => 'content',
       'schema' => [
           'properties' => [
               'title' => ['type' => 'string', 'title' => 'Title'],
               'content' => ['type' => 'string', 'title' => 'Content'],
           ],
       ],
       'defaults' => [
           'title' => 'Default Title',
           'content' => '',
       ],
   ]
   ```

## HRMAC Permissions

All admin routes are protected with HRMAC middleware. Configure permissions:

```php
// config/modules.php
[
    'id' => 'cms',
    'label' => 'Content Management',
    'submodules' => [
        [
            'id' => 'pages',
            'label' => 'Pages',
            'components' => [
                ['id' => 'list', 'label' => 'View Pages'],
                ['id' => 'editor', 'label' => 'Page Editor'],
            ],
        ],
        [
            'id' => 'media',
            'label' => 'Media Library',
            'components' => [
                ['id' => 'list', 'label' => 'View Media'],
            ],
        ],
    ],
]
```

Users need specific permissions:
- `cms.pages.list.index` - View pages
- `cms.pages.editor.create` - Create pages
- `cms.pages.editor.edit` - Edit pages
- `cms.pages.media.index` - Access media library

## Testing

Run tests with:
```bash
php artisan test tests/Feature/Cms/
```

Example test:
```php
public function test_can_create_page()
{
    $response = $this->actingAs($user)
        ->post(route('admin.cms.pages.store'), [
            'title' => 'Test Page',
            'slug' => 'test-page',
            'meta_title' => 'Test',
            'meta_description' => 'Test page',
        ]);

    $this->assertDatabaseHas('cms_pages', [
        'title' => 'Test Page',
    ]);
}
```

## Performance

- **Pagination**: Pages list paginated (30 per page)
- **Eager Loading**: Controllers eager-load relationships
- **Caching**: Block types and schemas cached
- **Lazy Loading**: Block previews load on-demand
- **File Optimization**: Images compressed and resized

## Security

- **Route Protection**: All admin routes protected with HRMAC
- **Form Validation**: All inputs validated with Form Requests
- **CSRF Protection**: All forms include CSRF tokens
- **File Security**: Media uploads sanitized and validated
- **HTML Sanitization**: Content escaped on output
- **Authorization**: Users can only edit pages they have permission for

## Troubleshooting

### Pages not showing
- Ensure page status is 'published'
- Check public routes are registered
- Verify page slug is unique

### Blocks not rendering
- Check block type is registered in BlockRenderer
- Ensure block schema is valid
- Check browser console for errors

### Permission denied
- Verify user has required HRMAC permissions
- Check route is protected with middleware
- Ensure module and permission names match

## API Reference

See [API_REFERENCE.md](./docs/API_REFERENCE.md) for detailed API documentation.

## Contributing

To contribute blocks or features:
1. Fork the package
2. Create feature branch
3. Add tests
4. Submit pull request

## License

Proprietary - All rights reserved
