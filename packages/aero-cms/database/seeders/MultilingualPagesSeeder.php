<?php

namespace Aero\Cms\Database\Seeders;

use Aero\Cms\Models\CmsPage;
use Aero\Cms\Models\CmsCategory;
use Illuminate\Database\Seeder;

class MultilingualPagesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create a category for testing
        $category = CmsCategory::firstOrCreate(
            ['slug' => 'documentation'],
            [
                'name' => 'Documentation',
                'description' => 'CMS Documentation pages in multiple languages',
            ]
        );

        // Create multilingual "About Us" pages
        $aboutPages = [
            [
                'lang' => 'en',
                'slug' => 'about-us',
                'title' => 'About Us',
                'content' => 'Learn more about aeos365 - the complete ERP solution for modern businesses.',
                'description' => 'About aeos365',
            ],
            [
                'lang' => 'es',
                'slug' => 'acerca-de',
                'title' => 'Acerca de Nosotros',
                'content' => 'Conozca más sobre aeos365 - la solución ERP completa para empresas modernas.',
                'description' => 'Acerca de aeos365',
            ],
            [
                'lang' => 'fr',
                'slug' => 'a-propos',
                'title' => 'À Propos',
                'content' => 'En savoir plus sur aeos365 - la solution ERP complète pour les entreprises modernes.',
                'description' => 'À Propos d\'aeos365',
            ],
            [
                'lang' => 'de',
                'slug' => 'uber-uns',
                'title' => 'Über Uns',
                'content' => 'Erfahren Sie mehr über aeos365 - die komplette ERP-Lösung für moderne Unternehmen.',
                'description' => 'Über aeos365',
            ],
        ];

        foreach ($aboutPages as $pageData) {
            CmsPage::firstOrCreate(
                [
                    'slug' => $pageData['slug'],
                    'language' => $pageData['lang'],
                    'translation_key' => 'about-us',
                ],
                [
                    'title' => $pageData['title'],
                    'content' => $pageData['content'],
                    'meta_title' => $pageData['title'],
                    'meta_description' => $pageData['description'],
                    'status' => 'published',
                    'language' => $pageData['lang'],
                    'translation_key' => 'about-us',
                    'cms_category_id' => $category->id,
                    'allow_indexing' => true,
                    'is_homepage' => false,
                ]
            );
        }

        // Create multilingual "Getting Started" pages
        $gettingStartedPages = [
            [
                'lang' => 'en',
                'slug' => 'getting-started',
                'title' => 'Getting Started',
                'content' => 'Get started with aeos365 in just a few minutes. Follow our comprehensive guide to set up your account and explore all features.',
                'description' => 'Getting Started with Aero',
            ],
            [
                'lang' => 'es',
                'slug' => 'comenzar',
                'title' => 'Cómo Comenzar',
                'content' => 'Comience con aeos365 en solo unos minutos. Siga nuestra guía completa para configurar su cuenta y explorar todas las características.',
                'description' => 'Cómo Comenzar con Aero',
            ],
            [
                'lang' => 'fr',
                'slug' => 'bien-demarrer',
                'title' => 'Bien Démarrer',
                'content' => 'Commencez avec aeos365 en quelques minutes seulement. Suivez notre guide complet pour configurer votre compte et explorer toutes les fonctionnalités.',
                'description' => 'Bien Démarrer avec Aero',
            ],
        ];

        foreach ($gettingStartedPages as $pageData) {
            CmsPage::firstOrCreate(
                [
                    'slug' => $pageData['slug'],
                    'language' => $pageData['lang'],
                    'translation_key' => 'getting-started',
                ],
                [
                    'title' => $pageData['title'],
                    'content' => $pageData['content'],
                    'meta_title' => $pageData['title'],
                    'meta_description' => $pageData['description'],
                    'status' => 'published',
                    'language' => $pageData['lang'],
                    'translation_key' => 'getting-started',
                    'cms_category_id' => $category->id,
                    'allow_indexing' => true,
                    'is_homepage' => false,
                ]
            );
        }

        $this->command->info('Multilingual CMS pages seeded successfully!');
    }
}
