import React, { useState, useCallback } from "react";
import { Box, VStack, HStack, Text, Spacer, Input,InputLeftElement,InputGroup } from "@chakra-ui/react";
import { FaSearch } from 'react-icons/fa';
import debounce from "lodash.debounce";

interface SidebarProps {
  categories: { name: string; count: number }[];
  onCategorySelect: (category: string) => void;
  onSearchChange: (searchTerm: string) => void;
}

const Sidebar: React.FC<SidebarProps> = ({ categories, onCategorySelect, onSearchChange }) => {
  const [searchTerm, setSearchTerm] = useState<string>("");

  // Debounced search function
  const debouncedSearchChange = useCallback(
    debounce((value: string) => {
      onSearchChange(value); // Call the parent function with the debounced value
    }, 300),
    [onSearchChange]
  );

  // Handle search input change
  const handleSearchChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const value = e.target.value;
    setSearchTerm(value);
    debouncedSearchChange(value);
  };

  return (
    <Box>
    <InputGroup mb={4}>
      <InputLeftElement pointerEvents="none">
        <FaSearch color="gray.300" />
      </InputLeftElement>
      <Input
        placeholder="Search Templates"
        value={searchTerm}
        onChange={handleSearchChange}
      />
    </InputGroup>
      <VStack align="stretch" spacing={2}>
        {categories.map((category) => (
          <HStack
            key={category.name}
            p={2}
            _hover={{ bg: "gray.100" }}
            borderRadius="md"
            cursor="pointer"
            onClick={() => onCategorySelect(category.name)}
          >
            <Text>{category.name}</Text>
            <Spacer />
            <Text color="gray.500">{category.count}</Text>
          </HStack>
        ))}
      </VStack>
    </Box>
  );
};

export default Sidebar;
