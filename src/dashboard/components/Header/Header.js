/**
 *  External Dependencies
 */
import {
	Box,
	Button,
	Center,
	Container,
	Divider,
	Drawer,
	DrawerBody,
	DrawerCloseButton,
	DrawerContent,
	DrawerHeader,
	DrawerOverlay,
	Image,
	Link,
	Stack,
	Tag,
	Tooltip,
	useDisclosure,
} from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import { useEffect, useMemo, useRef } from 'react';
import { NavLink, useLocation } from 'react-router-dom';

/**
 *  Internal Dependencies
 */
import ROUTES, {
	convertRoute,
	isExternalRoute,
	isRouteActive,
} from '../../Constants';
import announcement from '../../images/announcement.gif';
import Changelog from '../Changelog/Changelog';
import { EVF, ExternalLink } from '../Icon/Icon';
import IntersectObserver from '../IntersectionObserver/IntersectionObserver';

const Header = ({ hideSiteAssistant = false }) => {
	const { isOpen, onOpen, onClose } = useDisclosure();
	const ref = useRef();
	const location = useLocation();

	/* global _EVF_DASHBOARD_ */
	const { version, isPro, upgradeURL, pageType, adminURL } =
		typeof _EVF_DASHBOARD_ !== 'undefined' && _EVF_DASHBOARD_;

	const isSettingsPage = pageType === 'settings';
	const isEntriesPage = pageType === 'entries';
	const isAnalyticsPage = pageType === 'analytics';
	const isNonDashboardPage = isSettingsPage || isEntriesPage || isAnalyticsPage;

	useEffect(() => {
		if (isOpen) {
			document.body.classList.add('ur-modal-open');
		} else {
			document.body.classList.remove('ur-modal-open');
		}
		return () => {
			document.body.classList.remove('ur-modal-open');
		};
	}, [isOpen]);

	const { leftRoutes, rightRoutes } = useMemo(() => {
		const allRoutes = hideSiteAssistant
			? ROUTES.filter((route) => route.route !== '/')
			: ROUTES;

		const rightRoutePaths = ['/help', 'https://everestforms.net/free-vs-pro/'];

		return {
			leftRoutes: allRoutes.filter(
				(route) => !rightRoutePaths.includes(route.route),
			),
			rightRoutes: allRoutes
				.filter((route) => rightRoutePaths.includes(route.route))
				.sort(
					(a, b) =>
						rightRoutePaths.indexOf(a.route) - rightRoutePaths.indexOf(b.route),
				),
		};
	}, [hideSiteAssistant]);

	const renderNavLink = (route, label, external, showExternalIcon = false) => {
		const convertedRoute = convertRoute(route, isNonDashboardPage, adminURL);
		const isExternal = external || isExternalRoute(convertedRoute);
		const isActive = isRouteActive(
			route,
			location.pathname,
			isSettingsPage,
			pageType,
		);
		const shouldUseExternalLink = isNonDashboardPage || isExternal;
		const shouldShowIcon = showExternalIcon;

		return shouldUseExternalLink ? (
			<Link
				data-target={route}
				key={route}
				href={convertedRoute}
				isExternal={route === 'https://everestforms.net/free-vs-pro/'}
				fontSize="15px"
				fontWeight="semibold"
				lineHeight="150%"
				color={isActive ? 'primary.500' : '#383838'}
				borderBottom={isActive ? '3px solid' : 'none'}
				borderColor={isActive ? 'primary.500' : 'transparent'}
				marginBottom={isActive ? '-2px' : '0'}
				_hover={{
					color: 'primary.500',
				}}
				_focus={{
					boxShadow: 'none',
				}}
				display="inline-flex"
				alignItems="center"
				gap="1"
				px="2"
				h="full"
			>
				{label}
				{shouldShowIcon && (
					<ExternalLink w="16px" h="16px" fill="currentColor" />
				)}
			</Link>
		) : (
			<Link
				data-target={route}
				key={route}
				as={NavLink}
				to={route}
				fontSize="15px"
				fontWeight="semibold"
				lineHeight="150%"
				color="#383838"
				_hover={{
					color: 'primary.500',
				}}
				_focus={{
					boxShadow: 'none',
				}}
				_activeLink={{
					color: 'primary.500',
					borderBottom: '3px solid',
					borderColor: 'primary.500',
					marginBottom: '-2px',
				}}
				display="inline-flex"
				alignItems="center"
				gap="1"
				px="2"
				h="full"
			>
				{label}
				{shouldShowIcon && (
					<ExternalLink w="16px" h="16px" fill="currentColor" />
				)}
			</Link>
		);
	};

	return (
		<>
			<Box
				bg={'white'}
				borderBottom="1px solid #E9E9E9"
				width="100%"
				position={'relative'}
				zIndex="10"
			>
				<Container maxW="full">
					<Stack direction="row" minH="70px" justify="space-between">
						{/* Left Side - Logo and Main Navigation */}
						<Stack direction="row" align="center" gap="7">
							<Link
								as={isNonDashboardPage ? 'a' : NavLink}
								to={isNonDashboardPage ? undefined : '/'}
								href={
									isNonDashboardPage
										? `${adminURL}/admin.php?page=evf-dashboard`
										: undefined
								}
							>
								<EVF h="10" w="10" />
							</Link>

							<IntersectObserver routes={leftRoutes}>
								{leftRoutes.map(({ route, label, external }) =>
									renderNavLink(route, label, external),
								)}
							</IntersectObserver>
						</Stack>

						<Stack direction="row" align="center" spacing="12px">
							<Stack direction="row" align="center" gap="1">
								{rightRoutes.map(({ route, label, external }) =>
									renderNavLink(
										route,
										label,
										external,
										route === 'https://everestforms.net/free-vs-pro/',
									),
								)}
							</Stack>

							{rightRoutes.length > 0 && (
								<>
									<Center height="18px">
										<Divider orientation="vertical" />
									</Center>
								</>
							)}
							{!isPro && (
								<>
									<Link
										color="orange"
										fontSize="15px"
										height="18px"
										href={
											upgradeURL +
											'utm_medium=evf-dashboard&utm_source=evf-free&utm_campaign=header-upgrade-btn&utm_content=Upgrade%20to%20Pro'
										}
										isExternal
										display="inline-flex"
										alignItems="center"
										gap="1"
									>
										{__('Upgrade To Pro', 'everest-forms')}
										<ExternalLink w="16px" h="16px" fill="currentColor" />
									</Link>
								</>
							)}

							<Tooltip
								label={sprintf(
									__(
										'You are currently using Everest Forms %s',
										'everest-forms',
									),
									(isPro && 'Pro ') + 'v' + version,
								)}
							>
								<Tag
									display={'inline-flex !important'}
									variant="outline"
									colorScheme="primary"
									borderRadius="xl"
									bgColor="#F8FAFF"
									fontSize="xs"
								>
									{'v' + version}
								</Tag>
							</Tooltip>

							<Button
								onClick={onOpen}
								variant="unstyled"
								borderRadius="full"
								border="2px"
								borderColor="gray.200"
								w="40px"
								h="40px"
								position="relative"
							>
								<Tooltip label={__('Latest Updates', 'everest-forms')}>
									<Image
										src={announcement}
										alt="announcement"
										h="35px"
										w="35px"
										position="absolute"
										top="50%"
										left="50%"
										transform="translate(-40%, -50%)"
									/>
								</Tooltip>
							</Button>
						</Stack>
					</Stack>
				</Container>
			</Box>
			<Drawer
				isOpen={isOpen}
				placement="right"
				onClose={onClose}
				finalFocusRef={ref}
				size="md"
			>
				<DrawerOverlay
					bgColor="rgb(0,0,0,0.05)"
					sx={{ backdropFilter: 'blur(1px)' }}
				/>
				<DrawerContent
					className="everest-forms-announcement"
					top="var(--wp-admin--admin-bar--height, 0) !important"
				>
					<DrawerCloseButton />
					<DrawerHeader>{__('Latest Updates', 'everest-forms')}</DrawerHeader>
					<DrawerBody>
						<Changelog />
					</DrawerBody>
				</DrawerContent>
			</Drawer>
		</>
	);
};

export default Header;
