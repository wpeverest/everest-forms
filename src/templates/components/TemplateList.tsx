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
} from "@chakra-ui/react";
import apiFetch from "@wordpress/api-fetch";
import { templatesScriptData } from "../utils/global";
import PluginStatus from "./PluginStatus";

interface Template {
  id: number;
  title: string;
  slug: string;
  imageUrl: string;
  description: string;
  isPro: boolean;
  preview_link?: string;
  addons?: { [key: string]: string };
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
  const toast = useToast();

  const handleTemplateClick = (template: Template) => {
    setSelectedTemplateSlug(template.slug);
    setPreviewTemplate(template);
    setFormTemplateName(template.title);
    onOpen();
  };

  const handleFormTemplateSave = async () => {
    if (!formTemplateName) {
      toast({
        title: "Form name required",
        description: "Please provide a name for your form.",
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
          title: "Error",
          description: response.message || "Failed to create form template.",
          status: "error",
          position: "bottom-right",
          duration: 5000,
          isClosable: true,
          variant: "subtle",
        });
      }
    } catch (error) {
      console.error("Error creating form template:", error);
      toast({
        title: "Error",
        description: "An error occurred while creating the form template.",
        status: "error",
        position: "bottom-right",
        duration: 5000,
        isClosable: true,
        variant: "subtle",
      });
    }
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
      <SimpleGrid columns={[1, 2, 3]} spacing={6}>
        {templates.map((template) => (
			<Box
  key={template.slug}
  borderWidth={1}
  borderRadius="md"
  overflow="hidden"
  position="relative"
  onMouseOver={() => setHoverCardId(template.id)}
  onMouseLeave={() => setHoverCardId(null)}
  boxShadow={"sm"}
>
  {template.isPro && (
    <Badge
      colorScheme="green"
      position="absolute"
      top={2}
      right={2}
      zIndex={3}
      fontSize="0.8em"
	  size={"lg"}
    >
      Pro
    </Badge>
  )}

  <Box position="relative"  sx={{
      "&::before": {
        transition: "background-color 1s ease-in-out",
      },
    }} _hover={{ "&::before": {
      content: '""',
      position: "absolute",
      top: 0,
      left: 0,
      right: 0,
      bottom: 0,
      bg: "rgba(0, 0, 0, 0.4)", // Lighter overlay color
      zIndex: 1,
    }}}>
		<Box position={"relative"}>


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
          Get Started
        </Button>
        {template.preview_link && (
          <Button color={"white"} variant="outline" onClick={() => window.open(template.preview_link, "_blank")} _hover={{color:"black",bg:"white"}}>
            Preview
          </Button>
        )}
      </HStack>
    )}
	</Box>
  </Box>

  <Box p={4}>
    <Text fontWeight="bold" mb={2} fontSize={"md"}>
      {template.title}
    </Text>
    <Text fontSize="sm" mb={2} fontWeight={"semibold"}>
      {template.description}
    </Text>
  </Box>
</Box>


        ))}
      </SimpleGrid>


      <Modal isCentered isOpen={isOpen} onClose={onClose} size="lg">
        <ModalOverlay />
        <ModalContent>
          <ModalHeader textAlign="center">
            Uplift your form experience to the next level.
          </ModalHeader>
          <ModalCloseButton />
          <ModalBody>
            <Box mb={4}>
              <Input
                value={formTemplateName}
                onChange={(e) => setFormTemplateName(e.target.value)}
                placeholder="Give it a name."
                size="md"
              />
            </Box>
            <Divider mb={4} />
            <Text mb={2}>This form template requires the following addons:</Text>
            <Box borderWidth="1px" borderRadius="md" overflow="hidden" mb={4}>
              <PluginStatus requiredPlugins={requiredPlugins} onActivateAndContinue={handleFormTemplateSave} />
            </Box>
          </ModalBody>
        </ModalContent>
      </Modal>
    </Box>
  );
};

export default TemplateList;
