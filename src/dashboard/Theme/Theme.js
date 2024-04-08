import { extendTheme } from "@chakra-ui/react";

const Theme = extendTheme({
  colors: {
    primary: {
		50: '#fafafc',
		100: '#e8eefd',
		200: '#b9cdf9',
		300: '#8aabf4',
		400: '#5c8af0',
		500: '#2563eb',
		600: '#134fd2',
		700: '#0f3ea3',
		800: '#0b2c75',
		900: '#061a46',
	},
  },
  styles: {
    global: {
      ".wp-admin #everest-forms": {
        ms: "-20px"
      },
      ".toplevel_page_everest-forms #wpwrap": {
        bgColor: "primary.50"
      },
      ".ba-modal-open": {
        "#adminmenuwrap": {
          zIndex: 999
        }
      }
    }
  },
  components: {
    Button: {
      baseStyle: {
        borderRadius: "base"
      }
    },
    Heading: {
      baseStyle: {
        margin: 0
      }
    },
    Text: {
      baseStyle: {
        margin: 0
      }
    }
  }
});

export default Theme;
