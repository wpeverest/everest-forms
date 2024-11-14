export {};

interface ScriptData {
	security: string;
	restURL: string;
}

declare global {
	interface Window {
		evf_templates_script: ScriptData;
	}
}
