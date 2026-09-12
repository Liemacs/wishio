import { useState } from 'react';
import { Pressable, ScrollView, Text, View } from 'react-native';
import { useTranslation } from 'react-i18next';

import type { Person, PersonInput } from '../../api/types';
import { Button } from '../../components/ui/Button';
import { Field } from '../../components/ui/Field';

const RELATIONSHIPS = ['partner', 'friend', 'parent', 'child', 'sibling', 'colleague', 'other'] as const;
const GENDERS = ['m', 'f'] as const;

/**
 * Ziua de naștere se scrie liber, fiindcă foarte des se știe doar ziua și luna.
 * Acceptăm ZZ.LL și ZZ.LL.AAAA; anul lipsă nu e o eroare, e cazul obișnuit.
 */
function parseBirthDate(input: string): { date: string | null; yearKnown: boolean } | null {
  const trimmed = input.trim();

  if (!trimmed) return { date: null, yearKnown: false };

  const match = trimmed.match(/^(\d{1,2})[.\-/](\d{1,2})(?:[.\-/](\d{4}))?$/);
  if (!match) return null;

  const [, day, month, year] = match;
  const d = Number(day);
  const m = Number(month);

  if (d < 1 || d > 31 || m < 1 || m > 12) return null;

  // Fără an, folosim un an-bisect fix ca 29 februarie să rămână valid.
  const resolvedYear = year ?? '2000';

  return {
    date: `${resolvedYear}-${String(m).padStart(2, '0')}-${String(d).padStart(2, '0')}`,
    yearKnown: Boolean(year),
  };
}

function formatBirthDate(person?: Person): string {
  if (!person?.birth_date) return '';

  const [year, month, day] = person.birth_date.split('-');

  return person.birth_year_known ? `${day}.${month}.${year}` : `${day}.${month}`;
}

type Props = {
  person?: Person;
  saving?: boolean;
  onSubmit: (input: PersonInput) => void;
  children?: React.ReactNode;
};

export function PersonForm({ person, saving, onSubmit, children }: Props) {
  const { t } = useTranslation();

  const [name, setName] = useState(person?.display_name ?? '');
  const [relationship, setRelationship] = useState(person?.relationship ?? null);
  const [gender, setGender] = useState(person?.gender ?? null);
  const [birthDate, setBirthDate] = useState(formatBirthDate(person));
  const [budgetMin, setBudgetMin] = useState(person?.budget_min ? String(person.budget_min) : '');
  const [budgetMax, setBudgetMax] = useState(person?.budget_max ? String(person.budget_max) : '');
  const [notes, setNotes] = useState(person?.notes ?? '');
  const [dateError, setDateError] = useState<string | null>(null);

  const submit = () => {
    const parsed = parseBirthDate(birthDate);

    if (!parsed) {
      setDateError(t('person.birthDatePlaceholder'));
      return;
    }

    setDateError(null);

    onSubmit({
      display_name: name.trim(),
      relationship,
      gender,
      birth_date: parsed.date,
      birth_year_known: parsed.yearKnown,
      budget_min: budgetMin ? Number(budgetMin) : null,
      budget_max: budgetMax ? Number(budgetMax) : null,
      notes: notes.trim() || null,
    });
  };

  return (
    <ScrollView contentContainerStyle={{ padding: 20, paddingBottom: 60 }} keyboardShouldPersistTaps="handled">
      <View className="gap-5">
        <Field
          label={t('person.name')}
          value={name}
          onChangeText={setName}
          placeholder={t('person.namePlaceholder')}
          autoCapitalize="words"
        />

        <View>
          <Text className="text-sm font-medium text-surface-700">{t('person.relationship')}</Text>
          <View className="mt-2 flex-row flex-wrap gap-2">
            {RELATIONSHIPS.map((code) => (
              <Pressable
                key={code}
                onPress={() => setRelationship(relationship === code ? null : code)}
                className={`rounded-full px-3.5 py-2 ${relationship === code ? 'bg-primary-600' : 'bg-surface-200'}`}
              >
                <Text className={`text-sm font-medium ${relationship === code ? 'text-white' : 'text-surface-700'}`}>
                  {t(`relationships.${code}`)}
                </Text>
              </Pressable>
            ))}
          </View>
        </View>

        <View>
          <Text className="text-sm font-medium text-surface-700">{t('person.gender')}</Text>
          <View className="mt-2 flex-row gap-2">
            {GENDERS.map((code) => (
              <Pressable
                key={code}
                onPress={() => setGender(gender === code ? null : code)}
                className={`rounded-full px-4 py-2 ${gender === code ? 'bg-primary-600' : 'bg-surface-200'}`}
              >
                <Text className={`text-sm font-medium ${gender === code ? 'text-white' : 'text-surface-700'}`}>
                  {t(`genders.${code}`)}
                </Text>
              </Pressable>
            ))}
          </View>
        </View>

        <Field
          label={t('person.birthDate')}
          value={birthDate}
          onChangeText={setBirthDate}
          placeholder={t('person.birthDatePlaceholder')}
          help={t('person.birthDateHelp')}
          error={dateError ?? undefined}
          keyboardType="numbers-and-punctuation"
        />

        <View>
          <Text className="text-sm font-medium text-surface-700">{t('person.budget')}</Text>
          <View className="mt-1.5 flex-row gap-3">
            <View className="flex-1">
              <Field label="" value={budgetMin} onChangeText={setBudgetMin}
                     placeholder={t('person.budgetMin')} keyboardType="number-pad" />
            </View>
            <View className="flex-1">
              <Field label="" value={budgetMax} onChangeText={setBudgetMax}
                     placeholder={t('person.budgetMax')} keyboardType="number-pad" />
            </View>
          </View>
        </View>

        <Field
          label={t('person.notes')}
          value={notes}
          onChangeText={setNotes}
          placeholder=""
          help={t('person.notesHelp')}
          multiline
          numberOfLines={3}
          style={{ minHeight: 88, textAlignVertical: 'top' }}
        />

        {children}

        <Button label={t('common.save')} onPress={submit} loading={saving} disabled={!name.trim()} />
      </View>
    </ScrollView>
  );
}
