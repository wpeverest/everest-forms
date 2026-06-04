import React from "react";
import { Box, VStack, HStack, Text, Icon } from "@chakra-ui/react";
import { LuSparkles } from 'react-icons/lu';
import { __ } from '@wordpress/i18n';

interface SidebarProps {
  categories: { name: string; count: number }[];
  selectedCategory: string;
  onCategorySelect: (category: string) => void;
  onRequestTemplate?: () => void;
}

const Sidebar: React.FC<SidebarProps> = React.memo(({ categories, selectedCategory, onCategorySelect, onRequestTemplate }) => {
  const favorites = categories.find(cat => cat.name === 'Favorites');

  const orderedCategories = favorites && favorites.count > 0
    ? [favorites, ...categories.filter(cat => cat.name !== 'Favorites')]
    : categories;

  return (
    <Box display="flex" flexDirection="column">
      {/* Categories label */}
      <Text
        fontSize="11px"
        fontWeight="600"
        color="#9a9a9a"
        textTransform="uppercase"
        letterSpacing="0.12em"
        mb="2"
        px="2"
        pb="3"
        margin="0 0 8px 0"
      >
        {__("Categories", "everest-forms")}
      </Text>

      {/* Category list */}
      <VStack align="stretch" spacing="0.5">
        {orderedCategories.map((category) => {
          const isActive = selectedCategory === category.name;
          return (
            <HStack
              key={category.name}
              py="12px"
              px="3"
              bg={isActive ? "rgba(117,69,187,0.1)" : "transparent"}
              _hover={{ bg: isActive ? "rgba(117,69,187,0.1)" : "#f8fafc" }}
              borderRadius="8px"
              cursor="pointer"
              justify="space-between"
              onClick={() => onCategorySelect(category.name)}
              transition="background 0.15s"
            >
              <Text
                color={isActive ? "#7545BB" : "#383838"}
                fontSize="14px"
                fontWeight={isActive ? "500" : "400"}
                margin="0"
              >
                {category.name}
              </Text>
              <Text
                fontSize="12px"
                color={isActive ? "rgba(117,69,187,0.7)" : "#999999"}
                margin="0"
              >
                {category.count}
              </Text>
            </HStack>
          );
        })}
      </VStack>

      {/* Can't find a template? CTA */}
      <Box
        mt="20px"
        borderRadius="12px"
        border="1px solid #e2e8f0"
        bgGradient="linear(to-br, rgba(117,69,187,0.06), rgba(117,69,187,0.02))"
        p="16px"
      >
        <Text
          fontSize="14px"
          fontWeight="600"
          color="#0e0e0e"
          margin="0 0 4px 0"
        >
          {__("Can't find a template?", "everest-forms")}
        </Text>
        <Text
          fontSize="12px"
          color="#6b6b6b"
          lineHeight="1.5"
          margin="0 0 12px 0"
        >
          {__("Request a custom template built for your needs.", "everest-forms")}
        </Text>
        <Box
          as="button"
          width="100%"
          display="inline-flex"
          alignItems="center"
          justifyContent="center"
          gap="6px"
          height="36px"
          borderRadius="8px"
          bg="#7545BB"
          color="white"
          fontSize="13px"
          fontWeight="500"
          border="none"
          cursor="pointer"
          onClick={() => onRequestTemplate && onRequestTemplate()}
          _hover={{ bg: "rgba(117,69,187,0.88)" }}
          transition="background 0.2s"
        >
          <Icon as={LuSparkles} boxSize="3.5" />
          {__("Request Template", "everest-forms")}
        </Box>
      </Box>
    </Box>
  );
});

export default Sidebar;
