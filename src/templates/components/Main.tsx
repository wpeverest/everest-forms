import React, { useState, useEffect, useCallback } from "react";
import { Box, Flex, Button } from "@chakra-ui/react";
import Sidebar from "./Sidebar";
import TemplateList from "./TemplateList";
import apiFetch from "@wordpress/api-fetch";
import { templatesScriptData } from "../utils/global";
import debounce from "lodash.debounce";

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

const Main = () => {
  const [selectedCategory, setSelectedCategory] = useState<string>("All Forms");
  const [categories, setCategories] = useState<Category[]>([]);
  const [templates, setTemplates] = useState<Template[]>([]);
  const [filteredTemplates, setFilteredTemplates] = useState<Template[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [searchTerm, setSearchTerm] = useState<string>("");

  const handleCategorySelect = (category: string) => {
    setSelectedCategory(category);
  };

  const handleSearchChange = (term: string) => {
    setSearchTerm(term);
  };

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

  useEffect(() => {
    const result = templates.filter(template =>
      (selectedCategory === "All Forms" || template.categories.includes(selectedCategory)) &&
      template.title.toLowerCase().includes(searchTerm.toLowerCase())
    );
    setFilteredTemplates(result);
  }, [selectedCategory, searchTerm, templates]);

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
