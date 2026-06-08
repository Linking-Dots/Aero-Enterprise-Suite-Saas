export interface DialogContainerValue {
    type: 'modal' | 'popover' | 'tray' | 'fullscreen' | 'fullscreenTakeover';
    dismiss(): void;
}
export declare function useDialogContainer(): DialogContainerValue;
