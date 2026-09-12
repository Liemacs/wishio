import * as Contacts from 'expo-contacts';

/**
 * Citirea agendei. Cerem DOAR numele și ziua de naștere.
 *
 * Nu cerem numere, fotografii, emailuri sau adrese — nu ne trebuie și nu le
 * stocăm (docs/00 § D-017). Cererea minimă de câmpuri e și cerință App Store
 * 5.1.2: colectezi doar ce folosești.
 */
export type DeviceContact = {
  id: string;
  name: string;
  birthDate: string | null;   // YYYY-MM-DD
  birthYearKnown: boolean;
};

export type ContactsResult = {
  contacts: DeviceContact[];
  total: number;
  withBirthday: number;
};

export async function requestPermission(): Promise<boolean> {
  const { status } = await Contacts.requestPermissionsAsync();

  return status === 'granted';
}

export async function getPermission(): Promise<boolean> {
  const { status } = await Contacts.getPermissionsAsync();

  return status === 'granted';
}

/** Fără an, folosim un an bisect fix ca 29 februarie să rămână o dată validă. */
function toIsoDate(birthday: Contacts.Date | undefined): DeviceContact['birthDate'] {
  if (!birthday || birthday.month === undefined || birthday.day === undefined) {
    return null;
  }

  const year = birthday.year ?? 2000;
  const month = String(birthday.month + 1).padStart(2, '0'); // expo-contacts numără lunile de la 0
  const day = String(birthday.day).padStart(2, '0');

  return `${year}-${month}-${day}`;
}

export async function readContacts(): Promise<ContactsResult> {
  const { data } = await Contacts.getContactsAsync({
    fields: [Contacts.Fields.Name, Contacts.Fields.Birthday],
  });

  const contacts = data
    .filter((contact) => contact.id && (contact.name ?? '').trim().length >= 2)
    .map((contact): DeviceContact => ({
      id: contact.id as string,
      name: (contact.name as string).trim(),
      birthDate: toIsoDate(contact.birthday),
      birthYearKnown: contact.birthday?.year !== undefined,
    }))
    .sort((a, b) => a.name.localeCompare(b.name));

  return {
    contacts,
    total: data.length,
    withBirthday: contacts.filter((c) => c.birthDate !== null).length,
  };
}
