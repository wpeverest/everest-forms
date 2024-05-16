/**
 *  External Dependencies
 */
import React, { useState, useEffect } from "react";
import {
	TableContainer,
	Table,
	Thead,
	Tbody,
	Th,
	Td,
	Tr,
	Image,
	Stack,
	Box,
	Text,
	Button,
	Link,
} from "@chakra-ui/react";
import { __ } from "@wordpress/i18n";

/**
 *  Internal Dependencies
 */
import check from "./images/check.webp";
import close from "./images/close.webp";
import { Lock } from "../../components/Icon/Icon";
import { getAllModules } from "../Modules/components/modules-api";

const FreeVsPro = () => {
	const [contentsLoaded, setContentsLoaded] = useState(false);
	/* global _EVF_DASHBOARD_ */
	const { upgradeURL } =
		typeof _EVF_DASHBOARD_ !== "undefined" && _EVF_DASHBOARD_;
	const [tableContents, setTableContents] = useState([
		{
			type: "features",
			title: __("Features", "everest-forms"),
			contents: [
				{
					title: __("Unlimited Forms", "everest-forms"),
					free: true,
					pro: true,
				},
				{
					title: __("Unlimited Form Entries", "everest-forms"),
					free: true,
					pro: true,
				},
				{
					title: __("Email Notifications ", "everest-forms"),
					free: true,
					pro: true,
				},
				{
					title: __("Google Analytics ", "everest-forms"),
					free: true,
					pro: true,
				},
				{
					title: __("Multiple File Uploads", "everest-forms"),
					free: false,
					pro: true,
				},
				{
					title: __("User Redirection  ", "everest-forms"),
					free: true,
					pro: true,
				},
				{
					title: __("Import/Export Forms ", "everest-forms"),
					free: true,
					pro: true,
				},
				{
					title: __("Export Entries in CSV ", "everest-forms"),
					free: true,
					pro: true,
				},
				{
					title: __("E-signature ", "everest-forms"),
					free: false,
					pro: true,
				},
				{
					title: __("Webhook Support  ", "everest-forms"),
					free: false,
					pro: true,
				},
				{
					title: __("Honeypot Security ", "everest-forms"),
					free: true,
					pro: true,
				},
				{
					title: __("Google reCAPTCHA ", "everest-forms"),
					free: true,
					pro: true,
				},
				{
					title: __("HCaptcha", "everest-forms"),
					free: true,
					pro: true,
				},
				{
					title: __("Whitelist/Blacklist Domains ", "everest-forms"),
					free: false,
					pro: true,
				},
				{
					title: __("Google Drive", "everest-forms"),
					free: false,
					pro: true,
				},
				{
					title: __("Dropbox", "everest-forms"),
					free: false,
					pro: true,
				},
				{
					title: __("Smart Tags Support", "everest-forms"),
					free: true,
					pro: true,
				},
				{
					title: __("GDPR Compliance", "everest-forms"),
					free: false,
					pro: true,
				},
				{
					title: __("Test Emails", "everest-forms"),
					free: true,
					pro: true,
				},
				{
					title: __("AJAX Form Submission", "everest-forms"),
					free: true,
					pro: true,
				},
				{
					title: __("Auto-populate Forms ", "everest-forms"),
					free: false,
					pro: true,
				},
			],
		},
		{
			type: "addons",
			title: __("Addons", "everest-forms"),
			contents: [
				{
					title: __("Style Customizer ", "everest-forms"),
					free: false,
					pro: true,
				},
				{
					title: __("Muilti-step Forms ", "everest-forms"),
					free: false,
					pro: true,
				},
				{
					title: __("MailChimp ", "everest-forms"),
					free: false,
					pro: true,
				},
				{
					title: __("ConvertKit ", "everest-forms"),
					free: false,
					pro: true,
				},
				{
					title: __("PDF Form Submission ", "everest-forms"),
					free: false,
					pro: true,
				},
				{
					title: __("Custom CAPTCHA", "everest-forms"),
					free: false,
					pro: true,
				},
				{
					title: __("Geolocation ", "everest-forms"),
					free: false,
					pro: true,
				},
				{
					title: __("MailerLite", "everest-forms"),
					free: false,
					pro: true,
				},
				{
					title: __("ActiveCampaign ", "everest-forms"),
					free: false,
					pro: true,
				},
				{
					title: __("Campaign Monitor", "everest-forms"),
					free: false,
					pro: true,
				},
				{
					title: __("Google Sheets ", "everest-forms"),
					free: false,
					pro: true,
				},
				{
					title: __("Surveys, Polls, and Quiz ", "everest-forms"),
					free: false,
					pro: true,
				},
				{
					title: __("User Registration ", "everest-forms"),
					free: false,
					pro: true,
				},
				{
					title: __("Post Submission", "everest-forms"),
					free: false,
					pro: true,
				},
				{
					title: __("Email Templates", "everest-forms"),
					free: false,
					pro: true,
				},
				{
					title: __("PayPal Standard", "everest-forms"),
					free: false,
					pro: true,
				},
				{
					title: __("Stripe", "everest-forms"),
					free: false,
					pro: true,
				},
				{
					title: __("Authorize.Net", "everest-forms"),
					free: false,
					pro: true,
				},
				{
					title: __("Zapier", "everest-forms"),
					free: false,
					pro: true,
				},
				{
					title: __("Save and Continue", "everest-forms"),
					free: false,
					pro: true,
				},
				{
					title: __("Repeater Fields", "everest-forms"),
					free: false,
					pro: true,
				},
				{
					title: __("Calculations", "everest-forms"),
					free: false,
					pro: true,
				},
				{
					title: __("Razorpay", "everest-forms"),
					free: false,
					pro: true,
				},
				{
					title: __("SMS Notifications", "everest-forms"),
					free: false,
					pro: true,
				},
				{
					title: __("Coupons", "everest-forms"),
					free: false,
					pro: true,
				},
				{
					title: __("Frontend Listing", "everest-forms"),
					free: false,
					pro: true,
				},
				{
					title: __("Drip", "everest-forms"),
					free: false,
					pro: true,
				},
				{
					title: __("HubSpot", "everest-forms"),
					free: false,
					pro: true,
				},
				{
					title: __("Pipedrive", "everest-forms"),
					free: false,
					pro: true,
				},
				{
					title: __("Zoho CRM", "everest-forms"),
					free: false,
					pro: true,
				},
				{
					title: __("Salesforce", "everest-forms"),
					free: false,
					pro: true,
				},
				{
					title: __("Constant Contact", "everest-forms"),
					free: false,
					pro: true,
				},
				{
					title: __("Cloud Storage", "everest-forms"),
					free: false,
					pro: true,
				},
				{
					title: __("GetResponse", "everest-forms"),
					free: false,
					pro: true,
				},
				{
					title: __("Sendinblue", "everest-forms"),
					free: false,
					pro: true,
				},
				{
					title: __("Conversational Forms", "everest-forms"),
					free: false,
					pro: true,
				},
				{
					title: __("AI Contact Form - Free", "everest-forms"),
					free: true,
					pro: true,
				},
				{
					title: __("Advanced Form Analytics", "everest-forms"),
					free: false,
					pro: true,
				},
			],
		},
	]);

	useEffect(() => {
		if (!contentsLoaded) {
			const tableContentsRef = [...tableContents];

			getAllModules()
				.then((data) => {
					if (data.success) {
						tableContentsRef.map((tableContent, key) => {
							if (tableContent.type === "features") {
								data.modules_lists.map((module) => {
									if (module.type == "feature") {
										tableContent.contents = [
											...tableContent.contents,
											{
												title: module.title,
												free: false,
												pro: true,
											},
										];
									}
								});
								tableContentsRef[key] = tableContent;
							}
							if (tableContent.type === "addons") {
								data.modules_lists.map((module) => {
									if (module.type == "addon") {
										tableContent.contents = [
											...tableContent.contents,
											{
												title: module.title,
												free: false,
												pro: true,
											},
										];
									}
								});
								tableContentsRef[key] = tableContent;
							}
						});
						setTableContents(tableContentsRef);
					}
				})
				.catch((e) => {
					toast({
						title: e.message,
						status: "error",
						duration: 3000,
					});
				});
			setContentsLoaded(true);
		}
	}, [contentsLoaded, tableContents]);

	return (
		<Stack direction="column" gap="10px">
			<TableContainer my="8" mx="6">
				{tableContents.map((tableContent) => (
					<Table
						variant="simple"
						fontSize="14px"
						key={tableContent.type}
					>
						<Thead bgColor="#2563EB">
							<Tr border="1px solid #F4F4F4" alignItems="center">
								<Th w="50%" color="white">
									{tableContent.title}
								</Th>
								<Th w="25%" color="white">
									{__("Free", "everest-forms")}
								</Th>
								<Th w="25%" color="white">
									{__("Pro", "everest-forms")}
								</Th>
							</Tr>
						</Thead>
						<Tbody>
							{tableContent.contents.map((rowContent) => (
								<Tr
									border="1px solid #F4F4F4"
									alignItems="center"
									key={rowContent.title}
								>
									<Td>{rowContent.title}</Td>
									<Td>
										{rowContent.free ? (
											<Image
												w="16px"
												h="16px"
												src={check}
											/>
										) : (
											<Image
												w="16px"
												h="16px"
												src={close}
											/>
										)}
									</Td>
									<Td>
										{rowContent.pro ? (
											<Image
												w="16px"
												h="16px"
												src={check}
											/>
										) : (
											<Image
												w="16px"
												h="16px"
												src={close}
											/>
										)}
									</Td>
								</Tr>
							))}
						</Tbody>
					</Table>
				))}
			</TableContainer>
			<Stack
				gap="16px"
				direction="column"
				alignItems="center"
				bgColor="#F1F5FE"
				padding="32px 0px"
				borderRadius="4px"
				my="8"
				mx="6"
			>
				<Lock h={"70px"} w={"80px"} />
				<Text fontSize="18px" lineHeight="24px" fontWeight="700">
					{__("Upgrade Now", "everest-forms")}
				</Text>
				<Text
					fontSize="14px"
					lineHeight="24px"
					fontWeight="400"
					padding="10px 50px"
					color="#6B6B6B"
				>
					{__(
						"Access all premium addons, features and upcoming updates right away by upgrading to the Pro version.",
						"everest-forms"
					)}
				</Text>
				<Button
					as={Link}
					colorScheme="primary"
					href={
						upgradeURL +
						"&utm_source=dashboard-free-vs-pro&utm_medium=upgrade-button"
					}
					color="white !important"
					textDecor="none !important"
					isExternal
					padding="10px 16px"
					borderRadius="3px"
				>
					{__("Get Everest Form Pro Now", "everest-forms")}
				</Button>
			</Stack>
		</Stack>
	);
};

export default FreeVsPro;
