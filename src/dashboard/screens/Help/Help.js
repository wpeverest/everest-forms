/**
 *  External Dependencies
 */
import {
	Button,
	Grid,
	Heading,
	HStack,
	Image,
	Link,
	Stack,
	Text,
} from "@chakra-ui/react";
import { __ } from "@wordpress/i18n";
import React, { useState, useEffect } from "react";

/**
 *  Internal Dependencies
 */
import * as Icon from "../../components/Icon/Icon";
import facebook from "../../images/facebook.webp";
import x from "../../images/x.webp";
import youtube from "../../images/youtube.webp";
import ShortcodesLists from "./Lists/ShortcodesLists/ShortcodesLists";
import SmartTagsLists from "./Lists/SmartTagsLists/SmartTagsLists";
import {
	facebookUrl,
	youtubeChannelUrl,
	twitterUrl,
	reviewUrl,
} from "../../Constants";

/* global _EVF_DASHBOARD_ */
const { newFormURL, allFormsURL, utmCampaign } =
	typeof _EVF_DASHBOARD_ !== "undefined" && _EVF_DASHBOARD_;
export const supportURL =
	"https://everestforms.net/support/?utm_source=dashboard-help&utm_medium=support-button&utm_campaign=" +
	utmCampaign;
export const helpURL =
	"https://docs.everestforms.net/?utm_source=dashboard-help&utm_medium=help-button&utm_campaign=" +
	utmCampaign;
export const featureRequestURL =
	"https://everestforms.net/feature-requests/?utm_source=dashboard-help&utm_medium=sidebar-link&utm_campaign=" +
	utmCampaign;

