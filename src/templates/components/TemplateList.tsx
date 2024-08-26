import React, { useState, useCallback } from "react";
import {
  SimpleGrid,
  Box,
  Image,
  Text,
  Badge,
  Button,
  Center,
  Modal,
  ModalOverlay,
  ModalContent,
  ModalHeader,
  ModalBody,
  ModalCloseButton,
  useDisclosure,
  Input,
  VStack,
  HStack
} from "@chakra-ui/react";
import apiFetch from "@wordpress/api-fetch";
import { templatesScriptData } from "../utils/global";

interface Template {
  id: number;
  title: string;
  slug: string;
  imageUrl: string;
  description: string;
  isPro: boolean;
  preview_link?: string;
}

interface TemplateListProps {
  selectedCategory: string;
  templates: Template[];
}

const { restURL, security } = templatesScriptData;


const TemplateList: React.FC<TemplateListProps> = ({ selectedCategory, templates }) => {
  const [previewTemplate, setPreviewTemplate] = useState<Template | null>(null);
  const [showInputs, setShowInputs] = useState(false);
  const [inputTitle, setInputTitle] = useState("");
  const [inputSubtitle, setInputSubtitle] = useState("");
  const { isOpen, onOpen, onClose } = useDisclosure();
  const [hoverCardId, setHoverCardId] = useState<number|null>();
  const [selectedCardId,setSelectedCardId] = useState<object|any> (null);
  const [formTemplateName,setFormTemplateNam] = useState<string>()

  const handlePreviewClick = useCallback((template: Template) => {
    setPreviewTemplate(template);
    onOpen();
  }, [onOpen]);

  const handleGetStartedClick = useCallback((templateId:number) => {
   setSelectedCardId(templateId)
  }, []);

  const handlePreviewClose = useCallback(() => {
    setShowInputs(false);
    setPreviewTemplate(null);
    onClose();
  }, [onClose]);

const handleFormTemplateSave = async ()=>{
	const response = (await apiFetch({
		path: `${restURL}everest-forms/v1/templates`,
		method: "POST",
		body :JSON.stringify({
			form_template_name: formTemplateName,
		}),
		headers: {
		  "X-WP-Nonce": security,
		},
	  })) as any;

}



  return (
    <Box>
      <SimpleGrid columns={[1, 2, 3]} spacing={4}>
        {templates.map((template) => (
          <Box
            key={template.slug}
            borderWidth={1}
            borderRadius="md"
            overflow="hidden"
            position="relative"
            _hover={{ bg: "gray.50" }}
            onMouseOver={() => setHoverCardId(template?.id)}
            onMouseLeave={() => setHoverCardId(null)}
          >
            <Image src={template.imageUrl} alt={template.title} />
            <Box p={4}>
              <Text fontWeight="bold" mb={2}>
                {template.title}
              </Text>
              <Text fontSize="sm" mb={2}>
                {template.description}
              </Text>
              {template.isPro && <Badge colorScheme="green" mb={2}>Pro</Badge>}
              {hoverCardId === template?.id && (
                <VStack
                  spacing={2}
                  mt={4}
                  position="absolute"
                  bottom={4}
                  left={4}
                  zIndex={2}


                >
                  <Button colorScheme="purple" onClick={()=> handleGetStartedClick(template?.id)}>
                    Get Started
                  </Button>

				  {
					template?.preview_link &&  <Button variant="outline" onClick={() => window.open(template?.preview_link,"_blank")}  >
                    Preview
                  </Button>
				  }

                </VStack>
              )}
            </Box>
          </Box>
        ))}
      </SimpleGrid>

      {/* Modal for Preview */}
      <Modal isOpen={Boolean(selectedCardId)} onClose={()=>setSelectedCardId(null)}>
        <ModalOverlay />
        <ModalContent>
          <ModalHeader>{previewTemplate ? previewTemplate.title : "Preview"}</ModalHeader>
          <ModalCloseButton />
          <ModalBody>
       <HStack>
		<Input value={formTemplateName} onChange={e=>setFormTemplateNam(e.target.value)}/>
		<Button onClick={handleFormTemplateSave}>Click</Button>

	   </HStack>
          </ModalBody>
        </ModalContent>
      </Modal>
    </Box>
  );
};

export default TemplateList;
