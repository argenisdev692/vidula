import * as React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/pages/layouts/AppLayout';
import { useCompanyDataMutations } from '@/modules/company-data/hooks/useCompanyDataMutations';
import { PremiumField } from '@/shadcn/PremiumField';
import type { CreateCompanyDataDTO } from '@/types/api';
import { ArrowLeft, Save, Building2, Share2, Info } from 'lucide-react';

export default function CompanyDataCreatePage(): React.JSX.Element {
  const { createCompanyData: createMutation } = useCompanyDataMutations();

  const [form, setForm] = React.useState<CreateCompanyDataDTO>({
    user_id: 1,
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

  const [errors, setErrors] = React.useState<Record<string, string>>({});

  function handleChange(e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>): void {
    const { name, value } = e.target;
    setForm((prev) => ({ ...prev, [name]: value }));
    if (errors[name]) setErrors((prev) => ({ ...prev, [name]: '' }));
  }

  function handleSubmit(e: React.FormEvent): void {
    e.preventDefault();
    createMutation.mutate(form, {
      onSuccess: () => {
        router.visit('/company-data');
      },
      onError: (err: any) => {
        if (err.response?.data?.errors) {
          const serverErrors: Record<string, string> = {};
          for (const [key, msgs] of Object.entries(err.response.data.errors)) {
            serverErrors[key] = (msgs as string[])[0] ?? '';
          }
          setErrors(serverErrors);
        }
      },
    });
  }

  return (
    <AppLayout>
      <Head title="Create Company Profile" />
      <div className="max-w-5xl mx-auto flex flex-col gap-8 animate-in fade-in slide-in-from-bottom-4 duration-500 pb-12">

        {/* ── Header ── */}
        <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
          <div className="flex items-center gap-4">
            <Link
              href="/company-data"
              className="flex h-10 w-10 items-center justify-center rounded-xl bg-(--bg-card) border border-(--border-default) text-(--text-muted) hover:bg-(--bg-hover) hover:text-(--accent-primary) transition-all shadow-sm"
            >
              <ArrowLeft size={20} />
            </Link>
            <div>
              <h1 className="text-3xl font-extrabold tracking-tight text-(--text-primary)">
                New Company Profile
              </h1>
              <p className="text-sm text-(--text-muted) font-medium mt-0.5">
                Register a new corporate entity in the platform
              </p>
            </div>
          </div>

          <button
            onClick={handleSubmit}
            disabled={createMutation.isPending}
            className="btn-modern-primary flex items-center gap-2 px-8 py-3 shadow-xl hover:shadow-(--accent-primary)/20 transition-all font-bold disabled:opacity-50"
          >
            {createMutation.isPending ? (
              <span className="animate-pulse">Creating...</span>
            ) : (
              <>
                <Save size={18} />
                <span>Save Profile</span>
              </>
            )}
          </button>
        </div>

        {/* ── Body: 2/3 + 1/3 layout ── */}
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">

          {/* ── Left column: Main form sections ── */}
          <div className="lg:col-span-2 space-y-8">

            {/* Core Information */}
            <section className="card-modern p-8 space-y-8 shadow-2xl border border-(--border-default)">
              <div className="flex items-center gap-3">
                <Building2 className="text-(--accent-primary)" size={24} />
                <h2 className="text-xl font-bold text-(--text-primary)">Core Information</h2>
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div className="md:col-span-2">
                  <PremiumField
                    label="Official Company Name"
                    name="company_name"
                    value={form.company_name}
                    onChange={handleChange}
                    required
                    error={errors.company_name}
                    placeholder="Acme Corporation S.A."
                  />
                </div>
                <PremiumField
                  label="Legal Representative"
                  name="name"
                  value={form.name || ''}
                  onChange={handleChange}
                  error={errors.name}
                  placeholder="John Smith"
                />
                <PremiumField
                  label="Business Email"
                  name="email"
                  type="email"
                  value={form.email || ''}
                  onChange={handleChange}
                  error={errors.email}
                  placeholder="billing@acme.com"
                />
                <PremiumField
                  label="Public Phone"
                  name="phone"
                  value={form.phone || ''}
                  onChange={handleChange}
                  error={errors.phone}
                  placeholder="+1 800-ACME-CORP"
                />
                <div className="md:col-span-2">
                  <PremiumField
                    label="Primary Address"
                    name="address"
                    value={form.address || ''}
                    onChange={handleChange}
                    isTextArea
                    error={errors.address}
                    placeholder="123 Corporate Way, Silicon Valley, CA"
                  />
                </div>
              </div>
            </section>

            {/* Social Media & Public Presence */}
            <section className="card-modern p-8 space-y-8 shadow-2xl border border-(--border-default)">
              <div className="flex items-center gap-3">
                <Share2 className="text-(--accent-primary)" size={24} />
                <h2 className="text-xl font-bold text-(--text-primary)">Social Media &amp; Public Presence</h2>
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div className="md:col-span-2">
                  <PremiumField
                    label="Official Website"
                    name="website"
                    type="url"
                    value={form.website || ''}
                    onChange={handleChange}
                    error={errors.website}
                    placeholder="https://acme.com"
                  />
                </div>
                <PremiumField
                  label="LinkedIn"
                  name="linkedin_link"
                  value={form.linkedin_link || ''}
                  onChange={handleChange}
                  error={errors.linkedin_link}
                  placeholder="linkedin.com/company/acme"
                />
                <PremiumField
                  label="Instagram"
                  name="instagram_link"
                  value={form.instagram_link || ''}
                  onChange={handleChange}
                  error={errors.instagram_link}
                  placeholder="instagram.com/acme"
                />
                <PremiumField
                  label="Twitter / X"
                  name="twitter_link"
                  value={form.twitter_link || ''}
                  onChange={handleChange}
                  error={errors.twitter_link}
                  placeholder="x.com/acme"
                />
                <PremiumField
                  label="Facebook"
                  name="facebook_link"
                  value={form.facebook_link || ''}
                  onChange={handleChange}
                  error={errors.facebook_link}
                  placeholder="facebook.com/acme"
                />
              </div>
            </section>
          </div>

          {/* ── Right column: Sidebar ── */}
          <div className="space-y-6">
            <section className="card-modern p-6 bg-(--bg-surface) border border-(--border-subtle) space-y-4">
              <div className="flex items-center gap-3">
                <Info className="text-(--accent-primary)" size={18} />
                <h3 className="text-sm font-bold uppercase tracking-widest text-(--text-muted)">
                  Registration Info
                </h3>
              </div>

              <div className="space-y-3">
                <div className="p-4 rounded-xl bg-(--bg-card) border border-(--border-default) shadow-inner">
                  <p className="text-[11px] text-(--text-disabled) leading-relaxed">
                    After creating a company profile, you can assign users to it, attach documents, and configure geolocation data from the profile detail page.
                  </p>
                </div>

                <div className="p-4 rounded-xl bg-(--bg-card) border border-(--border-default) shadow-inner">
                  <p className="text-[11px] text-(--text-disabled) leading-relaxed">
                    Fields marked with <span className="text-(--accent-error) font-bold">*</span> are required. Social links and contact details can be filled in later.
                  </p>
                </div>
              </div>
            </section>

            {/* Status preview */}
            <section className="card-modern p-6 bg-(--bg-surface) border border-(--border-subtle) space-y-4">
              <h3 className="text-sm font-bold uppercase tracking-widest text-(--text-muted)">Status</h3>
              <div className="flex items-center justify-between px-4 py-3 rounded-xl bg-(--bg-card) border border-(--border-default)">
                <span className="text-sm font-medium text-(--text-primary)">Public Visibility</span>
                <div className="h-2.5 w-2.5 rounded-full bg-(--accent-success) shadow-sm animate-pulse" />
              </div>
              <p className="text-[11px] text-(--text-disabled) leading-relaxed italic text-center">
                New profiles are active by default.
              </p>
            </section>
          </div>
        </div>

      </div>
    </AppLayout>
  );
}
