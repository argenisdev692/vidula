import * as React from 'react';
import { Head, usePage, useForm } from '@inertiajs/react';
import AppLayout from '@/pages/layouts/AppLayout';
import { PremiumField } from '@/shadcn/PremiumField';
import { User, Smartphone, Globe, Shield, Loader2 } from 'lucide-react';
import { sileo } from 'sileo';
import type { ProfilePageProps } from '@/types/auth';

export default function ProfilePage(): React.JSX.Element {
  const { auth } = usePage<ProfilePageProps>().props;
  const user = auth.user;

  // Profile Form
  const { 
    data: profileData, 
    setData: setProfileData, 
    put: updateProfile, 
    processing: profileProcessing, 
    errors: profileErrors, 
    recentlySuccessful: profileSuccessful 
  } = useForm({
    name: user.name || '',
    last_name: user.last_name || '',
    email: user.email || '',
    username: user.username || '',
    phone: user.phone || '',
  });

  // Password Form
  const {
    data: pwdData,
    setData: setPwdData,
    put: updatePassword,
    processing: pwdProcessing,
    errors: pwdErrors,
    recentlySuccessful: pwdSuccessful,
    reset: resetPwd,
  } = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
  });

  const submitProfile = (e: React.FormEvent) => {
    e.preventDefault();
    updateProfile('/user/profile-information', {
      preserveScroll: true,
      onSuccess: () => sileo.success({ title: "Profile updated successfully." }),
      onError: () => sileo.error({ title: "Failed to update profile. Please check the errors." }),
    });
  };

  const submitPassword = (e: React.FormEvent) => {
    e.preventDefault();
    updatePassword('/user/password', {
      preserveScroll: true,
      onSuccess: () => {
        resetPwd();
        sileo.success({ title: "Password updated successfully." });
      },
      onError: () => sileo.error({ title: "Failed to update password. Please verify your information." }),
    });
  };

  return (
    <AppLayout>
      <Head title="My Profile" />
      <div className="max-w-4xl mx-auto space-y-10 animate-in fade-in slide-in-from-bottom-4 duration-700">
        
        {/* ── Page Header ── */}
        <div className="flex items-center gap-6 p-2">
            <div className="relative group">
                {user.profile_photo_path ? (
                    <img 
                        src={user.profile_photo_path} 
                        alt={`${user.name} ${user.last_name || ''}`}
                        className="h-24 w-24 rounded-2xl object-cover shadow-2xl border-2 border-(--accent-primary) group-hover:scale-105 transition-transform duration-300" 
                    />
                ) : (
                    <div className="h-24 w-24 rounded-2xl bg-(--grad-primary) flex items-center justify-center shadow-2xl group-hover:scale-105 transition-transform duration-300">
                        <span className="text-white text-3xl font-black">{((user.name?.[0] || '') + (user.last_name?.[0] || '')).toUpperCase() || 'U'}</span>
                    </div>
                )}
                <div className="absolute -bottom-1 -right-1 bg-(--accent-success) h-4 w-4 rounded-full border-2 border-(--bg-app)" />
            </div>
            <div>
              <h1 className="text-3xl font-black text-(--text-primary) tracking-tight">{`${user.name} ${user.last_name || ''}`.trim()}</h1>
              <p className="text-(--text-muted) font-medium">@{user.username || 'user'} • {user.roles?.[0] || 'Member'}</p>
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

                    <form onSubmit={submitProfile} className="relative z-10 block">
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <PremiumField 
                                label="First Name" 
                                name="name" 
                                value={profileData.name} 
                                onChange={(e) => setProfileData('name', e.target.value)} 
                                placeholder="John"
                                error={profileErrors.name}
                                required
                            />
                            <PremiumField 
                                label="Last Name" 
                                name="last_name" 
                                value={profileData.last_name} 
                                onChange={(e) => setProfileData('last_name', e.target.value)} 
                                placeholder="Doe"
                                error={profileErrors.last_name}
                                required
                            />
                            <div className="sm:col-span-2">
                                <PremiumField 
                                    label="Primary Email" 
                                    name="email" 
                                    type="email"
                                    value={profileData.email} 
                                    onChange={(e) => setProfileData('email', e.target.value)} 
                                    placeholder="john@example.com"
                                    error={profileErrors.email}
                                    required
                                />
                            </div>
                            <PremiumField 
                                label="Public Username" 
                                name="username" 
                                value={profileData.username} 
                                onChange={(e) => setProfileData('username', e.target.value)} 
                                placeholder="jdoe88"
                                error={profileErrors.username}
                                required
                            />
                            <PremiumField 
                                label="Contact Phone" 
                                name="phone" 
                                value={profileData.phone} 
                                onChange={(e) => setProfileData('phone', e.target.value)} 
                                placeholder="+1 555-0199"
                                error={profileErrors.phone}
                            />
                        </div>

                        <div className="pt-8 flex justify-end gap-4 items-center">
                            {profileSuccessful && <span className="text-sm text-(--accent-success) animate-pulse font-medium">Saved!</span>}
                            <button type="submit" disabled={profileProcessing} className="btn-modern btn-modern-primary px-8 py-2.5 font-bold shadow-lg hover:shadow-(--accent-primary)/30 transition-all disabled:opacity-50">
                                {profileProcessing && <Loader2 className="animate-spin w-4 h-4 mr-1" />}
                                Save Profile
                            </button>
                        </div>
                    </form>
                </section>

                {/* ── Security Section (Password) ── */}
                <section className="card-modern p-8 space-y-8 glass-morphism border-(--border-default) shadow-xl relative overflow-hidden">
                    <div className="absolute top-0 right-0 p-4 opacity-5">
                        <Shield size={120} />
                    </div>
                    
                    <div className="flex items-center gap-3 relative z-10">
                        <div className="p-2 rounded-lg bg-(--accent-info)/10 text-(--accent-info)">
                            <Shield size={20} />
                        </div>
                        <h2 className="text-lg font-bold text-(--text-primary)">Update Password</h2>
                    </div>

                    <form onSubmit={submitPassword} className="relative z-10 block">
                        <div className="grid grid-cols-1 gap-6">
                            <PremiumField 
                                label="Current Password" 
                                name="current_password" 
                                type="password"
                                value={pwdData.current_password} 
                                onChange={(e) => setPwdData('current_password', e.target.value)} 
                                error={pwdErrors.current_password}
                                required
                            />
                            <PremiumField 
                                label="New Password" 
                                name="password" 
                                type="password"
                                value={pwdData.password} 
                                onChange={(e) => setPwdData('password', e.target.value)} 
                                error={pwdErrors.password}
                                required
                            />
                            <PremiumField 
                                label="Confirm Password" 
                                name="password_confirmation" 
                                type="password"
                                value={pwdData.password_confirmation} 
                                onChange={(e) => setPwdData('password_confirmation', e.target.value)} 
                                error={pwdErrors.password_confirmation}
                                required
                            />
                        </div>

                        <div className="pt-8 flex justify-end gap-4 items-center">
                            {pwdSuccessful && <span className="text-sm text-(--accent-success) animate-pulse font-medium">Saved!</span>}
                            <button type="submit" disabled={pwdProcessing} className="btn-modern btn-modern-primary px-8 py-2.5 font-bold shadow-lg hover:shadow-(--accent-primary)/30 transition-all disabled:opacity-50">
                                {pwdProcessing && <Loader2 className="animate-spin w-4 h-4 mr-1" />}
                                Update Password
                            </button>
                        </div>
                    </form>
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
