import { useState } from 'react';
import { Text, View } from 'react-native';
import { useRouter } from 'expo-router';
import { useTranslation } from 'react-i18next';
import { MotiView } from 'moti';

import { Button } from '../../src/components/ui/Button';
import { Screen } from '../../src/components/ui/Screen';
import { requestPermission } from '../../src/features/contacts/deviceContacts';
import { TYPE } from '../../src/design/typography';

/** Textul cu **accent** devine bold, fără a introduce un parser de markdown. */
function Emphasised({ text }: { text: string }) {
  return (
    <Text className="text-base leading-relaxed text-surface-600">
      {text.split(/\*\*(.+?)\*\*/g).map((part, index) =>
        index % 2 === 1 ? (
          <Text key={index} className="font-semibold text-surface-900">{part}</Text>
        ) : (
          part
        ),
      )}
    </Text>
  );
}

/**
 * Ecranul O5 din docs/09: explicăm ce citim ÎNAINTE de promptul nativ.
 *
 * Nu e o alegere de stil — App Store Guideline 5.1.2 cere ca utilizatorul să
 * înțeleagă de ce ceri accesul, iar aplicația trebuie să funcționeze și dacă
 * refuză. Ambele rute de mai jos duc mai departe.
 */
export default function ContactsExplainer() {
  const { t } = useTranslation();
  const router = useRouter();
  const [busy, setBusy] = useState(false);

  const ask = async () => {
    setBusy(true);
    const granted = await requestPermission();
    setBusy(false);

    router.replace(granted ? '/onboarding/select' : '/people/new');
  };

  return (
    <Screen>
      <View className="flex-1 justify-center px-6">
        <MotiView from={{ opacity: 0, translateY: 12 }} animate={{ opacity: 1, translateY: 0 }}>
          <Text className="text-3xl">📇</Text>
          <Text className="mt-4 text-surface-900" style={TYPE.title}>
            {t('onboarding.explainTitle')}
          </Text>
          <View className="mt-3">
            <Emphasised text={t('onboarding.explainBody')} />
          </View>
        </MotiView>

        <View className="mt-8 gap-3">
          {['explainNo1', 'explainNo2', 'explainNo3'].map((key, index) => (
            <MotiView
              key={key}
              from={{ opacity: 0, translateX: -8 }}
              animate={{ opacity: 1, translateX: 0 }}
              transition={{ type: 'timing', duration: 300, delay: 120 + index * 70 }}
              className="flex-row items-center gap-3 rounded-card bg-white px-4 py-3"
            >
              <Text className="text-base">{index === 2 ? '✅' : '🚫'}</Text>
              <Text className="flex-1 text-sm text-surface-700">{t(`onboarding.${key}`)}</Text>
            </MotiView>
          ))}
        </View>

        <View className="mt-10 gap-3">
          <Button label={t('onboarding.allow')} onPress={ask} loading={busy} />
          <Button
            label={t('onboarding.manual')}
            variant="ghost"
            onPress={() => router.replace('/people/new')}
          />
        </View>
      </View>
    </Screen>
  );
}
