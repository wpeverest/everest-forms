import { AlertDialog, AlertDialogBody, AlertDialogCloseButton, AlertDialogContent, AlertDialogFooter, AlertDialogHeader, AlertDialogOverlay, Button, Modal, ModalBody, ModalContent, ModalFooter, ModalHeader, ModalOverlay, useDisclosure } from '@chakra-ui/react';
import React, { useRef } from 'react'

const TrashUserRoleModel = ( { deleteManager } ) => {
  const { isOpen, onOpen, onClose } = useDisclosure();
  const cancelRef = useRef()
  return (
    <>
	  	<Button
	 		style={{
				color: "#475BB2",
				fontWeight: "400",
				fontSize: "13px",
				backgroundColor: "#ffffff",
				padding: 0,
			}}
	  		onClick={onOpen}>
				Trash
		</Button>
      <AlertDialog
        motionPreset='slideInBottom'
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
            <Button colorScheme='red' ml={3} onClick={deleteManager}>
              Yes
            </Button>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </>
  )

}

export default TrashUserRoleModel
