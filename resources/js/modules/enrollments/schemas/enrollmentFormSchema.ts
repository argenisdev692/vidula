import { z } from 'zod';

export const enrollmentFormSchema = z.object({
    student_uuid: z.string().uuid('Select a student'),
    classroom_uuid: z.string().uuid('Select a classroom'),
    enrolled_at: z.string().min(1, 'Enrolled date is required'),
    enrollment_status: z.enum(['active', 'suspended', 'completed', 'dropped']),
    final_grade: z.number().min(0).max(100).nullable(),
    notes: z.string().trim().max(5000),
});

export type EnrollmentFormValues = z.infer<typeof enrollmentFormSchema>;
