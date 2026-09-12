import { ActivityIndicator, Pressable, Text, View } from 'react-native';
import * as Haptics from 'expo-haptics';
import Animated, { useAnimatedStyle, useSharedValue, withSpring } from 'react-native-reanimated';

import { spring, useReducedMotion } from '../../design/motion';
import { TYPE } from '../../design/typography';

const AnimatedPressable = Animated.createAnimatedComponent(Pressable);

type Variant = 'primary' | 'secondary' | 'ghost' | 'danger';

const STYLES: Record<Variant, { container: string; label: string }> = {
  primary:   { container: 'bg-primary-600', label: 'text-white' },
  secondary: { container: 'bg-surface-200', label: 'text-surface-800' },
  ghost:     { container: 'bg-transparent', label: 'text-primary-600' },
  danger:    { container: 'bg-transparent', label: 'text-danger' },
};

type Props = {
  label: string;
  onPress?: () => void;
  variant?: Variant;
  loading?: boolean;
  disabled?: boolean;
  className?: string;
};

export function Button({ label, onPress, variant = 'primary', loading, disabled, className = '' }: Props) {
  const reduced = useReducedMotion();
  const scale = useSharedValue(1);
  const style = useAnimatedStyle(() => ({ transform: [{ scale: scale.value }] }));
  const inactive = disabled || loading;

  return (
    <AnimatedPressable
      accessibilityRole="button"
      accessibilityState={{ disabled: !!inactive, busy: !!loading }}
      disabled={inactive}
      style={style}
      className={`rounded-button px-5 py-3.5 ${STYLES[variant].container} ${inactive ? 'opacity-50' : ''} ${className}`}
      /*
       * Feedbackul apare la APĂSARE, nu la eliberare. Un buton care așteaptă
       * ridicarea degetului ca să reacționeze se simte mort, oricât de rapid
       * ar fi restul.
       */
      onPressIn={() => {
        // Mișcarea redusă elimină scalarea, dar păstrează haptica: vibrația
        // nu e mișcare vestibulară și rămâne un semnal util.
        if (!reduced) {
          scale.value = withSpring(0.97, spring('press'));
        }

        Haptics.impactAsync(Haptics.ImpactFeedbackStyle.Light);
      }}
      onPressOut={() => {
        if (!reduced) {
          scale.value = withSpring(1, spring('press'));
        }
      }}
      onPress={onPress}
    >
      <View className="flex-row items-center justify-center gap-2">
        {loading && <ActivityIndicator size="small" color={variant === 'primary' ? '#fff' : '#71717a'} />}
        <Text
          className={`text-center ${STYLES[variant].label}`}
          style={{ ...TYPE.body, fontWeight: '600' }}
        >
          {label}
        </Text>
      </View>
    </AnimatedPressable>
  );
}
