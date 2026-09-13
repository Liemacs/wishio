import { AppState, Platform } from 'react-native';
import * as Network from 'expo-network';
import { focusManager, onlineManager } from '@tanstack/react-query';

type Connectivity = { isConnected?: boolean; isInternetReachable?: boolean };

/**
 * Leagă TanStack Query de telefon: starea rețelei și revenirea în aplicație.
 *
 * Offline, cererile se pun pe pauză și pornesc singure când revine conexiunea;
 * `OfflineBanner` citește aceeași stare. La revenirea în aplicație, datele mai
 * vechi de un minut se reîncarcă.
 */
export function connectQueryToDevice(): void {
  onlineManager.setEventListener((setOnline) => {
    // Necunoscut înseamnă online: un telefon care nu raportează nu trebuie blocat.
    const apply = (state: Connectivity) =>
      setOnline(state.isConnected !== false && state.isInternetReachable !== false);

    Network.getNetworkStateAsync().then(apply).catch(() => undefined);
    const subscription = Network.addNetworkStateListener((event) => apply(event));

    return () => subscription.remove();
  });

  AppState.addEventListener('change', (status) => {
    if (Platform.OS !== 'web') {
      focusManager.setFocused(status === 'active');
    }
  });
}
