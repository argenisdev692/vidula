import * as React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/pages/layouts/AppLayout';
import { useClientMutations } from '@/modules/clients/hooks/useClientMutations';
import type { CreateClientDTO } from '@/types/api';

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
// ClientCreatePage
// ══════════════════════════════════════════════════════════════
export default function ClientCreatePage(): React.JSX.Element {
  const { createClient: createMutation } = useClientMutations();
  const [formData, setFormData] = React.useState<CreateClientDTO>({
    userUuid: '', 
    companyName: '',
    email: '',
    phone: '',
    nif: '',
    address: '',
    website: '',
    facebookLink: '',
    instagramLink: '',
    linkedinLink: '',
    twitterLink: '',
  });

  const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) => {
    const { name, value } = e.target;
    setFormData((prev) => ({ ...prev, [name]: value }));
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    createMutation.mutate(formData, {
      onSuccess: () => {
        router.visit('/client');
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
              href="/client"
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
                <label className="input-label" htmlFor="companyName">Company Name *</label>
                <input
                  id="companyName"
                  name="companyName"
                  type="text"
                  required
                  value={formData.companyName}
                  onChange={handleChange}
                  className="input"
                  placeholder="e.g. Acme Corp"
                />
              </div>

              <div>
                <label className="input-label" htmlFor="nif">NIF</label>
                <input
                  id="nif"
                  name="nif"
                  type="text"
                  value={formData.nif || ''}
                  onChange={handleChange}
                  className="input"
                  placeholder="e.g. A12345678"
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
                <label className="input-label" htmlFor="linkedinLink">LinkedIn</label>
                <input
                  id="linkedinLink"
                  name="linkedinLink"
                  type="url"
                  value={formData.linkedinLink || ''}
                  onChange={handleChange}
                  className="input"
                  placeholder="https://linkedin.com/company/acmecorp"
                />
              </div>
              <div>
                <label className="input-label" htmlFor="twitterLink">Twitter (X)</label>
                <input
                  id="twitterLink"
                  name="twitterLink"
                  type="url"
                  value={formData.twitterLink || ''}
                  onChange={handleChange}
                  className="input"
                  placeholder="https://twitter.com/acmecorp"
                />
              </div>
              <div>
                <label className="input-label" htmlFor="facebookLink">Facebook</label>
                <input
                  id="facebookLink"
                  name="facebookLink"
                  type="url"
                  value={formData.facebookLink || ''}
                  onChange={handleChange}
                  className="input"
                  placeholder="https://facebook.com/acmecorp"
                />
              </div>
              <div>
                <label className="input-label" htmlFor="instagramLink">Instagram</label>
                <input
                  id="instagramLink"
                  name="instagramLink"
                  type="url"
                  value={formData.instagramLink || ''}
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
