/**
 *  External Dependencies
 */
import { Box, Heading, SimpleGrid, Stack } from "@chakra-ui/react";
import { __ } from "@wordpress/i18n";
import React, { useContext, useEffect } from "react";

/**
 *  Internal Dependencies
 */
import { PLUGINS, THEMES } from "../../Constants/Products";
import ProductCard from "./components/ProductCard";
import DashboardContext from "./../../context/DashboardContext";
import { actionTypes } from "../../reducers/DashboardReducer";

const Products = () => {
	const { plugins, themes } =
		typeof _EVF_DASHBOARD_ !== "undefined" && _EVF_DASHBOARD_;
	const [{ pluginsStatus, themesStatus }, dispatch] =
		useContext(DashboardContext);
	useEffect(() => {
		dispatch(
			{
				type: actionTypes.GET_PLUGINS_STATUS,
				pluginsStatus: plugins,
			},
			{
				type: actionTypes.GET_THEMES_STATUS,
				themeStatus: themes,
			}
		);
	}, [pluginsStatus, themesStatus]);
	return (
		<Stack my="8" mx="6">
			<Box>
				<Heading size="md" fontSize="xl" fontWeight="semibold" mb="8">
					{__("Plugins", "everest-forms")}
				</Heading>
				<SimpleGrid
					columns={{ base: 1, md: 2, lg: 3, xl: 4 }}
					spacing="5"
				>
					{PLUGINS.map((plugin) => (
						<ProductCard
							key={plugin.slug}
							{...plugin}
							pluginsStatus={pluginsStatus}
							themesStatus={themesStatus}
						/>
					))}
				</SimpleGrid>
			</Box>
			<Box>
				<Heading size="md" fontSize="xl" fontWeight="semibold" my="8">
					{__("Themes", "everest-forms")}
				</Heading>
				<SimpleGrid
					columns={{ base: 1, md: 2, lg: 3, xl: 4 }}
					spacing="5"
				>
					{THEMES.map((theme) => (
						<ProductCard
							key={theme.slug}
							{...theme}
							pluginsStatus={pluginsStatus}
							themesStatus={themesStatus}
						/>
					))}
				</SimpleGrid>
			</Box>
		</Stack>
	);
};

export default Products;
