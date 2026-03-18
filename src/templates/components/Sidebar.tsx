import React, { useState, useCallback } from "react";
import { Box, VStack, HStack, Text, Spacer, Input, InputLeftElement, InputGroup, Badge, CardHeader,CardFooter,Button,Card,Heading } from "@chakra-ui/react";
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
     <InputGroup mb="26px">
		<InputLeftElement pointerEvents="none" padding="0px 0px 0px 8px" borderRadius="8px" borderColor="#B0B0B0">
		<IoSearchOutline style={{ width: "18px", height: "18px" }} color="#737373" />
		</InputLeftElement>
		<Input
			placeholder={__("Search Templates", "everest-forms")}
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

      <VStack align="stretch" gap="4px">
        {orderedCategories.map((category) => (
          <HStack
            key={category.name}
			p="10px 10px 10px 16px"
			background="#f5f5f5"
            _hover={{
				bg: "#f5f5f5",
				"& > .badge": {
				  bg: selectedCategory === category.name ? "#FFFFFF" : "#FFFFFF"
				},
				
				"& > .evf-category-list": {
				  color: "#383838"
				}
			}}
            borderRadius="md"
            cursor="pointer"
			justifyContent="space-between"
            bg={selectedCategory === category.name ? "#f5f5f5" : "transparent"}
            onClick={() => onCategorySelect(category.name)}
          >
            <Text className="evf-category-list" color={selectedCategory === category.name ? "#7545BB" : "gray.600"} fontSize="14px" lineHeight="22px" fontWeight="medium" margin="0px">{category.name}</Text>
            
            <Badge className="badge" display="flex" alignItems="center" justifyContent="center" fontWeight="semibold" width="32px" height="24px" padding="0px" borderRadius="6px" color={selectedCategory === category.name ? "#7545BB" : ""} bg={selectedCategory === category.name ? "white" : "#F5F5F5"} >{category.count}</Badge>
          </HStack>
        ))}
		<Card
				align='center'
				marginTop="26px"
				padding="16px"
				bg="linear-gradient(135deg, rgba(96,64,240,0.08), rgba(61,126,245,0.06))"
				border="1px solid rgba(96,64,240,0.18)"
				borderRadius="9px"
				boxShadow="none"
				>
				<CardHeader padding="0px" marginBottom="12px">
					<Heading as="h5" fontSize="16px" color="#0f0f1a" lineHeight="26px" fontWeight="semibold"  padding="0px" margin="0px 0px 6px">
					{__("Can't find a template?", "everest-forms")}
					</Heading>
					<Text fontSize="14px" lineHeight="22px" color="#6b6b85" margin="0">{__('Request a custom template built for your needs.', 'everest-forms')}</Text>
				</CardHeader>
				<CardFooter padding="0" width="100%">
				<a
						href="https://everestforms.net/request-template"
						target="_blank"
						rel="noopener noreferrer"
						className="evf-custom-template"
						style={{
							display: "flex",
							alignItems: "center",
							justifyContent: "center",
							borderRadius:"4px",
							width: "100%",
							padding: "0px 12px",
							background: "linear-gradient(135deg, #8c40f0, #7d3df5)",
							color: "white",
							fontSize: "14px",
							lineHeight: "24px",
							fontWeight: "medium",
							height: "34px"							
						}}
						onFocus={(e) => {
							e.currentTarget.style.outline = "none";
							e.currentTarget.style.boxShadow = "none";
						}}									
						>
							✦ {__("Request Template","everest-forms")}
					</a>
				</CardFooter>
				</Card>
      </VStack>
    </Box>
  );
});

export default Sidebar;
