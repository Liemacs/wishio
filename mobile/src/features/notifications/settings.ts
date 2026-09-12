import { useCallback, useEffect, useState } from 'react';
import { AppState } from 'react-native';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';

import { api } from '../../api/client';
import { getPushPermission, requestPushPermission, type PushPermission, type PushStatus } from './push';

export type NotificationSettings = {
  reminder_days: number[];
  preferred_hour: number;
  quiet_from: number;
  quiet_to: number;
  push_enabled: boolean;
  email_digest: boolean;
  has_device: boolean;
};

/** Treptele din scara docs/09 § 3. Ziua ocaziei se adaugă mereu, pe server. */
export const REMINDER_STEPS = [14, 7, 3, 1] as const;

/** Câte trepte se pot alege: limita pe ocazie, minus ziua ocaziei. */
export const MAX_REMINDER_STEPS = 3;

const KEY = ['settings'] as const;
const MUTATION_KEY = ['settings', 'update'] as const;

/** Ora cade în intervalul de liniște? Aceeași regulă ca `UserSettings::isQuietHour`. */
export function isQuietHour(hour: number, from: number, to: number): boolean {
  return from > to ? hour >= from || hour < to : hour >= from && hour < to;
}

export function useNotificationSettings() {
  return useQuery({
    queryKey: KEY,
    queryFn: async () => (await api.get<{ data: NotificationSettings }>('/settings')).data.data,
  });
}

/**
 * Salvarea e imediată, ca în Setările telefonului: fără buton „Salvează”.
 *
 * Interfața se schimbă înainte de răspunsul serverului. Cererile rulează una
 * după alta, iar starea reală se reîncarcă abia după ultima: altfel un
 * răspuns întârziat ar readuce pentru o clipă o valoare veche.
 */
export function useUpdateNotificationSettings() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationKey: MUTATION_KEY,
    scope: { id: 'settings' },
    mutationFn: async (input: Partial<NotificationSettings>) =>
      (await api.patch<{ data: NotificationSettings }>('/settings', input)).data.data,
    onMutate: async (input) => {
      await queryClient.cancelQueries({ queryKey: KEY });
      const previous = queryClient.getQueryData<NotificationSettings>(KEY);

      if (previous) {
        queryClient.setQueryData<NotificationSettings>(KEY, { ...previous, ...input });
      }

      return { previous };
    },
    onError: (_error, _input, context) => {
      if (context?.previous) {
        queryClient.setQueryData(KEY, context.previous);
      }
    },
    onSettled: () => {
      // Cererea curentă se numără încă: 1 înseamnă că e ultima din serie.
      if (queryClient.isMutating({ mutationKey: MUTATION_KEY }) <= 1) {
        queryClient.invalidateQueries({ queryKey: KEY });
      }
    },
  });
}

/**
 * Permisiunea de notificări din sistem, ținută la zi.
 *
 * Se recitește la revenirea în aplicație: utilizatorul pleacă în Setările
 * telefonului, pornește notificările și se întoarce.
 */
export function usePushPermission() {
  const queryClient = useQueryClient();
  const [permission, setPermission] = useState<PushPermission | null>(null);

  const refresh = useCallback(async () => {
    setPermission(await getPushPermission());
  }, []);

  useEffect(() => {
    refresh();

    const subscription = AppState.addEventListener('change', (state) => {
      if (state === 'active') {
        refresh();
      }
    });

    return () => subscription.remove();
  }, [refresh]);

  const enable = useCallback(async (): Promise<PushStatus> => {
    const result = await requestPushPermission();

    await refresh();
    // Înregistrarea telefonului schimbă `has_device`.
    await queryClient.invalidateQueries({ queryKey: KEY });

    return result;
  }, [queryClient, refresh]);

  return { permission, enable };
}
