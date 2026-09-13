export type Locale = 'ro' | 'ru' | 'en';

export type NameDayCalendar = 'orthodox_new' | 'orthodox_old' | 'catholic';

export type Profile = {
  id: number;
  name: string;
  email: string;
  locale: Locale;
  country_code: string;
  timezone: string;
  name_day_calendar: NameDayCalendar;
  birth_date: string | null;
  /** A fost deja întrebat despre AI, și ce a răspuns. Stări distincte. */
  ai_consent: boolean;
  ai_consent_asked: boolean;
};

/** Sursele, de la cea mai de încredere la cea mai slabă. Vezi docs/04 § 3. */
export type FieldSource =
  | 'subject_confirmed'
  | 'subject_provided'
  | 'owner_manual'
  | 'device_contact'
  | 'derived'
  | 'ai_inferred';

export type FieldTrust = {
  source: FieldSource;
  confidence: number;
  overridden: boolean;
};

export type Interest = {
  id: number;
  code: string;
  label: string;
  is_experience: boolean;
  confidence?: number;
  source?: FieldSource;
};

export type InterestGroup = {
  code: string;
  label: string;
  icon: string | null;
  interests: Interest[];
};

export type Person = {
  id: number;
  display_name: string;
  relationship: string | null;
  gender: 'm' | 'f' | null;
  birth_date: string | null;
  birth_year_known: boolean;
  age: number | null;
  budget_min: number | null;
  budget_max: number | null;
  notes: string | null;
  avatar_path: string | null;
  /** Contactul din agenda telefonului de pe care s-a importat; de acolo vine poza. */
  device_contact_id: string | null;
  trust?: Record<string, FieldTrust>;
  interests?: Interest[];
  created_at: string | null;
};

export type PersonInput = Partial<
  Pick<
    Person,
    | 'display_name' | 'relationship' | 'gender' | 'birth_date'
    | 'birth_year_known' | 'budget_min' | 'budget_max' | 'notes'
  >
>;
