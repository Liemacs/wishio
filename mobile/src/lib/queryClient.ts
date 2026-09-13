import { QueryClient } from '@tanstack/react-query';

export const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      staleTime: 60_000,
      retry: 2,
      // Pe telefon, „focus” înseamnă revenirea în aplicație (`src/lib/network.ts`):
      // datele mai vechi de un minut se reîncarcă atunci.
      refetchOnWindowFocus: true,
    },
    mutations: {
      // O modificare făcută offline eșuează imediat, cu mesaj, în loc să aștepte
      // în tăcere și să se aplice mai târziu, când nimeni nu se mai așteaptă.
      networkMode: 'always',
    },
  },
});
