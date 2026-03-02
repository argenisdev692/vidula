import * as React from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import AppLayout from '@/pages/layouts/AppLayout';
import { useStudent } from '@/modules/student/hooks/useStudent';
import { useStudentMutations } from '@/modules/student/hooks/useStudentMutations';
import { PremiumField } from '@/shadcn/PremiumField';
import { UpdateStudentDTO } from '@/types/api';
import { ArrowLeft, Save, Building2, Share2, MapPin } from 'lucide-react';

import { AuthPageProps } from '@/types/auth';

export default function StudentEditPage(): React.JSX.Element {
  const { props } = usePage<AuthPageProps & { companyId?: string }>();
  // If companyId is in props, it's admin editing another company. Otherwise it's 'me'.
  const uuid = props.companyId; 

  const { data: company, isPending } = useStudent(uuid);
  const { updateStudent } = useStudentMutations();

  const [form, setForm] = React.useState<UpdateStudentDTO>({
    companyName: '',
    name: '',
    email: '',
    phone: '',
    address: '',
    website: '',
    facebook: '',
    instagram: '',
    linkedin: '',
    twitter: '',
  });

  React.useEffect(() => {
    if (company) {
      setForm({
        companyName: company.company_name,
        name: company.name || '',
        email: company.email || '',
        phone: company.phone || '',
        address: company.address || '',
        website: company.website || '',
        facebook: company.facebook_link || '',
        instagram: company.instagram_link || '',
        linkedin: company.linkedin_link || '',
        twitter: company.twitter_link || '',
        latitude: company.latitude || undefined,
        longitude: company.longitude || undefined,
      });
    }
  }, [company]);

  const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) => {
    const { name, value } = e.target;
    setForm((prev) => ({ ...prev, [name]: value }));
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    updateStudent.mutate({ userUuid: uuid, payload: form }, {
      onSuccess: () => {
        // Assuming we want to stay or go back to list if admin
        if (uuid) {
            router.visit('/student');
        } else {
            // Toast or stay
        }
      }
    });
  };

  if (isPending) {
    return (
      <AppLayout>
        <div className="flex h-[50vh] flex-col items-center justify-center gap-4">
            <div className="h-10 w-10 border-4 border-(--accent-primary) border-t-transparent rounded-full animate-spin" />
            <p className="text-sm font-medium text-(--text-disabled) animate-pulse">Loading Corporate Identity...</p>
        </div>
      </AppLayout>
    );
  }

  return (
    <AppLayout>
      <Head title={`Company Profile | ${company?.company_name}`} />
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
              <p className="text-sm text-(--text-muted) font-medium">Manage legal and contact information for <span className="text-(--accent-primary)">{company?.company_name}</span></p>
            </div>
          </div>

          <button
            onClick={handleSubmit}
            disabled={updateStudent.isPending}
            className="btn-modern-primary flex items-center gap-2 px-8 py-3 shadow-xl hover:shadow-(--accent-primary)/20 transition-all font-bold"
          >
            {updateStudent.isPending ? 'Syncing...' : <><Save size={18} /> Save Identity</>}
          </button>
        </div>

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
                                value={form.companyName} 
                                onChange={handleChange} 
                                required 
                                placeholder="Acme Corporation S.A."
                            />
                        </div>
                        <PremiumField 
                            label="Legal Representative" 
                            name="name" 
                            value={form.name || ''} 
                            onChange={handleChange} 
                            placeholder="John Smith"
                        />
                         <PremiumField 
                            label="Business Email" 
                            name="email" 
                            type="email"
                            value={form.email || ''} 
                            onChange={handleChange} 
                            placeholder="billing@acme.com"
                        />
                        <PremiumField 
                            label="Public Phone" 
                            name="phone" 
                            value={form.phone || ''} 
                            onChange={handleChange} 
                            placeholder="+1 800-ACME-CORP"
                        />
                        <div className="md:col-span-2">
                           <PremiumField 
                                label="Primary Address" 
                                name="address" 
                                value={form.address || ''} 
                                onChange={handleChange} 
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
                            name="linkedin" 
                            value={form.linkedin || ''} 
                            onChange={handleChange} 
                            placeholder="linkedin.com/company/acme"
                        />
                        <PremiumField 
                            label="Instagram" 
                            name="instagram" 
                            value={form.instagram || ''} 
                            onChange={handleChange} 
                            placeholder="instagram.com/acme"
                        />
                        <PremiumField 
                            label="Twitter / X" 
                            name="twitter" 
                            value={form.twitter || ''} 
                            onChange={handleChange} 
                            placeholder="x.com/acme"
                        />
                        <PremiumField 
                            label="Facebook" 
                            name="facebook" 
                            value={form.facebook || ''} 
                            onChange={handleChange} 
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
                            value={form.latitude?.toString() || ''} 
                            onChange={(e) => setForm(p => ({ ...p, latitude: parseFloat(e.target.value) }))} 
                        />
                        <PremiumField 
                            label="Longitude" 
                            name="longitude" 
                            type="number"
                            step="any"
                            value={form.longitude?.toString() || ''} 
                            onChange={(e) => setForm(p => ({ ...p, longitude: parseFloat(e.target.value) }))} 
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
                         <div className={`h-2.5 w-2.5 rounded-full shadow-sm animate-pulse ${!company?.deleted_at ? 'bg-(--accent-success)' : 'bg-(--accent-warning)'}`} />
                     </div>
                </section>
            </div>
        </div>
      </div>
    </AppLayout>
  );
}
