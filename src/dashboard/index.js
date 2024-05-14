import React from "react";
import ReactDOM from "react-dom/client";
import App from "./App";
(function () {
	const container = document.getElementById("everest-forms-dashboard");

	if (!container) return;

	const root = ReactDOM.createRoot(container);
	if (root) {
		root.render(<App />);
	}
})();
