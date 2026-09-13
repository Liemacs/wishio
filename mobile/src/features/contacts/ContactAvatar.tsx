import { useQuery } from '@tanstack/react-query';

import { Avatar } from '../../components/ui/Avatar';
import { readContactPhoto } from './deviceContacts';

/**
 * Poza unui contact, citită din agenda telefonului abia la afișare.
 *
 * Nu vine de pe server și nu ajunge acolo (regula 4, docs/00 § D-023). Fără
 * acces la agendă, pe alt telefon decât cel de pe care s-a făcut importul sau
 * pentru oamenii adăugați de mână, rezultatul e null și rămân inițialele.
 */
export function useContactPhoto(contactId: string | null | undefined, enabled = true) {
  return useQuery({
    queryKey: ['contact-photo', contactId],
    queryFn: () => readContactPhoto(contactId as string),
    enabled: enabled && !!contactId,
    // O poză de contact se schimbă rar: se recitește abia după ce iese din cache.
    staleTime: Infinity,
    retry: false,
    // Agenda e pe telefon, deci poza se citește și fără internet.
    networkMode: 'always',
  });
}

export function ContactAvatar({
  name,
  contactId,
  hasPhoto = true,
  size,
}: {
  name: string;
  contactId?: string | null;
  /** Când lista știe deja că nu există poză, agenda nu mai e întrebată. */
  hasPhoto?: boolean;
  size?: number;
}) {
  const { data: uri } = useContactPhoto(contactId, hasPhoto);

  return <Avatar name={name} uri={uri} size={size} />;
}
