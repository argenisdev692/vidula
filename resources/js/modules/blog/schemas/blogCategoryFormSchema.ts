import { z } from 'zod';

/**
 * Client-side UX validation for the blog-category create/edit form (Zod v4).
 * Mirrors the backend rules in Modules\Blog\Application\DTOs\BlogCategoryData
 * (name required ≤255, description nullable ≤1000); the backend stays
 * authoritative. The `image` upload is validated separately by FileUpload
 * (accept + size) and by the server (MIME + magic bytes + dimensions), so it is
 * NOT part of this schema.
 */
export const blogCategoryFormSchema = z.object({
    name: z.string().trim().min(1, 'Name is required.').max(255, 'Name must be 255 characters or fewer.'),
    description: z.string().trim().max(1000, 'Description must be 1000 characters or fewer.'),
});

export type BlogCategoryFormValues = z.infer<typeof blogCategoryFormSchema>;
