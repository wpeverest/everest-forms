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
	Link,
	SimpleGrid,
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
	});
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
					upgradeContentRef.buttonText = upgradeContentRef.buttonText =
						sprintf(
							__("Activate License", "everest-forms"),
							upgradeModal.moduleName
						);
					upgradeContentRef.upgradeURL = licenseActivationURL;
				}

			}
			setUpgradeContent(upgradeContentRef);
		}
	}, [upgradeModal]);

	const updateUpgradeModal = () => {
		const upgradeModalRef = { ...upgradeModal };
		upgradeModalRef.enable = false;
		dispatch({
			type: actionTypes.GET_UPGRADE_MODAL,
			upgradeModal: upgradeModalRef,
		});
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
								<Button
									as={Link}
									colorScheme="primary"
									href={upgradeContent.upgradeURL}
									color="white !important"
									textDecor="none !important"
									isExternal
									onClick={updateUpgradeModal}
									w="100%"
								>
									{upgradeContent.buttonText}
								</Button>
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
