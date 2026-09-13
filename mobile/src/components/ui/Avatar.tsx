import { StyleSheet, Text, View } from 'react-native';
import { Image } from 'expo-image';

/** Inițialele, cât timp nu avem poză. Chirilicele și diacriticele funcționează. */
export function initials(name: string): string {
  return name
    .split(/\s+/)
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part[0]?.toUpperCase() ?? '')
    .join('');
}

type Props = {
  name: string;
  /** Adresă locală: pozele de contact nu vin niciodată de pe server (regula 4). */
  uri?: string | null;
  size?: number;
};

/**
 * Poza unui om sau, fără ea, inițialele lui.
 *
 * E decorativ: numele stă mereu lângă el, deci cititorul de ecran îl sare.
 * Poza apare peste inițiale prin estompare, nu prin deplasare, așa că arată
 * la fel și cu mișcare redusă.
 */
export function Avatar({ name, uri, size = 44 }: Props) {
  return (
    <View
      accessibilityElementsHidden
      importantForAccessibility="no-hide-descendants"
      className="items-center justify-center overflow-hidden rounded-full bg-primary-50"
      style={{ width: size, height: size }}
    >
      <Text
        allowFontScaling={false}
        className="font-semibold text-primary-700"
        style={{ fontSize: Math.round(size * 0.36) }}
      >
        {initials(name)}
      </Text>

      {uri ? (
        <Image
          source={{ uri }}
          recyclingKey={uri}
          contentFit="cover"
          // Doar în memorie: poza din agendă nu primește încă o copie pe disc.
          cachePolicy="memory"
          transition={150}
          style={[StyleSheet.absoluteFill, { borderRadius: size / 2 }]}
        />
      ) : null}
    </View>
  );
}
