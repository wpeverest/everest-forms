/**
 *  External Dependencies
 */
import React, { useEffect, useRef, useState } from "react";
import {
	HStack,
	IconButton,
	Link,
	Menu,
	MenuButton,
	MenuItem,
	MenuList,
} from "@chakra-ui/react";
import { NavLink, useLocation } from "react-router-dom";
import PropTypes from "prop-types";

/**
 *  Internal Dependencies
 */
import { DotsHorizontal } from "../Icon/Icon";
import { convertRoute, isExternalRoute } from "../../Constants";

const IntersectionStyles = {
	visible: {
		order: 0,
		visibility: "visible",
		opacity: 1,
	},
	inVisible: {
		order: 100,
		visibility: "hidden",
		pointerEvents: "none",
	},
	toolbarWrapper: {
		overflow: "hidden",
		display: "flex",
		border: "1px solid black",
		alignItem: "center",
	},
	overflowStyle: {
		order: 99,
		position: "sticky",
		right: "0",
		backgroundColor: "white",
	},
};

const IntersectObserver = ({ children, routes }) => {
	const ref = useRef(null);
	const [visibleMap, setVisibleMap] = useState({});
	const location = useLocation();
	const hiddenRoutes = routes.filter((route) => !visibleMap[route.route]);

	const selectedHiddenRoute = hiddenRoutes.find(
		(h) => h.route === location.pathname
	);

	const { pageType, adminURL } =
		typeof _EVF_DASHBOARD_ !== "undefined" && _EVF_DASHBOARD_;
	const isSettingsPage = pageType === "settings";
	const isEntriesPage = pageType === "entries";
    const isFormsPage = pageType === "forms";
	const isAnalyticsPage = pageType === "analytics";
	const isNonDashboardPage = isSettingsPage || isEntriesPage || isAnalyticsPage || isFormsPage;

	useEffect(() => {
		if (!ref.current) return;
		const observer = new IntersectionObserver(
			(entries) => {
				const updatedEntries = {};
				entries.forEach((entry) => {
					const target = entry.target.dataset?.["target"];
					if (entry.isIntersecting && target) {
						updatedEntries[target] = true;
					}
					if (!entry.isIntersecting && target) {
						updatedEntries[target] = false;
					}
				});

				setVisibleMap((prev) => ({
					...prev,
					...updatedEntries,
				}));
			},
			{
				root: ref.current,
				threshold: 0.98,
			}
		);

		Array.from(ref.current.children).forEach((item) => {
			if (item.getAttribute("data-target")) {
				observer.observe(item);
			}
		});

		return () => observer.disconnect();
	}, []);

	const shouldShowMenu = Object.values(visibleMap).some((v) => v === false);

	return (
		<HStack
			ref={ref}
			width={{
				base: "50px",
				sm: "240px",
				md: "520px",
				lg: "570px",
				xl: "680px",
			}}
			overflow={"hidden"}
			h="full"
		>
			{React.Children.map(children, (child) => {
				const otherSX = visibleMap[child.props["data-target"]]
					? IntersectionStyles.visible
					: IntersectionStyles.inVisible;

				return React.cloneElement(child, {
					sx: { ...children?.props?.sx, ...otherSX },
				});
			})}

		</HStack>
	);
};

IntersectObserver.propTypes = {
	children: PropTypes.any,
	routes: PropTypes.arrayOf(
		PropTypes.shape({
			label: PropTypes.string.isRequired,
			route: PropTypes.string.isRequired,
		})
	).isRequired,
};

export default IntersectObserver;
