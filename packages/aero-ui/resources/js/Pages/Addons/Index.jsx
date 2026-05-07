import React, { useState } from 'react'
import { useForm } from '@inertiajs/react'
import AppLayout from '@/layouts/AppLayout'

export default function AddonsIndex({ installed, available, product, marketplace_url }) {
  const [showInstallForm, setShowInstallForm] = useState(null)

  const { data, setData, post, processing, errors, reset } = useForm({
    license_key:  '',
    product_code: '',
    zip_file:     null,
  })

  function openInstallForm(productCode) {
    setData({ license_key: '', product_code: productCode, zip_file: null })
    setShowInstallForm(productCode)
  }

  function submitInstall(e) {
    e.preventDefault()
    post(route('addons.install'), {
      forceFormData: true,
      onSuccess: () => { reset(); setShowInstallForm(null) },
    })
  }

  return (
    <AppLayout title="Add-ons &amp; Extensions">
      <div className="max-w-6xl mx-auto space-y-10 py-8 px-4">

        {/* Installed */}
        <section>
          <h2 className="text-lg font-semibold text-content mb-4">Installed Add-ons</h2>
          {installed.length === 0 ? (
            <p className="text-content-secondary text-sm">No add-ons installed yet.</p>
          ) : (
            <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
              {installed.map(addon => (
                <div key={addon.id} className="card p-4 flex items-start justify-between gap-3">
                  <div>
                    <p className="font-medium text-content">{addon.name}</p>
                    <p className="text-xs text-content-secondary mt-0.5">v{addon.version}</p>
                  </div>
                  <span className={`text-xs px-2 py-0.5 rounded-full font-medium ${
                    addon.status === 'active'
                      ? 'bg-success-subtle text-success'
                      : 'bg-danger-subtle text-danger'
                  }`}>
                    {addon.status}
                  </span>
                </div>
              ))}
            </div>
          )}
        </section>

        {/* Available */}
        <section>
          <h2 className="text-lg font-semibold text-content mb-4">Available Add-ons</h2>
          {available.length === 0 ? (
            <p className="text-content-secondary text-sm">All available add-ons are already installed.</p>
          ) : (
            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
              {available.map(prod => (
                <div key={prod.code} className="card overflow-hidden">
                  <div className="p-4">
                    <h3 className="font-semibold text-content">{prod.name}</h3>
                    <p className="text-sm text-content-secondary mt-1 line-clamp-2">{prod.description}</p>
                    <p className="text-sm font-semibold text-primary mt-2">
                      From ${prod.monthly_price}/{prod.currency || 'USD'} /mo
                    </p>
                  </div>

                  <div className="px-4 pb-4 flex gap-2">
                    <a
                      href={`${marketplace_url}/products/${prod.code}`}
                      target="_blank"
                      rel="noreferrer"
                      className="btn btn-outline flex-1 text-sm text-center"
                    >
                      View Details
                    </a>
                    <button
                      onClick={() => openInstallForm(prod.code)}
                      className="btn btn-primary flex-1 text-sm"
                    >
                      Install
                    </button>
                  </div>

                  {showInstallForm === prod.code && (
                    <form onSubmit={submitInstall} className="border-t border-border p-4 bg-surface-alt space-y-3">
                      <p className="text-xs text-content-secondary">
                        Enter your license key from your purchase email.{' '}
                        <a
                          href={`${marketplace_url}/products/${prod.code}`}
                          target="_blank"
                          rel="noreferrer"
                          className="text-primary hover:underline"
                        >
                          Buy now &rarr;
                        </a>
                      </p>

                      <input
                        type="text"
                        placeholder="XXXXXXXX-XXXXXXXX-XXXXXXXX-XXXXXXXX"
                        value={data.license_key}
                        onChange={e => setData('license_key', e.target.value.toUpperCase())}
                        className="input w-full font-mono text-sm"
                        required
                      />
                      {errors.license_key && (
                        <p className="text-xs text-danger">{errors.license_key}</p>
                      )}

                      <details className="text-sm">
                        <summary className="cursor-pointer text-content-secondary hover:text-content">
                          Upload ZIP manually (if auto-download fails)
                        </summary>
                        <input
                          type="file"
                          accept=".zip"
                          onChange={e => setData('zip_file', e.target.files[0])}
                          className="mt-2 text-sm w-full"
                        />
                        {errors.zip_file && (
                          <p className="text-xs text-danger mt-1">{errors.zip_file}</p>
                        )}
                      </details>

                      <div className="flex gap-2">
                        <button
                          type="button"
                          onClick={() => setShowInstallForm(null)}
                          className="btn btn-outline flex-1 text-sm"
                        >
                          Cancel
                        </button>
                        <button
                          type="submit"
                          disabled={processing}
                          className="btn btn-primary flex-1 text-sm disabled:opacity-50"
                        >
                          {processing ? 'Installing…' : 'Install Add-on'}
                        </button>
                      </div>
                    </form>
                  )}
                </div>
              ))}
            </div>
          )}
        </section>
      </div>
    </AppLayout>
  )
}
