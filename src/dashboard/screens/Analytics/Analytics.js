/**
 * External Dependencies
 */
import { Box, Button, Text } from '@chakra-ui/react';
import { applyFilters } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';
import { useEffect, useState } from 'react';

import analyticsPreview from '../../images/analytics-preview.png';

const ChevronDownIcon = () => (
	<svg
		xmlns="http://www.w3.org/2000/svg"
		width="20"
		height="20"
		viewBox="0 0 24 24"
		fill="none"
		stroke="#6b7280"
		strokeWidth="2"
		strokeLinecap="round"
		strokeLinejoin="round"
		style={{ flexShrink: 0, pointerEvents: 'none' }}
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

const TRIGGER_STYLE = {
	display: 'inline-flex',
	alignItems: 'center',
	gap: '8px',
	padding: '10px 14px',
	minHeight: '42px',
	fontSize: '14px',
	fontWeight: 500,
	lineHeight: '1.2rem',
	border: 0,
	boxShadow: 'none',
	backgroundColor: 'transparent',
	color: '#111827',
	borderRadius: '4px',
	cursor: 'default',
	userSelect: 'none',
	whiteSpace: 'nowrap',
	outline: 'none',
};

const METRIC_BOXES = [
	{ label: __('Total Submissions', 'everest-forms') },
	{ label: __('Complete Submissions', 'everest-forms') },
	{ label: __('Incomplete Submissions', 'everest-forms') },
	{ label: __('Impressions', 'everest-forms') },
];

const FreeAnalyticsContent = () => {
	const upgradeURL =
		typeof _EVF_DASHBOARD_ !== 'undefined' && _EVF_DASHBOARD_.upgradeURL
			? `${_EVF_DASHBOARD_.upgradeURL}utm_medium=evf-dashboard&utm_source=evf-free&utm_campaign=analytics-upgrade-btn&utm_content=Upgrade+to+Pro`
			: 'https://everestforms.net/pricing/?utm_medium=evf-dashboard&utm_source=evf-free&utm_campaign=analytics-upgrade-btn&utm_content=Upgrade+to+Pro';

	const dateRange = getStaticDateRange();

	return (
		<div
			style={{
				backgroundColor: '#fff',
				fontFamily: 'inherit',
				padding: '24px 32px 16px',
			}}
		>
			<div
				style={{
					display: 'flex',
					alignItems: 'center',
					gap: '16px',
					flexWrap: 'wrap',
				}}
			>
				<div style={{ marginLeft: '-12px' }}>
					<button style={TRIGGER_STYLE} disabled tabIndex={-1}>
						{__('All Forms', 'everest-forms')}
						<ChevronDownIcon />
					</button>
				</div>

				<div>
					<button style={TRIGGER_STYLE} disabled tabIndex={-1}>
						<span>{dateRange}</span>
						<ChevronDownIcon />
					</button>
				</div>

				<div>
					<button style={TRIGGER_STYLE} disabled tabIndex={-1}>
						{__('Day', 'everest-forms')}
						<ChevronDownIcon />
					</button>
				</div>
			</div>

			{/* ── Metric boxes ── */}
			<div
				style={{
					display: 'flex',
					flexWrap: 'wrap',
					width: '100%',
					borderTop: '1px solid #e5e7eb',
					marginTop: '16px',
				}}
			>
				{METRIC_BOXES.map((metric, index) => (
					<div
						key={metric.label}
						style={{
							flex: '1 1 0',
							minWidth: 0,
							padding: index !== 0 ? '16px 24px' : '16px 24px 16px 0px',
							borderBottom: '1px solid #e5e7eb',
							borderRight:
								index < METRIC_BOXES.length - 1 ? '1px solid #e5e7eb' : 'none',
						}}
					>
						<div
							style={{
								display: 'flex',
								alignItems: 'center',
								marginBottom: '8px',
							}}
						>
							<div style={{ marginLeft: '-12px' }}>
								<button style={TRIGGER_STYLE} disabled tabIndex={-1}>
									<span
										style={{
											fontSize: '14px',
											fontWeight: 500,
											color: '#111827',
										}}
									>
										{metric.label}
									</span>
								</button>
							</div>
						</div>

						{/* Value */}
						<div
							style={{
								fontSize: '24px',
								fontWeight: 600,
								color: '#111827',
								lineHeight: 1.5,
							}}
						>
							0
							<span
								style={{
									display: 'inline-flex',
									alignItems: 'center',
									gap: '2px',
									fontWeight: 500,
									fontSize: '14px',
									marginLeft: '8px',
									color: '#16a34a',
								}}
							>
								▲ 0%
							</span>
						</div>

						<div
							style={{
								fontSize: '14px',
								fontWeight: 400,
								color: '#6b7280',
								lineHeight: 1.5,
								marginTop: '4px',
							}}
						>
							{__('vs. 0 last period', 'everest-forms')}
						</div>
					</div>
				))}
			</div>

			<Box
				style={{ position: 'relative', overflow: 'hidden', marginTop: '20px' }}
			>
				<Box
					as="img"
					src={analyticsPreview}
					alt=""
					style={{
						display: 'block',
						width: '100%',
						filter: 'blur(2px)',
						transform: 'scale(1.02)',
						transformOrigin: 'top left',
						userSelect: 'none',
						pointerEvents: 'none',
					}}
				/>

				<Box
					style={{
						position: 'absolute',
						top: '40%',
						left: '50%',
						transform: 'translate(-50%, -50%)',
						backgroundColor: '#ffffff',
						borderRadius: '16px',
						boxShadow: '0 8px 48px rgba(0,0,0,0.18)',
						padding: '56px 48px',
						textAlign: 'center',
						maxWidth: '520px',
						width: '90%',
						zIndex: 10,
					}}
				>
					<Text
						style={{
							fontSize: '26px',
							fontWeight: '700',
							color: '#111827',
							marginBottom: '14px',
							lineHeight: 1.3,
						}}
					>
						{__('Unlock Advanced Analytics', 'everest-forms')}
					</Text>
					<Text
						style={{
							fontSize: '15px',
							color: '#6b7280',
							lineHeight: 1.7,
							marginBottom: '32px',
						}}
					>
						{__(
							'Get powerful analytics with submission tracking, form insights, conversion analysis, and advanced visualizations.',
							'everest-forms',
						)}
					</Text>
					<Button
						as="a"
						href={upgradeURL}
						colorScheme="primary"
						_hover={{ textDecoration: 'none', opacity: 0.88, color: 'white' }}
						_active={{ opacity: 0.8 }}
					>
						{__('Upgrade to Pro', 'everest-forms')}
					</Button>
				</Box>
			</Box>
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
