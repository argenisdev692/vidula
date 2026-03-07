import * as React from 'react';
import { Link } from '@inertiajs/react';
import { ArrowLeft, Save } from 'lucide-react';
import { PremiumField } from '@/shadcn/PremiumField';
import { useBlogCategories } from '@/modules/blog-categories/hooks/useBlogCategories';
import { PostEditor } from '@/modules/posts/components/PostEditor';
import type { CreatePostPayload, UpdatePostPayload } from '@/types/posts';

interface PostFormProps {
  title: string;
  subtitle: string;
  backHref: string;
  submitLabel: string;
  isSubmitting: boolean;
  values: CreatePostPayload | UpdatePostPayload;
  errors: Record<string, string>;
  onChange: (field: string, value: string | null) => void;
  onSubmit: (e: React.FormEvent) => void;
}

const statusOptions = [
  { value: 'draft', label: 'Draft' },
  { value: 'published', label: 'Published' },
  { value: 'scheduled', label: 'Scheduled' },
  { value: 'archived', label: 'Archived' },
] as const;

export function PostForm({
  title,
  subtitle,
  backHref,
  submitLabel,
  isSubmitting,
  values,
  errors,
  onChange,
  onSubmit,
}: PostFormProps): React.JSX.Element {
  const { data: categoryData } = useBlogCategories({ per_page: 100, status: 'active' });
  const categories = categoryData?.data ?? [];
  const status = values.post_status ?? 'draft';
  const showScheduledAt = status === 'scheduled';

  return (
    <form onSubmit={onSubmit} className="max-w-6xl mx-auto flex flex-col gap-8 animate-in fade-in duration-300">
      <div className="flex items-center justify-between gap-4">
        <div className="flex items-center gap-4">
          <Link
            href={backHref}
            className="flex h-10 w-10 items-center justify-center rounded-xl bg-(--bg-card) border border-(--border-default) text-(--text-muted) hover:bg-(--bg-hover) hover:text-(--accent-primary) transition-all shadow-sm"
          >
            <ArrowLeft size={20} />
          </Link>
          <div>
            <h1 className="text-2xl font-bold tracking-tight text-(--text-primary)">{title}</h1>
            <p className="text-sm text-(--text-muted)">{subtitle}</p>
          </div>
        </div>

        <button
          type="submit"
          disabled={isSubmitting}
          className="btn-modern btn-modern-primary flex items-center gap-2 px-6 py-2.5 shadow-lg hover:shadow-xl transition-all disabled:opacity-50"
        >
          {isSubmitting ? (
            <span className="animate-pulse">Saving...</span>
          ) : (
            <>
              <Save size={18} />
              <span className="font-bold">{submitLabel}</span>
            </>
          )}
        </button>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div className="lg:col-span-2 space-y-6">
          <div className="card-modern p-8 space-y-8 shadow-xl border border-(--border-default)">
            <div className="flex items-center gap-3">
              <div className="h-8 w-1 bg-(--accent-primary) rounded-full" />
              <h2 className="text-lg font-bold text-(--text-primary)">Post Content</h2>
            </div>

            <div className="grid grid-cols-1 gap-6">
              <PremiumField
                label="Post Title"
                name="post_title"
                value={values.post_title ?? ''}
                onChange={(e) => onChange('post_title', e.target.value)}
                required
                error={errors.post_title}
                placeholder="e.g. The Future of AI in Healthcare"
              />

              <PremiumField
                label="Slug"
                name="post_title_slug"
                value={values.post_title_slug ?? ''}
                onChange={(e) => onChange('post_title_slug', e.target.value)}
                error={errors.post_title_slug}
                placeholder="the-future-of-ai-in-healthcare"
              />

              <div>
                <label className="text-[11px] font-bold uppercase tracking-widest text-(--text-muted) mb-2 block">
                  Rich Content
                </label>
                <PostEditor
                  value={values.post_content ?? ''}
                  onChange={(nextValue) => onChange('post_content', nextValue)}
                  error={errors.post_content}
                />
              </div>

              <PremiumField
                label="Excerpt"
                name="post_excerpt"
                value={values.post_excerpt ?? ''}
                onChange={(e) => onChange('post_excerpt', e.target.value)}
                error={errors.post_excerpt}
                placeholder="Short teaser for cards, SEO and social previews"
                isTextArea
              />

              <PremiumField
                label="Cover Image URL"
                name="post_cover_image"
                value={values.post_cover_image ?? ''}
                onChange={(e) => onChange('post_cover_image', e.target.value)}
                error={errors.post_cover_image}
                placeholder="https://example.com/cover.jpg"
              />
            </div>
          </div>

          <div className="card-modern p-8 space-y-8 shadow-xl border border-(--border-default)">
            <div className="flex items-center gap-3">
              <div className="h-8 w-1 bg-(--accent-primary) rounded-full" />
              <h2 className="text-lg font-bold text-(--text-primary)">SEO Metadata</h2>
            </div>

            <div className="grid grid-cols-1 gap-6">
              <PremiumField
                label="Meta Title"
                name="meta_title"
                value={values.meta_title ?? ''}
                onChange={(e) => onChange('meta_title', e.target.value)}
                error={errors.meta_title}
                placeholder="Optimized SEO title"
              />
              <PremiumField
                label="Meta Description"
                name="meta_description"
                value={values.meta_description ?? ''}
                onChange={(e) => onChange('meta_description', e.target.value)}
                error={errors.meta_description}
                placeholder="Concise search preview description"
                isTextArea
              />
              <PremiumField
                label="Meta Keywords"
                name="meta_keywords"
                value={values.meta_keywords ?? ''}
                onChange={(e) => onChange('meta_keywords', e.target.value)}
                error={errors.meta_keywords}
                placeholder="ai, healthcare, automation"
              />
            </div>
          </div>
        </div>

        <div className="space-y-6">
          <div className="card-modern p-6 bg-(--bg-surface) border border-(--border-subtle) space-y-5">
            <h3 className="text-sm font-bold uppercase tracking-widest text-(--text-muted)">Publishing</h3>

            <div className="space-y-4">
              <div>
                <label className="text-[11px] font-bold uppercase tracking-widest text-(--text-muted) mb-2 block">
                  Status
                </label>
                <select
                  value={status}
                  onChange={(e) => onChange('post_status', e.target.value)}
                  className="w-full rounded-xl px-4 py-3 bg-(--bg-card) border border-(--border-default) text-sm outline-none focus:ring-2 focus:ring-(--accent-primary) transition-all text-(--text-primary)"
                >
                  {statusOptions.map((option) => (
                    <option key={option.value} value={option.value}>
                      {option.label}
                    </option>
                  ))}
                </select>
                {errors.post_status && <p className="text-xs text-(--accent-error) mt-1">{errors.post_status}</p>}
              </div>

              <div>
                <label className="text-[11px] font-bold uppercase tracking-widest text-(--text-muted) mb-2 block">
                  Category
                </label>
                <select
                  value={values.category_uuid ?? ''}
                  onChange={(e) => onChange('category_uuid', e.target.value || null)}
                  className="w-full rounded-xl px-4 py-3 bg-(--bg-card) border border-(--border-default) text-sm outline-none focus:ring-2 focus:ring-(--accent-primary) transition-all text-(--text-primary)"
                >
                  <option value="">Without category</option>
                  {categories.map((category) => (
                    <option key={category.uuid} value={category.uuid}>
                      {category.blog_category_name}
                    </option>
                  ))}
                </select>
                {errors.category_uuid && <p className="text-xs text-(--accent-error) mt-1">{errors.category_uuid}</p>}
              </div>

              {showScheduledAt ? (
                <PremiumField
                  label="Scheduled At"
                  type="datetime-local"
                  name="scheduled_at"
                  value={values.scheduled_at ? values.scheduled_at.slice(0, 16) : ''}
                  onChange={(e) => onChange('scheduled_at', e.target.value || null)}
                  error={errors.scheduled_at}
                />
              ) : null}
            </div>
          </div>

          <div className="card-modern p-6 bg-(--bg-surface) border border-(--border-subtle)">
            <h3 className="text-sm font-bold uppercase tracking-widest text-(--text-muted) mb-4">Editor Tips</h3>
            <div className="p-4 rounded-xl bg-(--bg-card) border border-(--border-default) shadow-inner">
              <p className="text-xs text-(--text-muted) leading-relaxed">
                Use headings, lists, links and images to build a fully customizable article body. The editor stores rich HTML, ideal for rendering your blog post exactly as designed.
              </p>
            </div>
          </div>
        </div>
      </div>
    </form>
  );
}
