import React, { useEffect, useState } from "react";
import apiFetch from "@wordpress/api-fetch";
import {
  Box,
  Button,
  Flex,
  HStack,
  Icon,
  Spinner,
  Text,
  VStack,
  useToast,
} from "@chakra-ui/react";
import { FiCheck, FiDownload, FiZap } from "react-icons/fi";
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

const StatusBadge: React.FC<{ status: string | undefined }> = ({ status }) => {
  if (!status) {
    return <Spinner size="xs" color="#7545BB" thickness="2px" />;
  }
  if (status === 'active') {
    return (
      <HStack spacing="5px">
        <Box w="6px" h="6px" borderRadius="full" bg="#22c55e" />
        <Text fontSize="12px" fontWeight="500" color="#16a34a" margin="0">
          {__('Active', 'everest-forms')}
        </Text>
      </HStack>
    );
  }
  if (status === 'inactive') {
    return (
      <HStack spacing="5px">
        <Box w="6px" h="6px" borderRadius="full" bg="#f59e0b" />
        <Text fontSize="12px" fontWeight="500" color="#d97706" margin="0">
          {__('Inactive', 'everest-forms')}
        </Text>
      </HStack>
    );
  }
  if (status === 'not-installed') {
    return (
      <HStack spacing="5px">
        <Box w="6px" h="6px" borderRadius="full" bg="#94a3b8" />
        <Text fontSize="12px" fontWeight="500" color="#64748b" margin="0">
          {__('Not installed', 'everest-forms')}
        </Text>
      </HStack>
    );
  }
  if (status === 'error') {
    return (
      <HStack spacing="5px">
        <Box w="6px" h="6px" borderRadius="full" bg="#ef4444" />
        <Text fontSize="12px" fontWeight="500" color="#dc2626" margin="0">
          {__('Error', 'everest-forms')}
        </Text>
      </HStack>
    );
  }
  return null;
};

