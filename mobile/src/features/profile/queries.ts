import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';

import { api } from '../../api/client';

export type WishKind = 'product' | 'place' | 'experience';
export type Visibility = 'private' | 'signal_only' | 'contacts' | 'public';

export type WishlistItem = {
  id: number;
  kind: WishKind;
  title: string;
  url: string | null;
  note: string | null;
  priority: 'want' | 'maybe';
  visibility: Visibility;
};

export type MyProfile = {
  slug: string;
  url: string;
  display_name: string;
  is_active: boolean;
  is_indexable: boolean;
  visibility: Record<string, Visibility>;
  view_count: number;
  submissions: number;
  wishlist: WishlistItem[];
};

const KEY = ['my-profile'] as const;

export function useMyProfile() {
  return useQuery({
    queryKey: KEY,
    queryFn: async () => (await api.get<{ data: MyProfile }>('/profile')).data.data,
  });
}

export function useUpdateMyProfile() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (input: Partial<Pick<MyProfile, 'display_name' | 'is_active' | 'visibility'>>) =>
      (await api.patch<{ data: MyProfile }>('/profile', input)).data.data,
    onSuccess: (profile) => queryClient.setQueryData(KEY, profile),
  });
}

export function useAddWish() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (input: { kind: WishKind; title: string; visibility?: Visibility }) =>
      (await api.post('/wishlist', input)).data,
    onSuccess: () => queryClient.invalidateQueries({ queryKey: KEY }),
  });
}

export function useRemoveWish() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (id: number) => { await api.delete(`/wishlist/${id}`); },
    onSuccess: () => queryClient.invalidateQueries({ queryKey: KEY }),
  });
}

export type Settings = {
  reminder_days: number[];
  preferred_hour: number;
  push_enabled: boolean;
  email_digest: boolean;
  has_device: boolean;
};

export function useSettings() {
  return useQuery({
    queryKey: ['settings'],
    queryFn: async () => (await api.get<{ data: Settings }>('/settings')).data.data,
  });
}

export function useUpdateSettings() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (input: Partial<Settings>) =>
      (await api.patch<{ data: Settings }>('/settings', input)).data.data,
    onSuccess: (settings) => queryClient.setQueryData(['settings'], settings),
  });
}
