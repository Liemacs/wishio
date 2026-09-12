import { Text, TextInput, View } from 'react-native';
import type { TextInputProps } from 'react-native';

type Props = TextInputProps & {
  label: string;
  help?: string;
  error?: string;
};

export function Field({ label, help, error, ...input }: Props) {
  return (
    <View>
      <Text className="text-sm font-medium text-surface-700">{label}</Text>

      <TextInput
        {...input}
        placeholderTextColor="#d4d4d8"
        className={`mt-1.5 rounded-button border px-3.5 py-3 text-base text-surface-900
                    ${error ? 'border-danger' : 'border-surface-200'} bg-white`}
      />

      {error ? (
        <Text className="mt-1.5 text-xs text-danger">{error}</Text>
      ) : help ? (
        <Text className="mt-1.5 text-xs leading-relaxed text-surface-400">{help}</Text>
      ) : null}
    </View>
  );
}
