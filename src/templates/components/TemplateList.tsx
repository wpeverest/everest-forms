import React, { useState } from "react";
import {
  SimpleGrid,
  Box,
  Image,
  Text,
  Badge,
  Button,
  Modal,
  ModalOverlay,
  ModalContent,
  ModalHeader,
  ModalBody,
  ModalCloseButton,
  useDisclosure,
  Input,
  VStack,
  Divider,
  useToast,
  HStack,
  Icon,
  DarkMode,
  Heading,
  Center,
} from "@chakra-ui/react";
import apiFetch from "@wordpress/api-fetch";
import { templatesScriptData } from "../utils/global";
import PluginStatus from "./PluginStatus";
import { FaHeart } from "react-icons/fa";
import { useMutation, useQueryClient } from "@tanstack/react-query";
import { __, _x } from '@wordpress/i18n';
import notFoundImage from "../images/not-found-image.png";
import { CiPlay1 } from "react-icons/ci";
import { IoEyeOutline } from "react-icons/io5";

interface Template {
  id: number;
  title: string;
  slug: string;
  imageUrl: string;
  description: string;
  isPro: boolean;
  preview_link?: string;
  addons?: { [key: string]: string };
  categories?: string[];
}

interface TemplateListProps {
  selectedCategory: string;
  templates: Template[];
}

const { restURL, security } = templatesScriptData;

interface CreateTemplateResponse {
  success: boolean;
  data?: {
    id: number;
    redirect: string;
    status: number;
  };
  message?: string;
}

