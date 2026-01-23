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
import { EVF } from '../Icon/Icon';
import IntersectObserver from '../IntersectionObserver/IntersectionObserver';

const Header = ({ hideSiteAssistant = false }) => {
	const { isOpen, onOpen, onClose } = useDisclosure();
	const ref = useRef();
	const location = useLocation();

	/* global _EVF_DASHBOARD_ */
	const { version, isPro, upgradeURL, pageType, adminURL } =
		typeof _EVF_DASHBOARD_ !== 'undefined' && _EVF_DASHBOARD_;

	const isSettingsPage = pageType === 'settings';

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

	const filteredRoutes = useMemo(() => {
		if (hideSiteAssistant) {
			return ROUTES.filter((route) => route.route !== '/');
		}
		return ROUTES;
	}, [hideSiteAssistant]);

	return (
		<>
			<Box
				top="var(--wp-admin--admin-bar--height, 0)"
				bg={'white'}
				borderBottom="1px solid #E9E9E9"
				width="100%"
				position={isSettingsPage ? 'relative' : 'sticky'}
				zIndex="10"
			>
				<Container maxW="full">
					<Stack direction="row" minH="70px" justify="space-between" px="6">
						<Stack direction="row" align="center" gap="7">
							<Link
								as={isSettingsPage ? 'a' : NavLink}
								to={isSettingsPage ? undefined : '/'}
								href={
									isSettingsPage
										? `${adminURL}admin.php?page=evf-dashboard`
										: undefined
								}
							>
								<EVF h="10" w="10" />
							</Link>

							<IntersectObserver routes={filteredRoutes}>
								{filteredRoutes.map(({ route, label, external }) => {
									const convertedRoute = convertRoute(
										route,
										isSettingsPage,
										adminURL,
									);
									const isExternal =
										external || isExternalRoute(convertedRoute);
									const isActive = isRouteActive(
										route,
										location.pathname,
										isSettingsPage,
									);

									const shouldUseExternalLink = isSettingsPage || isExternal;

									return shouldUseExternalLink ? (
										<Link
											data-target={route}
											key={route}
											href={convertedRoute}
											fontSize="sm"
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
											px="2"
											h="full"
										>
											{label}
										</Link>
									) : (
										<Link
											data-target={route}
											key={route}
											as={NavLink}
											to={route}
											fontSize="sm"
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
											px="2"
											h="full"
										>
											{label}
										</Link>
									);
								})}
							</IntersectObserver>
						</Stack>
						<Stack direction="row" align="center" spacing="12px">
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
									variant="outline"
									colorScheme="primary"
									borderRadius="xl"
									bgColor="#F8FAFF"
									fontSize="xs"
								>
									{'v' + version}
								</Tag>
							</Tooltip>
							<Center height="18px">
								<Divider orientation="vertical" />
							</Center>
							{!isPro && (
								<Link
									color="#2563EB"
									fontSize="12px"
									height="18px"
									w="85px"
									href={
										upgradeURL +
										'utm_medium=evf-dashboard&utm_source=evf-free&utm_campaign=header-upgrade-btn&utm_content=Upgrade%20to%20Pro'
									}
									isExternal
								>
									{__('Upgrade To Pro', 'everest-forms')}
								</Link>
							)}
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
