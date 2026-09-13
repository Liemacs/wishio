import { Linking, StyleSheet, Text, View } from 'react-native';
import { useTranslation } from 'react-i18next';
import { MotiView } from 'moti';

import { Button } from '../../components/ui/Button';
import { Screen } from '../../components/ui/Screen';
import { entrance, useReducedMotion } from '../../design/motion';
import { TYPE } from '../../design/typography';
import { APP_VERSION, isOlder, storeUrl, useAppConfig } from './version';

/**
 * Sub versiunea minimă, aplicația arată doar ecranul de actualizare (docs/10 § 5).
 *
 * Stă peste navigație, nu în locul ei: rutele rămân montate, dar nu se mai pot
 * atinge. Fără răspuns de la server — offline sau server căzut — aplicația
 * merge mai departe: o verificare care blochează la orice eroare de rețea ar
 * face mai mult rău decât o versiune veche.
 */
export function VersionGate() {
  const { data } = useAppConfig();

  if (!data || !isOlder(APP_VERSION, data.min_supported_version)) {
    return null;
  }

  return (
    <View style={StyleSheet.absoluteFill} accessibilityViewIsModal>
      <UpdateRequired url={storeUrl(data)} />
    </View>
  );
}

function UpdateRequired({ url }: { url: string | null }) {
  const { t } = useTranslation();
  const reduced = useReducedMotion();

  return (
    <Screen>
      <View className="flex-1 justify-center px-8">
        <MotiView {...entrance(reduced, 0)}>
          <Text className="text-center text-surface-900" style={TYPE.title}>{t('update.title')}</Text>
          <Text className="mt-3 text-center text-surface-500" style={TYPE.body}>{t('update.body')}</Text>
        </MotiView>

        <MotiView {...entrance(reduced, 1)} className="mt-8">
          {url ? (
            <Button label={t('update.action')} onPress={() => Linking.openURL(url)} />
          ) : (
            <Text className="text-center text-surface-500" style={TYPE.footnote}>{t('update.fromStore')}</Text>
          )}
        </MotiView>
      </View>
    </Screen>
  );
}
