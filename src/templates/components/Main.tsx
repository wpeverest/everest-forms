import React, { useState, useEffect } from "react";
import { Box, Flex } from "@chakra-ui/react";
import Sidebar from "./Sidebar";
import TemplateList from "./TemplateList";
import apiFetch from "@wordpress/api-fetch";
import { templatesScriptData } from "../utils/global";

const { restURL, security } = templatesScriptData;

interface Category {
  name: string;
  count: number;
}

interface Template {
  title: string;
  slug: string;
  imageUrl: string;
  description: string;
  isPro: boolean;
  categories: string[];
}

interface ApiResponse {
  templates: { category: string; templates: Template[] }[];
}

interface MainProps {
  filter: string;
}

const Main: React.FC<MainProps> = ({ filter }) => {
  const [selectedCategory, setSelectedCategory] = useState<string>("All Forms");
  const [categories, setCategories] = useState<Category[]>([]);
  const [templates, setTemplates] = useState<Template[]>([]);
  const [filteredTemplates, setFilteredTemplates] = useState<Template[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [searchTerm, setSearchTerm] = useState<string>("");

  // Handle category selection
  const handleCategorySelect = (category: string) => {
    setSelectedCategory(category);
  };

  // Handle search input change
  const handleSearchChange = (term: string) => {
    setSearchTerm(term);
  };

  // Fetch data from API
  useEffect(() => {
    const fetchData = async () => {
      try {
        setIsLoading(true);

        const response = (await apiFetch({
          path: `${restURL}everest-forms/v1/templates`,
          method: "GET",
          headers: {
            "X-WP-Nonce": security,
          },
        })) as ApiResponse;

        if (response && Array.isArray(response.templates)) {
          const allTemplates = response.templates.flatMap((category) => category.templates);
          setTemplates(allTemplates);

          // Create unique category list
          const categoriesSet = new Set<string>();
          allTemplates.forEach(template => {
            template.categories.forEach(category => categoriesSet.add(category));
          });

          const categoriesList = Array.from(categoriesSet).map((category) => ({
            name: category,
            count: allTemplates.filter(template => template.categories.includes(category)).length,
          }));

          setCategories([{ name: "All Forms", count: allTemplates.length }, ...categoriesList]);
        } else {
          throw new Error("Unexpected response format.");
        }
      } catch (error) {
        setError("Failed to load templates. Please try again later.");
        console.error("Error fetching templates:", error);
      } finally {
        setIsLoading(false);
      }
    };

    fetchData();
  }, []);

  // Filter templates based on selected category, search term, and filter type
  useEffect(() => {
    const result = templates.filter(template =>
      (selectedCategory === "All Forms" || template.categories.includes(selectedCategory)) &&
      template.title.toLowerCase().includes(searchTerm.toLowerCase()) &&
      (filter === "All" || (filter === "Free" && !template.isPro) || (filter === "Premium" && template.isPro))
    );
    setFilteredTemplates(result);
  }, [selectedCategory, searchTerm, templates, filter]);

  if (isLoading) {
    return <div>Loading...</div>;
  }

  if (error) {
    return <div>{error}</div>;
  }

  return (
    <Box>
      <Flex>
        <Box mr={4}>
          <Sidebar
            categories={categories}
            onCategorySelect={handleCategorySelect}
            onSearchChange={handleSearchChange}
          />
        </Box>
        <Box flex={1}>
          <TemplateList selectedCategory={selectedCategory} templates={filteredTemplates} />
        </Box>
      </Flex>
    </Box>
  );
};

export default Main;
