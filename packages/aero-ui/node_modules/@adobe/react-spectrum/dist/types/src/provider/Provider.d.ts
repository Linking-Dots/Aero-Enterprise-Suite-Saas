import { ProviderContext, ProviderProps } from './types';
import React from 'react';
/**
 * Provider is the container for all React Spectrum applications.
 * It defines the theme, locale, and other application level settings,
 * and can also be used to provide common properties to a group of components.
 */
export declare const Provider: React.ForwardRefExoticComponent<ProviderProps & React.RefAttributes<import("@react-types/shared").DOMRefValue<HTMLDivElement>>>;
/**
 * Returns the various settings and styles applied by the nearest parent Provider. Properties
 * explicitly set by the nearest parent Provider override those provided by preceeding Providers.
 */
export declare function useProvider(): ProviderContext;
export declare function useProviderProps<T>(props: T): T;
