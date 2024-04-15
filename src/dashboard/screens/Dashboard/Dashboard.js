/**
 *  External Dependencies
 */
import {
  AspectRatio,
  Box,
  Button,
  ButtonGroup,
  Grid,
  Heading,
  HStack,
  Link,
  Stack,
  Text
} from "@chakra-ui/react";
import { __ } from "@wordpress/i18n";
import React from "react";

/**
 *  Internal Dependencies
 */
import * as Icon from "../../components/Icon/Icon";
import UsefulPlugins from "./components/UsefulPlugins";

const Dashboard = () => {
  /* global _EVF_DASHBOARD_ */
  const { newFormURL, allFormsURL, utmCampaign } =
    typeof _EVF_DASHBOARD_ !== "undefined" && _EVF_DASHBOARD_;

  const helpURL =
      "https://docs.everestforms.net/?utm_source=dashboard-dashboard&utm_medium=sidebar-link&utm_campaign=" +
      utmCampaign,
    featureRequestURL =
      "https://everestforms.net/feature-requests/?utm_source=dashboard-dashboard&utm_medium=sidebar-link&utm_campaign=" +
      utmCampaign,
	  supportURL ="https://everestforms.net/support/?utm_source=dashboard-dashboard&utm_medium=sidebar-link&utm_campaign=" +
      utmCampaign;

  return (
    <Grid
      my="8"
      mx="6"
      gridGap="5"
      gridTemplateColumns={{
        sm: "1fr",
        md: "3fr 1fr"
      }}
    >
      <Stack gap="5">
        <Box
          p="6"
          borderRadius="base"
          border="1px"
          borderColor="gray.100"
          bgColor="white"
        >
          <Heading as="h3" mb="5" fontSize="2xl" fontWeight="semibold">
            {__("Welcome to Everest Forms!", "everest-forms")}
          </Heading>
          <AspectRatio ratio={16 / 9}>
            <iframe
              src="https://www.youtube.com/embed/AvK0KU2ycqc?autoplay=1&mute=1&rel=0"
              title="YouTube video player"
              style={{
                borderRadius: "11px",
                border: "none",
                overflow: "hidden"
              }}
              allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
              allowFullScreen
            />
          </AspectRatio>
          <ButtonGroup mt="5" spacing="6">
            <Button
              as={Link}
              colorScheme="primary"
              fontSize="14px"
              fontWeight="normal"
              borderRadius="base"
              color="white !important"
              textDecor="none !important"
              py="3"
              px="6"
              href={newFormURL}
            >
              {__("Create a Contact Form", "everest-forms")}
            </Button>
            <Button
              as={Link}
              variant="outline"
              colorScheme="primary"
              borderRadius="base"
              fontSize="14px"
              fontWeight="normal"
              href={allFormsURL}
              textDecor="none !important"
              isExternal
            >
              {__("View all forms", "everest-forms")}
            </Button>
          </ButtonGroup>
        </Box>
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
            <Icon.Team w="5" h="5" fill="primary.500" />
            <Heading as="h3" size="sm" fontWeight="semibold">
              {__("Everest Forms Community", "everest-forms")}
            </Heading>
          </HStack>
          <Text fontSize="13px" color="gray.700">
            {__(
              "Join our exclusive group and connect with fellow Everest Forms members. Ask questions, contribute to discussions, and share feedback!",
              "everest-forms"
            )}
          </Text>
          <Link
            color="var(--chakra-colors-primary-500) !important"
            textDecor="underline"
            href="https://www.facebook.com/groups/everestforms"
            isExternal
          >
            {__("Join our Facebook Group", "everest-forms")}
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
            <Icon.DocsLines w="5" h="5" fill="primary.500" />
            <Heading as="h3" size="sm" fontWeight="semibold">
              {__("Getting Started", "everest-forms")}
            </Heading>
          </HStack>
          <Text fontSize="13px" color="gray.700">
            {__(
              "Check our documentation for detailed information on Everest Forms features and how to use them.",
              "everest-forms"
            )}
          </Text>
          <Link
            color="var(--chakra-colors-primary-500) !important"
            textDecor="underline"
            href={helpURL}
            isExternal
          >
            {__("View Documentation", "everest-forms")}
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
            <Icon.Headphones w="5" h="5" fill="primary.500" />
            <Heading as="h3" size="sm" fontWeight="semibold">
              {__("Support", "everest-forms")}
            </Heading>
          </HStack>
          <Text fontSize="13px" color="gray.700">
            {__(
              "Submit a ticket for encountered issues and get help from our support team instantly.",
              "everest-forms"
            )}
          </Text>
          <Link
            color="var(--chakra-colors-primary-500) !important"
            textDecor="underline"
            href={supportURL}
            isExternal
          >
            {__("Create a Ticket", "everest-forms")}
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
            color="var(--chakra-colors-primary-500) !important"
            textDecor="underline"
            href={featureRequestURL}
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
            color="var(--chakra-colors-primary-500) !important"
            textDecor="underline"
            href="https://wordpress.org/support/plugin/everest-forms/reviews/?rate=5#new-post"
            isExternal
          >
            {__("Submit a Review", "everest-forms")}
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
            color="var(--chakra-colors-primary-500) !important"
            textDecor="underline"
            isExternal
            href="https://www.youtube.com/@EverestForms"
          >
            {__("Watch Videos", "everest-forms")}
          </Link>
        </Stack>
      </Stack>
    </Grid>
  );
};

export default Dashboard;
