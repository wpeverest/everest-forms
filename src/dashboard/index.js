import React from 'react';
import ReactDOM from "react-dom/client";
import App from './components/App';

const container = document.getElementById("everest-forms-dashboard");
const root = ReactDOM.createRoot(container);
if (root) {
	root.render(
			<App />
	);
}
