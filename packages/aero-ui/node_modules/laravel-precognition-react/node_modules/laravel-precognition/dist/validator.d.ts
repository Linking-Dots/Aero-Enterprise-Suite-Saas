import { ValidationCallback, NamedInputEvent, SimpleValidationErrors, ValidationErrors, Validator as TValidator } from './types.js';
export declare const createValidator: (callback: ValidationCallback, initialData?: Record<string, unknown>) => TValidator;
/**
 * Normalise the validation errors as Inertia formatted errors.
 */
export declare const toSimpleValidationErrors: (errors: ValidationErrors | SimpleValidationErrors) => SimpleValidationErrors;
/**
 * Normalise the validation errors as Laravel formatted errors.
 */
export declare const toValidationErrors: (errors: ValidationErrors | SimpleValidationErrors) => ValidationErrors;
/**
 * Resolve the input's "name" attribute.
 */
export declare const resolveName: (name: string | NamedInputEvent) => string;
