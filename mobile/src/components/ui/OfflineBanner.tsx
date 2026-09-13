import { useSyncExternalStore } from 'react';
import { Text, View } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { onlineManager } from '@tanstack/react-query';
import { useTranslation } from 'react-i18next';
import { AnimatePresence, MotiView } from 'moti';

import { useReducedMotion } from '../../design/motion';
import { TYPE } from '../../design/typography';

const subscribe = (listener: () => void) => onlineManager.subscribe(listener);
const isOnline = () => onlineManager.isOnline();

/**
 * Banda „ești offline”.
 *
 * Sursa e onlineManager-ul TanStack Query: banda apare exact când cererile sunt
 * puse pe pauză, nu după o regulă separată care ar putea să nu coincidă.
 * Nu blochează atingerile: ce e deja încărcat rămâne folosibil.
 */
export function OfflineBanner() {
  const { t } = useTranslation();
  const insets = useSafeAreaInsets();
  const reduced = useReducedMotion();
  const online = useSyncExternalStore(subscribe, isOnline);
  const offset = reduced ? { opacity: 0 } : { opacity: 0, translateY: -12 };

  return (
    <AnimatePresence>
      {!online ? (
        <MotiView
          key="offline"
          from={offset}
          animate={{ opacity: 1, translateY: 0 }}
          exit={offset}
          transition={{ type: 'timing', duration: 220 }}
          pointerEvents="none"
          style={{ position: 'absolute', left: 12, right: 12, top: insets.top + 6 }}
        >
          <View className="rounded-card bg-surface-900 px-4 py-2.5" accessibilityRole="alert" accessibilityLiveRegion="polite">
            <Text className="text-white" style={TYPE.callout}>{t('network.offlineTitle')}</Text>
            <Text className="mt-0.5 text-surface-300" style={TYPE.footnote}>{t('network.offlineBody')}</Text>
          </View>
        </MotiView>
      ) : null}
    </AnimatePresence>
  );
}
