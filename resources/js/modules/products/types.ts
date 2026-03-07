import type { PaginatedResponse } from '@/types/api';

export interface RawProductListItem {
  id: string;
  user_id: string;
  type: string;
  title: string;
  slug: string;
  status: string;
  price: number;
  currency: string;
  created_at: string;
  updated_at?: string | null;
  deleted_at?: string | null;
}

export interface RawProductDetail extends RawProductListItem {
  description: string | null;
  thumbnail: string | null;
  level: string;
  language: string;
  updated_at: string | null;
}

export interface ProductListItem {
  uuid: string;
  user_id: string;
  type: string;
  title: string;
  slug: string;
  status: string;
  price: number;
  currency: string;
  created_at: string;
  updated_at?: string | null;
  deleted_at: string | null;
}

export interface ProductDetail extends ProductListItem {
  description: string | null;
  thumbnail: string | null;
  level: string;
  language: string;
  updated_at: string | null;
}

export interface ProductFilters {
  page?: number;
  perPage?: number;
  search?: string;
  type?: string;
  status?: string;
  dateFrom?: string;
  dateTo?: string;
}

export interface CreateProductDTO {
  user_id: string;
  type: string;
  title: string;
  slug: string;
  price: number;
  currency: string;
  description?: string | null;
  level: string;
  language: string;
  thumbnail?: string | null;
}

export interface UpdateProductDTO {
  title: string;
  slug: string;
  price: number;
  currency: string;
  description?: string | null;
  level: string;
  language: string;
  thumbnail?: string | null;
}

export type ProductsResponse = PaginatedResponse<ProductListItem>;

export function toProductListItem(raw: RawProductListItem): ProductListItem {
  return {
    uuid: raw.id,
    user_id: raw.user_id,
    type: raw.type,
    title: raw.title,
    slug: raw.slug,
    status: raw.status,
    price: raw.price,
    currency: raw.currency,
    created_at: raw.created_at,
    updated_at: raw.updated_at ?? null,
    deleted_at: raw.deleted_at ?? null,
  };
}

export function toProductDetail(raw: RawProductDetail): ProductDetail {
  return {
    ...toProductListItem(raw),
    description: raw.description,
    thumbnail: raw.thumbnail,
    level: raw.level,
    language: raw.language,
    updated_at: raw.updated_at,
  };
}
