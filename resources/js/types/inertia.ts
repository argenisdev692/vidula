export interface AuthUser {
    id: number;
    name: string;
    email: string;
}

export interface SharedProps {
    auth: {
        user: AuthUser | null;
    };
    [key: string]: unknown;
}
