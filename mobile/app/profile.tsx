import { useState } from 'react';
import { ActivityIndicator, Alert, Pressable, ScrollView, Share, Text, TextInput, View } from 'react-native';
import { useRouter } from 'expo-router';
import { useTranslation } from 'react-i18next';
import { MotiView } from 'moti';
import { LinearGradient } from 'expo-linear-gradient';
import * as Clipboard from 'expo-clipboard';
import * as Haptics from 'expo-haptics';

import { errorMessage } from '../src/api/client';
import { Button } from '../src/components/ui/Button';
import { Screen } from '../src/components/ui/Screen';
import { useAddWish, useMyProfile, useRemoveWish, useUpdateMyProfile, type WishKind } from '../src/features/profile/queries';

const KINDS: WishKind[] = ['product', 'place', 'experience'];
const VISIBILITY_FIELDS = ['birth_date', 'interests', 'wishlist'] as const;

export default function MyProfileScreen() {
  const { t } = useTranslation();
  const router = useRouter();

  const { data: profile, isLoading } = useMyProfile();
  const updateProfile = useUpdateMyProfile();
  const addWish = useAddWish();
  const removeWish = useRemoveWish();

  const [wishText, setWishText] = useState('');
  const [wishKind, setWishKind] = useState<WishKind>('product');
  const [copied, setCopied] = useState(false);

  const share = () => {
    if (!profile) return;

    // Linkul îl trimite UTILIZATORUL, din propriul messenger. Aplicația nu
    // trimite niciodată mesaje în numele lui — regula 10 din CLAUDE.md și
    // cerință App Store.
    Share.share({ message: t('profile.shareMessage') + profile.url });
  };

  const copy = async () => {
    if (!profile) return;

    await Clipboard.setStringAsync(profile.url);
    Haptics.notificationAsync(Haptics.NotificationFeedbackType.Success);
    setCopied(true);
    setTimeout(() => setCopied(false), 1800);
  };

  const toggleVisibility = (field: string) => {
    if (!profile) return;

    Haptics.selectionAsync();

    updateProfile.mutate({
      visibility: {
        ...profile.visibility,
        [field]: profile.visibility[field] === 'public' ? 'private' : 'public',
      },
    });
  };

  if (isLoading || !profile) {
    return (
      <Screen>
        <View className="flex-1 items-center justify-center">
          <ActivityIndicator color="#e11d48" />
        </View>
      </Screen>
    );
  }

  return (
    <Screen edges={{ top: true, bottom: false }}>
      <View className="flex-row items-center gap-3 px-5 py-3">
        <Pressable onPress={() => router.back()} className="py-1 pr-2 active:opacity-60">
          <Text className="text-base text-primary-600">‹ {t('common.back')}</Text>
        </Pressable>
        <Text className="text-lg font-semibold text-surface-900">{t('profile.title')}</Text>
      </View>

      <ScrollView contentContainerStyle={{ paddingHorizontal: 20, paddingBottom: 60 }}>

        {/* ── Linkul: partea cea mai importantă a ecranului ──────────────── */}
        <MotiView
          from={{ opacity: 0, translateY: 8 }}
          animate={{ opacity: 1, translateY: 0 }}
          className="overflow-hidden rounded-card"
        >
          <LinearGradient colors={['#fff1f2', '#ffffff']} start={{ x: 0, y: 0 }} end={{ x: 1, y: 1 }}>
            <View className="p-5">
              <Text className="text-sm font-semibold text-surface-500">{t('profile.linkTitle')}</Text>

              <Pressable onPress={copy} className="mt-2 active:opacity-60">
                <Text className="text-base font-semibold text-primary-700" numberOfLines={1}>
                  {profile.url.replace(/^https?:\/\//, '')}
                </Text>
              </Pressable>

              <Text className="mt-2 text-sm leading-relaxed text-surface-500">{t('profile.linkHint')}</Text>

              <View className="mt-4 flex-row gap-2">
                <View className="flex-1">
                  <Button label={t('profile.share')} onPress={share} />
                </View>
                <View className="flex-1">
                  <Button label={copied ? t('profile.copied') : t('profile.copy')}
                          variant="secondary" onPress={copy} />
                </View>
              </View>

              <Text className="mt-4 text-xs text-surface-400">
                {profile.submissions > 0
                  ? t('profile.stats', { count: profile.submissions })
                  : t('profile.statsNone')}
                {profile.view_count > 0 ? ` · ${t('profile.views', { count: profile.view_count })}` : ''}
              </Text>
            </View>
          </LinearGradient>
        </MotiView>

        {/* ── Ce îmi doresc ──────────────────────────────────────────────── */}
        <Section title={t('profile.wishlistTitle')} hint={t('profile.wishlistHint')}>
          <View className="flex-row gap-2">
            {KINDS.map((kind) => (
              <Pressable
                key={kind}
                onPress={() => setWishKind(kind)}
                className={`rounded-full px-3 py-1.5 ${wishKind === kind ? 'bg-primary-600' : 'bg-surface-200'}`}
              >
                <Text className={`text-xs font-medium ${wishKind === kind ? 'text-white' : 'text-surface-700'}`}>
                  {t(`profile.kind${kind[0].toUpperCase()}${kind.slice(1)}`)}
                </Text>
              </Pressable>
            ))}
          </View>

          <View className="mt-3 flex-row gap-2">
            <TextInput
              value={wishText}
              onChangeText={setWishText}
              placeholder={t('profile.wishPlaceholder')}
              placeholderTextColor="#d4d4d8"
              className="flex-1 rounded-button border border-surface-200 bg-white px-3.5 py-2.5 text-base"
            />
            <Button
              label={t('profile.addWish')}
              disabled={wishText.trim().length < 2}
              loading={addWish.isPending}
              onPress={() =>
                addWish.mutate(
                  { kind: wishKind, title: wishText.trim(), visibility: 'public' },
                  {
                    onSuccess: () => setWishText(''),
                    onError: (error) => Alert.alert('', errorMessage(error)),
                  },
                )
              }
            />
          </View>

          {profile.wishlist.length === 0 ? (
            <Text className="mt-3 text-sm text-surface-400">{t('profile.wishlistEmpty')}</Text>
          ) : (
            <View className="mt-3 gap-2">
              {profile.wishlist.map((item) => (
                <View key={item.id} className="flex-row items-center gap-3 rounded-button bg-white px-4 py-3">
                  <Text className="flex-1 text-base text-surface-800" numberOfLines={1}>{item.title}</Text>
                  <Pressable onPress={() => removeWish.mutate(item.id)} className="active:opacity-60">
                    <Text className="text-sm text-surface-400">✕</Text>
                  </Pressable>
                </View>
              ))}
            </View>
          )}
        </Section>

        {/* ── Vizibilitate ───────────────────────────────────────────────── */}
        <Section title={t('profile.visibilityTitle')}>
          <View className="gap-2">
            {VISIBILITY_FIELDS.map((field) => {
              const isPublic = profile.visibility[field] === 'public';
              const label = { birth_date: 'visBirthday', interests: 'visInterests', wishlist: 'visWishlist' }[field];

              return (
                <Pressable
                  key={field}
                  onPress={() => toggleVisibility(field)}
                  className="flex-row items-center justify-between rounded-button bg-white px-4 py-3.5 active:opacity-70"
                >
                  <Text className="text-base text-surface-800">{t(`profile.${label}`)}</Text>
                  <View className={`rounded-full px-3 py-1 ${isPublic ? 'bg-success/10' : 'bg-surface-200'}`}>
                    <Text className={`text-xs font-semibold ${isPublic ? 'text-success' : 'text-surface-500'}`}>
                      {t(isPublic ? 'profile.visPublic' : 'profile.visPrivate')}
                    </Text>
                  </View>
                </Pressable>
              );
            })}
          </View>
        </Section>

        {/* ── Notificări (M6) și Cont (M7) ──────────────────────────────── */}
        <ProfileLink
          className="mt-8"
          label={t('profile.notifications')}
          hint={t('notifications.profileHint')}
          onPress={() => router.push('/notifications')}
        />
        <ProfileLink
          className="mt-3"
          label={t('account.title')}
          hint={t('profile.accountHint')}
          onPress={() => router.push('/account')}
        />
      </ScrollView>
    </Screen>
  );
}

function Section({ title, hint, children }: { title: string; hint?: string; children: React.ReactNode }) {
  return (
    <View className="mt-8">
      <Text className="text-xs font-semibold uppercase tracking-wide text-surface-400">{title}</Text>
      {hint ? <Text className="mt-1 text-sm leading-relaxed text-surface-500">{hint}</Text> : null}
      <View className="mt-3">{children}</View>
    </View>
  );
}

function ProfileLink({
  label,
  hint,
  onPress,
  className = '',
}: {
  label: string;
  hint: string;
  onPress: () => void;
  className?: string;
}) {
  return (
    <Pressable
      onPress={onPress}
      className={`flex-row items-center gap-3 rounded-button bg-white px-4 py-3.5 active:opacity-70 ${className}`}
    >
      <View className="flex-1">
        <Text className="text-base text-surface-800">{label}</Text>
        <Text className="mt-0.5 text-xs text-surface-400">{hint}</Text>
      </View>
      <Text className="text-lg text-surface-300">›</Text>
    </Pressable>
  );
}
