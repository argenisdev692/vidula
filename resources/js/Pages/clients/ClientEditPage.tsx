import * as React from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import AppLayout from '@/pages/layouts/AppLayout';
import { useSingleClient } from '@/modules/clients/hooks/useClient';
import { useClientMutations } from '@/modules/clients/hooks/useClientMutations';
import { PremiumField } from '@/shadcn/PremiumField';
import type { UpdateClientDTO } from '@/types/api';
import { ArrowLeft, Save, Building2, Share2, MapPin } from 'lucide-react';
import type { AuthPageProps } from '@/types/auth';

export default function ClientEditPage(): React.JSX.Element {
  const { props } = usePage<AuthPageProps & { clientId?: string }>();
  const uuid = props.clientId;

  const { data: client, isPending } = useSingleClient(uuid);
  const { updateClient } = useClientMutations();

  const [form, setForm] = React.useState<UpdateClientDTO>({
    clientName: '',
    email: '',
    phone: '',
    address: '',
    nif: '',
    website: '',
    facebookLink: '',
    instagramLink: '',
    linkedinLink: '',
    twitterLink: '',
  });

  React.useEffect(() => {
    if (client) {
      setForm({
        clientName: client.client_name || '',
        email: client.email || '',
        phone: client.phone || '',
        address: client.address || '',
        nif: client.nif || '',
        website: client.social_links?.website || '',
        facebookLink: client.social_links?.facebook || '',
        instagramLink: client.social_links?.instagram || '',
        linkedinLink: client.social_links?.linkedin || '',
        twitterLink: client.social_links?.twitter || '',
        latitude: client.coordinates?.latitude || undefined,
        longitude: client.coordinates?.longitude || undefined,
      });
    }
  }, [client]);

  const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) => {
    const { name, value } = e.target;
    setForm((prev) => ({ ...prev, [name]: value }));
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    updateClient.mutate({ userUuid: uuid, payload: form }, {
      onSuccess: () => {
        if (uuid) {
          router.visit('/clients');
        }
      },
    });
  };

  if (isPending) {
    return (
      <AppLayout>
        <Head title="Edit Client" />
        <div className="flex h-[50vh] flex-col items-center justify-center gap-4">
          <div
            className="h-10 w-10 rounded-full animate-spin"
            style={{ border: '4px solid var(--accent-primary)', borderTopColor: 'transparent' }}
          />
          <p className="text-sm font-medium animate-pulse" style={{ color: 'var(--text-muted)' }}>
            Loading client data...
          </p>
        </div>
      </AppLayout>
    );
  }

  return (
    <AppLayout>
      <Head title={`Edit Client | ${client?.client_name ?? ''}`} />
      <div className="max-w-5xl mx-auto flex flex-col gap-8 animate-in fade-in slide-in-from-bottom-4 duration-500 pb-12">

        {/* ── Header ── */}
        <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
          <div className="flex items-center gap-4">
            <Link
              href="/clients"
              className="flex h-10 w-10 items-center justify-center rounded-xl shadow-sm transition-all"
              style={{
                background: 'var(--bg-elevated)',
                border: '1px solid var(--border-default)',
                color: 'var(--text-muted)',
              }}
            >
              <ArrowLeft size={20} />
            </Link>
            <div>
              <h1 className="text-3xl font-extrabold tracking-tight" style={{ color: 'var(--text-primary)' }}>
                Edit Client
              </h1>
              <p className="text-sm font-medium" style={{ color: 'var(--text-muted)' }}>
                Manage information for <span style={{ color: 'var(--accent-primary)' }}>{client?.client_name}</span>
              </p>
            </div>
          </div>

          <button
            onClick={handleSubmit}
            disabled={updateClient.isPending}
            className="btn-modern btn-modern-primary flex items-center gap-2 px-8 py-3 shadow-xl font-bold"
          >
            {updateClient.isPending ? 'Saving...' : <><Save size={18} /> Save Changes</>}
          </button>
        </div>

        <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
          {/* ── Left Column: Main Info ── */}
          <div className="lg:col-span-2 space-y-8">
            <section
              className="card-modern p-8 space-y-8 shadow-2xl"
              style={{ border: '1px solid var(--border-default)' }}
            >
              <div className="flex items-center gap-3">
                <Building2 size={24} style={{ color: 'var(--accent-primary)' }} />
                <h2 className="text-xl font-bold" style={{ color: 'var(--text-primary)' }}>Core Information</h2>
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div className="md:col-span-2">
                  <PremiumField
                    label="Client Name"
                    name="clientName"
                    value={form.clientName || ''}
                    onChange={handleChange}
                    required
                    placeholder="Acme Corporation S.A."
                  />
                </div>
                <div className="md:col-span-2">
                  <PremiumField
                    label="NIF"
                    name="nif"
                    value={form.nif || ''}
                    onChange={handleChange}
                    placeholder="A12345678"
                  />
                </div>
                <PremiumField
                  label="Business Email"
                  name="email"
                  type="email"
                  value={form.email || ''}
                  onChange={handleChange}
                  placeholder="billing@acme.com"
                />
                <PremiumField
                  label="Phone"
                  name="phone"
                  value={form.phone || ''}
                  onChange={handleChange}
                  placeholder="+1 800 000 0000"
                />
                <div className="md:col-span-2">
                  <PremiumField
                    label="Address"
                    name="address"
                    value={form.address || ''}
                    onChange={handleChange}
                    isTextArea
                    placeholder="123 Corporate Way, Silicon Valley, CA"
                  />
                </div>
              </div>
            </section>

            <section
              className="card-modern p-8 space-y-8 shadow-2xl"
              style={{ border: '1px solid var(--border-default)' }}
            >
              <div className="flex items-center gap-3">
                <Share2 size={24} style={{ color: 'var(--accent-primary)' }} />
                <h2 className="text-xl font-bold" style={{ color: 'var(--text-primary)' }}>Social Media & Public Presence</h2>
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div className="md:col-span-2">
                  <PremiumField
                    label="Official Website"
                    name="website"
                    type="url"
                    value={form.website || ''}
                    onChange={handleChange}
                    placeholder="https://acme.com"
                  />
                </div>
                <PremiumField
                  label="LinkedIn"
                  name="linkedinLink"
                  value={form.linkedinLink || ''}
                  onChange={handleChange}
                  placeholder="linkedin.com/company/acme"
                />
                <PremiumField
                  label="Instagram"
                  name="instagramLink"
                  value={form.instagramLink || ''}
                  onChange={handleChange}
                  placeholder="instagram.com/acme"
                />
                <PremiumField
                  label="Twitter / X"
                  name="twitterLink"
                  value={form.twitterLink || ''}
                  onChange={handleChange}
                  placeholder="x.com/acme"
                />
                <PremiumField
                  label="Facebook"
                  name="facebookLink"
                  value={form.facebookLink || ''}
                  onChange={handleChange}
                  placeholder="facebook.com/acme"
                />
              </div>
            </section>
          </div>

          {/* ── Right Column: Sidebar ── */}
          <div className="space-y-8">
            <section
              className="card-modern p-6 space-y-6"
              style={{ background: 'var(--bg-surface)', border: '1px solid var(--border-subtle)' }}
            >
              <div className="flex items-center gap-3 mb-2">
                <MapPin size={20} style={{ color: 'var(--accent-primary)' }} />
                <h3
                  className="text-sm font-bold uppercase tracking-widest"
                  style={{ color: 'var(--text-muted)' }}
                >
                  Geolocation
                </h3>
              </div>

              <div className="space-y-4">
                <PremiumField
                  label="Latitude"
                  name="latitude"
                  type="number"
                  step="any"
                  value={form.latitude?.toString() || ''}
                  onChange={(e) => setForm(p => ({ ...p, latitude: parseFloat(e.target.value) || undefined }))}
                />
                <PremiumField
                  label="Longitude"
                  name="longitude"
                  type="number"
                  step="any"
                  value={form.longitude?.toString() || ''}
                  onChange={(e) => setForm(p => ({ ...p, longitude: parseFloat(e.target.value) || undefined }))}
                />
              </div>

              <div
                className="p-4 rounded-xl shadow-inner"
                style={{ background: 'var(--bg-elevated)', border: '1px solid var(--border-default)' }}
              >
                <p className="text-[11px] leading-relaxed text-center italic" style={{ color: 'var(--text-muted)' }}>
                  Used for map listings and service discovery.
                </p>
              </div>
            </section>

            <section
              className="card-modern p-6 space-y-4"
              style={{ background: 'var(--bg-surface)', border: '1px solid var(--border-subtle)' }}
            >
              <h3 className="text-sm font-bold uppercase tracking-widest" style={{ color: 'var(--text-muted)' }}>
                Status
              </h3>
              <div
                className="flex items-center justify-between px-4 py-3 rounded-xl"
                style={{ background: 'var(--bg-elevated)', border: '1px solid var(--border-default)' }}
              >
                <span className="text-sm font-medium" style={{ color: 'var(--text-primary)' }}>Visibility</span>
                <div
                  className="h-2.5 w-2.5 rounded-full shadow-sm animate-pulse"
                  style={{ background: !client?.deleted_at ? 'var(--accent-success)' : 'var(--accent-warning)' }}
                />
              </div>
            </section>
          </div>
        </div>
      </div>
    </AppLayout>
  );
}
