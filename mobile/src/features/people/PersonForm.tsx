import { useState } from 'react';
import { Pressable, ScrollView, Text, View } from 'react-native';
import { useTranslation } from 'react-i18next';

import type { Person, PersonInput } from '../../api/types';
import { BirthdayPicker, toIsoBirthday, type BirthdayValue } from '../../components/ui/BirthdayPicker';
import { Button } from '../../components/ui/Button';
import { Field } from '../../components/ui/Field';

const RELATIONSHIPS = ['partner', 'friend', 'parent', 'child', 'sibling', 'colleague', 'other'] as const;
const GENDERS = ['m', 'f'] as const;

function toBirthday(person?: Person): BirthdayValue | null {
  if (!person?.birth_date) return null;

  const [year, month, day] = person.birth_date.split('-').map(Number);

  return { day, month, year: person.birth_year_known ? year : null };
}

type Props = {
  person?: Person;
  saving?: boolean;
  onSubmit: (input: PersonInput) => void;
  /** Deasupra câmpurilor: de exemplu, poza persoanei. */
  header?: React.ReactNode;
  /** Sub butonul de salvare: secțiuni care nu se salvează odată cu formularul. */
  footer?: React.ReactNode;
  children?: React.ReactNode;
};

export function PersonForm({ person, saving, onSubmit, header, children, footer }: Props) {
  const { t } = useTranslation();

  const [name, setName] = useState(person?.display_name ?? '');
  const [relationship, setRelationship] = useState(person?.relationship ?? null);
  const [gender, setGender] = useState(person?.gender ?? null);
  const [birthday, setBirthday] = useState<BirthdayValue | null>(toBirthday(person));
  const [budgetMin, setBudgetMin] = useState(person?.budget_min ? String(person.budget_min) : '');
  const [budgetMax, setBudgetMax] = useState(person?.budget_max ? String(person.budget_max) : '');
  const [notes, setNotes] = useState(person?.notes ?? '');

  const submit = () => {
    onSubmit({
      display_name: name.trim(),
      relationship,
      gender,
      birth_date: birthday ? toIsoBirthday(birthday) : null,
      birth_year_known: birthday?.year != null,
      budget_min: budgetMin ? Number(budgetMin) : null,
      budget_max: budgetMax ? Number(budgetMax) : null,
      notes: notes.trim() || null,
    });
  };

  return (
    <ScrollView contentContainerStyle={{ padding: 20, paddingBottom: 60 }} keyboardShouldPersistTaps="handled">
      {header}

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

        <BirthdayPicker
          label={t('person.birthDate')}
          help={t('person.birthDateHelp')}
          value={birthday}
          onChange={setBirthday}
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

        {footer}
      </View>
    </ScrollView>
  );
}
