/**
 *  External Dependencies
 */
import React, { useState, useEffect } from "react";
import {
	Box,
	Accordion,
	AccordionItem,
	AccordionButton,
	AccordionPanel,
	Stack,
	Text,
	Button,
	Table,
	Tbody,
	Td,
	Tr,
	Thead,
	HStack,
	IconButton,
	Tooltip,
	useClipboard,
	useToast
} from "@chakra-ui/react";
import { __ } from "@wordpress/i18n";

import { CopyIcon } from '@chakra-ui/icons';

/**
 *  Internal Dependencies
 */
import { ArrowLeftFill, Add, Minus } from "../../../../components/Icon/Icon";

const ShortcodesLists = ({ setIsListViewerOpen }) => {
	const ShortcodeList = [
		{
			id: "[everest_form]",
			description: __(
				"Displays everest forms in the front end.",
				"everest-forms"
			),
			params: [
				{
					param_name: "id",
					param_description: __(
						"ID of the form to display.",
						"everest-forms"
					),
					required: true,
				},
			],
			example: [
				{
					example_name: '[everest_form id="5"]',
					example_description: __(
						"Displays Everest Forms with id 5",
						"everest-forms"
					),
				},
			],
		},
		{
			id: "[everest_forms_user_login]",
			description: __(
				"Display the login form for user who are created by using user registration addons.",
				"everest-forms"
			),
			params: [
				{
					param_name: "redirect_url",
					param_description: __(
						"Redirect URL after login.",
						"everest-forms"
					),
					required:false
				},
				{
					param_name: "recaptcha",
					param_description: __(
						"Enable the recaptcha.",
						"everest-forms"
					),
					required:false
				},
			],
			example: [
				{
					example_name: '[everest_forms_user_login redirect_url="sample_page" recaptcha="true"]',
					example_description: __(
						"Display the login form with recaptcha and redirect to sample page after login.",
						"everest-forms"
					),
				},
			],
			requires: __(
				"Requires Everest Forms Pro and User registration Addon to be activated.",
				"everest-forms"
			),
		},
		{
			id: "[everest_forms_frontend_list]",
			description: __(
				"Displays member directories in the front end.",
				"everest-forms"
			),
			params: [
				{
					param_name: "id",
					param_description: __(
						"Frontend Listing ID to render.",
						"everest-forms"
					),
					required: true,
				},
			],
			example: [
				{
					example_name: '[everest_forms_frontend_list id="1"]',
					example_description: __(
						"Displays user listing with ID 1 in the front end.",
						"everest-forms"
					),
				},
			],
			requires: __(
				"Requires Everest Forms Pro and Frontend Listing Addon to be activated.",
				"everest-forms"
			),
		},
	];
	const [isAccordionOpen, setIsAccordionOpen] = useState({});

	const [isShortcodeCopied, setShortcodeCopied] = useState({});

	const { onCopy, hasCopied } = useClipboard()
	const [isExampleShortcodeCopied, setIsExampleShortcodeCopied] = useState(false);

	const toast = useToast()

	useEffect(() => {
		const accordionOpener = { ...isAccordionOpen };
		ShortcodeList.map((shortcode) => {
			accordionOpener[shortcode.id] = false;
		});
		setIsAccordionOpen(accordionOpener);
	}, []);

	useEffect(() => {
		const shortcodeAccordion = isShortcodeCopied;
		ShortcodeList.map((shortcode) => {
			shortcodeAccordion[shortcode.id] = false;
		});
		setShortcodeCopied(shortcodeAccordion);
	}, [isShortcodeCopied]);

	const handleAccordionToggle = (shortcode_id) => {
		setIsAccordionOpen({
			...isAccordionOpen,
			[shortcode_id]: !isAccordionOpen[shortcode_id],
		});
	};

	const handleCopyClick = (shortcode_id, event) => {
		try {
		  let copiedText = shortcode_id;
		  if (shortcode_id === "[everest_form]") {
			copiedText = `[everest_form id=""]`;
		  } else if (shortcode_id === "[everest_forms_frontend_list]") {
			copiedText = `[everest_forms_frontend_list id=""]`;
		  }

		  const textField = document.createElement('textarea');
		  textField.innerText = copiedText;
		  document.body.appendChild(textField);
		  textField.select();
		  document.execCommand('copy');
		  textField.remove();

		  onCopy();

		  setShortcodeCopied({
			...isShortcodeCopied,
			[shortcode_id]: !isShortcodeCopied[shortcode_id],
		  });

		  event.stopPropagation();
		} catch (error) {
		  console.error("Error copying shortcode:", error);
		}
	  };

	const handleExampleShortcodeCopy = (example_name) => {
		try {
			const textField = document.createElement('textarea');
			textField.innerText = example_name;
			document.body.appendChild(textField);
			textField.select();
			document.execCommand('copy');
			textField.remove();
			onCopy();
			setIsExampleShortcodeCopied(true);
			event.stopPropagation();
			setTimeout(() => {
				setIsExampleShortcodeCopied(false);
			  }, 1000);
		  } catch (error) {
			console.error("Error copying shortcode:", error);
		  }
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
					boxShadow="none !important"
				>
					{__("All Shortcodes", "everest-forms")}
				</Button>
			</Stack>
			<Accordion allowMultiple>
				{ShortcodeList.map((shortcode) => (
					<AccordionItem key={shortcode.id} p="16px">
						<AccordionButton
							justifyContent="space-between"
							_expanded={{ bg: "#F8F8FE" }}
							onClick={() => {
								handleAccordionToggle(shortcode.id);
							}}
							boxShadow="none !important"
						>
							<Box
								flex="1"
								textAlign="left"
								bgColor="#EDEFF7"
								color="#2563EB"
								maxWidth="fit-content"
								p="4px 8px"
								fontWeight="600"
								fontSize="14px"
							>
								{shortcode.id}
							</Box>
							<Box
								textAlign="right"
							>
								<HStack>
									<IconButton
										size='md'
										icon = {<CopyIcon />}
										onClick={(event) => handleCopyClick(shortcode.id, event)}
									/>
									{hasCopied && isShortcodeCopied[shortcode.id] ?
										<Tooltip
											hasArrow={true}
											closeDelay = {2000}
										>
										{__('Copied!','everest-forms')}</Tooltip> : ''
									}
									{isAccordionOpen[shortcode.id] ? (
										<Minus h="5" w="5" />
									) : (
										<Add h="5" w="5" />
									)}
								</HStack>
							</Box>
						</AccordionButton>
						<AccordionPanel
							pb={4}
							bgColor="#F8F8FE"
							sx={{
								display: "flex",
								flexDirection: "column",
								gap: "20px",
							}}
						>
							<Text fontSize="14px">{shortcode.description}</Text>
							{shortcode.params && (
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
									<Thead>
										<Tr border="none">
											<Td
												sx={{
													fontWeight: "600",
													paddingLeft: "0px",
													border: "none",
												}}
											>
												{__(
													"Parameters:",
													"everest-forms"
												)}
											</Td>
										</Tr>
									</Thead>
									<Tbody
										sx={{
											display: "flex",
											flexDirection: "column",
											gap: "12px",
										}}
									>
										{shortcode.params.map(
											(
												{
													param_name,
													param_description,
													required,
												},
												key
											) => (
												<Tr key={key}>
													<Td
														px="0px"
														borderBottom="0px"
														width="200px"
													>
														<Box
															flex="1"
															textAlign="left"
															bgColor="#EDEFF7"
															color="#2563EB"
															maxWidth="fit-content"
															p="4px 8px"
															fontWeight="600"
														>
															{param_name}
														</Box>
													</Td>
													<Td borderBottom="0px">
														<Text>
															{required && (
																<strong>
																	{__(
																		"REQUIRED.",
																		"everest-forms"
																	)}
																</strong>
															)}{" "}
															{param_description}
														</Text>
													</Td>
												</Tr>
											)
										)}
									</Tbody>
								</Table>
							)}
							{shortcode.example && (
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
									<Thead>
										<Tr>
											<Td
												sx={{
													fontWeight: "600",
													paddingLeft: "0px",
													border: "none",
												}}
											>
												{__(
													"Examples:",
													"everest-forms"
												)}
											</Td>
										</Tr>
									</Thead>
									<Tbody
										sx={{
											display: "flex",
											flexDirection: "column",
											gap: "12px",
										}}
									>
										{shortcode.example.map(
											(
												{
													example_name,
													example_description,
												},
												key
											) => (
												<>
													<Tr key={key}>
														<Td
															paddingLeft="0px"
															paddingTop="2"
															paddingBottom="2"
															borderBottom="0px"
														>
															<Box
																flex="1"
																textAlign="left"
																bgColor="#EDEFF7"
																color="#2563EB"
																maxWidth="fit-content"
																p="4px 8px"
																fontWeight="600"
															>
																{example_name}
															</Box>
															</Td>
															<Td>
																{example_name =='[everest_forms_user_login redirect_url="sample_page" recaptcha="true"]' &&
																	<Box>
																		<IconButton
																		size='md'
																		icon = {<CopyIcon />}
																		onClick={(event) => handleExampleShortcodeCopy(example_name, event)}
																		/>
																		{isExampleShortcodeCopied ?
																			<Tooltip
																				hasArrow={true}
																				closeDelay = {1000}
																			>
																			{__('Copied!','everest-forms')}</Tooltip> : ''
																		}
																	</Box>
																}
															</Td>
													</Tr>
													<Tr>
														<Td
															paddingLeft="0px"
															paddingTop="2"
															paddingBottom="2"
															borderBottom="0px"
														>
															{
																example_description
															}
														</Td>
													</Tr>
												</>
											)
										)}
									</Tbody>
								</Table>
							)}
							{shortcode.requires && (
								<Text
									fontSize="14px"
									color="red"
									fontWeight="500"
									marginTop="10px"
								>
									{shortcode.requires}
								</Text>
							)}
						</AccordionPanel>
					</AccordionItem>
				))}
			</Accordion>
		</Stack>
	);
};

export default ShortcodesLists;
