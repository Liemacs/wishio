import { Pressable, Text, View } from 'react-native';
import { useTranslation } from 'react-i18next';

import type { Person } from '../../api/types';
import { ContactAvatar } from '../contacts/ContactAvatar';
import { relationshipLabel } from './relationship';

export function PersonRow({ person, onPress }: { person: Person; onPress: () => void }) {
  const { t } = useTranslation();

  const subtitle = [
    relationshipLabel(person.relationship) ?? '',
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
      <ContactAvatar name={person.display_name} contactId={person.device_contact_id} size={44} />

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
