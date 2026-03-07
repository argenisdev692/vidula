export interface ClientListItem {
  uuid: string;
  user_uuid: string;
  client_name: string;
  nif: string | null;
  email: string | null;
  phone: string | null;
  created_at: string;
  deleted_at: string | null;
}

export interface ClientDetail extends ClientListItem {
  address: string | null;
  social_links: Record<string, string>;
  coordinates: Record<string, number>;
  updated_at: string | null;
}

export interface ClientFilters {
  page?: number;
  perPage?: number;
  search?: string;
  status?: string;
  dateFrom?: string;
  dateTo?: string;
}

export interface CreateClientDTO {
  userUuid: string;
  clientName: string;
  email?: string | null;
  phone?: string | null;
  address?: string | null;
  nif?: string | null;
  website?: string | null;
  facebookLink?: string | null;
  instagramLink?: string | null;
  linkedinLink?: string | null;
  twitterLink?: string | null;
  latitude?: number | null;
  longitude?: number | null;
}

export interface UpdateClientDTO {
  clientName?: string;
  email?: string | null;
  phone?: string | null;
  address?: string | null;
  nif?: string | null;
  website?: string | null;
  facebookLink?: string | null;
  instagramLink?: string | null;
  linkedinLink?: string | null;
  twitterLink?: string | null;
  latitude?: number | null;
  longitude?: number | null;
}
