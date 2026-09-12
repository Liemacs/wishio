import { View, Text, Pressable, ScrollView } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { useTranslation } from 'react-i18next';
import { MotiView } from 'moti';
import { LinearGradient } from 'expo-linear-gradient';
import * as Haptics from 'expo-haptics';
import Animated, {
  useAnimatedStyle,
  useSharedValue,
  withSpring,
} from 'react-native-reanimated';

import { SUPPORTED_LOCALES, type Locale } from '../src/i18n';
import { useLocaleStore } from '../src/stores/locale';

const LOCALE_NAMES: Record<Locale, string> = {
  ro: 'Română',
  ru: 'Русский',
  en: 'English',
};

const AnimatedPressable = Animated.createAnimatedComponent(Pressable);

/** Buton cu feedback tactil si de scalare — baza pentru src/components/ui/Button. */
function PressableScale({
  children,
  onPress,
  className,
}: {
  children: React.ReactNode;
  onPress?: () => void;
  className?: string;
}) {
  const scale = useSharedValue(1);
  const style = useAnimatedStyle(() => ({ transform: [{ scale: scale.value }] }));

  return (
    <AnimatedPressable
      className={className}
      style={style}
      onPressIn={() => {
        scale.value = withSpring(0.96, { damping: 15, stiffness: 400 });
        Haptics.impactAsync(Haptics.ImpactFeedbackStyle.Light);
      }}
      onPressOut={() => {
        scale.value = withSpring(1, { damping: 15, stiffness: 400 });
      }}
      onPress={onPress}
    >
      {children}
    </AnimatedPressable>
  );
}

/**
 * Ecran de verificare a fundatiei (PLAN.md S1.5 / S1.6).
 * Confirma i18n, pluralul, NativeWind, Moti, Reanimated, gradient si haptics.
 * Se inlocuieste cu Home real la S5.6.
 */
export default function Index() {
  const { t } = useTranslation();
  const insets = useSafeAreaInsets();
  const { locale, setLocale } = useLocaleStore();

  return (
    <ScrollView
      className="flex-1 bg-surface-50"
      contentContainerStyle={{ paddingTop: insets.top + 24, paddingBottom: 48 }}
    >
      <View className="px-6">
        <MotiView
          from={{ opacity: 0, translateY: 12 }}
          animate={{ opacity: 1, translateY: 0 }}
          transition={{ type: 'timing', duration: 400 }}
        >
          <Text className="text-4xl font-bold text-surface-900">
            {t('common.appName')}
          </Text>
          <Text className="mt-2 text-base text-surface-500">
            {t('common.tagline')}
          </Text>
        </MotiView>

        <Text className="mt-10 text-xs font-semibold uppercase tracking-wide text-surface-400">
          {t('common.language')}
        </Text>
        <View className="mt-3 flex-row gap-2">
          {SUPPORTED_LOCALES.map((code) => {
            const active = code === locale;
            return (
              <PressableScale
                key={code}
                onPress={() => setLocale(code)}
                className={`rounded-button px-4 py-3 ${
                  active ? 'bg-primary-600' : 'bg-surface-200'
                }`}
              >
                <Text
                  className={`text-sm font-semibold ${
                    active ? 'text-white' : 'text-surface-700'
                  }`}
                >
                  {LOCALE_NAMES[code]}
                </Text>
              </PressableScale>
            );
          })}
        </View>

        {/* Formele de plural: rusa are 3 (1 / 2-4 / 5+), romana 3, engleza 2. */}
        <Text className="mt-10 text-xs font-semibold uppercase tracking-wide text-surface-400">
          Plural
        </Text>
        <View className="mt-3 rounded-card bg-white p-4">
          {[1, 2, 5, 21, 101].map((count, i) => (
            <MotiView
              key={count}
              from={{ opacity: 0, translateX: -8 }}
              animate={{ opacity: 1, translateX: 0 }}
              transition={{ type: 'timing', duration: 300, delay: i * 60 }}
            >
              <Text className="py-1 text-base text-surface-800">
                {t('reminder.daysBefore', { count })}
              </Text>
            </MotiView>
          ))}
        </View>

        <Text className="mt-10 text-xs font-semibold uppercase tracking-wide text-surface-400">
          {t('home.upcoming')}
        </Text>
        <MotiView
          from={{ opacity: 0, scale: 0.97 }}
          animate={{ opacity: 1, scale: 1 }}
          transition={{ type: 'spring', damping: 18, delay: 200 }}
          className="mt-3 overflow-hidden rounded-card"
        >
          <LinearGradient
            colors={['#fff1f2', '#ffffff']}
            start={{ x: 0, y: 0 }}
            end={{ x: 1, y: 1 }}
          >
            <View className="p-5">
              <View className="flex-row items-center gap-2">
                <View className="h-2 w-2 rounded-full bg-occasion-birthday" />
                <Text className="text-sm text-surface-500">
                  {t('occasions.birthday')}
                </Text>
              </View>
              <Text className="mt-1 text-2xl font-semibold text-surface-900">
                Alex
              </Text>
              <Text className="mt-1 text-base text-surface-500">
                {t('reminder.daysBefore', { count: 5 })}
              </Text>
              <PressableScale className="mt-4 rounded-button bg-primary-600 px-5 py-3">
                <Text className="text-center text-base font-semibold text-white">
                  {t('reminder.findGift')}
                </Text>
              </PressableScale>
            </View>
          </LinearGradient>
        </MotiView>
      </View>
    </ScrollView>
  );
}