const TemplateList: React.FC<TemplateListProps> = ({ selectedCategory, templates }) => {
  const [previewTemplate, setPreviewTemplate] = useState<Template | null>(null);
  const [formTemplateName, setFormTemplateName] = useState<string>("");
  const [selectedTemplateSlug, setSelectedTemplateSlug] = useState<string>("");
  const { isOpen, onOpen, onClose } = useDisclosure();
  const [hoverCardId, setHoverCardId] = useState<number | null>(null);
  const [favorites, setFavorites] = useState<string[]>([]);
  const toast = useToast();
  const queryClient = useQueryClient();

  const handleTemplateClick = (template: Template) => {
    setSelectedTemplateSlug(template.slug);
    setPreviewTemplate(template);
    setFormTemplateName(template.title);
    onOpen();
  };

  const handleFormTemplateSave = async () => {
    if (!formTemplateName) {
      toast({
		title: __("Form name required", "everest-forms"),
        description: __("Please provide a name for your form.", "everest-forms"),
        status: "warning",
        position: "bottom-right",
        duration: 5000,
        isClosable: true,
        variant: "subtle",
      });
      return;
    }

    try {
      const response = (await apiFetch({
        path: `${restURL}everest-forms/v1/templates/create`,
        method: "POST",
        body: JSON.stringify({
          title: formTemplateName,
          slug: selectedTemplateSlug,
        }),
        headers: {
          "Content-Type": "application/json",
          "X-WP-Nonce": security,
        },
      })) as CreateTemplateResponse;

      if (response.success && response.data) {
        window.location.href = response.data.redirect;
      } else {
        toast({
			title: __("Error", "everest-forms"),
			description: response.message || __("Failed to create form template.", "everest-forms"),
			status: "error",
			position: "bottom-right",
			duration: 5000,
			isClosable: true,
			variant: "subtle",
        });
      }
    } catch (error) {
      toast({
		title: __("Error", "everest-forms"),
        description: __("An error occurred while creating the form template.", "everest-forms"),
        status: "error",
        position: "bottom-right",
        duration: 5000,
        isClosable: true,
        variant: "subtle",
      });
    }
  };

  const mutation = useMutation(
    async (slug: string) => {
      const newFavorites = favorites.includes(slug)
        ? favorites.filter((item) => item !== slug)
        : [...favorites, slug];

      setFavorites(newFavorites);

      await apiFetch({
        path: `${restURL}everest-forms/v1/templates/favorite`,
        method: "POST",
        body: JSON.stringify({
          action: newFavorites.includes(slug) ? "add_favorite" : "remove_favorite",
          slug,
        }),
        headers: {
          "Content-Type": "application/json",
          "X-WP-Nonce": security,
        },
      });

      return newFavorites;
    },
    {
      onError: (error) => {
        toast({
			title: __("Error", "everest-forms"),
			description: __("An error occurred while updating favorites.", "everest-forms"),
			status: "error",
			position: "bottom-right",
			duration: 5000,
			isClosable: true,
			variant: "subtle",
        });
      },
      onSuccess: (newFavorites) => {
        queryClient.invalidateQueries({ queryKey: ['templates'] });
        setFavorites(newFavorites);
        queryClient.invalidateQueries(['favorites']);
      }
    }
  );

  const handleFavoriteToggle = (slug: string) => {
    mutation.mutate(slug);
  };

  const addonEntries = previewTemplate?.addons
    ? Object.entries(previewTemplate.addons).map(([key, value]) => ({ key, value }))
    : [];

  const requiredPlugins = addonEntries.map((addon) => ({
    key: addon.key,
    value: addon.value,
  }));



  return (
    <Box>
	 <Heading  as="h3" fontSize="18px" lineHeight="26px" letterSpacing="0.4px" fontWeight="semibold" m="0px 0px 32px" color="#26262E" borderBottom="1px solid #CDD0D8" paddingBottom="12px">
              {selectedCategory}
    </Heading>
		{
			templates?.length ?  (
				<SimpleGrid columns={[1, 2, 3, 4]} spacing={6}>
				{templates.map((template) => (

				  <Box
					key={template.slug}
					borderWidth= "2px"
					borderRadius="8px"
					borderColor="#F6F4FA"
					overflow="hidden"
					position="relative"
					onMouseOver={() => setHoverCardId(template.id)}
					onMouseLeave={() => setHoverCardId(null)}
					textAlign="center"
					bg="white"
					p={0}
					transition="all .3s"
					_hover={{ boxShadow: "0px 5px 24px rgba(58, 34, 93, 0.12)" }}
				  >
					<Center mb={0}>
					  <Box
						position="relative"
						width="100%"
						display="flex"
						justifyContent="center"
						alignItems="center"
						bg="#F7F4FB"
						pt="80px"
						height="250px"
						borderRadius="4px 4px 0px 0px"
						overflow="hidden"
						transition="all .3s"
						 _hover={{
							"::before": {
							  content: '""',
							  position: "absolute",
							  width: "100%",
							  height: "100%",
							  bg: "rgba(0, 0, 0, 0.5)",
							  top: "50%",
							  left: "50%",
							  transform: "translate(-50%, -50%)",
							  zIndex: 1
							}
						 }}

					  >
						<Image boxShadow="0px 3px 12px rgba(58, 34, 93, 0.12)" src={template.imageUrl} alt={template.title} objectFit="contain" />

						{template.isPro && (
						  <Badge
							bg="#4BCE61"
							color="white"
							position="absolute"
							bottom="12px"
  							right="12px"
							borderRadius="4px"
							fontSize="12px"
							p="2px 6px"
							textTransform="capitalize"
							zIndex="2"
						  >
							{__("Pro", "everest-forms")}
						  </Badge>
						)}

						{/* Hover Buttons */}
						{hoverCardId === template.id && (
							 <VStack
							spacing={4}
							position="absolute"
							top="50%"
							left="50%"
							transform="translate(-50%, -50%)"
							zIndex={2}
						  >
							<Button leftIcon={<CiPlay1 />} colorScheme="purple" onClick={() => handleTemplateClick(template)}>
							  {__("Get Started", "everest-forms")}
							</Button>
							{template.preview_link && (
							  <Button
							  leftIcon={<IoEyeOutline />}
								color="white"
								variant="outline"
								onClick={() => window.open(template.preview_link, "_blank")}
								_hover={{ color: "black", bg: "white" }}
							  >
								{__("Preview", "everest-forms")}
							  </Button>
							)}
						  </VStack>

						)}
					  </Box>
					</Center>

					{hoverCardId === template.id && (
					  <Box
						as="button"
						onClick={() => handleFavoriteToggle(template.slug)}
						aria-label={`Toggle favorite for ${template.title}`}
						position="absolute"
						top={2}
						right={2}
						zIndex={3}
						bg="transparent"
						border="none"
						_hover={{ color: "red.600" }}
					  >
						<Icon
						  as={FaHeart}
						  boxSize={6}
						  color={template.categories.includes("Favorites") ? "red" : "gray"}
						/>
					  </Box>
					)}

					<VStack padding="16px">
					  <Heading width="100%" textAlign="left" fontWeight="bold" fontSize="16px" margin="0px">
					  {template.title}
					</Heading>
					  <Text textAlign="left"  margin="0px" fontSize="14px" fontWeight="400" color="gray.600">
						{template.description}
					  </Text>
					</VStack>
				  </Box>
				))}
			  </SimpleGrid>
		) : (
			<Box
			display="flex"
			flexDirection="column"
			justifyContent="center"
			alignItems="center"
			height="80vh"
			width="100%"
		  >
			<Image
			  src={notFoundImage}
			  alt={__("Not Found", "everest-forms")}
			  boxSize="300px"
			  objectFit="cover"
			/>
			<Text
			  mt={4}
			  fontSize="lg"
			  fontWeight="bold"
			  textAlign="center"
			>
			  {__("No Templates Found", "everest-forms")}
			</Text>
			<Text
			  margin={0}
			  fontSize="sm"
			  textAlign="center"
			  color= "grey"
			>
			  {__("Sorry, we didn't find any templates that match your criteria", "everest-forms")}
			</Text>
		  </Box>
		)}

      <Modal isCentered isOpen={isOpen} onClose={onClose} size="xl" >
	  <ModalOverlay />
        <ModalContent  borderRadius="8px" padding="40px">
          <ModalHeader padding="0px" textAlign="left" fontSize="20px" lineHeight="28px" color="#26262E">
            {__("Uplift your form experience to the next level.","everest-forms")}
          </ModalHeader>
          <ModalCloseButton top="12px" right="12px" />
          <ModalBody padding="0px" marginTop="16px">
            <Box mb="20px" padding="0px">
			<Text margin="0px 0px 6px" fontSize="16px" lineHeight="29px">{__("Give it a name","everest-forms")}</Text>
              <Input
			  width={"full"}
                value={formTemplateName}
                onChange={(e) => setFormTemplateName(e.target.value)}
                placeholder="Give it a name."
                size="md"
              />
            </Box>

            <Box overflow="hidden" mb="0px" padding="0px">
              <PluginStatus requiredPlugins={requiredPlugins} onActivateAndContinue={handleFormTemplateSave} />
            </Box>
          </ModalBody>
        </ModalContent>
      </Modal>
    </Box>
  );
};

export default TemplateList;
