import { Text, View } from 'react-native';
import { MotiView } from 'moti';

import { entrance, useReducedMotion } from '../../design/motion';
import { TYPE } from '../../design/typography';

type Props = {
  emoji: string;
  title: string;
  description?: string;
  children?: React.ReactNode;
};

export function EmptyState({ emoji, title, description, children }: Props) {
  const reduced = useReducedMotion();

  return (
    <MotiView {...entrance(reduced)} className="items-center px-8 py-16">
      <View className="h-16 w-16 items-center justify-center rounded-full bg-surface-100">
        <Text className="text-3xl">{emoji}</Text>
      </View>

      <Text className="mt-5 text-center text-surface-800" style={TYPE.heading}>{title}</Text>

      {description ? (
        <Text className="mt-2 text-center text-sm leading-relaxed text-surface-500">{description}</Text>
      ) : null}

      {children ? <View className="mt-6 w-full">{children}</View> : null}
    </MotiView>
  );
}
