/**
 * Ambient declarations for the Style Customizer v2 panel.
 * (Babel strips types at build; these only satisfy the TS language service.)
 */
declare module '*.scss';
declare module '*.css';

interface Window {
	wp?: {
		apiFetch?: ( args: any ) => Promise< any >;
		i18n?: { __: ( text: string, domain?: string ) => string };
		media?: any;
	};
	evfStyleV2?: import('./types').BootstrapSettings;
}
