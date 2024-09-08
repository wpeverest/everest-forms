import React, { useState, useCallback } from "react";
import { Box, VStack, HStack, Text, Spacer, Input, InputLeftElement, InputGroup, Badge } from "@chakra-ui/react";
import { FaSearch } from 'react-icons/fa';
import debounce from "lodash.debounce";
import { __ } from '@wordpress/i18n';
interface SidebarProps {
  categories: { name: string; count: number }[];
  selectedCategory: string;
  onCategorySelect: (category: string) => void;
  onSearchChange: (searchTerm: string) => void;
}

const Sidebar: React.FC<SidebarProps> = React.memo(({ categories, selectedCategory, onCategorySelect, onSearchChange }) => {
  const [searchTerm, setSearchTerm] = useState<string>("");

  const debouncedSearchChange = useCallback(
    debounce((value: string) => {
      onSearchChange(value);
    }, 300),
    [onSearchChange]
  );

  const handleSearchChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const value = e.target.value;
    setSearchTerm(value);
    debouncedSearchChange(value);
  };

  const favorites = categories.find(cat => cat.name === 'Favorites');

  const orderedCategories = favorites && favorites.count > 0
    ? [favorites, ...categories.filter(cat => cat.name !== 'Favorites')]
    : categories;

  return (
    <Box>
      <InputGroup mb={4}>
        <InputLeftElement pointerEvents="none">
          <FaSearch color="gray.300" />
        </InputLeftElement>
        <Input
          placeholder={__("Search Templates", "everest-forms")}
          value={searchTerm}
          onChange={handleSearchChange}
        />
      </InputGroup>
      <VStack align="stretch" spacing={2}>
        {orderedCategories.map((category) => (
          <HStack
            key={category.name}
            p="3px"
            _hover={{ bg: "gray.100" }}
            borderRadius="md"
            cursor="pointer"
            bg={selectedCategory === category.name ? "gray.200" : "transparent"}
            onClick={() => onCategorySelect(category.name)}
          >
            <Text fontWeight="semibold">{category.name}</Text>
            <Spacer />
            <Badge color="gray.500">{category.count}</Badge>
          </HStack>
        ))}
      </VStack>
    </Box>
  );
});

export default Sidebar;
