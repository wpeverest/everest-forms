/**
 * External Dependencies
 */
import { applyFilters } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';
import { useEffect, useState } from 'react';

import analyticsPreview from '../../images/analytics-preview.png';
import './main.scss';

const CrownIcon = () => (
	<svg
		xmlns="http://www.w3.org/2000/svg"
		viewBox="0 0 24 24"
		fill="none"
		stroke="currentColor"
		strokeWidth="2"
		strokeLinecap="round"
		strokeLinejoin="round"
	>
		<path d="M11.562 3.266a.5.5 0 0 1 .876 0L15.39 8.87a1 1 0 0 0 1.516.294L21.183 5.5a.5.5 0 0 1 .798.519l-2.834 10.246a1 1 0 0 1-.956.734H5.81a1 1 0 0 1-.957-.734L2.02 6.02a.5.5 0 0 1 .798-.519l4.276 3.664a1 1 0 0 0 1.516-.294z" />
		<path d="M5 21h14" />
	</svg>
);

const ChevronDownIcon = () => (
	<svg
		xmlns="http://www.w3.org/2000/svg"
		viewBox="0 0 24 24"
		fill="none"
		stroke="#6b7280"
		strokeWidth="2"
		strokeLinecap="round"
		strokeLinejoin="round"
	>
		<polyline points="6 9 12 15 18 9" />
	</svg>
);

const getStaticDateRange = () => {
	const now = new Date();
	const from = new Date(now);
	from.setDate(from.getDate() - 30);
	const fmt = (d) =>
		d.toLocaleDateString('en-US', {
			month: 'short',
			day: 'numeric',
			year: 'numeric',
		});
	return `${fmt(from)} – ${fmt(now)}`;
};

const METRIC_BOXES = [
	{ label: __('Total Submissions', 'everest-forms') },
	{ label: __('Complete Submissions', 'everest-forms') },
	{ label: __('Incomplete Submissions', 'everest-forms') },
	{ label: __('Impressions', 'everest-forms') },
];

/**
 * FreeAnalyticsContent
 *
 * Shows a non-interactive header (filters + metric boxes showing 0) that
 * mirrors the Pro analytics UI, then a blurred Pro screenshot with an
 * "Unlock Advanced Analytics" upgrade overlay.
 *
 * All styles live in ./main.scss under .EVF-Free-Analytics.
 * When the Pro plugin is active it replaces this entirely via the
 * `everest-forms-analytics` WordPress filter.
 */
const FreeAnalyticsContent = () => {
	const upgradeURL =
		typeof _EVF_DASHBOARD_ !== 'undefined' && _EVF_DASHBOARD_.upgradeURL
			? `${_EVF_DASHBOARD_.upgradeURL}utm_medium=evf-dashboard&utm_source=evf-free&utm_campaign=analytics-upgrade-btn&utm_content=Upgrade+to+Pro`
			: 'https://everestforms.net/pricing/?utm_medium=evf-dashboard&utm_source=evf-free&utm_campaign=analytics-upgrade-btn&utm_content=Upgrade+to+Pro';

	const dateRange = getStaticDateRange();

	return (
		<div className="EVF-Free-Analytics">
			<div className="EVF-Free-Analytics__Filters">
				<div>
					<button
						className="EVF-Free-Analytics__FilterTrigger"
						disabled
						tabIndex={-1}
					>
						{__('All Forms', 'everest-forms')}
						<ChevronDownIcon />
					</button>
				</div>
				<div>
					<button
						className="EVF-Free-Analytics__FilterTrigger"
						disabled
						tabIndex={-1}
					>
						<span>{dateRange}</span>
						<ChevronDownIcon />
					</button>
				</div>
				<div>
					<button
						className="EVF-Free-Analytics__FilterTrigger"
						disabled
						tabIndex={-1}
					>
						{__('Day', 'everest-forms')}
						<ChevronDownIcon />
					</button>
				</div>
			</div>

			<div className="EVF-Free-Analytics__Metrics">
				{METRIC_BOXES.map((metric) => (
					<div key={metric.label} className="EVF-Free-Analytics__Metric">
						<div className="EVF-Free-Analytics__MetricHeader">
							<button
								className="EVF-Free-Analytics__MetricLabel"
								disabled
								tabIndex={-1}
							>
								<span>{metric.label}</span>
							</button>
						</div>
						<div className="EVF-Free-Analytics__MetricValue">
							0<span className="EVF-Free-Analytics__MetricDelta">▲ 0%</span>
						</div>
						<div className="EVF-Free-Analytics__MetricComparison">
							{__('vs. 0 last period', 'everest-forms')}
						</div>
					</div>
				))}
			</div>

			<div className="EVF-Free-Analytics__Preview">
				<img
					className="EVF-Free-Analytics__PreviewImage"
					src={analyticsPreview}
					alt=""
				/>
				<div className="EVF-Free-Analytics__Overlay">
					<h3 className="EVF-Free-Analytics__OverlayTitle">
						{__('Unlock Advanced Analytics', 'everest-forms')}
					</h3>
					<p className="EVF-Free-Analytics__OverlayText">
						{__(
							'Get powerful analytics with submission tracking, form insights, conversion analysis, and advanced visualizations.',
							'everest-forms',
						)}
					</p>
					<a href={upgradeURL} className="EVF-Free-Analytics__UpgradeBtn" >
						<CrownIcon />
						{__('Upgrade to Pro', 'everest-forms')}
					</a>
				</div>
			</div>
		</div>
	);
};

/**
 * Analytics Screen Component
 *
 * Uses a WordPress filter so the Pro plugin can inject its own analytics UI.
 * Falls back to FreeAnalyticsContent for free users.
 */
const Analytics = () => {
	const [isReady, setIsReady] = useState(false);

	useEffect(() => {
		const timer = setTimeout(() => {
			setIsReady(true);
		}, 0);
		return () => clearTimeout(timer);
	}, []);

	const AnalyticsContent = applyFilters(
		'everest-forms-analytics',
		FreeAnalyticsContent,
	);

	if (!isReady) {
		return null;
	}

	return <AnalyticsContent />;
};

export default Analytics;
