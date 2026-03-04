import * as React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/pages/layouts/AppLayout';
import { useClientMutations } from '@/modules/clients/hooks/useClientMutations';
import type { CreateClientDTO } from '@/types/api';
import { ArrowLeft, Save } from 'lucide-react';

export default function ClientCreatePage(): React.JSX.Element {
  const { createClient: createMutation } = useClientMutations();
  const [formData, setFormData] = React.useState<CreateClientDTO>({
    userUuid: '',
    clientName: '',
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
        router.visit('/clients');
      },
    });
  };

  return (
    <AppLayout>
      <Head title="New Client" />
      <div
        className="max-w-4xl mx-auto flex flex-col gap-8 animate-in fade-in slide-in-from-bottom-4 duration-500 pb-12"
        style={{ fontFamily: 'var(--font-sans)' }}
      >

        {/* ── Header ── */}
        <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
          <div className="flex items-center gap-4">
            <Link
              href="/clients"
              className="flex h-10 w-10 items-center justify-center rounded-xl transition-all shadow-sm"
              style={{
                background: 'var(--bg-elevated)',
                border: '1px solid var(--border-default)',
                color: 'var(--text-muted)',
              }}
            >
              <ArrowLeft size={20} />
            </Link>
            <div>
              <h1
                className="text-2xl font-extrabold tracking-tight"
                style={{ color: 'var(--text-primary)' }}
              >
                New Client
              </h1>
              <p className="text-sm font-medium mt-0.5" style={{ color: 'var(--text-muted)' }}>
                Enter the details below to register a new client.
              </p>
            </div>
          </div>
          <button
            onClick={handleSubmit}
            disabled={createMutation.isPending}
            className="btn-modern btn-modern-primary flex items-center gap-2 px-6 py-2.5 font-bold shadow-lg"
          >
            {createMutation.isPending ? 'Saving...' : <><Save size={16} /> Save Client</>}
          </button>
        </div>

        {/* ── Form Card ── */}
        <div className="card-modern p-8 shadow-2xl" style={{ border: '1px solid var(--border-default)' }}>
          <form onSubmit={handleSubmit} className="space-y-8">

            {/* ── Section: Core Information ── */}
            <div>
              <h3
                className="text-sm font-bold uppercase tracking-widest mb-6"
                style={{ color: 'var(--text-muted)' }}
              >
                Core Information
              </h3>
              <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                {/* Client Name */}
                <div>
                  <label className="input-label" htmlFor="clientName" style={{ color: 'var(--text-secondary)' }}>Client Name *</label>
                  <input
                    id="clientName"
                    name="clientName"
                    type="text"
                    required
                    value={formData.clientName}
                    onChange={handleChange}
                    className="input"
                    placeholder="e.g. Acme Corp"
                  />
                </div>

                <div>
                  <label className="input-label" htmlFor="nif" style={{ color: 'var(--text-secondary)' }}>NIF</label>
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
                  <label className="input-label" htmlFor="email" style={{ color: 'var(--text-secondary)' }}>Contact Email</label>
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
                  <label className="input-label" htmlFor="phone" style={{ color: 'var(--text-secondary)' }}>Phone Number</label>
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
                  <label className="input-label" htmlFor="website" style={{ color: 'var(--text-secondary)' }}>Website URL</label>
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
                  <label className="input-label" htmlFor="address" style={{ color: 'var(--text-secondary)' }}>Address</label>
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
            </div>

            <hr style={{ borderColor: 'var(--border-subtle)' }} />

            {/* ── Section: Social Links ── */}
            <div>
              <h3
                className="text-sm font-bold uppercase tracking-widest mb-6"
                style={{ color: 'var(--text-muted)' }}
              >
                Social Links
              </h3>
              <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div>
                  <label className="input-label" htmlFor="linkedinLink" style={{ color: 'var(--text-secondary)' }}>LinkedIn</label>
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
                  <label className="input-label" htmlFor="twitterLink" style={{ color: 'var(--text-secondary)' }}>Twitter (X)</label>
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
                  <label className="input-label" htmlFor="facebookLink" style={{ color: 'var(--text-secondary)' }}>Facebook</label>
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
                  <label className="input-label" htmlFor="instagramLink" style={{ color: 'var(--text-secondary)' }}>Instagram</label>
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
            </div>

          </form>
        </div>
      </div>
    </AppLayout>
  );
}
