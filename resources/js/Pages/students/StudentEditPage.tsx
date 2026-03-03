import * as React from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import AppLayout from '@/pages/layouts/AppLayout';
import { useStudent } from '@/modules/students/hooks/useStudent';
import { useStudentMutations } from '@/modules/students/hooks/useStudentMutations';
import { PremiumField } from '@/shadcn/PremiumField';
import type { UpdateStudentDTO } from '@/types/api';
import { ArrowLeft, Save, Building2, Share2, MapPin } from 'lucide-react';
import { AuthPageProps } from '@/types/auth';

/**
 * StudentEditPage - React 19 Modern Implementation
 * Uses standard form with FormData API and TanStack Query mutations
 */
export default function StudentEditPage(): React.JSX.Element {
  const { props } = usePage<AuthPageProps & { companyId?: string }>();
  const uuid = props.companyId;

  const { data: company, isPending: isLoadingCompany } = useStudent(uuid);
  const { updateStudent } = useStudentMutations();
  const [errors, setErrors] = React.useState<Record<string, string>>({});

  async function handleSubmit(e: React.FormEvent<HTMLFormElement>) {
    e.preventDefault();
    const formData = new FormData(e.currentTarget);
    
    const payload: UpdateStudentDTO = {
      companyName: formData.get('companyName') as string,
      name: formData.get('name') as string || null,
      email: formData.get('email') as string || null,
      phone: formData.get('phone') as string || null,
      address: formData.get('address') as string || null,
      website: formData.get('website') as string || null,
      linkedin: formData.get('linkedin') as string || null,
      twitter: formData.get('twitter') as string || null,
      facebook: formData.get('facebook') as string || null,
      instagram: formData.get('instagram') as string || null,
      latitude: formData.get('latitude') ? parseFloat(formData.get('latitude') as string) : null,
      longitude: formData.get('longitude') ? parseFloat(formData.get('longitude') as string) : null,
    };

    try {
      await updateStudent.mutateAsync({ userUuid: uuid, payload });
      if (uuid) {
        router.visit('/student');
      }
    } catch (err: any) {
      if (err.response?.data?.errors) {
        setErrors(err.response.data.errors);
      }
    }
  }
  
  const isPending = updateStudent.isPending;

  if (isLoadingCompany) {
    return (
      <AppLayout>
        <div className="flex h-[50vh] flex-col items-center justify-center gap-4">
          <div className="h-10 w-10 border-4 border-(--accent-primary) border-t-transparent rounded-full animate-spin" />
          <p className="text-sm font-medium text-(--text-disabled) animate-pulse">Loading Corporate Identity...</p>
        </div>
      </AppLayout>
    );
  }

  if (!company) {
    return (
      <AppLayout>
        <div className="flex h-[50vh] flex-col items-center justify-center gap-4">
          <p className="text-sm font-medium text-(--accent-error)">Company not found</p>
        </div>
      </AppLayout>
    );
  }

  return (
    <AppLayout>
      <Head title={`Company Profile | ${company.company_name}`} />
      <div className="max-w-5xl mx-auto flex flex-col gap-8 animate-in fade-in slide-in-from-bottom-4 duration-500 pb-12">
        
        {/* ── Header ── */}
        <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
          <div className="flex items-center gap-4">
            <Link
              href="/student"
              className="flex h-10 w-10 items-center justify-center rounded-xl bg-(--bg-card) border border-(--border-default) text-(--text-muted) hover:bg-(--bg-hover) transition-all shadow-sm"
            >
              <ArrowLeft size={20} />
            </Link>
            <div>
              <h1 className="text-3xl font-extrabold tracking-tight text-(--text-primary)">
                Corporate Profile
              </h1>
              <p className="text-sm text-(--text-muted) font-medium">
                Manage legal and contact information for <span className="text-(--accent-primary)">{company.company_name}</span>
              </p>
            </div>
          </div>

          <button
            type="submit"
            form="company-edit-form"
            disabled={isPending}
            className="btn-modern-primary flex items-center gap-2 px-8 py-3 shadow-xl hover:shadow-(--accent-primary)/20 transition-all font-bold disabled:opacity-50"
          >
            {isPending ? (
              <span className="animate-pulse">Syncing...</span>
            ) : (
              <>
                <Save size={18} />
                Save Identity
              </>
            )}
          </button>
        </div>

        {/* Global Error */}
        {errors.general && (
          <div className="p-4 rounded-xl bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800">
            <p className="text-sm text-red-600 dark:text-red-400">{errors.general}</p>
          </div>
        )}

        {/* ── Form Body ── */}
        <form id="company-edit-form" onSubmit={handleSubmit}>
          <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
            {/* ── Left Column: Main Info ── */}
            <div className="lg:col-span-2 space-y-8">
              <section className="card-modern p-8 space-y-8 shadow-2xl border border-(--border-default) glass-morphism">
                <div className="flex items-center gap-3">
                  <Building2 className="text-(--accent-primary)" size={24} />
                  <h2 className="text-xl font-bold text-(--text-primary)">Core Information</h2>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div className="md:col-span-2">
                    <PremiumField 
                      label="Official Company Name" 
                      name="companyName" 
                      defaultValue={company.company_name}
                      required 
                      error={errors.companyName?.[0]} 
                      placeholder="Acme Corporation S.A."
                    />
                  </div>
                  <PremiumField 
                    label="Legal Representative" 
                    name="name" 
                    defaultValue={company.name || ''}
                    error={errors.name?.[0]} 
                    placeholder="John Smith"
                  />
                  <PremiumField 
                    label="Business Email" 
                    name="email" 
                    type="email"
                    defaultValue={company.email || ''}
                    error={errors.email?.[0]} 
                    placeholder="billing@acme.com"
                  />
                  <PremiumField 
                    label="Public Phone" 
                    name="phone" 
                    defaultValue={company.phone || ''}
                    error={errors.phone?.[0]} 
                    placeholder="+1 800-ACME-CORP"
                  />
                  <div className="md:col-span-2">
                    <PremiumField 
                      label="Official Website" 
                      name="website" 
                      type="url"
                      defaultValue={company.website || ''}
                      error={errors.website?.[0]} 
                      placeholder="https://acme.com"
                    />
                  </div>
                  <div className="md:col-span-2">
                    <PremiumField 
                      label="Primary Address" 
                      name="address" 
                      defaultValue={company.address || ''}
                      error={errors.address?.[0]} 
                      isTextArea
                      placeholder="123 Corporate Way, Silicon Valley, CA"
                    />
                  </div>
                </div>
              </section>

              <section className="card-modern p-8 space-y-8 shadow-2xl border border-(--border-default) glass-morphism">
                <div className="flex items-center gap-3">
                  <Share2 className="text-(--accent-primary)" size={24} />
                  <h2 className="text-xl font-bold text-(--text-primary)">Social Media & Public Presence</h2>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <PremiumField 
                    label="LinkedIn" 
                    name="linkedin" 
                    defaultValue={company.linkedin_link || ''}
                    error={errors.linkedin?.[0]} 
                    placeholder="linkedin.com/company/acme"
                  />
                  <PremiumField 
                    label="Instagram" 
                    name="instagram" 
                    defaultValue={company.instagram_link || ''}
                    error={errors.instagram?.[0]} 
                    placeholder="instagram.com/acme"
                  />
                  <PremiumField 
                    label="Twitter / X" 
                    name="twitter" 
                    defaultValue={company.twitter_link || ''}
                    error={errors.twitter?.[0]} 
                    placeholder="x.com/acme"
                  />
                  <PremiumField 
                    label="Facebook" 
                    name="facebook" 
                    defaultValue={company.facebook_link || ''}
                    error={errors.facebook?.[0]} 
                    placeholder="facebook.com/acme"
                  />
                </div>
              </section>
            </div>

            {/* ── Right Column: Sidebar ── */}
            <div className="space-y-8">
              <section className="card-modern p-6 bg-(--bg-surface) border border-(--border-subtle) space-y-6">
                <div className="flex items-center gap-3 mb-2">
                  <MapPin className="text-(--accent-primary)" size={20} />
                  <h3 className="text-sm font-bold uppercase tracking-widest text-(--text-muted)">Geolocation</h3>
                </div>

                <div className="space-y-4">
                  <PremiumField 
                    label="Latitude" 
                    name="latitude" 
                    type="number"
                    step="any"
                    defaultValue={company.latitude?.toString() || ''}
                    error={errors.latitude?.[0]} 
                    placeholder="40.7128"
                  />
                  <PremiumField 
                    label="Longitude" 
                    name="longitude" 
                    type="number"
                    step="any"
                    defaultValue={company.longitude?.toString() || ''}
                    error={errors.longitude?.[0]} 
                    placeholder="-74.0060"
                  />
                </div>
                
                <div className="p-4 rounded-xl bg-(--bg-card) border border-(--border-default) shadow-inner">
                  <p className="text-[11px] text-(--text-disabled) leading-relaxed text-center italic">
                    Used for public map listings and service discovery.
                  </p>
                </div>
              </section>

              <section className="card-modern p-6 bg-(--bg-surface) border border-(--border-subtle) space-y-4">
                <h3 className="text-sm font-bold uppercase tracking-widest text-(--text-muted)">Status</h3>
                <div className="flex items-center justify-between px-4 py-3 rounded-xl bg-(--bg-card) border border-(--border-default)">
                  <span className="text-sm font-medium text-(--text-primary)">Public Visibility</span>
                  <div className={`h-2.5 w-2.5 rounded-full shadow-sm animate-pulse ${!company.deleted_at ? 'bg-(--accent-success)' : 'bg-(--accent-warning)'}`} />
                </div>
              </section>
            </div>
          </div>
        </form>
      </div>
    </AppLayout>
  );
}
