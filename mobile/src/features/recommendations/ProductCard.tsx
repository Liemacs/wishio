import { Pressable, Text, View } from 'react-native';
import { Image } from 'expo-image';
import { useTranslation } from 'react-i18next';
import { MotiView } from 'moti';

import { entrance, useReducedMotion } from '../../design/motion';
import { TYPE } from '../../design/typography';
import type { RecommendationItem } from './queries';

/** Cardul din ecranul R4. Prețul și magazinul sunt la fel de importante ca titlul. */
export function ProductCard({
  item,
  index,
  onPress,
  saved = false,
  onSave,
}: {
  item: RecommendationItem;
  index: number;
  onPress: () => void;
  saved?: boolean;
  onSave?: () => void;
}) {
  const { t, i18n } = useTranslation();
  const reduced = useReducedMotion();
  const { product } = item;

  const price = product.price
    ? new Intl.NumberFormat(i18n.language, { maximumFractionDigits: 0 }).format(product.price)
    : null;

  return (
    <MotiView {...entrance(reduced, index)}>
      <Pressable onPress={onPress} className="flex-row gap-3 rounded-card bg-white p-3 active:opacity-70">
        <View className="h-20 w-20 items-center justify-center overflow-hidden rounded-button bg-surface-100">
          {product.image_url ? (
            <Image source={product.image_url} style={{ width: '100%', height: '100%' }}
                   contentFit="contain" transition={200} />
          ) : (
            <Text className="text-2xl">🎁</Text>
          )}
        </View>

        <View className="flex-1">
          <Text className="text-base font-semibold leading-snug text-surface-900" numberOfLines={2}>
            {product.title}
          </Text>

          {/* Fără consimțământ AI nu inventăm motive: arătăm un text neutru,
              care spune adevărul — vine din interesele persoanei. */}
          <Text className="mt-1 text-xs leading-relaxed text-surface-500" numberOfLines={2}>
            {item.reason ?? t('gift.noReason')}
          </Text>

          <View className="mt-1.5 flex-row items-baseline gap-2">
            {price ? (
              <Text className="text-base font-bold text-surface-900">
                {price} {product.currency}
              </Text>
            ) : null}

            {product.offer ? (
              <Text className="text-xs text-surface-400" numberOfLines={1}>
                {t('gift.at', { merchant: product.offer.merchant })}
              </Text>
            ) : null}
          </View>

          {product.other_offers_count > 0 ? (
            <Text className="mt-0.5 text-xs text-primary-600">
              {t('gift.otherOffers', { count: product.other_offers_count })}
            </Text>
          ) : null}

          {onSave ? (
            <Pressable
              onPress={onSave}
              disabled={saved}
              hitSlop={8}
              accessibilityRole="button"
              accessibilityState={{ selected: saved }}
              className={`mt-2 self-start rounded-full px-3 py-1 ${saved ? 'bg-primary-50' : 'bg-surface-100 active:bg-surface-200'}`}
            >
              <Text className={saved ? 'text-primary-700' : 'text-surface-700'} style={TYPE.footnote}>
                {saved ? `★ ${t('gifts.saved')}` : `☆ ${t('gifts.save')}`}
              </Text>
            </Pressable>
          ) : null}
        </View>
      </Pressable>
    </MotiView>
  );
}
