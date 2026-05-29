import ReactDOM from 'react-dom/client';
import App from './App';
import Analytics from './screens/Analytics/Analytics';

(function () {
	const container = document.getElementById('everest-forms-dashboard');

	if (!container) return;

	const root = ReactDOM.createRoot(container);
	if (root) {
		root.render(<App />);
	}
})();

// Mount on the dedicated evf-analytics page (free version — shows upgrade prompt).
const analyticsRoot = document.getElementById('evf-analytics-root');
if (analyticsRoot) {
	ReactDOM.createRoot(analyticsRoot).render(<Analytics />);
}
