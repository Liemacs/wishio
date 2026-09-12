import { ActivityIndicator, View } from 'react-native';

/** Ecran de tranziție cât AuthGate decide unde mergem. */
export default function Index() {
  return (
    <View className="flex-1 items-center justify-center bg-surface-50">
      <ActivityIndicator color="#e11d48" />
    </View>
  );
}
