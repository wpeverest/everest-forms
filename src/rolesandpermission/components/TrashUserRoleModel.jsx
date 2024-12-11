import { Button, Modal, ModalBody, ModalContent, ModalFooter, ModalHeader, ModalOverlay, useDisclosure } from '@chakra-ui/react';
import React from 'react'

const TrashUserRoleModel = ( { deleteManager } ) => {
  const { isOpen, onOpen, onClose } = useDisclosure();
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
	  	onClick={onOpen}>Trash</Button>

      <Modal isOpen={isOpen} onClose={onClose} size={"lg"}>
        <ModalOverlay />
        <ModalContent>
          <ModalBody>
		  		Are you sure to delete this?
          </ModalBody>

          <ModalFooter>
            <Button variantColor="blue" mr={3} onClick={onClose}>
              Cancel
            </Button>
            <Button variant="ghost" onClick={deleteManager}>Confirm</Button>
          </ModalFooter>
        </ModalContent>
      </Modal>
    </>
  )

}

export default TrashUserRoleModel
