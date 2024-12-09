import apiFetch from "@wordpress/api-fetch";

const { restURL, security } =
	typeof evf_roles_and_permission !== "undefined" && evf_roles_and_permission;
const base = restURL + "everest-forms/v1/roels_and_permission/";

const urls = {
	bulkAssignPermission: base + "bulk-assign-permission-based-on-role",
	getWPRoles: base + "get-wp-roles"
};

export const bulkAssignPermission = async () => {
	return apiFetch({
		path: urls.bulkAssignPermission,
		method: "POST",
		headers: {
			"X-WP-Nonce": security,
		},
	}).then((res) => res);
};

export const getWPRoles = async () => {
	return apiFetch({
		path: urls.getWPRoles,
		method: "get",
		headers: {
			"X-WP-Nonce": security,
		},
	}).then((res)=> res);
}
