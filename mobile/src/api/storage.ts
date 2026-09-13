import { Platform } from 'react-native';

/**
 * Stocarea tokenurilor, adaptată platformei.
 *
 * Pe iOS și Android folosim Keychain / Keystore prin expo-secure-store.
 * Pe web acel modul nu există, iar importul lui strică bundle-ul — de aceea
 * se încarcă leneș, doar pe native. Web-ul îl folosim doar pentru verificări
 * de dezvoltare, unde localStorage e suficient.
 */
const KEY = 'wishio.token';

/** Tokenul de push înregistrat pe cont: deconectarea trebuie să-l poată retrage. */
const PUSH_KEY = 'wishio.push-token';

async function secureStore() {
  return import('expo-secure-store');
}

async function readItem(key: string): Promise<string | null> {
  try {
    if (Platform.OS === 'web') {
      return globalThis.localStorage?.getItem(key) ?? null;
    }

    return await (await secureStore()).getItemAsync(key);
  } catch {
    return null;
  }
}

async function writeItem(key: string, value: string | null): Promise<void> {
  try {
    if (Platform.OS === 'web') {
      if (value) {
        globalThis.localStorage?.setItem(key, value);
      } else {
        globalThis.localStorage?.removeItem(key);
      }

      return;
    }

    const store = await secureStore();

    if (value) {
      await store.setItemAsync(key, value);
    } else {
      await store.deleteItemAsync(key);
    }
  } catch {
    // Keystore-ul poate fi indisponibil pe unele dispozitive. Nu blocăm
    // aplicația: utilizatorul se va reautentifica la următoarea pornire.
  }
}

export const readToken = () => readItem(KEY);
export const writeToken = (token: string | null) => writeItem(KEY, token);

export const readPushToken = () => readItem(PUSH_KEY);
export const writePushToken = (token: string | null) => writeItem(PUSH_KEY, token);
