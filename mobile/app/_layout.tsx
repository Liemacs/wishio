import '../global.css';
import { useEffect } from 'react';
import { Stack, useRouter, useSegments } from 'expo-router';
import { QueryClientProvider } from '@tanstack/react-query';
import { SafeAreaProvider } from 'react-native-safe-area-context';
import { GestureHandlerRootView } from 'react-native-gesture-handler';
import { StatusBar } from 'expo-status-bar';
import { I18nextProvider } from 'react-i18next';

import * as Notifications from 'expo-notifications';

import i18n from '../src/i18n';
import { queryClient } from '../src/lib/queryClient';
import { routeFromNotification } from '../src/features/notifications/push';
import { useAuthStore } from '../src/stores/auth';

// Notificarea se vede și când aplicația e deschisă: altfel utilizatorul
// primește reminderul exact în momentul în care nu îl observă.
Notifications.setNotificationHandler({
  handleNotification: async () => ({
    shouldShowBanner: true,
    shouldShowList: true,
    shouldPlaySound: true,
    shouldSetBadge: false,
  }),
});

/** Apăsarea unei notificări duce la persoana respectivă, nu la ecranul principal. */
function useNotificationRouting() {
  const router = useRouter();

  useEffect(() => {
    const subscription = Notifications.addNotificationResponseReceivedListener((response) => {
      const path = routeFromNotification(response);

      if (path) {
        router.push(path as never);
      }
    });

    return () => subscription.remove();
  }, [router]);
}

/** Trimite utilizatorul spre login sau spre aplicație, după starea sesiunii. */
function AuthGate() {
  const status = useAuthStore((s) => s.status);
  const restore = useAuthStore((s) => s.restore);
  const segments = useSegments();
  const router = useRouter();

  useEffect(() => { restore(); }, [restore]);

  useNotificationRouting();

  useEffect(() => {
    if (status === 'loading') return;

    // Cu typedRoutes, segments e un tuplu tipat — comparăm pe string.
    const first = segments[0] as string | undefined;
    const onLogin = first === 'login';
    const atRoot = first === undefined;
    // Onboarding-ul e o zonă autentificată cu rutare proprie; nu-l întrerupem.
    const inOnboarding = first === 'onboarding';

    if (status === 'guest' && !onLogin) {
      router.replace('/login');
    } else if (status === 'authenticated' && (onLogin || atRoot) && !inOnboarding) {
      router.replace('/home');
    }
  }, [status, segments, router]);

  return null;
}

export default function RootLayout() {
  return (
    <GestureHandlerRootView style={{ flex: 1 }}>
      <I18nextProvider i18n={i18n}>
        <QueryClientProvider client={queryClient}>
          <SafeAreaProvider>
            <StatusBar style="auto" />
            <AuthGate />
            <Stack screenOptions={{ headerShown: false, contentStyle: { backgroundColor: '#fafafa' } }} />
          </SafeAreaProvider>
        </QueryClientProvider>
      </I18nextProvider>
    </GestureHandlerRootView>
  );
}
