import { useEffect } from "react";

const Settings = ({ to }) => {
	useEffect(() => {
		window.open(to);
		window.history.back();
	}, [to]);

	return null;
};

export default Settings;
