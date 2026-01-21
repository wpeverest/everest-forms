/**
 * External Dependencies
 */
import { Box, Heading, Text } from '@chakra-ui/react';
import { applyFilters } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';
import { useEffect, useState } from 'react';

/**
 * Default Analytics Content for Free Users
 */
const FreeAnalyticsContent = () => {
	return (
		<Box p={6}>
			<Heading size="lg" mb={2}>
				{__('Analytics', 'everest-forms')}
			</Heading>
			<Text color="gray.500">
				{__('Free version analytics content', 'everest-forms')}
			</Text>
		</Box>
	);
};

/**
 * Analytics Screen Component
 *
 * @since x.x.x
 *
 * This function uses WordPress hooks to allow pro plugin to inject analytics.
 * Re-checks filter after mount to handle timing issues.
 */
const Analytics = () => {
	const [, forceUpdate] = useState(0);

	// Re-render once after mount to pick up any late-registered filters
	useEffect(() => {
		const timer = setTimeout(() => {
			forceUpdate((n) => n + 1);
		}, 0);
		return () => clearTimeout(timer);
	}, []);

	const AnalyticsContent = applyFilters(
		'everest-forms-analytics',
		FreeAnalyticsContent,
	);

	return <AnalyticsContent />;
};

export default Analytics;
