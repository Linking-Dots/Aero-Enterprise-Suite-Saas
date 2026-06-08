import React from 'react';
export interface CardViewContextValue {
    state: 'idle' | 'loading' | 'error';
    isQuiet: boolean;
    layout: 'grid' | 'gallery' | 'waterfall';
    cardOrientation: 'horizontal' | 'vertical';
    renderEmptyState: () => React.ReactNode;
}
export declare const CardViewContext: React.Context<CardViewContextValue>;
export declare function useCardViewContext(): CardViewContextValue | null;
