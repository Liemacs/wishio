import { useEffect } from 'react';
import { View, type DimensionValue } from 'react-native';
import { useTranslation } from 'react-i18next';
import Animated, { cancelAnimation, useAnimatedStyle, useSharedValue, withRepeat, withTiming } from 'react-native-reanimated';

import { useReducedMotion } from '../../design/motion';

/**
 * Locul conținutului care se încarcă: forma lui, nu un spinner.
 *
 * Pulsează ușor. Cu mișcare redusă rămâne static: o animație în buclă nu-i
 * folosește cuiva care a cerut mai puțină mișcare.
 */
export function Skeleton({ width = '100%', height = 16, radius = 8 }: { width?: DimensionValue; height?: number; radius?: number }) {
  const reduced = useReducedMotion();
  const opacity = useSharedValue(1);

  useEffect(() => {
    if (reduced) {
      cancelAnimation(opacity);
      opacity.value = 1;

      return;
    }

    opacity.value = withRepeat(withTiming(0.45, { duration: 900 }), -1, true);

    return () => cancelAnimation(opacity);
  }, [reduced, opacity]);

  const style = useAnimatedStyle(() => ({ opacity: opacity.value }));

  return <Animated.View style={[{ width, height, borderRadius: radius, backgroundColor: '#e4e4e7' }, style]} />;
}

/** Rânduri de listă care se încarcă, cu înălțimea rândurilor reale: nimic nu sare la sosirea datelor. */
export function SkeletonRows({ count = 4 }: { count?: number }) {
  const { t } = useTranslation();

  return (
    <View className="gap-2" accessible accessibilityRole="progressbar" accessibilityLabel={t('common.loading')}>
      {Array.from({ length: count }, (_, index) => (
        <View key={index} className="flex-row items-center gap-3 rounded-card bg-white px-4 py-3.5">
          <Skeleton width={40} height={40} radius={20} />
          <View className="flex-1 gap-2">
            <Skeleton width="60%" height={14} />
            <Skeleton width="35%" height={12} />
          </View>
        </View>
      ))}
    </View>
  );
}
