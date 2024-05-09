import React from "react";
import { chakra, forwardRef } from "@chakra-ui/react";
import { createElement } from "@wordpress/element";

export const EverestFormIcon = createElement(
	"svg",
	{ width: 24, height: 24, viewBox: "0 0 24 24" },
	createElement("path", {
		fill: "#7e3bd0",
		d: "M18.1 4h-3.8l1.2 2h3.9zM20.6 8h-3.9l1.2 2h3.9zM20.6 18H5.8L12 7.9l2.5 4.1H12l-1.2 2h7.3L12 4.1 2.2 20h19.6z",
	}),
);
export const ContactForm = (props) => (
	<chakra.svg
		xmlns="http://www.w3.org/2000/svg"
		viewBox="0 0 24 24"
		fill="#475BB2"
		h="6"
		w="6"
		{...props}
	>
		<path
			fillRule="evenodd"
			d="M9.455 2a1.818 1.818 0 0 0-1.819 1.818h-.909A2.727 2.727 0 0 0 4 6.545v12.728A2.727 2.727 0 0 0 6.727 22h10.91a2.727 2.727 0 0 0 2.727-2.727V6.545a2.727 2.727 0 0 0-2.728-2.727h-.909A1.818 1.818 0 0 0 14.91 2H9.455Zm7.272 3.636a1.818 1.818 0 0 1-1.818 1.819H9.455a1.818 1.818 0 0 1-1.819-1.819h-.909a.91.91 0 0 0-.909.91v12.727a.91.91 0 0 0 .91.909h10.908a.91.91 0 0 0 .91-.91V6.546a.91.91 0 0 0-.91-.909h-.909Zm-7.272-.909v.91h5.454v-1.82H9.455v.91Zm-1.819 6.364a.91.91 0 0 1 .91-.91h7.272a.91.91 0 1 1 0 1.819H8.545a.91.91 0 0 1-.909-.91Zm.91 3.636a.91.91 0 1 0 0 1.819h7.272a.91.91 0 0 0 0-1.819H8.545Z"
			clipRule="evenodd"
		></path>
	</chakra.svg>
);
export const FrontendListing = (props) => (
	<chakra.svg
		xmlns="http://www.w3.org/2000/svg"
		viewBox="0 0 24 24"
		fill="#475BB2"
		h="6"
		w="6"
		{...props}
	>
		<path
			fillRule="evenodd"
			d="M4 2a2 2 0 0 0-2 2v5a2 2 0 0 0 2 2h5a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H4Zm0 2h5v5H4V4Zm0 9a2 2 0 0 0-2 2v5a2 2 0 0 0 2 2h5a2 2 0 0 0 2-2v-5a2 2 0 0 0-2-2H4Zm0 2h5v5H4v-5Z"
			clipRule="evenodd"
		></path>
		<path
			fillRule="evenodd"
			d="M13 4a1 1 0 0 1 1-1h7a1 1 0 1 1 0 2h-7a1 1 0 0 1-1-1Zm1 4a1 1 0 1 0 0 2h7a1 1 0 1 0 0-2h-7Zm-1 7a1 1 0 0 1 1-1h7a1 1 0 1 1 0 2h-7a1 1 0 0 1-1-1Zm1 4a1 1 0 1 0 0 2h7a1 1 0 1 0 0-2h-7Z"
			clipRule="evenodd"
		></path>
	</chakra.svg>
);
export const LoginForm = (props) => (
	<chakra.svg
		xmlns="http://www.w3.org/2000/svg"
		viewBox="0 0 24 24"
		fill="#475BB2"
		h="6"
		w="6"
		{...props}
	>
		<path
			fillRule="evenodd"
			d="M8.545 9.273a.91.91 0 1 0 0 1.818h7.273a.91.91 0 1 0 0-1.818H8.545Zm-.909 4.545a.91.91 0 0 1 .91-.909h7.272a.91.91 0 1 1 0 1.818H8.545a.91.91 0 0 1-.909-.909Zm.909 2.728a.91.91 0 1 0 0 1.818h1.819a.91.91 0 1 0 0-1.819H8.545Z"
			clipRule="evenodd"
		></path>
		<path
			fillRule="evenodd"
			d="M7.636 3.818C7.636 2.814 8.45 2 9.455 2h5.454c1.004 0 1.818.814 1.818 1.818h.91a2.727 2.727 0 0 1 2.727 2.727v12.728A2.727 2.727 0 0 1 17.636 22H6.727A2.727 2.727 0 0 1 4 19.273V6.545a2.727 2.727 0 0 1 2.727-2.727h.91Zm7.273 3.637a1.818 1.818 0 0 0 1.818-1.819h.91a.91.91 0 0 1 .909.91v12.727a.91.91 0 0 1-.91.909H6.727a.91.91 0 0 1-.909-.91V6.546a.91.91 0 0 1 .91-.909h.908c0 1.005.814 1.819 1.819 1.819h5.454ZM9.455 5.636V3.818h5.454v1.818H9.455Z"
			clipRule="evenodd"
		></path>
	</chakra.svg>
);
