import { Color } from 'react-stately/Color';
import { DOMProps, RefObject } from '@react-types/shared';
import { CSSProperties, JSX, ReactElement } from 'react';
interface ColorThumbProps extends DOMProps {
    value: Color;
    isDisabled?: boolean;
    isDragging?: boolean;
    isFocused?: boolean;
    className?: string;
    children?: ReactElement;
    style?: CSSProperties;
    containerRef?: RefObject<HTMLElement | null>;
}
declare function ColorThumb(props: ColorThumbProps): JSX.Element;
export { ColorThumb };
