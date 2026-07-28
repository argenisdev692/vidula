export type EnrollmentSoftStatus = 'active' | 'suspended';
export type EnrollmentLifecycleStatus = 'active' | 'suspended' | 'completed' | 'dropped';
export type AttendanceStatus = 'present' | 'absent' | 'late' | 'justified';

export interface EnrollmentStudent {
    uuid: string;
    name: string;
    email: string | null;
}

export interface EnrollmentClassroom {
    uuid: string;
    product?: { uuid: string; title: string; type: string } | null;
}

export interface Enrollment {
    uuid: string;
    enrolled_at: string;
    enrollment_status: EnrollmentLifecycleStatus;
    final_grade: string | number | null;
    notes: string | null;
    student?: EnrollmentStudent | null;
    classroom?: EnrollmentClassroom | null;
    created_at: string | null;
    deleted_at: string | null;
}

export interface EnrollmentStudentOption {
    uuid: string;
    name: string;
    email: string | null;
}

export interface EnrollmentClassroomOption {
    uuid: string;
    title: string;
    product_type: string;
}

export interface EnrollmentFilters {
    search: string | null;
    status: EnrollmentSoftStatus | null;
    enrollment_status: EnrollmentLifecycleStatus | null;
    classroom_uuid: string | null;
    student_uuid: string | null;
    date_from: string | null;
    date_to: string | null;
}

export interface EnrollmentQuery extends EnrollmentFilters {
    page: number;
    per_page: number;
}

export interface AttendanceSession {
    uuid: string;
    session_number: number;
    title: string;
    session_date: string | null;
    hours: string | number | null;
}

export interface AttendanceEnrollmentRow {
    uuid: string;
    enrolled_at: string | null;
    enrollment_status: EnrollmentLifecycleStatus;
    student: EnrollmentStudent;
}

export interface AttendanceMark {
    uuid?: string;
    enrollment_uuid: string | null;
    product_session_uuid: string | null;
    date: string | null;
    attendance_status: AttendanceStatus;
    observation: string | null;
}

export interface PaginatedResponse<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
}
