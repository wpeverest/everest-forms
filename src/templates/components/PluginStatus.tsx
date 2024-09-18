import React, { useEffect, useState } from "react";
import apiFetch from "@wordpress/api-fetch";
import {
  Button,
  useToast,
  Spinner,
  Box,
  Text,
  Table,
  Tbody,
  Tr,
  Td,
  Icon,
  Divider,
  VStack,
} from "@chakra-ui/react";
import { CheckCircleIcon, WarningIcon } from "@chakra-ui/icons";
import { templatesScriptData } from "../utils/global";
import { __, sprintf } from '@wordpress/i18n';

interface PluginStatusProps {
  requiredPlugins: { key: string; value: string }[];
  onActivateAndContinue: () => void;
}

interface PluginStatusResponse {
  success: boolean;
  plugin_status: Record<string, string>;
  message?: string;
}

const { restURL, security } = templatesScriptData;

const PluginStatus: React.FC<PluginStatusProps> = ({
  requiredPlugins,
  onActivateAndContinue,
}) => {
  const [pluginStatuses, setPluginStatuses] = useState<Record<string, string>>({});
  const [loading, setLoading] = useState(false);
  const [installInProgress, setInstallInProgress] = useState(false);
  const [installComplete, setInstallComplete] = useState(false);
  const [buttonLabel, setButtonLabel] = useState("");
  const toast = useToast();

  useEffect(() => {
    const fetchPluginStatus = async () => {
      try {
        const response = await apiFetch<PluginStatusResponse>({
          path: `${restURL}everest-forms/v1/plugin/status`,
          method: "GET",
          headers: {
            "X-WP-Nonce": security,
          },
        });

        if (response.success) {
          setPluginStatuses(response.plugin_status);
          updateButtonLabel(response.plugin_status);
        } else {
		  toast({
			title:__("Error", "everest-forms"),
			description: __("Invalid response format.","everest-forms"),
			status: "error",
			position: "bottom-right",
			duration: 5000,
			isClosable: true,
			variant: "subtle",
		  });
        }
      } catch (error) {
        toast({
          title: __("Error", "everest-forms"),
          description: __("Unable to check plugin status.","everest-forms"),
          status: "error",
          position: "bottom-right",
          duration: 5000,
          isClosable: true,
          variant: "subtle",
        });
      }
    };

    fetchPluginStatus();
  }, [toast]);

  const updateButtonLabel = (statuses: Record<string, string>) => {
    const allActive = requiredPlugins.every(
      (plugin) => statuses[plugin.key] === "active"
    );
    const anyNotInstalled = requiredPlugins.some(
      (plugin) => statuses[plugin.key] === "not-installed"
    );
    const anyInactive = requiredPlugins.some(
      (plugin) => statuses[plugin.key] === "inactive"
    );

    if (allActive) {
      setButtonLabel(__("Continue","everest-forms"));
      setInstallComplete(true);
    } else if (anyNotInstalled) {
      setButtonLabel(__("Install & Activate","everest-forms"));
      setInstallComplete(false);
    } else if (anyInactive) {
      setButtonLabel(__("Activate and Continue","everest-forms"));
      setInstallComplete(false);
    } else {
		setButtonLabel(__("Continue","everest-forms"));
      setInstallComplete(false);
    }
  };
  const handleButtonClick = async () => {
	if (installComplete) {
	  onActivateAndContinue();
	} else {
	  const anyNotInstalled = requiredPlugins.some(
		(plugin) => pluginStatuses[plugin.key] === "not-installed"
	  );
	  const anyInactive = requiredPlugins.some(
		(plugin) => pluginStatuses[plugin.key] === "inactive"
	  );

	  if (anyInactive || anyNotInstalled) {
		setLoading(true);
		setInstallInProgress(true);

		let finalMessage = "";
		for (const plugin of requiredPlugins) {
		  try {
			const response = (await apiFetch({
			  path: `${restURL}everest-forms/v1/plugin/activate`,
			  method: "POST",
			  body: JSON.stringify({
				moduleData: [
				  {
					name: plugin.value,
					slug: plugin.key,
					type: pluginStatuses[plugin.key] === "not-installed" ? "addon" : "addon",
				  },
				],
			  }),
			  headers: {
				"Content-Type": "application/json",
				"X-WP-Nonce": security,
			  },
			})) as PluginStatusResponse;

			if (response.success) {
			  setPluginStatuses((prevStatuses) => ({
				...prevStatuses,
				[plugin.key]: "active",
			  }));

			  finalMessage = response.message || __("Plugin activated successfully.", "everest-forms");

			} else {
			  setPluginStatuses((prevStatuses) => ({
				...prevStatuses,
				[plugin.key]: "error",
			  }));

			  finalMessage = response.message || sprintf(
				__("Failed to activate plugin: %s.", "everest-forms"),
				plugin.value
			  );
			}
		  } catch (error) {
			setPluginStatuses((prevStatuses) => ({
			  ...prevStatuses,
			  [plugin.key]: "error",
			}));

			// Store the error message for catch block
			finalMessage = sprintf(
			  __("Unable to activate %s.", "everest-forms"),
			  plugin.value
			);
		  }
		}

		setLoading(false);
		setInstallInProgress(false);
		setInstallComplete(true);
		setButtonLabel("Continue");

		toast({
		  title: __("Success", "everest-forms"),
		  description: finalMessage,
		  status: "success",
		  position: "bottom-right",
		  duration: 5000,
		  isClosable: true,
		  variant: "subtle",
		});
	  } else {
		onActivateAndContinue();
	  }
	}
  };


  return (
    <VStack spacing={4} align="stretch">
      {requiredPlugins?.length > 0 && (
        <>
          <Divider color={"gray.200"} mb={0} />
          <Text my={0} fontSize={16} color={"gray.700"}>
            This form template requires the following addons:
          </Text>
          <Box borderWidth="1px" borderRadius="md" overflow="hidden" w="100%">
            <Table variant="simple">
              <Tbody>
                {requiredPlugins.map((plugin) => (
                  <Tr key={plugin.key}>
                    <Td>{plugin.value}</Td>
                    <Td textAlign="right">
                      {pluginStatuses[plugin.key] === "active" ? (
                        <Icon as={CheckCircleIcon} color="green" />
                      ) : pluginStatuses[plugin.key] === "inactive" ||
                        pluginStatuses[plugin.key] === "not-installed" ? (
                        <Icon as={WarningIcon} color="yellow" />
                      ) : (
                        <Spinner size="sm" />
                      )}
                    </Td>
                  </Tr>
                ))}
              </Tbody>
            </Table>
          </Box>
        </>
      )}
      {buttonLabel && (
		<Button
			marginLeft={"auto"}
			onClick={handleButtonClick}
			colorScheme="purple"
			size="md"
			isLoading={loading}
			isDisabled={installInProgress}
		>
			{buttonLabel}
		</Button>
		)}
    </VStack>
  );
};

export default PluginStatus;
