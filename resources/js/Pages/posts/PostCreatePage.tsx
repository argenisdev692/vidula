import * as React from 'react';
import { Head, router } from '@inertiajs/react';
import AppLayout from '@/pages/layouts/AppLayout';
import { PostForm } from '@/modules/posts/components/PostForm';
import { usePostMutations } from '@/modules/posts/hooks/usePostMutations';
import type { CreatePostPayload } from '@/types/posts';

export default function PostCreatePage(): React.JSX.Element {
  const [form, setForm] = React.useState<CreatePostPayload>({
    post_title: '',
    post_title_slug: '',
    post_content: '<p></p>',
    post_excerpt: '',
    post_cover_image: '',
    meta_title: '',
    meta_description: '',
    meta_keywords: '',
    category_uuid: '',
    post_status: 'draft',
    published_at: null,
    scheduled_at: null,
  });
  const [errors, setErrors] = React.useState<Record<string, string>>({});
  const { createPost } = usePostMutations();

  function handleChange(field: string, value: string | null): void {
    setForm((prev) => ({ ...prev, [field]: value ?? '' }));
    if (errors[field]) setErrors((prev) => ({ ...prev, [field]: '' }));
  }

  function normalizePayload(payload: CreatePostPayload): CreatePostPayload {
    return {
      ...payload,
      category_uuid: payload.category_uuid || undefined,
      published_at: payload.published_at || null,
      scheduled_at: payload.scheduled_at || null,
    };
  }

  async function handleSubmit(e: React.FormEvent): Promise<void> {
    e.preventDefault();

    createPost.mutate(normalizePayload(form), {
      onSuccess: (response) => {
        const createdUuid = response.data?.data?.uuid as string | undefined;
        router.visit(createdUuid ? `/posts/${createdUuid}` : '/posts');
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
      <Head title="Create Post" />
      <PostForm
        title="New Post"
        subtitle="Create a customizable blog post with rich content"
        backHref="/posts"
        submitLabel="Save Post"
        isSubmitting={createPost.isPending}
        values={form}
        errors={errors}
        onChange={handleChange}
        onSubmit={handleSubmit}
      />
    </AppLayout>
  );
}
