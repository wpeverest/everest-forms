/**
 *  External Dependencies
 */
import React, { useEffect } from 'react'
import { Route, Routes, useNavigate, Navigate } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import apiFetch from '@wordpress/api-fetch'
import { Flex, Spinner, useToast } from '@chakra-ui/react'
import { __ } from '@wordpress/i18n'

/**
 *  Internal Dependencies
 */
import {
	SiteAssistant,
	Help,
	Products,
	Settings,
	Modules,
	FreeVsPro
} from '../screens'
import { Header } from "../components";

const Router= () => {
	const toast = useToast()

	/* global _EVF_DASHBOARD_ */
	const { isPro, settingsURL, evfRestApiNonce, restURL ,allStepsCompleted} =
		typeof _EVF_DASHBOARD_ !== 'undefined'
			? _EVF_DASHBOARD_
			: { isPro: false, settingsURL: '', evfRestApiNonce: '', restURL: '' }

	const siteAssistantQuery = useQuery({
		queryKey: ['siteAssistant'],
		queryFn: async () => {
			const response = await apiFetch({
				path: `${restURL}everest-forms/v1/site-assistant`,
				method: 'GET',
				headers: {
					'X-WP-Nonce': evfRestApiNonce,
				},
			})
			return response
		},
		cacheTime:Infinity,
		staleTime:Infinity,
		retry: 1,
		onError: (error) => {
			console.error('Error fetching site assistant data:', error)
			toast({
				title: __('Error', 'everest-forms'),
				description: __('Failed to load setup status.', 'everest-forms'),
				status: 'error',
				duration: 3000,
				isClosable: true,
			})
		},
	})

	const isAllStepsCompleted = siteAssistantQuery?.isLoading ? Boolean(allStepsCompleted === "1") : siteAssistantQuery?.data?.data?.all_steps_completed

	return (
		<>
		<Header hideSiteAssistant ={isAllStepsCompleted} />
		<Routes>
			<Route
				path='/'
				element={
					isAllStepsCompleted ? (
						<Navigate to='/features' replace />
					) : (
						<SiteAssistant siteAssistantQuery={siteAssistantQuery} />
					)
				}
			/>
			<Route path='/settings' element={<Settings to={settingsURL} />} />
			<Route path='/features' element={<Modules />} />
			{!isPro && <Route path='/free-vs-pro' element={<FreeVsPro />} />}
			<Route path='/help' element={<Help />} />
			<Route
				path='*'
				element={
					isAllStepsCompleted ? (
						<Navigate to='/features' replace />
					) : (
						<SiteAssistant siteAssistantQuery={siteAssistantQuery} />
					)
				}
				/>
			<Route path='/products' element={<Products />} />
		</Routes>
				</>
	)
}

export default Router