const PluginStatus: React.FC<PluginStatusProps> = ({
  requiredPlugins,
  onActivateAndContinue,
}) => {
  const [pluginStatuses, setPluginStatuses] = useState<Record<string, string>>({});
  const [loading, setLoading] = useState(false);
  const [installInProgress, setInstallInProgress] = useState(false);
  const [buttonState, setButtonState] = useState<'idle' | 'install' | 'activate' | 'continue'>('idle');
  const toast = useToast();

  useEffect(() => {
    const fetchPluginStatus = async () => {
      try {
        const response = await apiFetch<PluginStatusResponse>({
          path: `${restURL}everest-forms/v1/plugin/status`,
          method: "GET",
          headers: { "X-WP-Nonce": security },
        });
        if (response.success) {
          setPluginStatuses(response.plugin_status);
          deriveButtonState(response.plugin_status);
        }
      } catch {
        toast({
          title: __("Error", "everest-forms"),
          description: __("Unable to check plugin status.", "everest-forms"),
          status: "error", position: "bottom-right", duration: 5000, isClosable: true, variant: "subtle",
        });
      }
    };
    fetchPluginStatus();
  }, []);

  const deriveButtonState = (statuses: Record<string, string>) => {
    const allActive   = requiredPlugins.every(p => statuses[p.key] === 'active');
    const anyNotInst  = requiredPlugins.some(p => statuses[p.key] === 'not-installed');
    const anyInactive = requiredPlugins.some(p => statuses[p.key] === 'inactive');

    if (allActive)        setButtonState('continue');
    else if (anyNotInst)  setButtonState('install');
    else if (anyInactive) setButtonState('activate');
    else                  setButtonState('continue');
  };

  const handleButtonClick = async () => {
    if (buttonState === 'continue') {
      onActivateAndContinue();
      return;
    }

    setLoading(true);
    setInstallInProgress(true);

    let finalMessage = "";
    for (const plugin of requiredPlugins) {
      if (pluginStatuses[plugin.key] === 'active') continue;
      try {
        const response = (await apiFetch({
          path: `${restURL}everest-forms/v1/plugin/activate`,
          method: "POST",
          body: JSON.stringify({
            moduleData: [{ name: plugin.value, slug: plugin.key, type: "addon" }],
          }),
          headers: { "Content-Type": "application/json", "X-WP-Nonce": security },
        })) as PluginStatusResponse;

        if (response.success) {
          setPluginStatuses(prev => ({ ...prev, [plugin.key]: 'active' }));
          finalMessage = response.message || __("Plugin activated successfully.", "everest-forms");
        } else {
          setPluginStatuses(prev => ({ ...prev, [plugin.key]: 'error' }));
          finalMessage = response.message || sprintf(__("Failed to activate: %s.", "everest-forms"), plugin.value);
        }
      } catch {
        setPluginStatuses(prev => ({ ...prev, [plugin.key]: 'error' }));
        finalMessage = sprintf(__("Unable to activate %s.", "everest-forms"), plugin.value);
      }
    }

    setLoading(false);
    setInstallInProgress(false);
    setButtonState('continue');

    toast({
      title: __("Success", "everest-forms"),
      description: finalMessage,
      status: "success", position: "bottom-right", duration: 5000, isClosable: true, variant: "subtle",
    });
  };

  const buttonConfig = {
    install:  { label: __('Install & Activate', 'everest-forms'), icon: FiDownload },
    activate: { label: __('Activate',           'everest-forms'), icon: FiZap      },
    continue: { label: __('Continue',            'everest-forms'), icon: FiCheck    },
    idle:     { label: __('Continue',            'everest-forms'), icon: FiCheck    },
  }[buttonState];

  if (requiredPlugins.length === 0) return null;

  return (
    <VStack align="stretch" spacing="0">
      {/* Addon rows */}
      <VStack align="stretch" spacing="2px" mb="20px">
        {requiredPlugins.map((plugin, i) => (
          <Flex
            key={plugin.key}
            align="center"
            justify="space-between"
            px="14px"
            py="12px"
            bg={i % 2 === 0 ? '#fafafa' : 'white'}
            border="1px solid #f1f5f9"
            borderRadius={
              i === 0
                ? '10px 10px 0 0'
                : i === requiredPlugins.length - 1
                ? '0 0 10px 10px'
                : '0'
            }
            borderTop={i > 0 ? 'none' : undefined}
          >
            <Text fontSize="14px" fontWeight="500" color="#374151" margin="0">
              {plugin.value}
            </Text>
            <StatusBadge status={pluginStatuses[plugin.key]} />
          </Flex>
        ))}
      </VStack>

      {/* Action button */}
      {buttonState !== 'idle' && (
        <Box
          as="button"
          display="inline-flex"
          alignItems="center"
          justifyContent="center"
          gap="8px"
          alignSelf="flex-end"
          h="40px"
          px="20px"
          borderRadius="8px"
          bg={buttonState === 'continue' ? '#22c55e' : '#7545BB'}
          color="white"
          fontSize="14px"
          fontWeight="500"
          border="none"
          cursor={loading || installInProgress ? 'not-allowed' : 'pointer'}
          opacity={loading || installInProgress ? 0.75 : 1}
          onClick={!loading && !installInProgress ? handleButtonClick : undefined}
          transition="background 0.2s, opacity 0.2s"
          _hover={{ bg: buttonState === 'continue' ? '#16a34a' : '#6a3daa' }}
        >
          {loading ? (
            <Spinner size="xs" color="white" thickness="2px" />
          ) : (
            <Icon as={buttonConfig.icon} boxSize="4" />
          )}
          <Text margin="0" color="white">
            {loading ? __('Processing…', 'everest-forms') : buttonConfig.label}
          </Text>
        </Box>
      )}
    </VStack>
  );
};

export default PluginStatus;
