import { useState } from 'react';
import { Alert } from 'react-native';
import * as Haptics from 'expo-haptics';
import * as WebBrowser from 'expo-web-browser';

import { errorMessage } from '../../api/client';
import { useTrackClick } from '../recommendations/queries';
import { IdeaSheet } from './IdeaSheet';
import { useDeleteIdea, useMarkGiven, useUpdateIdeaStatus, type GiftIdea } from './queries';

/** Foaia de acțiuni pentru o idee: aceeași în fișa persoanei și în lista globală. */
export function useIdeaActions() {
  const [idea, setIdea] = useState<GiftIdea | null>(null);
  const updateStatus = useUpdateIdeaStatus();
  const markGiven = useMarkGiven();
  const deleteIdea = useDeleteIdea();
  const track = useTrackClick();

  const close = () => setIdea(null);
  const fail = (error: unknown) => Alert.alert('', errorMessage(error));

  const sheet = (
    <IdeaSheet
      idea={idea}
      onClose={close}
      onStatus={(status) => {
        if (!idea || status === idea.status) return;

        Haptics.selectionAsync();
        // Foaia arată noua stare imediat; lista se reîncarcă după răspuns.
        setIdea({ ...idea, status });
        updateStatus.mutate({ id: idea.id, status }, { onError: fail });
      }}
      onGiven={() => {
        if (!idea) return;

        markGiven.mutate(idea.id, {
          onSuccess: () => {
            Haptics.notificationAsync(Haptics.NotificationFeedbackType.Success);
            close();
          },
          onError: fail,
        });
      }}
      onShop={async () => {
        if (!idea?.offer_id) return;

        try {
          const url = await track.mutateAsync({ offerId: idea.offer_id, personId: idea.person_id, context: 'idea' });
          await WebBrowser.openBrowserAsync(url);
        } catch (error) {
          fail(error);
        }
      }}
      onDelete={() => {
        if (!idea) return;

        deleteIdea.mutate(idea.id, { onSuccess: close, onError: fail });
      }}
    />
  );

  return { open: setIdea, sheet };
}
