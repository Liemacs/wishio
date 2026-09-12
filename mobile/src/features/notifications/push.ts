import { Platform } from 'react-native';
import * as Device from 'expo-device';
import * as Notifications from 'expo-notifications';
import Constants from 'expo-constants';

import { api } from '../../api/client';

/**
 * Înregistrarea pentru notificări.
 *
 * Permisiunea se cere DUPĂ ce utilizatorul a văzut ce am găsit în agenda lui —
 * nu la pornire. Vezi docs/02 § R3: cerută prea devreme, rata de acceptare
 * scade mult, iar reminderul e mecanismul de retenție al produsului.
 */
export type PushStatus = 'granted' | 'denied' | 'unsupported' | 'unregistered';

/**
 * Cere permisiunea și înregistrează telefonul. Nu aruncă niciodată.
 *
 * Înainte, o eroare la obținerea tokenului bloca onboarding-ul pe un spinner:
 * în Expo Go, fără proiect EAS, tokenul nu se poate obține deloc.
 */
export async function requestPushPermission(): Promise<PushStatus> {
  if (!Device.isDevice) {
    return 'unsupported';   // simulatoarele nu primesc push
  }

  try {
    const existing = await Notifications.getPermissionsAsync();
    const status =
      existing.status === 'granted'
        ? existing
        : await Notifications.requestPermissionsAsync();

    if (status.status !== 'granted') {
      return 'denied';
    }

    if (Platform.OS === 'android') {
      await Notifications.setNotificationChannelAsync('occasions', {
        name: 'Occasions',
        importance: Notifications.AndroidImportance.DEFAULT,
      });
    }
  } catch {
    return 'unsupported';
  }

  try {
    await registerDevice();
  } catch {
    // Permisiunea există, dar telefonul nu s-a putut înregistra: Expo Go fără
    // proiect EAS, lipsă de rețea sau server oprit. Se poate reîncerca din
    // setările de notificări.
    return 'unregistered';
  }

  return 'granted';
}

export type PushPermission = 'granted' | 'denied' | 'undetermined' | 'unsupported';

/** Starea permisiunii din sistem, fără să-l întrebe pe utilizator. */
export async function getPushPermission(): Promise<PushPermission> {
  if (!Device.isDevice) {
    return 'unsupported';
  }

  try {
    const { status, canAskAgain } = await Notifications.getPermissionsAsync();

    if (status === 'granted') {
      return 'granted';
    }

    // După un refuz, iOS nu mai afișează dialogul: rămân doar setările telefonului.
    return status === 'denied' || !canAskAgain ? 'denied' : 'undetermined';
  } catch {
    return 'unsupported';
  }
}

async function registerDevice(): Promise<void> {
  const projectId =
    Constants.expoConfig?.extra?.eas?.projectId ?? Constants.easConfig?.projectId;

  const { data: token } = await Notifications.getExpoPushTokenAsync(
    projectId ? { projectId } : undefined,
  );

  await api.post('/devices', {
    token,
    platform: Platform.OS === 'ios' ? 'ios' : Platform.OS === 'android' ? 'android' : 'web',
  });
}

/** Apăsarea unei notificări duce la persoana respectivă, nu la ecranul principal. */
export function routeFromNotification(
  response: Notifications.NotificationResponse,
): string | null {
  const data = response.notification.request.content.data as { person_id?: number } | undefined;

  return data?.person_id ? `/people/${data.person_id}` : null;
}
