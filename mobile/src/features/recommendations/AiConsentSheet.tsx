import { Modal, Text, View } from 'react-native';
import { useTranslation } from 'react-i18next';
import { MotiView } from 'moti';

import { Button } from '../../components/ui/Button';
import { useAiConsent } from './queries';
import { TYPE } from '../../design/typography';

/**
 * Ecranul R2 din docs/09.
 *
 * Apple cere, din 13 noiembrie 2025, permisiune explicită înainte ca date
 * personale să ajungă la un AI terț (docs/06 § 2). Spunem exact ce trimitem
 * ȘI ce nu trimitem — a doua parte contează mai mult.
 *
 * Refuzul duce mai departe, nu într-un zid: utilizatorul primește aceleași
 * produse, doar fără explicații.
 */
export function AiConsentSheet({
  open,
  onDecided,
}: {
  open: boolean;
  onDecided: (granted: boolean) => void;
}) {
  const { t } = useTranslation();
  const consent = useAiConsent();

  const decide = (granted: boolean) =>
    consent.mutate(granted, {
      onSuccess: () => onDecided(granted),
      onError: () => onDecided(false),
    });

  return (
    <Modal visible={open} animationType="slide" presentationStyle="pageSheet" onRequestClose={() => onDecided(false)}>
      <View className="flex-1 justify-center bg-surface-50 px-6">
        <MotiView from={{ opacity: 0, translateY: 10 }} animate={{ opacity: 1, translateY: 0 }}>
          <Text className="text-4xl">✨</Text>
          <Text className="mt-4 text-surface-900" style={TYPE.title}>
            {t('aiConsent.title')}
          </Text>
          <Text className="mt-3 text-base leading-relaxed text-surface-600">
            {t('aiConsent.body')}
          </Text>
        </MotiView>

        <View className="mt-6 gap-3">
          <View className="flex-row items-start gap-3 rounded-card bg-white px-4 py-3.5">
            <Text className="text-base">✅</Text>
            <Text className="flex-1 text-sm leading-relaxed text-surface-700">{t('aiConsent.sends')}</Text>
          </View>

          <View className="flex-row items-start gap-3 rounded-card bg-white px-4 py-3.5">
            <Text className="text-base">🚫</Text>
            <Text className="flex-1 text-sm font-medium leading-relaxed text-surface-800">
              {t('aiConsent.notSends')}
            </Text>
          </View>
        </View>

        <View className="mt-8 gap-3">
          <Button label={t('aiConsent.allow')} loading={consent.isPending} onPress={() => decide(true)} />
          <Button label={t('aiConsent.skip')} variant="ghost" onPress={() => decide(false)} />
        </View>

        <Text className="mt-5 text-center text-xs text-surface-400">{t('aiConsent.note')}</Text>
      </View>
    </Modal>
  );
}
