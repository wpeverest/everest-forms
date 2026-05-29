/**
 *  External Dependencies
 */
import React, { useState, useEffect } from "react";
import {
	Box,
	Accordion,
	AccordionItem,
	AccordionButton,
	AccordionIcon,
	AccordionPanel,
	Stack,
	Text,
	Button,
	Table,
	Tbody,
	Td,
	Tr,
} from "@chakra-ui/react";
import { __ } from "@wordpress/i18n";

/**
 *  Internal Dependencies
 */
import { ArrowLeftFill, Add, Minus } from "../../../../components/Icon/Icon";

const SmartTagsLists = ({ setIsListViewerOpen }) => {
	const SmartTagsList = [
		{
			id: __("Others Smart Tags"),
			description: __(
				"The Smart Tags listed below can be used to parse user data that doesn’t require user to be registered in the email content.",
				"everest-forms"
			),
			smartTag: [
				{
					id: "{{current_date}}",
					description: __("Current Date.", "everest-forms"),
				},
				{
					id: "{{current_time}}",
					description: __("Current Time.", "everest-forms"),
				},
				{
					id: "{{admin_email}}",
					description: __("Site admin email.", "everest-forms"),
				},
				{
					id: "{{site_name}}",
					description: __("Name of the website.", "everest-forms"),
				},
				{
					id: "{{site_url}}",
					description: __("URL of the website.", "everest-forms"),
				},
				{
					id: "{{page_title}}",
					description: __("Current Page Title.", "everest-forms"),
				},
				{
					id: "{{page_url}}",
					description: __("Current Page Url.", "everest-forms"),
				},
				{
					id: "{{page_id}}",
					description: __("Current Page ID.", "everest-forms"),
				},
				{
					id: "{{page_title}}",
					description: __("Current Page Title.", "everest-forms"),
				},
				{
					id: "{{author_email}}",
					description: __(
						"Current page or post's author email.",
						"everest-forms"
					),
				},
				{
					id: "{{author_name}}",
					description: __(
						"Current page or post's author name.",
						"everest-forms"
					),
				},
				{
					id: "{{form_name}}",
					description: __("Current form name", "everest-forms"),
				},
				{
					id: "{{user_ip_address}}",
					description: __(
						"Current user's ip address.",
						"everest-forms"
					),
				},
				{
					id: "{{user_id}}",
					description: __("User ID.", "everest-forms"),
				},
				{
					id: "{{user_meta key=whatever}}",
					description: __(
						"User Meta of particular key.",
						"everest-forms"
					),
				},
				{
					id: "{{user_name}}",
					description: __("User Name", "everest-forms"),
				},
				{
					id: "{{display_name}}",
					description: __("Display Name.", "everest-forms"),
				},
				{
					id: "{{first_name}}",
					description: __("First Name.", "everest-forms"),
				},
				{
					id: "{{last_name}}",
					description: __("Last Name.", "everest-forms"),
				},
				{
					id: "{{user_email}}",
					description: __("Current User Email.", "everest-forms"),
				},
				{
					id: "{{user_role}}",
					description: __("Current User Role.", "everest-forms"),
				},
				{
					id: "{{referrer_url}}",
					description: __(
						"URL of the referrer page from where users landed on the form.",
						"everest-forms"
					),
				},
				{
					id: "{{form_id}}",
					description: __("Current Form ID", "everest-forms"),
				},
			],
		},
	];

	const [isAccordionOpen, setIsAccordionOpen] = useState({});

	useEffect(() => {
		const accordionOpener = { ...isAccordionOpen };
		SmartTagsList.map((smartTag) => {
			accordionOpener[smartTag.id] = false;
		});
		setIsAccordionOpen(accordionOpener);
	}, []);

	const handleAccordionToggle = (smarttag_id) => {
		setIsAccordionOpen({
			...isAccordionOpen,
			[smarttag_id]: !isAccordionOpen[smarttag_id],
		});
	};

	return (
		<Stack
			px="6"
			py="8"
			direction="column"
			bgColor="white"
			borderRadius="base"
			border="1px"
			borderColor="gray.100"
		>
			<Stack direction="row">
				<Button
					leftIcon={
						<ArrowLeftFill
							w="30"
							h="30"
							position="relative"
							top="2px"
						/>
					}
					variant="outline"
					border="none"
					size="md"
					fontSize="16px"
					fontWeight="600"
					onClick={() => setIsListViewerOpen(false)}
				>
					{__("All Smart Tags", "everest-forms")}
				</Button>
			</Stack>
			<Accordion allowMultiple>
				{SmartTagsList.map((smartTags) => (
					<AccordionItem key={smartTags.id} p="16px">
						<AccordionButton
							justifyContent="space-between"
							_expanded={{ bg: "#F8F8FE" }}
							onClick={() => {
								handleAccordionToggle(smartTags.id);
							}}
							boxShadow="none !important"
						>
							<Box
								flex="1"
								textAlign="left"
								bgColor="#F8F2FF"
								color="#A975E8"
								maxWidth="fit-content"
								p="4px 8px"
								fontWeight="600"
								fontSize="14px"
							>
								{smartTags.id}
							</Box>
							{isAccordionOpen[smartTags.id] ? (
								<Minus h="5" w="5" />
							) : (
								<Add h="5" w="5" />
							)}
						</AccordionButton>
						<AccordionPanel
							pb={4}
							bgColor="#FBF8FE"
							sx={{
								display: "flex",
								flexDirection: "column",
								gap: "20px",
							}}
						>
							<Text fontSize="14px">{smartTags.description}</Text>
							{smartTags.smartTag && (
								<Table
									variant="simple"
									fontSize="14px"
									size="sm"
									sx={{
										display: "flex",
										flexDirection: "column",
										gap: "16px",
									}}
								>
									<Tbody
										sx={{
											display: "flex",
											flexDirection: "column",
											gap: "12px",
										}}
									>
										{smartTags.smartTag.map(
											({ id, description }, key) => (
												<Tr key={key}>
													<Td
														px="0px"
														borderBottom="0px"
														width="200px"
													>
														<Box
															flex="1"
															textAlign="left"
															bgColor="#F8F2FF"
															color="#A975E8"
															maxWidth="fit-content"
															p="4px 8px"
															fontWeight="600"
														>
															{id}
														</Box>
													</Td>
													<Td borderBottom="0px">
														{description}
													</Td>
												</Tr>
											)
										)}
									</Tbody>
								</Table>
							)}
						</AccordionPanel>
					</AccordionItem>
				))}
			</Accordion>
		</Stack>
	);
};

export default SmartTagsLists;
