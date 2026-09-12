import axios from 'axios';
import Constants from 'expo-constants';

import i18n from '../i18n';
import { readToken, writeToken } from './storage';

/**
 * În dezvoltare, Expo Go rulează pe telefon și nu poate ajunge la 127.0.0.1.
 * Folosim gazda de la care s-a încărcat bundle-ul — adică IP-ul din rețeaua
 * locală al calculatorului tău. Se poate suprascrie cu EXPO_PUBLIC_API_URL.
 */
function resolveBaseUrl(): string {
  if (process.env.EXPO_PUBLIC_API_URL) {
    return process.env.EXPO_PUBLIC_API_URL;
  }

  const host = Constants.expoConfig?.hostUri?.split(':')[0];

  return host ? `http://${host}:8000/api/v1` : 'http://127.0.0.1:8000/api/v1';
}

export const api = axios.create({
  baseURL: resolveBaseUrl(),
  timeout: 15000,
  headers: { Accept: 'application/json' },
});

export const getToken = readToken;
export const setToken = writeToken;

api.interceptors.request.use(async (config) => {
  const token = await getToken();

  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }

  // Backendul răspunde în limba cerută; o trimitem la fiecare cerere,
  // ca schimbarea din aplicație să se vadă imediat.
  config.headers['Accept-Language'] = i18n.language;

  return config;
});

/** Mesajul de eroare pe care merită să-l arătăm utilizatorului. */
export function errorMessage(error: unknown): string {
  if (axios.isAxiosError(error)) {
    const data = error.response?.data as { message?: string; errors?: Record<string, string[]> } | undefined;
    const firstFieldError = data?.errors ? Object.values(data.errors)[0]?.[0] : undefined;

    return firstFieldError ?? data?.message ?? i18n.t('errors.network');
  }

  return i18n.t('errors.unknown');
}
