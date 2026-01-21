/**
 *  External Dependencies
 */
import { Route, Routes } from 'react-router-dom';

/**
 *  Internal Dependencies
 */
import {
	Analytics,
	Dashboard,
	FreeVsPro,
	Help,
	Modules,
	Products,
	Settings,
} from './../screens';

const Router = () => {
	/* global _EVF_DASHBOARD_ */
	const { isPro, settingsURL } =
		typeof _EVF_DASHBOARD_ !== 'undefined' && _EVF_DASHBOARD_;

	return (
		<Routes>
			<Route path="/" element={<Dashboard />} />
			<Route path="/analytics" element={<Analytics />} />
			<Route path="/settings" element={<Settings to={settingsURL} />} />
			<Route path="/features" element={<Modules />} />
			{!isPro && <Route path="/free-vs-pro" element={<FreeVsPro />} />}
			<Route path="/help" element={<Help />} />
			<Route path="/products" element={<Products />} />
			<Route path="*" element={<Dashboard />} />
		</Routes>
	);
};

export default Router;
