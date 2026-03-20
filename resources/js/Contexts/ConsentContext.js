import { createContext, useContext } from 'react';

export const ConsentContext = createContext(null);

export function useConsentContext() {
    return useContext(ConsentContext);
}
