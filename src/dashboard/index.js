import React from "react";
import ReactDOM from "react-dom/client";
import App from "./App";
import dashboardReducer, { initialState } from "./reducers/DashboardReducer";
import { DashboardProvider } from "./context/DashboardContext";
(function() {
  const container = document.getElementById("everest-forms-dashboard");

  if (!container) return;

  const root = ReactDOM.createRoot(container);
  if (root) {
    root.render(
      <DashboardProvider
        initialState={initialState}
        dashboardReducer={dashboardReducer}
      >
        <App />
      </DashboardProvider>
    );
  }
})();
