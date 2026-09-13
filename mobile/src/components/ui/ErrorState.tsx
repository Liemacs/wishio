import { useTranslation } from 'react-i18next';

import { errorMessage } from '../../api/client';
import { Button } from './Button';
import { EmptyState } from './EmptyState';

/**
 * Datele nu s-au putut încărca. Spunem ce s-a întâmplat și oferim reîncercarea,
 * în loc de un spinner fără sfârșit sau de o listă goală care minte.
 */
export function ErrorState({ error, onRetry }: { error: unknown; onRetry: () => void }) {
  const { t } = useTranslation();

  return (
    <EmptyState emoji="⚠️" title={t('errors.loadTitle')} description={errorMessage(error)}>
      <Button label={t('common.retry')} variant="secondary" onPress={onRetry} />
    </EmptyState>
  );
}
