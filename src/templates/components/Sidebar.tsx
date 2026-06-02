import React, { useState, useCallback } from "react";
import { Box, VStack, HStack, Text, Spacer, Input, InputLeftElement, InputGroup, Badge, Button } from "@chakra-ui/react";
import { IoSearchOutline } from "react-icons/io5";
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
      <InputGroup mb="20px">
        <InputLeftElement pointerEvents="none" padding="0px 0px 0px 8px" borderRadius="8px" borderColor="#B0B0B0">
          <IoSearchOutline style={{ width: "18px", height: "18px" }} color="#737373" />
        </InputLeftElement>
        <Input
          placeholder={__("Search templates", "everest-forms")}
          value={searchTerm}
          onChange={handleSearchChange}
          fontSize="14px"
          lineHeight="24px"
          border="1px solid #e1e1e1"
          borderRadius="4px"
          height="38px"
          padding="0 12px 0 40px"
          _focus={{
            borderColor: "#7545BB",
            outline: "none",
            boxShadow: "none",
          }}
        />
      </InputGroup>

      <Text
        fontSize="11px"
        fontWeight="700"
        color="#9999aa"
        textTransform="uppercase"
        letterSpacing="0.6px"
        margin="0 0 8px 2px"
      >
        {__("Categories", "everest-forms")}
      </Text>

      <VStack align="stretch" gap="2px">
        {orderedCategories.map((category) => {
          const isActive = selectedCategory === category.name;
          return (
            <HStack
              key={category.name}
              p="10px 12px"
              background={isActive ? "#7545BB" : "transparent"}
              _hover={{ bg: isActive ? "#6a3daa" : "#f5f5f5" }}
              borderRadius="8px"
              cursor="pointer"
              justifyContent="space-between"
              onClick={() => onCategorySelect(category.name)}
              transition="all 0.2s"
            >
              <Text
                className="evf-category-list"
                color={isActive ? "white" : "#646970"}
                fontSize="14px"
                lineHeight="22px"
                fontWeight={isActive ? "600" : "500"}
                margin="0px"
              >
                {category.name}
              </Text>

              <Badge
                className="badge"
                display="flex"
                alignItems="center"
                justifyContent="center"
                fontWeight="600"
                fontSize="12px"
                width="28px"
                height="22px"
                padding="0px"
                borderRadius="4px"
                color={isActive ? "white" : "#646970"}
                bg={isActive ? "rgba(255,255,255,0.2)" : "#e8e8e8"}
              >
                {category.count}
              </Badge>
            </HStack>
          );
        })}
      </VStack>
    </Box>
  );
});

export default Sidebar;
