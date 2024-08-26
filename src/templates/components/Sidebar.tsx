import React, { useState, useCallback } from "react";
import { Box, VStack, HStack, Text, Spacer, Input } from "@chakra-ui/react";
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
    setSearchTerm(value); // Update local searchTerm
    debouncedSearchChange(value); // Trigger debounced search
  };

  return (
    <Box width="200px">
      <Input
        placeholder="Search Templates"
        mb={4}
        value={searchTerm}
        onChange={handleSearchChange}
      />
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
