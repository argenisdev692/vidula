import * as React from 'react';
import { Head, usePage } from '@inertiajs/react';
import AppLayout from '@/pages/layouts/AppLayout';
import { PremiumField } from '@/shadcn/PremiumField';
import { useUserMutations } from '@/modules/users/hooks/useUserMutations';
import { UserDetail } from '@/types/users';
import { User, Smartphone, Globe } from 'lucide-react';

interface ProfileProps {
    auth: {
        user: UserDetail;
    };
}

export default function ProfilePage(): React.JSX.Element {
  const { auth } = usePage<ProfileProps>().props;
  const user = auth.user;

  const [form, setForm] = React.useState({
    name: user.name,
    lastName: user.lastName,
    email: user.email,
    username: user.username || '',
    phone: user.phone || '',
  });

  const [errors, setErrors] = React.useState<Record<string, string>>({});
  
  // Logic for updating profile would go here (using mutations)
  
  const handleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const { name, value } = e.target;
    setForm(prev => ({ ...prev, [name]: value }));
  };

  return (
    <AppLayout>
      <Head title="My Profile" />
      <div className="max-w-4xl mx-auto space-y-10 animate-in fade-in slide-in-from-bottom-4 duration-700">
        
        {/* ── Page Header ── */}
        <div className="flex items-center gap-6 p-2">
            <div className="relative group">
                {user.profilePhotoPath ? (
                    <img 
                        src={user.profilePhotoPath} 
                        alt={user.fullName} 
                        className="h-24 w-24 rounded-2xl object-cover shadow-2xl border-2 border-(--accent-primary) group-hover:scale-105 transition-transform duration-300" 
                    />
                ) : (
                    <div className="h-24 w-24 rounded-2xl bg-(--grad-primary) flex items-center justify-center shadow-2xl group-hover:scale-105 transition-transform duration-300">
                        <span className="text-white text-3xl font-black">{(user.name[0] + user.lastName[0]).toUpperCase()}</span>
                    </div>
                )}
                <div className="absolute -bottom-1 -right-1 bg-(--accent-success) h-4 w-4 rounded-full border-2 border-(--bg-app)" />
            </div>
            <div>
              <h1 className="text-3xl font-black text-(--text-primary) tracking-tight">{user.fullName}</h1>
              <p className="text-(--text-muted) font-medium">@{user.username || 'user'} • {user.role || 'Member'}</p>
              <div className="mt-2 flex gap-2">
                  <span className="px-2 py-0.5 rounded-md bg-(--accent-primary)/10 text-(--accent-primary) text-[10px] font-bold uppercase tracking-wider border border-(--accent-primary)/20">
                      Standard Account
                  </span>
              </div>
            </div>
        </div>

        {/* ── Grid Layout ── */}
        <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
            
            {/* ── Personal Info Section ── */}
            <div className="md:col-span-2 space-y-8">
                <section className="card-modern p-8 space-y-8 glass-morphism border-(--border-default) shadow-xl relative overflow-hidden">
                    <div className="absolute top-0 right-0 p-4 opacity-5">
                        <User size={120} />
                    </div>
                    <div className="flex items-center gap-3 relative z-10">
                        <div className="p-2 rounded-lg bg-(--accent-primary)/10 text-(--accent-primary)">
                            <User size={20} />
                        </div>
                        <h2 className="text-lg font-bold text-(--text-primary)">Account Settings</h2>
                    </div>

                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-6 relative z-10">
                        <PremiumField 
                            label="First Name" 
                            name="name" 
                            value={form.name} 
                            onChange={handleChange} 
                            placeholder="John"
                        />
                        <PremiumField 
                            label="Last Name" 
                            name="lastName" 
                            value={form.lastName} 
                            onChange={handleChange} 
                            placeholder="Doe"
                        />
                        <div className="sm:col-span-2">
                             <PremiumField 
                                label="Primary Email" 
                                name="email" 
                                type="email"
                                value={form.email} 
                                onChange={handleChange} 
                                placeholder="john@example.com"
                            />
                        </div>
                        <PremiumField 
                            label="Public Username" 
                            name="username" 
                            value={form.username} 
                            onChange={handleChange} 
                            placeholder="jdoe88"
                        />
                        <PremiumField 
                            label="Contact Phone" 
                            name="phone" 
                            value={form.phone} 
                            onChange={handleChange} 
                            placeholder="+1 555-0199"
                        />
                    </div>

                    <div className="pt-4 flex justify-end relative z-10">
                        <button className="btn-modern-primary px-8 py-2.5 font-bold shadow-lg hover:shadow-(--accent-primary)/30 transition-all">
                            Save Profile
                        </button>
                    </div>
                </section>
            </div>

            {/* ── Sidebar Column ── */}
            <div className="space-y-6">
                <section className="card-modern p-6 space-y-4 border-(--border-subtle) bg-(--bg-surface) shadow-sm">
                    <div className="flex items-center gap-2 mb-2 text-(--text-muted)">
                        <Smartphone size={16} />
                        <h3 className="text-xs font-bold uppercase tracking-widest">Connect</h3>
                    </div>
                    <p className="text-xs text-(--text-disabled) leading-relaxed">
                        Link your phone number to enable Two-Factor Authentication and receive instant notifications.
                    </p>
                    <button className="w-full py-2.5 rounded-xl border border-(--border-default) bg-(--bg-card) text-xs font-bold text-(--text-primary) hover:bg-(--bg-hover) transition-all shadow-sm">
                        Verify Phone
                    </button>
                </section>

                <section className="card-modern p-6 space-y-4 border-(--border-subtle) bg-(--bg-surface) shadow-sm">
                    <div className="flex items-center gap-2 mb-2 text-(--text-muted)">
                        <Globe size={16} />
                        <h3 className="text-xs font-bold uppercase tracking-widest">Region</h3>
                    </div>
                    <div className="flex items-center justify-between">
                         <span className="text-xs font-medium text-(--text-secondary)">Language</span>
                         <span className="text-[11px] font-bold text-(--accent-primary)">English (US)</span>
                    </div>
                </section>
            </div>

        </div>
      </div>
    </AppLayout>
  );
}
