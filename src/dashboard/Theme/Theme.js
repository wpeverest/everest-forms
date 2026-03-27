import { extendTheme } from '@chakra-ui/react';

const Theme = extendTheme({
	colors: {
		primary: {
			15: '#FAF8FC',
			25: '#f6f3fa',
			50: '#eee8f7',
			100: '#cab7e5',
			200: '#b094d8',
			300: '#8c64c6',
			400: '#7545bb',
			500: '#5317aa',
			600: '#4c159b',
			700: '#3b1079',
			800: '#2e0d5e',
			900: '#230a47',
		},
		grey: {
			0: '#FFFFFF',
			15: '#FDFDFD',
			25: '#F4F4F4',
			50: '#E9E9E9',
			60: '#E1E1E1',
			75: '#BDBDBD',
			100: '#BABABA',
			150: '#8A8A8A',
			175: '#7A7A7A',
			200: '#999999',
			300: '#6B6B6B',
			350: '#5C5C5C',
			400: '#383838',
			500: '#222222',
			600: '#1F1F1F',
			700: '#181818',
			800: '#131313',
			900: '#0E0E0E',
		},
		orange: '#ff8c39',
	},
	styles: {
		global: {
			'.wp-admin #everest-forms': {
				ms: '-20px',
			},
			'.toplevel_page_everest-forms #wpwrap': {
				bgColor: 'primary.50',
			},
			'.ba-modal-open': {
				'#adminmenuwrap': {
					zIndex: 999,
				},
			},
		},
	},
	components: {
		Button: {
			baseStyle: {
				borderRadius: 'base',
				fontWeight: 'medium',
				_focus: {
					boxShadow: 'none',
				},
			},
			variants: {
				solid: (props) => {
					const { colorScheme } = props;
					if (colorScheme === 'primary') {
						return {
							bg: 'primary.400',
							color: 'white',
							_hover: {
								bg: 'primary.500',
								_disabled: {
									bg: 'primary.400',
								},
							},
							_active: {
								bg: 'primary.600',
							},
						};
					}
				},
				outline: (props) => {
					const { colorScheme } = props;
					if (colorScheme === 'primary') {
						return {
							borderColor: 'primary.400',
							color: 'primary.400',
							_hover: {
								bg: 'primary.15',
							},
							_active: {
								bg: 'primary.25',
							},
						};
					}
				},
				ghost: (props) => {
					const { colorScheme } = props;
					if (colorScheme === 'primary') {
						return {
							color: 'primary.400',
							_hover: {
								bg: 'primary.15',
							},
							_active: {
								bg: 'primary.25',
							},
						};
					}
				},
			},
			sizes: {
				sm: {
					fontSize: 'sm',
					px: 3,
					py: 2,
				},
				md: {
					fontSize: 'md',
					px: 4,
					py: 2,
				},
				lg: {
					fontSize: 'lg',
					px: 6,
					py: 3,
				},
			},
			defaultProps: {
				colorScheme: 'primary',
			},
		},
		Heading: {
			baseStyle: {
				margin: 0,
			},
		},
		Text: {
			baseStyle: {
				margin: 0,
			},
		},
	},
});

export default Theme;
