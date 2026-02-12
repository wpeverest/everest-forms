/**
 *  External Dependencies
 */
import { Box, Spinner, useToast } from '@chakra-ui/react';
import { useQuery } from '@tanstack/react-query';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';
import { useEffect } from 'react';
import { Route, Routes, useLocation } from 'react-router-dom';

/**
 *  Internal Dependencies
 */
import { Header } from '../components';
import {
	FreeVsPro,
	Help,
	Modules,
	Products,
	Settings,

	SiteAssistant,
} from '../screens';
import SiteAssistantSkeleton from '../skeleton/SiteAssistantSkeleton';

const Router = () => {
	const toast = useToast();
	const location = useLocation();

	/* global _EVF_DASHBOARD_ */
	const {
		isPro,
		settingsURL,
		evfRestApiNonce,
		restURL,
		allStepsCompleted,
		adminURL,
		showAnalyticsTab,
	} =
		typeof _EVF_DASHBOARD_ !== 'undefined'
			? _EVF_DASHBOARD_
			: {
					isPro: false,
					settingsURL: '',
					evfRestApiNonce: '',
					restURL: '',
					adminURL: '',
					showAnalyticsTab: false,
				};

	const siteAssistantQuery = useQuery({
		queryKey: ['siteAssistant'],
		queryFn: async () => {
			const response = await apiFetch({
				path: `${restURL}everest-forms/v1/site-assistant`,
				method: 'GET',
				headers: {
					'X-WP-Nonce': evfRestApiNonce,
				},
			});
			return response;
		},
		cacheTime: Infinity,
		staleTime: Infinity,
		retry: 1,
		onError: (error) => {
			console.error('Error fetching site assistant data:', error);
			toast({
				title: __('Error', 'everest-forms'),
				description: __('Failed to load setup status.', 'everest-forms'),
				status: 'error',
				duration: 3000,
				isClosable: true,
			});
		},
	});

	const isAllStepsCompleted = siteAssistantQuery?.isLoading
		? Boolean(allStepsCompleted === '1')
		: siteAssistantQuery?.data?.data?.all_steps_completed;

	const RedirectToPhpPage = ({ page }) => {
		useEffect(() => {
			let cleanURL = adminURL;
			if (cleanURL.endsWith('/')) {
				cleanURL = cleanURL.slice(0, -1);
			}
			if (cleanURL.endsWith('/admin.php')) {
				cleanURL = cleanURL.slice(0, -10);
			}

			window.location.href = `${cleanURL}/admin.php?page=${page}`;
		}, [page]);

		return null;
	};

	const DefaultRedirect = () => {
		// Show Site Assistant skeleton while fetching data
		if (siteAssistantQuery.isLoading) {
			return <SiteAssistantSkeleton />;
		}

		if (isAllStepsCompleted) {
			if (isPro && showAnalyticsTab) {
				return <RedirectToPhpPage page="everest-forms-analytics" />;
			} else {
				return <RedirectToPhpPage page="evf-entries" />;
			}
		} else {
			return <SiteAssistant siteAssistantQuery={siteAssistantQuery} />;
		}
	};

	// Determine if we should show the loader (only when redirecting from root path)
	const isOnRootPath = location.pathname === '/' || location.pathname === '';
	const showLoader =
		isOnRootPath &&
		((siteAssistantQuery.isLoading && Boolean(allStepsCompleted === '1')) ||
			(!siteAssistantQuery.isLoading && isAllStepsCompleted));

	// Show header on all pages except when redirecting from root
	const shouldShowHeader = !(isOnRootPath && isAllStepsCompleted);

	// If showing loader, show spinner and hidden routes for redirect
	if (showLoader) {
		return (
			<>
				<Box
					display="flex"
					justifyContent="center"
					alignItems="center"
					minHeight="calc(100vh - 32px)"
					bg="white"
				>
					<Spinner
						thickness="4px"
						speed="0.65s"
						emptyColor="gray.200"
						color="primary.500"
						size="xl"
					/>
				</Box>
				{/* Hidden routes to execute redirect */}
				<Box display="none">
					<Routes>
						<Route path="/" element={<DefaultRedirect />} />
						<Route path="*" element={<DefaultRedirect />} />
					</Routes>
				</Box>
			</>
		);
	}

	// Normal rendering when not redirecting
	return (
		<>
			{shouldShowHeader && <Header hideSiteAssistant={isAllStepsCompleted} />}
			<Routes>
				<Route path="/" element={<DefaultRedirect />} />
				<Route path="/settings" element={<Settings to={settingsURL} />} />
				<Route path="/features" element={<Modules />} />
				{!isPro && <Route path="/free-vs-pro" element={<FreeVsPro />} />}
				<Route path="/help" element={<Help />} />
				<Route path="/products" element={<Products />} />
				<Route path="*" element={<DefaultRedirect />} />
			</Routes>
		</>
	);
};

export default Router;
