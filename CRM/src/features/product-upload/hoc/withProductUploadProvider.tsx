/**
 * HOC: mbështjell komponentë me `ProductUploadProvider` kur `businessId` është i njohur paraprakisht.
 * Faqja `ProductUploadPage` përdor `ProductUploadBusinessGate` + provider me ID nga `/api/business/me`.
 */

import type { ComponentType } from 'react';
import { ProductUploadProvider, type ProductUploadProviderProps } from '../context/ProductUploadContext';

type ProviderOpts = Pick<ProductUploadProviderProps, 'businessId'>;

export function withProductUploadProvider<P extends object>(
  Wrapped: ComponentType<P>,
  providerProps?: ProviderOpts,
): ComponentType<P> {
  function WithProductUploadProviderComponent(props: P) {
    return (
      <ProductUploadProvider businessId={providerProps?.businessId}>
        <Wrapped {...props} />
      </ProductUploadProvider>
    );
  }

  const wrappedName = Wrapped.displayName ?? Wrapped.name ?? 'Component';
  WithProductUploadProviderComponent.displayName = `WithProductUploadProvider(${wrappedName})`;

  return WithProductUploadProviderComponent;
}
