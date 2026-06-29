import { View, Text } from 'react-native';
import { useTheme } from '@/context/ThemeContext';

export default function MockSetupScreen() {
  const { theme } = useTheme();
  const isDark = theme === 'dark';

  return (
    <View className={`flex-1 items-center justify-center ${isDark ? 'bg-neutral-950' : 'bg-white'}`}>
      <Text className={`text-2xl font-bold ${isDark ? 'text-neutral-50' : 'text-neutral-900'}`}>Mock Exam Setup</Text>
      <Text className={`mt-2 ${isDark ? 'text-neutral-400' : 'text-neutral-500'}`}>Start a full length mock</Text>
    </View>
  );
}
