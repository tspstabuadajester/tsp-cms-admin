import type { LucideIcon } from 'lucide-vue-next';

export interface Auth {
    user: User | null;
    roles: string[];
    permissions: string[];
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavItem {
    title: string;
    href: string;
    icon?: LucideIcon;
    iconSrc?: string;
    isActive?: boolean;
}

export type ToastVariant = 'success' | 'warning' | 'error' | 'info';

export interface FlashToast {
    message: string;
    variant: ToastVariant;
}

export interface SharedData {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    flash?: {
        toast?: FlashToast | null;
    };
    ziggy: {
        location: string;
        url: string;
        port: null | number;
        defaults: Record<string, unknown>;
        routes: Record<string, string>;
    };
}

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string; // filename only, e.g. "uuid.svg"
    status?: 'active' | 'inactive';
    business_id?: number | null;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
}

export type BusinessOption = Pick<Business, 'id' | 'name'>;

export type RoleOption = {
    name: string;
};

export interface Website {
    id: number;
    uuid: string;
    name: string;
    slug: string;
    primary_domain: string | null;
    business_id: number | null;
    status: 'active' | 'inactive';
    logo?: string | null;
    seo_defaults: Record<string, unknown> | null;
    settings: Record<string, unknown> | null;
    published_at: string | null;
    created_at: string;
    updated_at: string;
}

export type WebsiteListItem = Pick<
    Website,
    'id' | 'uuid' | 'name' | 'slug' | 'primary_domain' | 'logo' | 'status' | 'created_at'
> & {
    has_layout: boolean;
};

export type WebsiteTemplateItemType = 'folder' | 'html' | 'css' | 'javascript' | 'json';

export type WebsiteTemplateItem = {
    path: string;
    name: string;
    type: WebsiteTemplateItemType;
};

export interface Business {
    id: number;
    uuid: string;
    name: string;
    address: string | null;
    phone: string | null;
    email: string | null;
    status?: 'active' | 'inactive';
    created_at: string;
    updated_at: string;
}

export interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

export interface Paginated<T> {
    data: T[];
    current_page: number;
    first_page_url: string;
    from: number | null;
    last_page: number;
    last_page_url: string;
    links: PaginationLink[];
    next_page_url: string | null;
    path: string;
    per_page: number;
    prev_page_url: string | null;
    to: number | null;
    total: number;
}

export type BreadcrumbItemType = BreadcrumbItem;
