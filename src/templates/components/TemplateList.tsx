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
} from "@chakra-ui/react";
import apiFetch from "@wordpress/api-fetch";
import { templatesScriptData } from "../utils/global";
import PluginStatus from "./PluginStatus";
import { FaHeart } from "react-icons/fa";
import { useMutation, useQueryClient } from "@tanstack/react-query";
import { __, _x } from '@wordpress/i18n';

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
console.log(templates);

  return (
    <Box>
		{
			templates?.length ?  ( <SimpleGrid columns={[1, 2, 3]} spacing={6}>
			{templates.map((template: Template) => (
			  <Box
				key={template.slug}
				borderWidth={1}
				borderRadius="md"
				overflow="hidden"
				position="relative"
				onMouseOver={() => setHoverCardId(template.id)}
				onMouseLeave={() => setHoverCardId(null)}
				boxShadow="sm"
			  >
				{/* Pro Badge */}
				{template.isPro && (
				  <Badge
					colorScheme="green"
					position="absolute"
					bottom={2}
					right={2}
					zIndex={3}
					fontSize="0.8em"
					size="lg"
				  >
					Pro
				  </Badge>
				)}

				{/* Favorite Icon */}
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
				<Box
				  position="relative"
				  _hover={{
					"&::before": {
					  content: '""',
					  position: "absolute",
					  top: 0,
					  left: 0,
					  right: 0,
					  bottom: 0,
					  bg: "rgba(0, 0, 0, 0.4)",
					  zIndex: 1,
					},
				  }}
				>
				  <Image src={template.imageUrl} alt={template.title} />
				  {/* Buttons appear on hover */}
				  {hoverCardId === template.id && (
					<HStack
					  spacing={4}
					  mt={4}
					  position="absolute"
					  top="50%"
					  left="50%"
					  transform="translate(-50%, -50%)"
					  zIndex={2}
					>
					  <Button colorScheme="purple" onClick={() => handleTemplateClick(template)}>
					  {__("Get Started", "everest-forms")}
					  </Button>
					  {template.preview_link && (
						<Button
						  color="white"
						  variant="outline"
						  onClick={() => window.open(template.preview_link, "_blank")}
						  _hover={{ color: "black", bg: "white" }}
						>
						  {__("Preview", "everest-forms")}
						</Button>
					  )}
					</HStack>
				  )}
				</Box>
				<Box p={4}>
				  <Text fontWeight="bold" mb={2} fontSize="16" >
					{template.title}
				  </Text>
				  <Text fontSize="14" mb={2} fontWeight="400">
					{template.description}
				  </Text>
				</Box>
			  </Box>
			))}
		  </SimpleGrid>
		) : (
			<Text textAlign="center" fontSize="lg" color="gray.600" mt={8}>
			{__("No templates found", "everest-forms")}
		  </Text>
		)}

      <Modal isCentered isOpen={isOpen} onClose={onClose} size="xl">
	  <ModalOverlay />
        <ModalContent>
          <ModalHeader textAlign="center">
            {__("Uplift your form experience to the next level.","everest-forms")}
          </ModalHeader>
          <ModalCloseButton />
          <ModalBody>
            <Box mb={4} padding={2}>
              <Input
			  width={"full"}
                value={formTemplateName}
                onChange={(e) => setFormTemplateName(e.target.value)}
                placeholder="Give it a name."
                size="md"
              />
            </Box>

            <Box   overflow="hidden" mb={4} padding={2}>
              <PluginStatus requiredPlugins={requiredPlugins} onActivateAndContinue={handleFormTemplateSave} />
            </Box>
          </ModalBody>
        </ModalContent>
      </Modal>
    </Box>
  );
};

export default TemplateList;
