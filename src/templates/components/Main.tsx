import React, { useState, useCallback, useMemo } from "react";
import { Box, Flex, Spinner } from "@chakra-ui/react";
import Sidebar from "./Sidebar";
import TemplateList from "./TemplateList";
import { useQuery } from '@tanstack/react-query';
import apiFetch from "@wordpress/api-fetch";
import { templatesScriptData } from "../utils/global";
import { __ } from '@wordpress/i18n';

const { restURL, security } = templatesScriptData;

const fetchTemplates = async () => {
    const response = (await apiFetch({
        path: `${restURL}everest-forms/v1/templates`,
        method: "GET",
        headers: {
            "X-WP-Nonce": security,
        },
    })) as { templates: { category: string; templates: Template[] }[] };

    if (response && Array.isArray(response.templates)) {
        const allTemplates = response.templates.flatMap((category) => category.templates);
        return allTemplates;
    } else {
        throw new Error(__("Unexpected response format.", "everest-forms"));
    }
};

const Main: React.FC<{ filter: string }> = ({ filter }) => {
    const [state, setState] = useState({
        selectedCategory: __("All Forms", "everest-forms"),
        searchTerm: ""
    });

    const { selectedCategory, searchTerm } = state;

    const { data: templates = [], isLoading, error } = useQuery(['templates'], fetchTemplates);

    const categories = useMemo(() => {
        const categoriesSet = new Set<string>();
        templates.forEach(template => {
            template.categories.forEach(category => categoriesSet.add(category));
        });

        return [
            { name: __("All Forms", "everest-forms"), count: templates.length },
            ...Array.from(categoriesSet).map((category) => ({
                name: category,
                count: templates.filter(template => template.categories.includes(category)).length,
            }))
        ];
    }, [templates]);

    const filteredTemplates = useMemo(() => {
        return templates.filter(template =>
            (selectedCategory === __("All Forms", "everest-forms") || template.categories.includes(selectedCategory)) &&
            template.title.toLowerCase().includes(searchTerm.toLowerCase()) &&
            (filter === "All" || (filter === "Free" && !template.isPro) || (filter === "Premium" && template.isPro))
        );
    }, [selectedCategory, searchTerm, templates, filter]);

    const handleCategorySelect = useCallback((category) => {
        setState(prevState => ({ ...prevState, selectedCategory: category }));
    }, []);

    const handleSearchChange = useCallback((searchTerm) => {
        setState(prevState => ({ ...prevState, searchTerm }));
    }, []);

    if (isLoading) return (
        <Flex justify="center" align="center" height="100vh">
            <Spinner size="xl" />
        </Flex>
    );
    if (error) return <div>{(error as Error).message}</div>;

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
