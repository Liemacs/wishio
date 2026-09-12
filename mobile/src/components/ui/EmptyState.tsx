import { Text, View } from 'react-native';
import { MotiView } from 'moti';

type Props = {
  emoji: string;
  title: string;
  description?: string;
  children?: React.ReactNode;
};

export function EmptyState({ emoji, title, description, children }: Props) {
  return (
    <MotiView
      from={{ opacity: 0, translateY: 8 }}
      animate={{ opacity: 1, translateY: 0 }}
      transition={{ type: 'timing', duration: 350 }}
      className="items-center px-8 py-16"
    >
      <View className="h-16 w-16 items-center justify-center rounded-full bg-surface-100">
        <Text className="text-3xl">{emoji}</Text>
      </View>

      <Text className="mt-5 text-center text-lg font-semibold text-surface-800">{title}</Text>

      {description ? (
        <Text className="mt-2 text-center text-sm leading-relaxed text-surface-500">{description}</Text>
      ) : null}

      {children ? <View className="mt-6 w-full">{children}</View> : null}
    </MotiView>
  );
}
