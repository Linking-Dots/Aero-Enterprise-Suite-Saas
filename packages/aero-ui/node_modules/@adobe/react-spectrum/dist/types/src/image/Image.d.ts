import { DOMProps, StyleProps } from '@react-types/shared';
import React, { ReactEventHandler } from 'react';
export interface ImageProps {
    /**
     * The URL of the image.
     */
    src: string;
    /**
     * Text description of the image.
     */
    alt?: string;
    /**
     * Sets the Image [object-fit](https://developer.mozilla.org/en-US/docs/Web/CSS/object-fit) style.
     */
    objectFit?: any;
    /**
     * Called if an error occurs while loading or rendering an image, see [Image loading
     * errors](https://developer.mozilla.org/en-US/docs/Web/HTML/Element/img#image_loading_errors).
     */
    onError?: ReactEventHandler<HTMLImageElement>;
    /**
     * Called when the image has successfully loaded, see [load
     * event](https://developer.mozilla.org/en-US/docs/Web/API/HTMLElement/load_event).
     */
    onLoad?: ReactEventHandler<HTMLImageElement>;
    /**
     * Indicates if the fetching of the image must be done using a CORS request.
     * [See MDN](https://developer.mozilla.org/en-US/docs/Web/HTML/Attributes/crossorigin).
     */
    crossOrigin?: 'anonymous' | 'use-credentials';
}
export interface SpectrumImageProps extends ImageProps, DOMProps, StyleProps {
    /**
     * A slot to place the image in.
     *
     * @default 'image'
     */
    slot?: string;
}
/**
 * Image is used to insert and display an image within a component.
 */
export declare const Image: React.ForwardRefExoticComponent<SpectrumImageProps & React.RefAttributes<import("@react-types/shared").DOMRefValue<HTMLDivElement>>>;
