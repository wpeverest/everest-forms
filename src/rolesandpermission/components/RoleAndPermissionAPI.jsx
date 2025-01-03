import apiFetch from "@wordpress/api-fetch";

const { restURL, security } =
	typeof evf_roles_and_permission !== "undefined" && evf_roles_and_permission;
const base = restURL + "everest-forms/v1/roels_and_permission/";

const urls = {
	bulkAssignPermission: base + "bulk-assign-permission-based-on-role",
	getWPRoles: base + "get-wp-roles",
	addUserManager : base + "add-user-manager",
	getManagers : base + "get-managers",
	removeManager: base + "remove-manager",
	bulkRemoveManager : base + "bulk-remove-managers"
};

export const bulkAssignPermission = async ( checkedItems ) => {
	return apiFetch({
		path: urls.bulkAssignPermission,
		method: "POST",
		headers: {
			"X-WP-Nonce": security,
		},
		data : {
			request: {

				checked_roles : checkedItems
			}
		}
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

export const addManagerRole = async ( user_email, assignedPermissions ) => {
	return apiFetch({
		path : urls.addUserManager,
		method : "POST",
		headers : {
			"X-WP-Nonce" : security,
		},
		data: {
			request: {
				user_email : user_email,
				assigned_permission : assignedPermissions
			},
		},
	}).then((res) => res);
}

export const getManagers = async ( offset="", pageSize="", searchManager="" ) =>{
	return apiFetch(
		{
			path: urls.getManagers,
			method: "POST",
			headers : {
				"X-WP-Nonce" : security,
			},
			data:{
				request: {
					offset: offset,
					page_size: pageSize,
					search_manager: searchManager,
				}
			}
		}
	).then( (res) => res );
 }

 export const removeManager = async ( userID ) => {
	return apiFetch(
		{
			path: urls.removeManager,
			method: "POST",
			headers: {
				"X-WP-Nonce" : security,
			},
			data: {
				request: {
					user_id : userID,
				},
			},
		}
	).then( (res) => res );
 }


 export const bulkRemoveManager = async ( userIDs ) => {
		return apiFetch({
			path: urls.bulkRemoveManager,
			method: "POST",
			headers: {
				"X-WP-Nonce" : security,
			},
			data: {
				request: {
					user_ids : userIDs,
				},
			},
		})
 }
