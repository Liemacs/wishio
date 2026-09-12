import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';

import { api } from '../../api/client';
import type { DeviceContact } from './deviceContacts';

export type ImportSummary = {
  created: number;
  updated: number;
  birthdays: number;
  name_days: number;
  name_days_to_confirm: number;
  person_ids: number[];
};

export type Occasion = {
  id: number;
  type: 'birthday' | 'name_day' | 'anniversary' | 'holiday' | 'custom';
  label: string;
  month: number;
  day: number;
  year: number | null;
  days_until: number;
  source: string;
  confidence: number;
  confirmed: boolean;
  rejected: boolean;
  is_muted: boolean;
  may_notify: boolean;
  saint_name?: string | null;
  person?: { id: number; display_name: string } | null;
  /** Doar pentru sărbători: cine din contacte se potrivește. */
  audience?: { id: number; display_name: string }[];
};

export function useImportContacts() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (input: { contacts: DeviceContact[]; total: number; withBirthday: number }) => {
      const { data } = await api.post<{ data: ImportSummary }>('/contacts/import', {
        contacts: input.contacts.map((c) => ({
          device_contact_id: c.id,
          display_name: c.name,
          birth_date: c.birthDate,
          birth_year_known: c.birthYearKnown,
        })),
        // Agregat, fără date personale: cât de des e completată ziua de
        // naștere în agendele reale. Măsoară riscul R1 în producție.
        stats: { contacts_total: input.total, contacts_with_birthday: input.withBirthday },
      });

      return data.data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['people'] });
      queryClient.invalidateQueries({ queryKey: ['occasions'] });
    },
  });
}

export function useOccasions(options: { unconfirmed?: boolean } = {}) {
  return useQuery({
    queryKey: ['occasions', options],
    queryFn: async () =>
      (await api.get<{ data: Occasion[] }>('/occasions', { params: options })).data.data,
  });
}

export function useSetOccasionStatus() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (input: { id: number; status: 'confirmed' | 'rejected'; month?: number; day?: number }) =>
      (await api.patch<{ data: Occasion }>(`/occasions/${input.id}`, input)).data.data,
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['occasions'] }),
  });
}
