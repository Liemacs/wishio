import { Pressable, Text, View } from 'react-native';
import { useTranslation } from 'react-i18next';

import type { Person } from '../../api/types';

/** Inițialele, cât timp nu avem avatar. Cyrillic și diacriticele funcționează. */
function initials(name: string): string {
  return name
    .split(/\s+/)
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part[0]?.toUpperCase() ?? '')
    .join('');
}

export function PersonRow({ person, onPress }: { person: Person; onPress: () => void }) {
  const { t } = useTranslation();

  const subtitle = [
    person.relationship ? t(`relationships.${person.relationship}`, { defaultValue: '' }) : '',
    person.age !== null ? t('people.years', { count: person.age }) : '',
  ]
    .filter(Boolean)
    .join(' · ');

  return (
    <Pressable
      onPress={onPress}
      accessibilityRole="button"
      className="flex-row items-center gap-3 rounded-card bg-white px-4 py-3.5 active:opacity-70"
    >
      <View className="h-11 w-11 items-center justify-center rounded-full bg-primary-50">
        <Text className="text-base font-semibold text-primary-700">{initials(person.display_name)}</Text>
      </View>

      <View className="flex-1">
        <Text className="text-base font-semibold text-surface-900" numberOfLines={1}>
          {person.display_name}
        </Text>
        {subtitle ? (
          <Text className="mt-0.5 text-sm text-surface-500" numberOfLines={1}>
            {subtitle}
          </Text>
        ) : null}
      </View>

      <Text className="text-surface-300">›</Text>
    </Pressable>
  );
}
