import { Modal, Text, View } from 'react-native';
import { useTranslation } from 'react-i18next';

import { Button } from '../../components/ui/Button';
import { CheckRow, Group, Row, Section } from '../../components/ui/SettingsList';
import { useReducedMotion } from '../../design/motion';
import { TYPE } from '../../design/typography';
import { IDEA_STATUSES, type GiftIdea, type IdeaStatus } from './queries';

/**
 * Ce se poate face cu o idee: ecranul G2 din docs/09.
 *
 * „Am oferit cadoul” stă separat de stări, pentru că nu e o stare: mută ideea
 * în istoric, iar din acel moment recomandările nu mai propun produsul.
 */
export function IdeaSheet({
  idea,
  onClose,
  onStatus,
  onGiven,
  onShop,
  onDelete,
}: {
  idea: GiftIdea | null;
  onClose: () => void;
  onStatus: (status: IdeaStatus) => void;
  onGiven: () => void;
  onShop: () => void;
  onDelete: () => void;
}) {
  const { t } = useTranslation();
  const reduced = useReducedMotion();

  return (
    <Modal visible={idea !== null} animationType="slide" presentationStyle="pageSheet" onRequestClose={onClose}>
      <View className="flex-1 bg-surface-50 px-5 pt-8">
        {idea ? (
          <>
            <Text className="px-1 text-surface-900" style={TYPE.heading} numberOfLines={3}>
              {idea.title}
            </Text>

            <Section title={t('gifts.statusTitle')} index={0} reduced={reduced}>
              <Group>
                {IDEA_STATUSES.map((status) => (
                  <CheckRow
                    key={status}
                    single
                    label={t(`gifts.status.${status}`)}
                    checked={idea.status === status}
                    onPress={() => onStatus(status)}
                  />
                ))}
              </Group>
            </Section>

            <Section footer={t('gifts.givenHint')} index={1} reduced={reduced}>
              <Group>
                <Row label={t('gifts.given')} tone="accent" onPress={onGiven} />
                {idea.offer_id ? <Row label={t('gift.buy')} accessory="external" onPress={onShop} /> : null}
              </Group>
            </Section>

            <Section index={2} reduced={reduced}>
              <Group>
                <Row label={t('gifts.deleteIdea')} tone="danger" onPress={onDelete} />
              </Group>
            </Section>

            <Button label={t('common.done')} variant="ghost" className="mt-6" onPress={onClose} />
          </>
        ) : null}
      </View>
    </Modal>
  );
}
