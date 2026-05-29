import {
	AlertDialog,
	AlertDialogBody,
	AlertDialogCloseButton,
	AlertDialogContent,
	AlertDialogFooter,
	AlertDialogHeader,
	AlertDialogOverlay,
	Button,
	useDisclosure,
} from '@chakra-ui/react';
import React, { useRef } from 'react';

const TrashUserRoleModel = ({ deleteManager }) => {
	const { isOpen, onOpen, onClose } = useDisclosure();
	const cancelRef = useRef();

	return (
		<>
			<Button
				variant="link"
				color="gray.500"
				fontWeight="400"
				fontSize="13px"
				minW="auto"
				height="auto"
				padding={0}
				_hover={{ color: 'red.500', textDecoration: 'none' }}
				onClick={onOpen}
			>
				Trash
			</Button>

			<AlertDialog
				motionPreset="slideInBottom"
				leastDestructiveRef={cancelRef}
				onClose={onClose}
				isOpen={isOpen}
				isCentered
			>
				<AlertDialogOverlay />
				<AlertDialogContent>
					<AlertDialogHeader>Delete Manager?</AlertDialogHeader>
					<AlertDialogCloseButton />
					<AlertDialogBody>
						Are you sure you want to delete manager.
					</AlertDialogBody>
					<AlertDialogFooter>
						<Button ref={cancelRef} onClick={onClose}>
							No
						</Button>
						<Button colorScheme="red" ml={3} onClick={deleteManager}>
							Yes
						</Button>
					</AlertDialogFooter>
				</AlertDialogContent>
			</AlertDialog>
		</>
	);
};

export default TrashUserRoleModel;
