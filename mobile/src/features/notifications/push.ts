import { Platform } from 'react-native';
import * as Device from 'expo-device';
import * as Notifications from 'expo-notifications';
import Constants from 'expo-constants';

import { api } from '../../api/client';
import { readPushToken, writePushToken } from '../../api/storage';

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

  await writePushToken(token);
}

/**
 * Retrage telefonul de pe cont, la deconectare. Fără asta, un telefon
 * deconectat primea în continuare reminderele contului, cu numele persoanelor
 * pe ecranul blocat (docs/21, M-06). Nu aruncă: ieșirea din cont nu așteaptă
 * după rețea.
 */
export async function unregisterDevice(): Promise<void> {
  const token = await readPushToken();

  if (!token) return;

  try {
    await api.delete('/devices', { data: { token } });
    await writePushToken(null);
  } catch {
    // Fără rețea, cererea se pierde. Dacă altcineva intră apoi în cont pe
    // același telefon, serverul mută tokenul la el (updateOrCreate pe token).
  }
}

type NotificationData = {
  type?: string;
  person_id?: number;
  submission_id?: number;
  notification_id?: number;
} | undefined;

/**
 * Un reminder apăsat se numără pe server: rata de deschidere e poarta G4 din
 * docs/07. Nu așteptăm răspunsul și nu arătăm erori — navigarea contează mai mult.
 */
export function markReminderOpened(data: unknown): void {
  const id = (data as NotificationData)?.notification_id;

  if (id) {
    api.post(`/notifications/${id}/opened`).catch(() => undefined);
  }
}

/** O notificare despre completările din link care așteaptă „cine este?”. */
export function isSubmissionNotification(data: unknown): boolean {
  const type = (data as NotificationData)?.type;

  return type === 'submission' || type === 'submissions';
}

/**
 * Apăsarea unei notificări duce la ce anunță: la persoană, la completarea care
 * așteaptă răspuns sau, pentru mai multe completări, la ecranul principal, unde
 * stau toate.
 */
export function routeFromNotification(
  response: Notifications.NotificationResponse,
): string | null {
  const data = response.notification.request.content.data as NotificationData;

  if (data?.type === 'submission' && data.submission_id) {
    return `/submissions/${data.submission_id}`;
  }

  if (data?.type === 'submissions') {
    return '/home';
  }

  return data?.person_id ? `/people/${data.person_id}` : null;
}
