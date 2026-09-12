import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';

import { api } from '../../api/client';
import type { InterestGroup, Person, PersonInput } from '../../api/types';

const keys = {
  people: ['people'] as const,
  person: (id: number) => ['people', id] as const,
  interests: ['interests'] as const,
};

export function usePeople() {
  return useQuery({
    queryKey: keys.people,
    queryFn: async () => (await api.get<{ data: Person[] }>('/people')).data.data,
  });
}

export function usePerson(id: number) {
  return useQuery({
    queryKey: keys.person(id),
    queryFn: async () => (await api.get<{ data: Person }>(`/people/${id}`)).data.data,
    enabled: Number.isFinite(id),
  });
}

export function useInterestGroups() {
  return useQuery({
    queryKey: keys.interests,
    queryFn: async () => (await api.get<{ data: InterestGroup[] }>('/interests')).data.data,
    staleTime: 1000 * 60 * 60, // taxonomia se schimbă rar
  });
}

export function useCreatePerson() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (input: PersonInput) => (await api.post<{ data: Person }>('/people', input)).data.data,
    onSuccess: () => queryClient.invalidateQueries({ queryKey: keys.people }),
  });
}

export function useUpdatePerson(id: number) {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (input: PersonInput) => (await api.patch<{ data: Person }>(`/people/${id}`, input)).data.data,
    onSuccess: (person) => {
      queryClient.setQueryData(keys.person(id), person);
      queryClient.invalidateQueries({ queryKey: keys.people });
    },
  });
}

export function useDeletePerson() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (id: number) => { await api.delete(`/people/${id}`); },
    onSuccess: () => queryClient.invalidateQueries({ queryKey: keys.people }),
  });
}

export function useSaveInterests(id: number) {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (codes: string[]) =>
      (await api.put<{ data: Person }>(`/people/${id}/interests`, { codes })).data.data,
    onSuccess: (person) => {
      queryClient.setQueryData(keys.person(id), person);
      queryClient.invalidateQueries({ queryKey: keys.people });
    },
  });
}
