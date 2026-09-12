import { Platform } from 'react-native';

/**
 * Stocarea tokenului, adaptată platformei.
 *
 * Pe iOS și Android folosim Keychain / Keystore prin expo-secure-store.
 * Pe web acel modul nu există, iar importul lui strică bundle-ul — de aceea
 * se încarcă leneș, doar pe native. Web-ul îl folosim doar pentru verificări
 * de dezvoltare, unde localStorage e suficient.
 */
const KEY = 'wishio.token';

async function secureStore() {
  return import('expo-secure-store');
}

export async function readToken(): Promise<string | null> {
  try {
    if (Platform.OS === 'web') {
      return globalThis.localStorage?.getItem(KEY) ?? null;
    }

    return await (await secureStore()).getItemAsync(KEY);
  } catch {
    return null;
  }
}

export async function writeToken(token: string | null): Promise<void> {
  try {
    if (Platform.OS === 'web') {
      if (token) {
        globalThis.localStorage?.setItem(KEY, token);
      } else {
        globalThis.localStorage?.removeItem(KEY);
      }

      return;
    }

    const store = await secureStore();

    if (token) {
      await store.setItemAsync(KEY, token);
    } else {
      await store.deleteItemAsync(KEY);
    }
  } catch {
    // Keystore-ul poate fi indisponibil pe unele dispozitive. Nu blocăm
    // aplicația: utilizatorul se va reautentifica la următoarea pornire.
  }
}
