export type TemplatesScriptData = {
	security: string;
	restURL: string;
	/** admin-ajax.php URL for the ThemeGrill AI Cloud (Python) actions. */
	ajaxUrl: string;
	/** Nonce for the evf_ai_* AJAX actions. */
	aiNonce: string;
	/** Whether the site is registered with the AI gateway. */
	aiRegistered: boolean;
	/** Current AI tier ("free" | "pro"). */
	aiTier: string;
	/** Whether the "Create with AI" feature is available (false on local/dev sites). */
	aiEnabled: boolean;
};
