import { useState } from 'react';
import { useForm, router } from '@inertiajs/react';
import {
  FormPageLayout,
  Field,
  Input,
  Button,
  Toggle,
  HStack,
  VStack,
  Eyebrow,
  Card,
  CardBody,
  Text,
  Mono,
  Alert,
  useToast,
  useHRMAC,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

export default function SeoIndex({ settings, analytics, sitemap }) {
  const toast       = useToast();
  const canEdit     = useHRMAC('seo-management.seo-settings.edit');
  const canAnalytics = useHRMAC('seo-management.analytics-integrations.configure');
  const canSitemap  = useHRMAC('seo-management.sitemap-management.generate');

  const [regenerating, setRegenerating] = useState(false);

  const seoForm = useForm({
    meta_title:         settings?.meta_title         ?? '',
    meta_description:   settings?.meta_description   ?? '',
    og_title:           settings?.og_title           ?? '',
    og_description:     settings?.og_description     ?? '',
    og_image:           settings?.og_image           ?? '',
    canonical_url:      settings?.canonical_url      ?? '',
    noindex:            settings?.noindex            ?? false,
    sitemap_enabled:    settings?.sitemap_enabled    ?? true,
  });

  const analyticsForm = useForm({
    ga_measurement_id:  analytics?.ga_measurement_id  ?? '',
    gtm_container_id:   analytics?.gtm_container_id   ?? '',
    fb_pixel_id:        analytics?.fb_pixel_id        ?? '',
    hotjar_site_id:     analytics?.hotjar_site_id     ?? '',
  });

  function handleSeoSubmit(e) {
    e.preventDefault();
    seoForm.put(route('platform.admin.seo.settings.update'), {
      onSuccess: () => toast.success('SEO settings saved.'),
      onError:   () => toast.error('Failed to save SEO settings.'),
    });
  }

  function handleAnalyticsSubmit(e) {
    e.preventDefault();
    analyticsForm.put(route('platform.admin.seo.analytics.update'), {
      onSuccess: () => toast.success('Analytics integrations saved.'),
      onError:   () => toast.error('Failed to save analytics settings.'),
    });
  }

  function regenerateSitemap() {
    setRegenerating(true);
    router.post(
      route('platform.admin.seo.sitemap.regenerate'),
      {},
      {
        onSuccess: () => toast.success('Sitemap regenerated successfully.'),
        onError:   () => toast.error('Failed to regenerate sitemap.'),
        onFinish:  () => setRegenerating(false),
      }
    );
  }

  return (
    <VStack gap={6}>

      {/* SEO Settings form */}
      <FormPageLayout
        title="SEO Settings"
        breadcrumb={[
          { label: 'Platform Admin', href: route('platform.admin.onboarding.dashboard') },
          { label: 'SEO' },
        ]}
        description="Global meta tags, Open Graph defaults, and sitemap configuration."
        onSubmit={handleSeoSubmit}
      >
        <VStack gap={6}>

          <Card>
            <CardBody>
              <VStack gap={4}>
                <Eyebrow>Meta Tags</Eyebrow>

                <Field
                  label="Meta Title"
                  htmlFor="meta_title"
                  error={seoForm.errors.meta_title}
                  hint="Default page title for search engines."
                >
                  <Input
                    id="meta_title"
                    value={seoForm.data.meta_title}
                    onChange={e => seoForm.setData('meta_title', e.target.value)}
                    placeholder="AEOS365 — Enterprise Made Simple"
                    error={seoForm.errors.meta_title}
                  />
                </Field>

                <Field
                  label="Meta Description"
                  htmlFor="meta_description"
                  error={seoForm.errors.meta_description}
                  hint="155–160 characters recommended."
                >
                  <Input
                    id="meta_description"
                    value={seoForm.data.meta_description}
                    onChange={e => seoForm.setData('meta_description', e.target.value)}
                    placeholder="The all-in-one enterprise SaaS platform…"
                    error={seoForm.errors.meta_description}
                  />
                </Field>

                <Field
                  label="Canonical URL"
                  htmlFor="canonical_url"
                  error={seoForm.errors.canonical_url}
                >
                  <Input
                    id="canonical_url"
                    type="url"
                    leftIcon="link"
                    value={seoForm.data.canonical_url}
                    onChange={e => seoForm.setData('canonical_url', e.target.value)}
                    placeholder="https://aeos365.com"
                    error={seoForm.errors.canonical_url}
                  />
                </Field>

                <Toggle
                  label="Mark site as noindex (disables search engine indexing)"
                  checked={seoForm.data.noindex}
                  onChange={checked => seoForm.setData('noindex', checked)}
                />
              </VStack>
            </CardBody>
          </Card>

          <Card>
            <CardBody>
              <VStack gap={4}>
                <Eyebrow>Open Graph</Eyebrow>

                <Field
                  label="OG Title"
                  htmlFor="og_title"
                  error={seoForm.errors.og_title}
                  hint="Overrides meta title for social sharing."
                >
                  <Input
                    id="og_title"
                    value={seoForm.data.og_title}
                    onChange={e => seoForm.setData('og_title', e.target.value)}
                    placeholder="AEOS365"
                    error={seoForm.errors.og_title}
                  />
                </Field>

                <Field
                  label="OG Description"
                  htmlFor="og_description"
                  error={seoForm.errors.og_description}
                >
                  <Input
                    id="og_description"
                    value={seoForm.data.og_description}
                    onChange={e => seoForm.setData('og_description', e.target.value)}
                    placeholder="Enterprise made simple."
                    error={seoForm.errors.og_description}
                  />
                </Field>

                <Field
                  label="OG Image URL"
                  htmlFor="og_image"
                  error={seoForm.errors.og_image}
                  hint="Recommended size: 1200×630 px."
                >
                  <Input
                    id="og_image"
                    type="url"
                    leftIcon="photo"
                    value={seoForm.data.og_image}
                    onChange={e => seoForm.setData('og_image', e.target.value)}
                    placeholder="https://cdn.example.com/og.png"
                    error={seoForm.errors.og_image}
                  />
                </Field>
              </VStack>
            </CardBody>
          </Card>

          <Card>
            <CardBody>
              <VStack gap={4}>
                <Eyebrow>Sitemap</Eyebrow>

                <Toggle
                  label="Enable XML sitemap"
                  checked={seoForm.data.sitemap_enabled}
                  onChange={checked => seoForm.setData('sitemap_enabled', checked)}
                />

                {sitemap?.last_generated && (
                  <HStack gap={2}>
                    <Text tone="secondary">Last generated:</Text>
                    <Mono tone="secondary">{sitemap.last_generated}</Mono>
                  </HStack>
                )}

                {canSitemap && (
                  <Button
                    intent="soft"
                    leftIcon="arrowPath"
                    loading={regenerating}
                    disabled={regenerating}
                    onClick={regenerateSitemap}
                    type="button"
                  >
                    Regenerate Sitemap Now
                  </Button>
                )}
              </VStack>
            </CardBody>
          </Card>

          {canEdit && (
            <HStack gap={3}>
              <Button
                type="submit"
                intent="primary"
                loading={seoForm.processing}
                disabled={seoForm.processing}
              >
                Save SEO Settings
              </Button>
            </HStack>
          )}

        </VStack>
      </FormPageLayout>

      {/* Analytics Integrations */}
      <FormPageLayout
        title="Analytics Integrations"
        description="Connect Google Analytics, Google Tag Manager, Meta Pixel, and Hotjar."
        onSubmit={handleAnalyticsSubmit}
      >
        <VStack gap={6}>

          <Card>
            <CardBody>
              <VStack gap={4}>
                <Eyebrow>Google</Eyebrow>

                <HStack gap={4}>
                  <Field
                    label="GA4 Measurement ID"
                    htmlFor="ga_measurement_id"
                    error={analyticsForm.errors.ga_measurement_id}
                    hint="Format: G-XXXXXXXXXX"
                  >
                    <Input
                      id="ga_measurement_id"
                      value={analyticsForm.data.ga_measurement_id}
                      onChange={e => analyticsForm.setData('ga_measurement_id', e.target.value)}
                      placeholder="G-XXXXXXXXXX"
                      error={analyticsForm.errors.ga_measurement_id}
                    />
                  </Field>

                  <Field
                    label="GTM Container ID"
                    htmlFor="gtm_container_id"
                    error={analyticsForm.errors.gtm_container_id}
                    hint="Format: GTM-XXXXXXX"
                  >
                    <Input
                      id="gtm_container_id"
                      value={analyticsForm.data.gtm_container_id}
                      onChange={e => analyticsForm.setData('gtm_container_id', e.target.value)}
                      placeholder="GTM-XXXXXXX"
                      error={analyticsForm.errors.gtm_container_id}
                    />
                  </Field>
                </HStack>
              </VStack>
            </CardBody>
          </Card>

          <Card>
            <CardBody>
              <VStack gap={4}>
                <Eyebrow>Meta &amp; Hotjar</Eyebrow>

                <HStack gap={4}>
                  <Field
                    label="Meta Pixel ID"
                    htmlFor="fb_pixel_id"
                    error={analyticsForm.errors.fb_pixel_id}
                  >
                    <Input
                      id="fb_pixel_id"
                      value={analyticsForm.data.fb_pixel_id}
                      onChange={e => analyticsForm.setData('fb_pixel_id', e.target.value)}
                      placeholder="123456789012345"
                      error={analyticsForm.errors.fb_pixel_id}
                    />
                  </Field>

                  <Field
                    label="Hotjar Site ID"
                    htmlFor="hotjar_site_id"
                    error={analyticsForm.errors.hotjar_site_id}
                  >
                    <Input
                      id="hotjar_site_id"
                      value={analyticsForm.data.hotjar_site_id}
                      onChange={e => analyticsForm.setData('hotjar_site_id', e.target.value)}
                      placeholder="1234567"
                      error={analyticsForm.errors.hotjar_site_id}
                    />
                  </Field>
                </HStack>
              </VStack>
            </CardBody>
          </Card>

          {canAnalytics && (
            <HStack gap={3}>
              <Button
                type="submit"
                intent="primary"
                loading={analyticsForm.processing}
                disabled={analyticsForm.processing}
              >
                Save Analytics Settings
              </Button>
            </HStack>
          )}

        </VStack>
      </FormPageLayout>

    </VStack>
  );
}

SeoIndex.layout = page => <App title="SEO Settings">{page}</App>;
