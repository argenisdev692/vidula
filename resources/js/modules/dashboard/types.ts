/**
 * Dashboard Inertia props — snake_case counts from DashboardController.
 */
export interface DashboardStats {
    users: number;
    students: number;
    classrooms: number;
    ai_generations: number;
}

export interface DashboardActivity {
    id: string;
    user: string;
    initials: string;
    action: string;
    target: string;
    time: string;
    icon: string;
    iconColor: string;
}
