import { useMutation, useQuery } from '@tanstack/react-query';

import { api } from '../../api/client';
import type { Interest } from '../../api/types';
import { useAuthStore } from '../../stores/auth';

export type RecommendedProduct = {
  id: number;
  title: string;
  brand: string | null;
  image_url: string | null;
  category: string | null;
  gift_score: number;
  price: number | null;
  old_price: number | null;
  currency: string;
  offer: { id: number; merchant: string } | null;
  other_offers_count: number;
  interests?: Interest[];
};

export type RecommendationItem = {
  rank: number;
  score: number;
  reason: string | null;
  product: RecommendedProduct;
};

export type RecommendationRun = {
  id: number;
  status: 'pending' | 'ready' | 'failed';
  kind: 'gift' | 'experience';
  has_ai: boolean;
  criteria: { interests: string[] } | null;
  items?: RecommendationItem[];
};

export function useStartRecommendation(personId: number) {
  return useMutation({
    mutationFn: async (budget: { budget_min?: number | null; budget_max?: number | null }) =>
      (await api.post<{ data: RecommendationRun }>(`/people/${personId}/recommendations`, budget)).data.data,
  });
}

/**
 * Generarea nu blocheaza request-ul: interogam pana devine `ready`.
 * Vezi docs/05 § 7 si regula 8 din CLAUDE.md.
 */
export function useRecommendation(runId: number | null) {
  return useQuery({
    queryKey: ['recommendations', runId],
    queryFn: async () => (await api.get<{ data: RecommendationRun }>(`/recommendations/${runId}`)).data.data,
    enabled: runId !== null,
    refetchInterval: (query) => (query.state.data?.status === 'pending' ? 1200 : false),
  });
}

export function useAiConsent() {
  return useMutation({
    mutationFn: async (granted: boolean) => {
      await api.post('/ai-consent', { granted });

      return granted;
    },
    // Reîncărcăm profilul: `ai_consent_asked` decide dacă mai întrebăm.
    onSuccess: () => useAuthStore.getState().restore(),
  });
}

/** Deschide magazinul si inregistreaza clickul — evenimentul care aduce bani. */
export function useTrackClick() {
  return useMutation({
    mutationFn: async (input: { offerId: number; personId?: number; context?: string }) =>
      (await api.post<{ data: { url: string } }>(`/offers/${input.offerId}/click`, {
        person_id: input.personId,
        context: input.context ?? 'recommendation',
      })).data.data.url,
  });
}

export type InterestSuggestion = {
  interest: Interest;
  confidence: number;
  matched: string[];
};

export function useAnalyzePerson(personId: number) {
  return useMutation({
    mutationFn: async (text: string) =>
      (await api.post<{ data: { suggestions: InterestSuggestion[] } }>(
        `/people/${personId}/analyze`, { text },
      )).data.data.suggestions,
  });
}
