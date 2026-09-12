import { useState } from 'react';
import { KeyboardAvoidingView, Platform, Pressable, ScrollView, Text, View } from 'react-native';
import { useRouter } from 'expo-router';
import { useTranslation } from 'react-i18next';
import { MotiView } from 'moti';

import { errorMessage } from '../src/api/client';
import { Button } from '../src/components/ui/Button';
import { Field } from '../src/components/ui/Field';
import { Screen } from '../src/components/ui/Screen';
import { SUPPORTED_LOCALES, type Locale } from '../src/i18n';
import { useAuthStore } from '../src/stores/auth';
import { useLocaleStore } from '../src/stores/locale';
import { TYPE } from '../src/design/typography';

const LOCALE_NAMES: Record<Locale, string> = { ro: 'Română', ru: 'Русский', en: 'English' };

export default function Login() {
  const { t } = useTranslation();
  const router = useRouter();
  const { locale, setLocale } = useLocaleStore();
  const { login, register } = useAuthStore();

  const [mode, setMode] = useState<'login' | 'register'>('login');
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState<string | null>(null);
  const [busy, setBusy] = useState(false);

  const submit = async () => {
    setBusy(true);
    setError(null);
    try {
      if (mode === 'login') {
        await login(email.trim(), password);
      } else {
        await register(name.trim(), email.trim(), password);
        // Un cont nou pornește direct în onboarding; unul existent are deja oameni.
        router.replace('/onboarding/contacts');
      }
    } catch (e) {
      setError(errorMessage(e));
    } finally {
      setBusy(false);
    }
  };

  return (
    <Screen>
      <KeyboardAvoidingView behavior={Platform.OS === 'ios' ? 'padding' : undefined} className="flex-1">
        <ScrollView contentContainerStyle={{ flexGrow: 1, justifyContent: 'center', padding: 24 }}>
          <MotiView from={{ opacity: 0, translateY: 12 }} animate={{ opacity: 1, translateY: 0 }}>
            <Text className="text-surface-900" style={TYPE.title}>{t('auth.welcome')}</Text>
            <Text className="mt-2 text-base leading-relaxed text-surface-500">{t('auth.subtitle')}</Text>
          </MotiView>

          <View className="mt-8 gap-4">
            {mode === 'register' && (
              <Field
                label={t('auth.name')}
                value={name}
                onChangeText={setName}
                autoCapitalize="words"
                textContentType="name"
              />
            )}

            <Field
              label={t('auth.email')}
              value={email}
              onChangeText={setEmail}
              autoCapitalize="none"
              autoCorrect={false}
              keyboardType="email-address"
              textContentType="emailAddress"
            />

            <Field
              label={t('auth.password')}
              value={password}
              onChangeText={setPassword}
              secureTextEntry
              textContentType="password"
              help={mode === 'register' ? t('auth.passwordHelp') : undefined}
              error={error ?? undefined}
            />
          </View>

          <Button
            label={t(mode === 'login' ? 'auth.login' : 'auth.register')}
            onPress={submit}
            loading={busy}
            disabled={!email || !password || (mode === 'register' && !name)}
            className="mt-7"
          />

          <Pressable
            onPress={() => { setMode(mode === 'login' ? 'register' : 'login'); setError(null); }}
            className="mt-4 py-2"
          >
            <Text className="text-center text-sm font-medium text-primary-600">
              {t(mode === 'login' ? 'auth.toRegister' : 'auth.toLogin')}
            </Text>
          </Pressable>

          <View className="mt-10 flex-row justify-center gap-2">
            {SUPPORTED_LOCALES.map((code) => (
              <Pressable
                key={code}
                onPress={() => setLocale(code)}
                className={`rounded-full px-4 py-2 ${code === locale ? 'bg-surface-200' : ''}`}
              >
                <Text className={`text-xs font-semibold ${code === locale ? 'text-surface-800' : 'text-surface-400'}`}>
                  {LOCALE_NAMES[code]}
                </Text>
              </Pressable>
            ))}
          </View>
        </ScrollView>
      </KeyboardAvoidingView>
    </Screen>
  );
}
