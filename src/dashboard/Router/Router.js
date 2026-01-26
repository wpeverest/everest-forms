/**
 *  External Dependencies
 */
import { useToast } from '@chakra-ui/react';
import { useQuery } from '@tanstack/react-query';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';
import { useEffect } from 'react';
import { Route, Routes } from 'react-router-dom';

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

const Router = () => {
	const toast = useToast();

	/* global _EVF_DASHBOARD_ */
	const {
		isPro,
		settingsURL,
		evfRestApiNonce,
		restURL,
		allStepsCompleted,
		adminURL,
	} =
		typeof _EVF_DASHBOARD_ !== 'undefined'
			? _EVF_DASHBOARD_
			: {
					isPro: false,
					settingsURL: '',
					evfRestApiNonce: '',
					restURL: '',
					adminURL: '',
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
		if (isAllStepsCompleted) {
			if (isPro) {
				return <RedirectToPhpPage page="everest-forms-analytics" />;
			} else {
				return <RedirectToPhpPage page="evf-entries" />;
			}
		} else {
			return <SiteAssistant siteAssistantQuery={siteAssistantQuery} />;
		}
	};

	return (
		<>
			<Header hideSiteAssistant={isAllStepsCompleted} />
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
