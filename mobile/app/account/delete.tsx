import { useState } from 'react';
import { Alert, KeyboardAvoidingView, Platform, Pressable, ScrollView, Text, View } from 'react-native';
import { useRouter } from 'expo-router';
import { useTranslation } from 'react-i18next';
import { MotiView } from 'moti';
import * as Haptics from 'expo-haptics';

import { errorMessage, fieldErrors } from '../../src/api/client';
import { Button } from '../../src/components/ui/Button';
import { Field } from '../../src/components/ui/Field';
import { Screen } from '../../src/components/ui/Screen';
import { entrance, useReducedMotion } from '../../src/design/motion';
import { TYPE } from '../../src/design/typography';
import { useDeleteAccount, useExportData } from '../../src/features/account/queries';
import { useAuthStore } from '../../src/stores/auth';

/** Ce dispare odată cu contul. Trebuie să corespundă cu DeleteAccount.php. */
const WHAT_IS_DELETED = ['whatPeople', 'whatGifts', 'whatLink', 'whatWishlist', 'whatSettings'] as const;

/**
 * Ștergerea contului.
 *
 * Două confirmări, ca pe server: emailul scris de mână și parola. Butonul
 * rămâne inactiv până când emailul corespunde, deci o apăsare greșită nu
 * ajunge nici măcar la server.
 *
 * Exportul e oferit chiar aici, înaintea câmpurilor: după ștergere nu mai
 * rămâne nimic de descărcat.
 */
export default function DeleteAccountScreen() {
  const { t } = useTranslation();
  const router = useRouter();
  const reduced = useReducedMotion();
  const email = useAuthStore((s) => s.profile?.email ?? '');
  const endSession = useAuthStore((s) => s.endSession);
  const deleteAccount = useDeleteAccount();
  const exportData = useExportData();

  const [confirmEmail, setConfirmEmail] = useState('');
  const [password, setPassword] = useState('');
  const [errors, setErrors] = useState<Record<string, string>>({});

  // Aceeași comparație ca pe server: fără spațiile de la capete, fără majuscule.
  const emailMatches = email !== '' && confirmEmail.trim().toLowerCase() === email.toLowerCase();
  const canSubmit = emailMatches && password.length > 0;

  const onExport = () =>
    exportData.mutate(undefined, {
      onSuccess: (result) => {
        if (result === 'unavailable') {
          Alert.alert('', t('account.exportUnavailable'));
        }
      },
      onError: (error) => Alert.alert('', errorMessage(error)),
    });

  const submit = async () => {
    setErrors({});

    try {
      await deleteAccount.mutateAsync({ confirm_email: confirmEmail.trim(), password });
    } catch (error) {
      Haptics.notificationAsync(Haptics.NotificationFeedbackType.Error);

      const fields = fieldErrors(error);
      setErrors(Object.keys(fields).length > 0 ? fields : { general: errorMessage(error) });
      return;
    }

    Haptics.notificationAsync(Haptics.NotificationFeedbackType.Success);
    Alert.alert(t('accountDelete.doneTitle'), t('accountDelete.doneBody'));

    // Închiderea sesiunii duce singură la login, prin AuthGate.
    await endSession();
  };

  return (
    <Screen>
      <View className="px-5 py-3">
        <Pressable onPress={() => router.back()} hitSlop={8} className="self-start py-1 pr-2 active:opacity-60">
          <Text className="text-base text-primary-600">‹ {t('common.back')}</Text>
        </Pressable>
      </View>

      <KeyboardAvoidingView behavior={Platform.OS === 'ios' ? 'padding' : undefined} className="flex-1">
        <ScrollView
          contentContainerStyle={{ paddingHorizontal: 20, paddingBottom: 40 }}
          keyboardShouldPersistTaps="handled"
        >
          <MotiView {...entrance(reduced, 0)}>
            <Text className="text-surface-900" style={TYPE.title}>{t('accountDelete.title')}</Text>
            <Text className="mt-3 text-surface-600" style={TYPE.body}>{t('accountDelete.body')}</Text>
          </MotiView>

          <MotiView {...entrance(reduced, 1)}>
            <View className="mt-6 rounded-card bg-white px-4 py-4">
              <Text className="text-surface-400" style={{ ...TYPE.caption, textTransform: 'uppercase' }}>
                {t('accountDelete.whatTitle')}
              </Text>

              {WHAT_IS_DELETED.map((key) => (
                <View key={key} className="mt-3 flex-row gap-3">
                  <View className="mt-2 h-1.5 w-1.5 rounded-full bg-danger" />
                  <Text className="flex-1 text-surface-700" style={TYPE.callout}>
                    {t(`accountDelete.${key}`)}
                  </Text>
                </View>
              ))}
            </View>
          </MotiView>

          <MotiView {...entrance(reduced, 2)}>
            <View className="mt-4 rounded-card bg-white px-4 py-4">
              <Text className="text-surface-900" style={TYPE.callout}>{t('accountDelete.exportTitle')}</Text>
              <Text className="mt-1 text-surface-500" style={TYPE.footnote}>{t('accountDelete.exportBody')}</Text>
              <Button
                label={t('accountDelete.exportAction')}
                variant="secondary"
                className="mt-3"
                loading={exportData.isPending}
                onPress={onExport}
              />
            </View>
          </MotiView>

          <MotiView {...entrance(reduced, 3)}>
            <View className="mt-8 gap-4">
              <Field
                label={t('accountDelete.confirmEmail')}
                help={t('accountDelete.confirmEmailHelp', { email })}
                error={errors.confirm_email}
                value={confirmEmail}
                onChangeText={setConfirmEmail}
                autoCapitalize="none"
                autoCorrect={false}
                keyboardType="email-address"
                /*
                 * Fără completare automată: confirmarea trebuie scrisă, altfel
                 * redevine o simplă apăsare pe o sugestie.
                 */
                autoComplete="off"
                textContentType="none"
              />

              <Field
                label={t('auth.password')}
                error={errors.password}
                value={password}
                onChangeText={setPassword}
                secureTextEntry
                autoComplete="current-password"
                textContentType="password"
              />
            </View>
          </MotiView>

          {errors.general ? (
            <Text className="mt-4 text-danger" style={TYPE.footnote}>{errors.general}</Text>
          ) : null}

          <Button
            label={t('accountDelete.submit')}
            variant="destructive"
            className="mt-6"
            disabled={!canSubmit}
            loading={deleteAccount.isPending}
            onPress={submit}
          />
        </ScrollView>
      </KeyboardAvoidingView>
    </Screen>
  );
}
