import {
  Box,
  Container,
  Grid,
  HStack,
  Stack,
  Skeleton,
  SkeletonText,
} from "@chakra-ui/react";
import React from "react";

const SiteAssistantSkeleton = () => {
  return (
    <Container maxW="full" py={10}>
      <Grid
        gridGap="5"
        gridTemplateColumns={{
          sm: '1fr',
          md: '2fr 2fr',
          lg: '3fr 2fr',
          xl: '3fr 1fr',
        }}
      >
         <Stack gap="5">
           <Stack
            p="6"
            gap="5"
            bgColor="white"
            borderRadius="base"
            border="1px"
            borderColor="gray.100"
          >
            <HStack justify={'space-between'}>
              <Skeleton height="24px" width="180px" borderRadius="md" />
              <Skeleton height="32px" width="32px" borderRadius="base" />
            </HStack>
            <Stack gap={3}>
              <Skeleton height="1px" width="full" />
              <SkeletonText noOfLines={2} spacing={3} skeletonHeight="16px" />
              <Stack gap={2}>
                <Skeleton height="16px" width="200px" borderRadius="md" />
                <Skeleton height="40px" width="full" borderRadius="md" />
              </Stack>
              <Skeleton height="40px" width="150px" borderRadius="md" />
            </Stack>
          </Stack>

          {/* Spam Protection Section Skeleton */}
          <Stack
            p="6"
            gap="5"
            bgColor="white"
            borderRadius="base"
            border="1px"
            borderColor="gray.100"
          >
            <HStack justify={'space-between'}>
              <Skeleton height="24px" width="180px" borderRadius="md" />
              <Skeleton height="32px" width="32px" borderRadius="base" />
            </HStack>
            <Stack gap={3}>
              <Skeleton height="1px" width="full" />
              <SkeletonText noOfLines={2} spacing={3} skeletonHeight="16px" />
              <Stack
                bg="gray.50"
                p="4"
                borderRadius="md"
                gap={2}
              >
                <Skeleton height="18px" width="120px" borderRadius="md" />
                <Skeleton height="14px" width="200px" borderRadius="md" />
              </Stack>
              <HStack justify="space-between">
                <Skeleton height="14px" width="250px" borderRadius="md" />
                <Skeleton height="14px" width="80px" borderRadius="md" />
              </HStack>
            </Stack>
          </Stack>
        </Stack>

        {/* Sidebar Skeleton */}
        <Stack gap="5">
          {/* Community Card Skeleton */}
          <Stack
            p="6"
            gap="3"
            bgColor="white"
            borderRadius="base"
            border="1px"
            borderColor="gray.100"
          >
            <HStack gap="2">
              <Skeleton height="20px" width="20px" borderRadius="sm" />
              <Skeleton height="18px" width="180px" borderRadius="md" />
            </HStack>
            <SkeletonText noOfLines={3} spacing={2} skeletonHeight="13px" />
            <Skeleton height="14px" width="150px" borderRadius="md" />
          </Stack>

          {/* Getting Started Card Skeleton */}
          <Stack
            p="6"
            gap="3"
            bgColor="white"
            borderRadius="base"
            border="1px"
            borderColor="gray.100"
          >
            <HStack gap="2">
              <Skeleton height="20px" width="20px" borderRadius="sm" />
              <Skeleton height="18px" width="140px" borderRadius="md" />
            </HStack>
            <SkeletonText noOfLines={3} spacing={2} skeletonHeight="13px" />
            <Skeleton height="14px" width="140px" borderRadius="md" />
          </Stack>

          {/* Support Card Skeleton */}
          <Stack
            p="6"
            gap="3"
            bgColor="white"
            borderRadius="base"
            border="1px"
            borderColor="gray.100"
          >
            <HStack gap="2">
              <Skeleton height="20px" width="20px" borderRadius="sm" />
              <Skeleton height="18px" width="100px" borderRadius="md" />
            </HStack>
            <SkeletonText noOfLines={3} spacing={2} skeletonHeight="13px" />
            <Skeleton height="14px" width="120px" borderRadius="md" />
          </Stack>

          {/* Feature Request Card Skeleton */}
          <Stack
            p="6"
            gap="3"
            bgColor="white"
            borderRadius="base"
            border="1px"
            borderColor="gray.100"
          >
            <HStack gap="2">
              <Skeleton height="20px" width="20px" borderRadius="sm" />
              <Skeleton height="18px" width="140px" borderRadius="md" />
            </HStack>
            <SkeletonText noOfLines={3} spacing={2} skeletonHeight="13px" />
            <Skeleton height="14px" width="130px" borderRadius="md" />
          </Stack>

          {/* Submit Review Card Skeleton */}
          <Stack
            p="6"
            gap="3"
            bgColor="white"
            borderRadius="base"
            border="1px"
            borderColor="gray.100"
          >
            <HStack gap="2">
              <Skeleton height="20px" width="20px" borderRadius="sm" />
              <Skeleton height="18px" width="140px" borderRadius="md" />
            </HStack>
            <SkeletonText noOfLines={3} spacing={2} skeletonHeight="13px" />
            <Skeleton height="14px" width="130px" borderRadius="md" />
          </Stack>

          {/* Video Tutorials Card Skeleton */}
          <Stack
            p="6"
            gap="3"
            bgColor="white"
            borderRadius="base"
            border="1px"
            borderColor="gray.100"
          >
            <HStack gap="2">
              <Skeleton height="20px" width="20px" borderRadius="sm" />
              <Skeleton height="18px" width="130px" borderRadius="md" />
            </HStack>
            <SkeletonText noOfLines={3} spacing={2} skeletonHeight="13px" />
            <Skeleton height="14px" width="110px" borderRadius="md" />
          </Stack>
        </Stack>
      </Grid>
    </Container>
  );
};

export default SiteAssistantSkeleton;
