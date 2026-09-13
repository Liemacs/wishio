import { Platform } from 'react-native';
import Constants from 'expo-constants';
import { useQuery } from '@tanstack/react-query';

import { api } from '../../api/client';

export type AppConfig = {
  min_supported_version: string;
  store_url: { ios: string | null; android: string | null };
};

/** Versiunea din app.json: aceeași pe care o arată store-ul. */
export const APP_VERSION = Constants.expoConfig?.version ?? '0.0.0';

/** „1.10.0” e mai nouă decât „1.9.3”: comparăm numerele, nu textul. */
export function isOlder(version: string, minimum: string): boolean {
  const parts = (value: string) => value.split('.').map((part) => Number.parseInt(part, 10) || 0);
  const current = parts(version);
  const required = parts(minimum);

  for (let i = 0; i < Math.max(current.length, required.length); i++) {
    const difference = (current[i] ?? 0) - (required[i] ?? 0);

    if (difference !== 0) {
      return difference < 0;
    }
  }

  return false;
}

export function storeUrl(config: AppConfig): string | null {
  if (Platform.OS === 'ios') return config.store_url.ios;
  if (Platform.OS === 'android') return config.store_url.android;

  return null;
}

/** Se cere la pornire, fără cont, și din nou când aplicația revine în prim-plan. */
export function useAppConfig() {
  return useQuery({
    queryKey: ['app-config'],
    queryFn: async () => (await api.get<{ data: AppConfig }>('/app-config')).data.data,
    staleTime: 10 * 60 * 1000,
    retry: 1,
  });
}
