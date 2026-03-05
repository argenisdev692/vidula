import * as React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/pages/layouts/AppLayout';
import { useBlogCategoryMutations } from '@/modules/blog-categories/hooks/useBlogCategoryMutations';
import { PremiumField } from '@/shadcn/PremiumField';
import type { CreateBlogCategoryPayload } from '@/types/blog-categories';
import { ArrowLeft, Save } from 'lucide-react';

export default function BlogCategoryCreatePage(): React.JSX.Element {
  const [form, setForm] = React.useState<CreateBlogCategoryPayload>({
    blog_category_name: '',
    blog_category_description: '',
    blog_category_image: '',
  });

  const [errors, setErrors] = React.useState<Record<string, string>>({});
  const { createBlogCategory } = useBlogCategoryMutations();

  function handleChange(e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>): void {
    const { name, value } = e.target;
    setForm((prev) => ({ ...prev, [name]: value }));
    if (errors[name]) setErrors((prev) => ({ ...prev, [name]: '' }));
  }

  async function handleSubmit(e: React.FormEvent): Promise<void> {
    e.preventDefault();

    createBlogCategory.mutate(form, {
      onSuccess: () => {
        router.visit('/blog-categories');
      },
      onError: (err: Error) => {
        const axiosErr = err as { response?: { data?: { errors?: Record<string, string[]> } } };
        if (axiosErr.response?.data?.errors) {
          const serverErrors: Record<string, string> = {};
          for (const [key, msgs] of Object.entries(axiosErr.response.data.errors)) {
            serverErrors[key] = msgs[0] ?? '';
          }
          setErrors(serverErrors);
        }
      },
    });
  }

  return (
    <AppLayout>
      <Head title="Create Blog Category" />
      <form
        onSubmit={handleSubmit}
        className="max-w-4xl mx-auto flex flex-col gap-8 animate-in fade-in slide-in-from-bottom-4 duration-300"
      >
        {/* ── Header ── */}
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-4">
            <Link
              href="/blog-categories"
              className="flex h-10 w-10 items-center justify-center rounded-xl bg-(--bg-card) border border-(--border-default) text-(--text-muted) hover:bg-(--bg-hover) hover:text-(--accent-primary) transition-all shadow-sm"
            >
              <ArrowLeft size={20} />
            </Link>
            <div>
              <h1 className="text-2xl font-bold tracking-tight text-(--text-primary)">
                New Category
              </h1>
              <p className="text-sm text-(--text-muted)">
                Create a new blog content category
              </p>
            </div>
          </div>

          <button
            type="submit"
            disabled={createBlogCategory.isPending}
            className="btn-modern btn-modern-primary flex items-center gap-2 px-6 py-2.5 shadow-lg hover:shadow-xl transition-all disabled:opacity-50"
          >
            {createBlogCategory.isPending ? (
              <span className="animate-pulse">Creating...</span>
            ) : (
              <>
                <Save size={18} />
                <span className="font-bold">Save Category</span>
              </>
            )}
          </button>
        </div>

        {/* ── Form Body ── */}
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
          <div className="lg:col-span-2 space-y-6">
            <div className="card-modern p-8 space-y-8 shadow-xl border border-(--border-default)">
              <div className="flex items-center gap-3">
                <div className="h-8 w-1 bg-(--accent-primary) rounded-full" />
                <h2 className="text-lg font-bold text-(--text-primary)">Category Details</h2>
              </div>

              <div className="grid grid-cols-1 gap-6">
                <PremiumField
                  label="Category Name"
                  name="blog_category_name"
                  value={form.blog_category_name}
                  onChange={handleChange}
                  required
                  error={errors.blog_category_name}
                  placeholder="e.g. Artificial Intelligence"
                />
                <div>
                  <label
                    className="text-[11px] font-bold uppercase tracking-widest text-(--text-muted) mb-2 block"
                  >
                    Description
                  </label>
                  <textarea
                    name="blog_category_description"
                    value={form.blog_category_description ?? ''}
                    onChange={handleChange}
                    rows={4}
                    placeholder="Short description for this category..."
                    className="w-full rounded-xl px-4 py-3 bg-(--bg-card) border border-(--border-default) text-sm outline-none focus:ring-2 focus:ring-(--accent-primary) transition-all text-(--text-primary) placeholder:text-(--text-disabled) resize-none"
                  />
                  {errors.blog_category_description && (
                    <p className="text-xs text-(--accent-error) mt-1">
                      {errors.blog_category_description}
                    </p>
                  )}
                </div>
                <PremiumField
                  label="Image URL"
                  name="blog_category_image"
                  value={form.blog_category_image ?? ''}
                  onChange={handleChange}
                  error={errors.blog_category_image}
                  placeholder="https://example.com/image.jpg"
                />
              </div>
            </div>
          </div>

          <div className="space-y-6">
            <div className="card-modern p-6 bg-(--bg-surface) border border-(--border-subtle)">
              <h3 className="text-sm font-bold uppercase tracking-widest text-(--text-muted) mb-4">
                Info
              </h3>
              <div className="p-4 rounded-xl bg-(--bg-card) border border-(--border-default) shadow-inner">
                <p className="text-xs text-(--text-muted) leading-relaxed">
                  Blog categories help organize your blog posts into logical sections. 
                  Categories are visible to readers and used for navigation.
                </p>
              </div>
            </div>
          </div>
        </div>
      </form>
    </AppLayout>
  );
}
