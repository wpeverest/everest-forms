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
          throw new Error("Invalid response format");
        }
      } catch (error) {
        console.error("Error fetching plugin status:", error);
        toast({
          title: "Error",
          description: "Unable to check plugin status.",
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
      setButtonLabel("Continue");
      setInstallComplete(true);
    } else if (anyNotInstalled) {
      setButtonLabel("Install & Activate");
      setInstallComplete(false);
    } else if (anyInactive) {
      setButtonLabel("Activate and Continue");
      setInstallComplete(false);
    } else {
      setButtonLabel("Continue");
      setInstallComplete(false);
    }
  };

  const handleButtonClick = async () => {
    if (installComplete) {
      // If the installation and activation are complete, just proceed
      onActivateAndContinue();
    } else {
      // If installation and activation are not complete
      const anyNotInstalled = requiredPlugins.some(
        (plugin) => pluginStatuses[plugin.key] === "not-installed"
      );
      const anyInactive = requiredPlugins.some(
        (plugin) => pluginStatuses[plugin.key] === "inactive"
      );

      if (anyInactive || anyNotInstalled) {
        setLoading(true);
        setInstallInProgress(true);

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
            } else {
              setPluginStatuses((prevStatuses) => ({
                ...prevStatuses,
                [plugin.key]: "error",
              }));
            }
          } catch (error) {
            console.error("Error activating plugin:", error);
            setPluginStatuses((prevStatuses) => ({
              ...prevStatuses,
              [plugin.key]: "error",
            }));
            toast({
              title: "Error",
              description: `Unable to activate ${plugin.value}.`,
              status: "error",
              position: "bottom-right",
              duration: 5000,
              isClosable: true,
              variant: "subtle",
            });
          }
        }
        setLoading(false);
        setInstallInProgress(false);
        setInstallComplete(true);
		setButtonLabel("Continue");
        toast({
          title: "Success",
          description: "All required plugins installed and activated successfully.",
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
    </VStack>
  );
};

export default PluginStatus;
