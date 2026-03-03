import * as React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/pages/layouts/AppLayout';
import { useProductMutations } from '@/modules/products/hooks/useProductMutations';
import type { CreateProductDTO } from '@/types/api';

// ══════════════════════════════════════════════════════════════
// Icons
// ══════════════════════════════════════════════════════════════
const ic = {
  w: 16, h: 16, viewBox: '0 0 24 24', fill: 'none',
  stroke: 'currentColor', strokeWidth: 2,
  strokeLinecap: 'round' as const, strokeLinejoin: 'round' as const,
};
const IconArrowLeft = () => <svg {...ic}><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>;
const IconSave = () => <svg {...ic}><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>;

// ══════════════════════════════════════════════════════════════
// ProductCreatePage
// ══════════════════════════════════════════════════════════════
export default function ProductCreatePage(): React.JSX.Element {
  const { createProduct: createMutation } = useProductMutations();
  const [formData, setFormData] = React.useState<CreateProductDTO>({
    user_id: 1, // Defaulting to 1 for now, or this could come from auth context
    company_name: '',
    name: '',
    email: '',
    phone: '',
    address: '',
    website: '',
    facebook_link: '',
    instagram_link: '',
    linkedin_link: '',
    twitter_link: '',
  });

  const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) => {
    const { name, value } = e.target;
    setFormData((prev) => ({ ...prev, [name]: value }));
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    createMutation.mutate(formData, {
      onSuccess: () => {
        router.visit('/product');
      },
      onError: (error) => {
        console.error('Failed to create company data:', error);
        alert('Failed to save company data. Please check the console.');
      }
    });
  };

  return (
    <AppLayout>
      <Head title="Create Company Profile" />
      <div style={{ fontFamily: 'var(--font-sans)', maxWidth: '800px', margin: '0 auto' }}>
        
        {/* ── Header ── */}
        <div className="mb-6 flex items-center justify-between">
          <div className="flex items-center gap-4">
            <Link
              href="/product"
              className="flex h-9 w-9 items-center justify-center rounded-lg transition-all hover:bg-(--bg-hover)"
              style={{ color: 'var(--text-muted)' }}
            >
              <IconArrowLeft />
            </Link>
            <div>
              <h1 className="text-xl font-bold tracking-tight text-gray-900 dark:text-gray-100">
                New Company Profile
              </h1>
              <p className="text-xs mt-1" style={{ color: 'var(--text-muted)' }}>
                Enter the details below to register a new corporate entity.
              </p>
            </div>
          </div>
          <button
            onClick={handleSubmit}
            disabled={createMutation.isPending}
            className="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold transition-all disabled:opacity-50"
            style={{
              background: 'var(--accent-primary)',
              color: 'var(--color-white)',
            }}
          >
            {createMutation.isPending ? 'Saving...' : <><IconSave /> Save Profile</>}
          </button>
        </div>

        {/* ── Form Card ── */}
        <div className="card">
          <form onSubmit={handleSubmit} className="space-y-6">
            
            <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
              {/* Company Name */}
              <div>
                <label className="input-label" htmlFor="company_name">Company Name *</label>
                <input
                  id="company_name"
                  name="company_name"
                  type="text"
                  required
                  value={formData.company_name}
                  onChange={handleChange}
                  className="input"
                  placeholder="e.g. Acme Corp"
                />
              </div>

              {/* Representative Name */}
              <div>
                <label className="input-label" htmlFor="name">Representative Name</label>
                <input
                  id="name"
                  name="name"
                  type="text"
                  value={formData.name || ''}
                  onChange={handleChange}
                  className="input"
                  placeholder="e.g. Jane Doe"
                />
              </div>

              {/* Email */}
              <div>
                <label className="input-label" htmlFor="email">Contact Email</label>
                <input
                  id="email"
                  name="email"
                  type="email"
                  value={formData.email || ''}
                  onChange={handleChange}
                  className="input"
                  placeholder="contact@acmecorp.com"
                />
              </div>

              {/* Phone */}
              <div>
                <label className="input-label" htmlFor="phone">Phone Number</label>
                <input
                  id="phone"
                  name="phone"
                  type="text"
                  value={formData.phone || ''}
                  onChange={handleChange}
                  className="input"
                  placeholder="+1 (555) 000-0000"
                />
              </div>
              
              {/* Website */}
              <div className="md:col-span-2">
                <label className="input-label" htmlFor="website">Website URL</label>
                <input
                  id="website"
                  name="website"
                  type="url"
                  value={formData.website || ''}
                  onChange={handleChange}
                  className="input"
                  placeholder="https://www.acmecorp.com"
                />
              </div>

              {/* Address */}
              <div className="md:col-span-2">
                <label className="input-label" htmlFor="address">Address</label>
                <textarea
                  id="address"
                  name="address"
                  rows={3}
                  value={formData.address || ''}
                  onChange={handleChange}
                  className="input h-auto! pt-2"
                  placeholder="123 Corporate Blvd, Suite 100..."
                />
              </div>
            </div>

            <hr style={{ borderColor: 'var(--border-subtle)', margin: '24px 0' }} />

            <h3 className="mb-4 text-sm font-semibold" style={{ color: 'var(--text-secondary)' }}>
              Social Links
            </h3>

            <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
              <div>
                <label className="input-label" htmlFor="linkedin_link">LinkedIn</label>
                <input
                  id="linkedin_link"
                  name="linkedin_link"
                  type="url"
                  value={formData.linkedin_link || ''}
                  onChange={handleChange}
                  className="input"
                  placeholder="https://linkedin.com/company/acmecorp"
                />
              </div>
              <div>
                <label className="input-label" htmlFor="twitter_link">Twitter (X)</label>
                <input
                  id="twitter_link"
                  name="twitter_link"
                  type="url"
                  value={formData.twitter_link || ''}
                  onChange={handleChange}
                  className="input"
                  placeholder="https://twitter.com/acmecorp"
                />
              </div>
              <div>
                <label className="input-label" htmlFor="facebook_link">Facebook</label>
                <input
                  id="facebook_link"
                  name="facebook_link"
                  type="url"
                  value={formData.facebook_link || ''}
                  onChange={handleChange}
                  className="input"
                  placeholder="https://facebook.com/acmecorp"
                />
              </div>
              <div>
                <label className="input-label" htmlFor="instagram_link">Instagram</label>
                <input
                  id="instagram_link"
                  name="instagram_link"
                  type="url"
                  value={formData.instagram_link || ''}
                  onChange={handleChange}
                  className="input"
                  placeholder="https://instagram.com/acmecorp"
                />
              </div>
            </div>

          </form>
        </div>
      </div>
    </AppLayout>
  );
}
