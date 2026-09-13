import { useState } from 'react';
import { Text, View } from 'react-native';
import { useRouter } from 'expo-router';
import { useTranslation } from 'react-i18next';
import { MotiView } from 'moti';

import { Button } from '../../src/components/ui/Button';
import { Screen } from '../../src/components/ui/Screen';
import { requestPushPermission } from '../../src/features/notifications/push';
import { TYPE } from '../../src/design/typography';

/**
 * Ecranul O9 din docs/09. Cerem push DUPĂ momentul „aha”, niciodată la pornire.
 *
 * Utilizatorul tocmai a văzut câte ocazii i-am găsit — abia acum întrebarea
 * „să te anunțăm?” are un răspuns evident. Vezi docs/02 § R3.
 */
export default function PushPermission() {
  const { t } = useTranslation();
  const router = useRouter();
  const [busy, setBusy] = useState(false);
  const [declined, setDeclined] = useState(false);

  const ask = async () => {
    setBusy(true);
    const status = await requestPushPermission();
    setBusy(false);

    // Refuzul nu e un zid: spunem ce rămâne fără notificări (docs/09, O9).
    if (status === 'denied') {
      setDeclined(true);

      return;
    }

    router.replace('/home');
  };

  if (declined) {
    return (
      <Screen>
        <View className="flex-1 justify-center px-6">
          <Text className="text-center text-5xl">🗓️</Text>
          <Text className="mt-6 text-center text-surface-900" style={TYPE.title}>
            {t('push.deniedTitle')}
          </Text>
          <Text className="mt-3 text-center text-base leading-relaxed text-surface-500">
            {t('push.deniedBody')}
          </Text>
          <View className="mt-10">
            <Button label={t('common.continue')} onPress={() => router.replace('/home')} />
          </View>
        </View>
      </Screen>
    );
  }

  return (
    <Screen>
      <View className="flex-1 justify-center px-6">
        <MotiView
          from={{ opacity: 0, scale: 0.92 }}
          animate={{ opacity: 1, scale: 1 }}
          transition={{ type: 'spring', damping: 16 }}
          className="items-center"
        >
          <Text className="text-5xl">🔔</Text>
        </MotiView>

        <Text className="mt-6 text-center text-surface-900" style={TYPE.title}>
          {t('push.title')}
        </Text>

        <Text className="mt-3 text-center text-base leading-relaxed text-surface-500">
          {t('push.body')}
        </Text>

        <View className="mt-10 gap-3">
          <Button label={t('push.allow')} onPress={ask} loading={busy} />
          <Button label={t('push.skip')} variant="ghost" onPress={() => router.replace('/home')} />
        </View>
      </View>
    </Screen>
  );
}
