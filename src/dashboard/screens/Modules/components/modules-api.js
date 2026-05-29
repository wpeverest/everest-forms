import apiFetch from "@wordpress/api-fetch";

/* global _EVF_DASHBOARD_ */
const { evfRestApiNonce, restURL } =
	typeof _EVF_DASHBOARD_ !== "undefined" && _EVF_DASHBOARD_;

const base = restURL + "everest-forms/v1/";
const urls = {
	modules: base + "modules",
	activateModule: base + "modules/activate",
	deactivateModule: base + "modules/deactivate",
	bulkActivateModules: base + "modules/bulk-activate",
	bulkDeactivateModules: base + "modules/bulk-deactivate",
	activateLicense: base + "modules/activate-license",
};

export const getAllModules = async () => {
	return apiFetch({
		path: `${urls.modules}`,
		method: "get",
		headers: {
			"X-WP-Nonce": evfRestApiNonce,
		},
	}).then((res) => res);
};

export const activateModule = async (slug, name, type) => {
	return apiFetch({
		path: urls.activateModule,
		method: "POST",
		headers: {
			"X-WP-Nonce": evfRestApiNonce,
		},
		data: {
			slug: slug,
			name: name,
			type: type,
		},
	}).then((res) => res);
};

export const deactivateModule = async (slug, type) => {
	return apiFetch({
		path: `${urls.deactivateModule}`,
		method: "POST",
		headers: {
			"X-WP-Nonce": evfRestApiNonce,
		},
		data: {
			slug: slug,
			type: type,
		},
	}).then((res) => res);
};
export const bulkActivateModules = async (moduleData) => {
	return apiFetch({
		path: urls.bulkActivateModules,
		method: "POST",
		headers: {
			"X-WP-Nonce": evfRestApiNonce,
		},
		data: {
			moduleData: moduleData,
		},
	}).then((res) => res);
};

export const bulkDeactivateModules = async (moduleData) => {
	return apiFetch({
		path: urls.bulkDeactivateModules,
		method: "POST",
		headers: {
			"X-WP-Nonce": evfRestApiNonce,
		},
		data: {
			moduleData: moduleData,
		},
	}).then((res) => res);
};

export const activateLicense = async (licenseActivationKey) => {
	return await apiFetch({
		path: urls.activateLicense,
		method: "POST",
		headers: {
			"X-WP-Nonce": evfRestApiNonce,
		},
		data: {
			licenseActivationKey: licenseActivationKey,
		},
	}).then((res) => res);
};
