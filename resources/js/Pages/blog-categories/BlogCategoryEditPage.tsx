import * as React from 'react';
import { Link, Head, router } from '@inertiajs/react';
import AppLayout from '@/pages/layouts/AppLayout';
import { useBlogCategory } from '@/modules/blog-categories/hooks/useBlogCategory';
import { useBlogCategoryMutations } from '@/modules/blog-categories/hooks/useBlogCategoryMutations';
import { PremiumField } from '@/shadcn/PremiumField';
import type { UpdateBlogCategoryPayload } from '@/types/blog-categories';
import { ArrowLeft, Save } from 'lucide-react';

// ══════════════════════════════════════════════════════════════
// Props
// ══════════════════════════════════════════════════════════════
interface BlogCategoryEditPageProps {
  uuid: string;
}

// ══════════════════════════════════════════════════════════════
// BlogCategoryEditPage
// ══════════════════════════════════════════════════════════════
export default function BlogCategoryEditPage({ uuid }: BlogCategoryEditPageProps): React.JSX.Element {
  const { data: category, isPending: isLoading } = useBlogCategory(uuid);
  const { updateBlogCategory } = useBlogCategoryMutations();

  const [form, setForm] = React.useState<UpdateBlogCategoryPayload>({
    blog_category_name: '',
    blog_category_description: '',
    blog_category_image: '',
  });
  const [errors, setErrors] = React.useState<Record<string, string>>({});
  const [initialized, setInitialized] = React.useState<boolean>(false);

  // Populate form when category loads
  React.useEffect(() => {
    if (category && !initialized) {
      setForm({
        blog_category_name: category.blog_category_name ?? '',
        blog_category_description: category.blog_category_description ?? '',
        blog_category_image: category.blog_category_image ?? '',
      });
      setInitialized(true);
    }
  }, [category, initialized]);

  function handleChange(e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>): void {
    const { name, value } = e.target;
    setForm((prev) => ({ ...prev, [name]: value }));
    if (errors[name]) setErrors((prev) => ({ ...prev, [name]: '' }));
  }

  async function handleSubmit(e: React.FormEvent): Promise<void> {
    e.preventDefault();

    updateBlogCategory.mutate(
      { uuid, payload: form },
      {
        onSuccess: () => {
          router.visit(`/blog-categories/${uuid}`);
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
      },
    );
  }

  if (isLoading || !category) {
    return (
      <AppLayout>
        <Head title="Edit Category" />
        <div className="flex items-center justify-center py-24">
          <span className="text-(--text-muted) text-sm animate-pulse">Loading category...</span>
        </div>
      </AppLayout>
    );
  }

  return (
    <>
      <Head title={`Edit — ${category.blog_category_name}`} />
      <AppLayout>
        <form
          onSubmit={handleSubmit}
          className="max-w-4xl mx-auto flex flex-col gap-8 animate-in fade-in duration-300"
        >
          {/* ── Header ── */}
          <div className="flex items-center justify-between">
            <div className="flex items-center gap-4">
              <Link
                href={`/blog-categories/${uuid}`}
                className="flex h-10 w-10 items-center justify-center rounded-xl bg-(--bg-card) border border-(--border-default) text-(--text-muted) hover:bg-(--bg-hover) hover:text-(--accent-primary) transition-all shadow-sm"
              >
                <ArrowLeft size={20} />
              </Link>
              <div>
                <h1 className="text-2xl font-bold tracking-tight text-(--text-primary)">
                  Edit Category
                </h1>
                <p className="text-sm text-(--text-muted)">
                  {category.blog_category_name}
                </p>
              </div>
            </div>

            <button
              type="submit"
              disabled={updateBlogCategory.isPending}
              className="btn-modern btn-modern-primary flex items-center gap-2 px-6 py-2.5 shadow-lg hover:shadow-xl transition-all disabled:opacity-50"
            >
              {updateBlogCategory.isPending ? (
                <span className="animate-pulse">Saving...</span>
              ) : (
                <>
                  <Save size={18} />
                  <span className="font-bold">Save Changes</span>
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
                    value={form.blog_category_name ?? ''}
                    onChange={handleChange}
                    required
                    error={errors.blog_category_name}
                    placeholder="e.g. Artificial Intelligence"
                  />
                  <div>
                    <label className="text-[11px] font-bold uppercase tracking-widest text-(--text-muted) mb-2 block">
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
                    Editing this category will update it across all associated blog posts.
                  </p>
                </div>
              </div>
            </div>
          </div>
        </form>
      </AppLayout>
    </>
  );
}
