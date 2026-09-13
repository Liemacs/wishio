import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';

import { api } from '../../api/client';

export type IdentityCandidate = {
  id: number;
  display_name: string;
  device_contact_id: string | null;
  relationship: string | null;
  birth_date: string | null;
  birth_year_known: boolean;
};

export type PendingSubmission = {
  id: number;
  display_name: string;
  birth_date: string | null;
  birth_year_known: boolean;
  interests: { code: string; label: string }[];
  message: string | null;
  submitted_at: string | null;
  /** Până atunci trebuie ales „cine este?”; apoi completarea se șterge (D-024). */
  expires_at: string | null;
  candidates: IdentityCandidate[];
};

const KEY = ['submissions', 'pending'] as const;

/** Completările din linkul public care așteaptă „cine este?” (S9.8). */
export function usePendingSubmissions() {
  return useQuery({
    queryKey: KEY,
    queryFn: async () => (await api.get<{ data: PendingSubmission[] }>('/submissions/pending')).data.data,
  });
}

/** `personId` null înseamnă „altcineva”. Întoarce persoana care a primit datele. */
export function useResolveSubmission() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async ({ id, personId }: { id: number; personId: number | null }) =>
      (await api.post<{ data: { person_id: number } }>(`/submissions/${id}/resolve`, { person_id: personId }))
        .data.data.person_id,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: KEY });
      queryClient.invalidateQueries({ queryKey: ['people'] });
      queryClient.invalidateQueries({ queryKey: ['occasions'] });
    },
  });
}
