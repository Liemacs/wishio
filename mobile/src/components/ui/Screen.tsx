import { View } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';

type Props = {
  children: React.ReactNode;
  /** Fără padding sus când ecranul are deja un header de navigare. */
  edges?: { top?: boolean; bottom?: boolean };
};

export function Screen({ children, edges = { top: true, bottom: true } }: Props) {
  const insets = useSafeAreaInsets();

  return (
    <View
      className="flex-1 bg-surface-50"
      style={{
        paddingTop: edges.top ? insets.top : 0,
        paddingBottom: edges.bottom ? insets.bottom : 0,
      }}
    >
      {children}
    </View>
  );
}
