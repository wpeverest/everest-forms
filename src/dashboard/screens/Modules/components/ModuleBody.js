/**
 *  External Dependencies
 */
import React, { useState, useEffect, useContext } from "react";
import {
	Tabs,
	Container,
	Modal,
	ModalOverlay,
	ModalContent,
	ModalCloseButton,
	ModalFooter,
	Button,
	Text,
	SimpleGrid,
	Input,
	Link,
	VStack,
	useToast,
} from "@chakra-ui/react";
import { sprintf, __ } from "@wordpress/i18n";

/**
 *  Internal Dependencies
 */
import { isArray, isEmpty } from "./../../../utils/utils";
import { actionTypes } from "./../../../reducers/DashboardReducer";
import DashboardContext from "./../../../context/DashboardContext";
import { Lock } from "./../../../components/Icon/Icon";
import ModuleItem from "./ModuleItem";
import AddonsSkeleton from "./../../../skeleton/AddonsSkeleton/AddonsSkeleton";
import { activateLicense } from "./modules-api";

const ModuleBody = ({
	isPerformingBulkAction,
	filteredAddons,
	setSelectedModuleData,
	selectedModuleData,
}) => {
	/* global _EVF_DASHBOARD_ */
	const { upgradeURL, licenseActivationURL, licensePlan, isPro } =
		typeof _EVF_DASHBOARD_ !== "undefined" && _EVF_DASHBOARD_;
	const [{ upgradeModal }, dispatch] = useContext(DashboardContext);
	const [upgradeContent, setUpgradeContent] = useState({
		title: "",
		body: "",
		buttonText: __("Upgrade to Pro", "everest-forms"),
		upgradeURL:
			upgradeURL +
			"&utm_source=dashboard-addons&utm_medium=upgrade-popup",
		licenseActivationPlaceholder: __("License key","everest-forms"),
	});

	const toast = useToast();

	const [licenseActivationKey, setLicenseKey] = useState('');
	const [licenseActivationValidationMessage, setLicenseValidationMessage] = useState('');
	const [reloadPage, setReloadPage] = useState(false);

	const handleActivationKeyChange = (event) => {
		setLicenseKey(event.target.value);
	};

	const [licenseValidationStatus, setLicenseValidationStatus] = useState('');

	const [isLicenseActivation, setLicenseActivation] = useState(false);

	const handleCheckedChange = (slug, checked, name, type) => {
		var selectedModules = { ...selectedModuleData };

		if (checked) {
			selectedModules[slug] = {
				slug: slug + "/" + slug + ".php",
				name,
				type,
			};
		} else {
			if (selectedModules.hasOwnProperty(slug)) {
				delete selectedModules[slug];
			}
		}
		setSelectedModuleData(selectedModules);
	};

	useEffect(() => {
		const upgradeContentRef = { ...upgradeContent };

		if (upgradeModal.enable) {
			if (!isPro) {
				upgradeContentRef.title = __(
					"Everest Froms Pro Required",
					"everest-forms"
				);
				upgradeContentRef.body = sprintf(
					__(
						"%s requires Everest Froms Pro to be activated. Please upgrade to a premium plan and unlock this %s.",
						"everest-forms"
					),
					upgradeModal.moduleName,
					upgradeModal.moduleType
				);
			} else {
				if ( !licensePlan) {
					upgradeContentRef.title = __(
						"License Activation Required",
						"everest-forms"
					);
					upgradeContentRef.body = sprintf(
						__(
							"Please activate license of Everest Froms Pro plugin in order to use %s",
							"everest-forms"
						),
						upgradeModal.moduleName
					);
					upgradeContentRef.buttonText = sprintf(
						__("Activate License", "everest-forms"),
						upgradeModal.moduleName
					);
					upgradeContentRef.licenseActivationPlaceholder = sprintf(
						__("Enter your license key", "everest-forms"),
						upgradeModal.moduleName
					);
					upgradeContentRef.upgradeURL = licenseActivationURL;
				}

			}
			setUpgradeContent(upgradeContentRef);
		}
	}, [upgradeModal]);

	useEffect(() => {
		if(reloadPage){
			window.location.reload();
			setReloadPage(false);
		}
	},[reloadPage]);

	const updateUpgradeModal = () => {
		const upgradeModalRef = { ...upgradeModal };
		upgradeModalRef.enable = false;
		dispatch({
			type: actionTypes.GET_UPGRADE_MODAL,
			upgradeModal: upgradeModalRef,
		});
	};

	const licenseActivation = () => {
		if( '' === licenseActivationKey ){
			setLicenseValidationMessage(sprintf(__('Please enter your plugin activation license key','everest-forms')));
			setLicenseValidationStatus(true);
		} else if( licenseActivationKey.length < 32 ){
			setLicenseValidationMessage(sprintf(__('Please enter the valid license key','everest-forms')));
			setLicenseValidationStatus(true);
		} else {
			setLicenseValidationMessage('');
			setLicenseValidationStatus(false);
			setLicenseActivation(true);

			activateLicense(licenseActivationKey)
			.then((data) => {
				setLicenseActivation(true);
				if (data.code === 200) {
					toast({
						title: data.message,
						status: "success",
						duration: 3000,
					});
					setLicenseActivation(false);
					setReloadPage(true);
				} else if( data.code === 400 ) {
					toast({
						title: data.message,
						status: "error",
						duration: 3000,
					});
				}
			}).catch((e) => {
					toast({
						title: e.message,
						status: "error",
						duration: 3000,
					});
			}).finally(() => {
				setLicenseActivation(false);
			});
		}
	};

	return (
		<>
			<Tabs>
				{upgradeModal.enable && (
					<Modal
						isOpen={true}
						onClose={updateUpgradeModal}
						size="lg"
						isCentered
					>
						<ModalOverlay />
						<ModalContent
							alignItems={"center"}
							p="50px 11px 55px 11px"
						>
							<Lock h={"131px"} w={"150px"} />
							<Text
								fontSize="24px"
								lineHeight="44px"
								fontWeight="600"
							>
								{upgradeContent.title}
							</Text>
							<ModalCloseButton boxShadow="none !important" />
							<Text
								fontSize="16px"
								lineHeight="26px"
								fontWeight="400"
								padding="10px 50px"
							>
								{upgradeContent.body}
							</Text>
							<ModalFooter paddingBottom="0px" w="400px">
							<VStack
								width="100%"
							>
							{isPro && (
								<Input
									placeholder={upgradeContent.licenseActivationPlaceholder}
									onChange={handleActivationKeyChange}
								/>
							)}
								<Button
									colorScheme="primary"
									color="white !important"
									textDecor="none !important"
									onClick={licenseActivation}
									w="100%"
									as={!isPro ? Link : ''}
									href={!isPro ? upgradeContent.upgradeURL : ''}
									isExternal
									isLoading = {isLicenseActivation}
								>
									{upgradeContent.buttonText}
								</Button>
								{isPro && licenseActivationValidationMessage && (
									<Text fontSize='md' color='red'>{licenseActivationValidationMessage}</Text>
								)}
								</VStack>
							</ModalFooter>
						</ModalContent>
					</Modal>
				)}
				<Container maxW="container.xl">
					{isEmpty(filteredAddons) ? (
						<AddonsSkeleton />
					) : (
						<SimpleGrid columns={3} spacing="5">
							{isArray(filteredAddons) &&
								filteredAddons?.map((data) => (
									<ModuleItem
										key={data.slug}
										data={data}
										isChecked={selectedModuleData.hasOwnProperty(
											data.slug
										)}
										onCheckedChange={(slug, checked) => {
											handleCheckedChange(
												slug,
												checked,
												data.name,
												data.type
											);
										}}
										isPerformingBulkAction={
											isPerformingBulkAction
										}
										selectedModuleData={selectedModuleData}
									/>
								))}
						</SimpleGrid>
					)}
				</Container>
			</Tabs>
		</>
	);
};

export default ModuleBody;
