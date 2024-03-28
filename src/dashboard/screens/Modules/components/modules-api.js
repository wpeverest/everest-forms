import apiFetch from "@wordpress/api-fetch";

/* global _EVF_DASHBOARD_ */
const { evfRestApiNonce, restURL } =
	typeof _EVF_DASHBOARD_ !== "undefined" && _EVF_DASHBOARD_;

const base = restURL + "everest-forms/v1/";
const urls = {
	modules: base + "modules",
};

export const getAllModules = async() => {
	return apiFetch({
		path: `${urls.modules}`,
		method: "get",
		headers: {
			"X-WP-Nonce": evfRestApiNonce,
		},
	}).then((res) => res);
};
