import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';

import { api } from '../../api/client';

export type IdeaStatus = 'idea' | 'chosen' | 'purchased';

export const IDEA_STATUSES: IdeaStatus[] = ['idea', 'chosen', 'purchased'];

export type GiftIdea = {
  id: number;
  person_id: number;
  title: string;
  status: IdeaStatus;
  price: number | null;
  currency: string | null;
  product_id: number | null;
  image_url: string | null;
  offer_id: number | null;
};

export type GlobalGiftIdea = GiftIdea & { person: { id: number; display_name: string } };

export type GiftHistoryEntry = {
  id: number;
  title: string;
  year: number;
  occasion_type: string | null;
  amount: number | null;
  product_id: number | null;
};

export type PersonGifts = { ideas: GiftIdea[]; history: GiftHistoryEntry[] };

/** O schimbare atinge fișa persoanei, lista globală și rezultatele recomandărilor. */
function useRefresh() {
  const queryClient = useQueryClient();

  return () => {
    queryClient.invalidateQueries({ queryKey: ['gifts'] });
    queryClient.invalidateQueries({ queryKey: ['ideas'] });
    queryClient.invalidateQueries({ queryKey: ['recommendations'] });
  };
}

export function usePersonGifts(personId: number) {
  return useQuery({
    queryKey: ['gifts', personId],
    queryFn: async () => (await api.get<{ data: PersonGifts }>(`/people/${personId}/gifts`)).data.data,
  });
}

export function useAllIdeas() {
  return useQuery({
    queryKey: ['ideas'],
    queryFn: async () => (await api.get<{ data: GlobalGiftIdea[] }>('/ideas')).data.data,
  });
}

export function useSaveIdea(personId: number) {
  const refresh = useRefresh();

  return useMutation({
    mutationFn: async (input: { product_id?: number; title?: string; status?: IdeaStatus }) =>
      (await api.post<{ data: GiftIdea }>(`/people/${personId}/ideas`, input)).data.data,
    onSuccess: () => refresh(),
  });
}

export function useUpdateIdeaStatus() {
  const refresh = useRefresh();

  return useMutation({
    mutationFn: async ({ id, status }: { id: number; status: IdeaStatus }) =>
      (await api.patch<{ data: GiftIdea }>(`/ideas/${id}`, { status })).data.data,
    onSuccess: () => refresh(),
  });
}

export function useDeleteIdea() {
  const refresh = useRefresh();

  return useMutation({
    mutationFn: async (id: number) => {
      await api.delete(`/ideas/${id}`);
    },
    onSuccess: () => refresh(),
  });
}

/** „Am oferit cadoul”: ideea trece în istoric, iar recomandările nu-l mai propun. */
export function useMarkGiven() {
  const refresh = useRefresh();

  return useMutation({
    mutationFn: async (id: number) =>
      (await api.post<{ data: GiftHistoryEntry }>(`/ideas/${id}/given`, {})).data.data,
    onSuccess: () => refresh(),
  });
}

export function useAddHistory(personId: number) {
  const refresh = useRefresh();

  return useMutation({
    mutationFn: async (input: { title: string; year: number; amount?: number | null }) =>
      (await api.post<{ data: GiftHistoryEntry }>(`/people/${personId}/history`, input)).data.data,
    onSuccess: () => refresh(),
  });
}

export function useDeleteHistory() {
  const refresh = useRefresh();

  return useMutation({
    mutationFn: async (id: number) => {
      await api.delete(`/history/${id}`);
    },
    onSuccess: () => refresh(),
  });
}
