
import React, { createContext, useReducer } from 'react';

const DashboardContext = createContext();

export const DashboardProvider = ({ dashboardReducer, initialState, children }) => {
  return (
    <DashboardContext.Provider value={useReducer(dashboardReducer, initialState)}>
      {children}
    </DashboardContext.Provider>
  );
};

export default DashboardContext;
