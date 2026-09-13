// Din SDK 57, funcțiile vechi importate din „expo-contacts” aruncă la rulare;
// aceleași funcții, neschimbate, stau în „expo-contacts/legacy”. API-ul nou, pe
// clase, numără lunile de la 1 și citește altfel ziua de naștere pe Android:
// migrarea cere un test pe ambele platforme, nu doar o schimbare de import.
import * as Contacts from 'expo-contacts/legacy';

/**
 * Citirea agendei: numele, ziua de naștere și poza.
 *
 * Pe server ajung doar numele și ziua, și doar pentru contactele bifate. Poza
 * nu pleacă de pe telefon (regula 4, docs/00 § D-023): se citește din agendă
 * abia când trebuie afișată. Nu cerem numere, emailuri sau adrese — nu ne
 * trebuie (D-017). Cererea minimă de câmpuri e și cerință App Store 5.1.2:
 * colectezi doar ce folosești.
 */
export type DeviceContact = {
  id: string;
  name: string;
  birthDate: string | null;   // YYYY-MM-DD
  birthYearKnown: boolean;
  /** Doar dacă are poză. Poza însăși se citește la afișare, cu `readContactPhoto`. */
  hasPhoto: boolean;
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

export type ContactsPermission = 'granted' | 'undetermined' | 'blocked';

/**
 * Starea permisiunii, fără să întrebe. „blocked”: sistemul nu mai afișează
 * dialogul, iar accesul se poate porni doar din Setările telefonului.
 */
export async function getContactsPermission(): Promise<ContactsPermission> {
  const { status, canAskAgain } = await Contacts.getPermissionsAsync();

  if (status === 'granted') return 'granted';

  return status === 'denied' && !canAskAgain ? 'blocked' : 'undetermined';
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
    // Doar semnul că există o poză. Cerută aici, poza ar fi scrisă pe iOS într-un
    // fișier pentru fiecare contact, la fiecare deschidere a listei.
    fields: [Contacts.Fields.Name, Contacts.Fields.Birthday, Contacts.Fields.ImageAvailable],
  });

  const contacts = data
    .filter((contact) => contact.id && (contact.name ?? '').trim().length >= 2)
    .map((contact): DeviceContact => ({
      id: contact.id as string,
      name: (contact.name as string).trim(),
      birthDate: toIsoDate(contact.birthday),
      birthYearKnown: contact.birthday?.year !== undefined,
      hasPhoto: contact.imageAvailable === true,
    }))
    .sort((a, b) => a.name.localeCompare(b.name));

  return {
    contacts,
    total: data.length,
    withBirthday: contacts.filter((c) => c.birthDate !== null).length,
  };
}

/**
 * Adresa locală a pozei unui contact, sau null.
 *
 * Pe iOS, modulul scrie miniatura în cache-ul aplicației; pe Android, adresa
 * duce direct în agendă. În ambele cazuri, poza rămâne pe telefon.
 */
export async function readContactPhoto(id: string): Promise<string | null> {
  try {
    // Fără acces dat deja, agenda nu se atinge: pe iOS, prima citire ar deschide
    // promptul de sistem departe de ecranul care îl explică (docs/09, O5).
    if (!(await getPermission())) return null;

    const contact = await Contacts.getContactByIdAsync(id, [Contacts.Fields.Image]);

    return contact?.image?.uri ?? null;
  } catch {
    // Fără acces la agendă sau cu un contact șters între timp: rămân inițialele.
    return null;
  }
}
