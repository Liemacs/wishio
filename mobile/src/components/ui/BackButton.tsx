import { Pressable, Text, View } from 'react-native';
import { useRouter } from 'expo-router';
import { useTranslation } from 'react-i18next';

/** Revenirea la ecranul anterior, pentru ecranele fără bară de navigare. */
export function BackButton() {
  const { t } = useTranslation();
  const router = useRouter();

  return (
    <View className="px-5 py-3">
      <Pressable
        onPress={() => router.back()}
        hitSlop={8}
        accessibilityRole="button"
        className="self-start py-1 pr-2 active:opacity-60"
      >
        <Text className="text-base text-primary-600">‹ {t('common.back')}</Text>
      </Pressable>
    </View>
  );
}