const Help = () => {
	const [isListViewerOpen, setIsListViewerOpen] = useState(false);
	const [listViewerType, setListViewerType] = useState("");

	useEffect(() => {}, [isListViewerOpen]);

	return (
		<Grid
			my="8"
			mx="6"
			gridGap="5"
			gridTemplateColumns={{
				sm: "1fr",
				md: "3fr 1fr",
			}}
		>
			<Stack gap="5">
				{isListViewerOpen ? (
					listViewerType === "shortcodes" ? (
						<ShortcodesLists
							setIsListViewerOpen={setIsListViewerOpen}
						/>
					) : (
						<SmartTagsLists
							setIsListViewerOpen={setIsListViewerOpen}
						/>
					)
				) : (
					<Grid
						gridTemplateColumns={{
							sm: "1fr",
							md: "1fr 1fr",
						}}
						gridGap="5"
					>
						<Stack
							px="6"
							py="8"
							align="center"
							gap="3"
							bgColor="white"
							borderRadius="base"
							border="1px"
							borderColor="gray.100"
							textAlign="center"
						>
							<Icon.Shortcode w="8" h="8" fill="primary.500" />
							<Heading as="h3" size="sm" fontWeight="semibold">
								{__("Shortcodes", "everest-forms")}
							</Heading>
							<Text fontSize="13px" color="gray.700">
								{__(
									"Find the complete list of shortcodes with their usage information and parameter details.",
									"everest-forms"
								)}
							</Text>
							<Button
								mt="10"
								variant="outline"
								colorScheme="primary"
								borderRadius="base"
								fontSize="14px"
								fontWeight="normal"
								onClick={() => {
									setIsListViewerOpen(true);
									setListViewerType("shortcodes");
								}}
							>
								{__("View all Shortcodes", "everest-forms")}
							</Button>
						</Stack>
						<Stack
							px="6"
							py="8"
							align="center"
							gap="3"
							bgColor="white"
							borderRadius="base"
							border="1px"
							borderColor="gray.100"
							textAlign="center"
						>
							<Icon.SmartTag w="8" h="8" fill="primary.500" />
							<Heading as="h3" size="sm" fontWeight="semibold">
								{__("Smart Tags", "everest-forms")}
							</Heading>
							<Text fontSize="13px" color="gray.700">
								{__(
									"Find the complete list of smart tags with their usage information and parameter details.",
									"everest-forms"
								)}
							</Text>
							<Button
								mt="10"
								variant="outline"
								colorScheme="primary"
								borderRadius="base"
								fontSize="14px"
								fontWeight="normal"
								onClick={() => {
									setIsListViewerOpen(true);
									setListViewerType("smartTags");
								}}
							>
								{__("View Tags", "everest-forms")}
							</Button>
						</Stack>
						<Stack
							px="6"
							py="8"
							align="center"
							gap="3"
							bgColor="white"
							borderRadius="base"
							border="1px"
							borderColor="gray.100"
							textAlign="center"
						>
							<Icon.Chat w="8" h="8" fill="primary.500" />
							<Heading as="h3" size="sm" fontWeight="semibold">
								{__("Support", "everest-forms")}
							</Heading>
							<Text fontSize="13px" color="gray.700">
								{__(
									"If you have any issues or questions, our team is on standby to help you instantly.",
									"everest-forms"
								)}
							</Text>
							<Button
								mt="10"
								as={Link}
								variant="outline"
								colorScheme="primary"
								borderRadius="base"
								fontSize="14px"
								fontWeight="normal"
								href={supportURL}
								isExternal
								textDecor="none !important"
							>
								{__("Contact Support", "everest-forms")}
							</Button>
						</Stack>
						<Stack
							px="6"
							py="8"
							align="center"
							gap="3"
							bgColor="white"
							borderRadius="base"
							border="1px"
							borderColor="gray.100"
							textAlign="center"
						>
							<Icon.DocsLines w="8" h="8" fill="primary.500" />
							<Heading as="h3" size="sm" fontWeight="semibold">
								{__("Need Some Help?", "everest-forms")}
							</Heading>
							<Text fontSize="13px" color="gray.700">
								{__(
									"Check our documentation for detailed information on Everest Forms features and how to use them.",
									"everest-forms"
								)}
							</Text>
							<Button
								mt="10"
								as={Link}
								colorScheme="primary"
								borderRadius="base"
								fontSize="14px"
								fontWeight="normal"
								textDecor="none !important"
								href={helpURL}
								isExternal
								variant="outline"
							>
								{__("View Now", "everest-forms")}
							</Button>
						</Stack>
					</Grid>
				)}
				<Stack>
					<Heading as="h3" fontSize="lg" fontWeight="semibold">
						{__("Join Our Community", "everest-forms")}
					</Heading>
				</Stack>
				<Grid
					gridTemplateColumns="1fr 1fr"
					p="4"
					bgColor="white"
					border="1px"
					borderColor="gray.100"
					borderRadius="base"
					gridGap="7"
				>
					<Image src={facebook} w="full" />
					<Stack gap="2" justify="center">
						<Heading
							as="h3"
							fontSize="xl"
							fontWeight="normal"
							color="gray.700"
						>
							{__("Facebook Community", "everest-forms")}
						</Heading>
						<Text fontSize="13px" color="gray.700">
							{__(
								"Join our exclusive group and connect with fellow Everest Forms members. Ask questions, contribute to discussions, and share feedback!",
								"everest-forms"
							)}
						</Text>
						<Button
							as={Link}
							colorScheme="primary"
							borderRadius="base"
							fontSize="14px"
							fontWeight="normal"
							alignSelf="start"
							mt="5"
							color="white !important"
							isExternal
							href={facebookUrl}
							textDecor="none !important"
						>
							{__("Join Group", "everest-forms")}
						</Button>
					</Stack>
				</Grid>
				<Grid
					gridTemplateColumns="1fr 1fr"
					p="4"
					bgColor="white"
					border="1px"
					borderColor="gray.100"
					borderRadius="base"
					gridGap="7"
				>
					<Image src={x} />
					<Stack gap="2" justify="center">
						<Heading
							as="h3"
							fontSize="xl"
							fontWeight="normal"
							color="gray.700"
						>
							{__("X ( Twitter )", "everest-forms")}
						</Heading>
						<Text fontSize="13px" color="gray.700">
							{__(
								"Follow us on X to get the latest news and updates about Everest Forms and the team behind it.",
								"everest-forms"
							)}
						</Text>
						<Button
							as={Link}
							borderRadius="base"
							fontSize="14px"
							fontWeight="normal"
							alignSelf="start"
							mt="5"
							color="white !important"
							bgColor="black !important"
							isExternal
							href={twitterUrl}
							textDecor="none !important"
						>
							{__("Follow", "everest-forms")}
						</Button>
					</Stack>
				</Grid>
				<Grid
					gridTemplateColumns="1fr 1fr"
					p="4"
					bgColor="white"
					border="1px"
					borderColor="gray.100"
					borderRadius="base"
					gridGap="7"
				>
					<Image src={youtube} />
					<Stack gap="2" justify="center">
						<Heading
							as="h3"
							fontSize="xl"
							fontWeight="normal"
							color="gray.700"
						>
							{__("YouTube", "everest-forms")}
						</Heading>
						<Text fontSize="13px" color="gray.700">
							{__(
								"Subscribe to our YouTube channel, where we guide you on using Everest Forms’s features and add-ons.",
								"everest-forms"
							)}
						</Text>
						<Button
							as={Link}
							colorScheme="red"
							borderRadius="base"
							fontSize="14px"
							fontWeight="normal"
							alignSelf="start"
							mt="5"
							color="white !important"
							isExternal
							href={youtubeChannelUrl}
							textDecor="none !important"
						>
							{__("Subscribe", "everest-forms")}
						</Button>
					</Stack>
				</Grid>
			</Stack>
			<Stack gap="5">
				<Stack
					p="4"
					gap="3"
					bgColor="white"
					borderRadius="base"
					border="1px"
					borderColor="gray.100"
				>
					<HStack gap="2">
						<Icon.Video w="5" h="5" fill="primary.500" />
						<Heading as="h3" size="sm" fontWeight="semibold">
							{__("Video Tutorials", "everest-forms")}
						</Heading>
					</HStack>
					<Text fontSize="13px" color="gray.700">
						{__(
							"Watch our step-by-step video tutorials that’ll help you get the best out of Everest Forms’s features.",
							"everest-forms"
						)}
					</Text>
					<Link
						isExternal
						color="var(--chakra-colors-primary-500) !important"
						textDecor="underline"
						href={youtubeChannelUrl}
					>
						{__("Watch Videos", "everest-forms")}
					</Link>
				</Stack>
				<Stack
					p="4"
					gap="3"
					bgColor="white"
					borderRadius="base"
					border="1px"
					borderColor="gray.100"
				>
					<HStack gap="2">
						<Icon.Bulb w="5" h="5" fill="primary.500" />
						<Heading as="h3" size="sm" fontWeight="semibold">
							{__("Feature Request", "everest-forms")}
						</Heading>
					</HStack>
					<Text fontSize="13px" color="gray.700">
						{__(
							"Don’t find a feature you’re looking for? Suggest any features you think would enhance our product.",
							"everest-forms"
						)}
					</Text>
					<Link
						href={featureRequestURL}
						color="var(--chakra-colors-primary-500) !important"
						textDecor="underline"
						isExternal
					>
						{__("Request a Feature", "everest-forms")}
					</Link>
				</Stack>
				<Stack
					p="4"
					gap="3"
					bgColor="white"
					borderRadius="base"
					border="1px"
					borderColor="gray.100"
				>
					<HStack gap="2">
						<Icon.Star w="5" h="5" fill="primary.500" />
						<Heading as="h3" size="sm" fontWeight="semibold">
							{__("Submit a Review", "everest-forms")}
						</Heading>
					</HStack>
					<Text fontSize="13px" color="gray.700">
						{__(
							"Please take a moment to give us a review. We appreciate honest feedback that’ll help us improve our plugin.",
							"everest-forms"
						)}
					</Text>
					<Link
						href={reviewUrl}
						color="var(--chakra-colors-primary-500) !important"
						textDecor="underline"
						isExternal
					>
						{__("Submit a Review", "everest-forms")}
					</Link>
				</Stack>
			</Stack>
		</Grid>
	);
};

export default Help;
