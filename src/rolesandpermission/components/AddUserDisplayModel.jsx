import { AddIcon } from '@chakra-ui/icons'
import {
	Modal,
	ModalOverlay,
	ModalContent,
	ModalHeader,
	ModalFooter,
	ModalBody,
	ModalCloseButton,
	useDisclosure,
	Button,
  } from '@chakra-ui/react'

  import React from 'react'

  const AddUserDisplayModel = () => {
	const { isOpen, onOpen, onClose } = useDisclosure()
  return (
    <>
      <Button
	  		onClick={onOpen}
			width={"113px"}
			height={"41px"}
			backgroundColor={"#7545BB"}
			padding={"10px 16px"}
			gap={"6px"}
			fontWeight={"500"}
			lineHeight={"21px"}
			fontSize={"14px"}
			color={"#FFFFFF"}
			_hover={{ backgroundColor: "#7545BB" }}
		>
			<AddIcon height={"9.95px"} width={"9.9px"} fontWeight={"500"} color={"#FFFFFF"}/> Add User
		</Button>

      <Modal isOpen={isOpen} onClose={onClose} isCentered>
        <ModalOverlay
			bg='none'
			backdropFilter='auto'
			backdropInvert='0%'
			backdropBlur='2px'
		/>
        <ModalContent>
          <ModalHeader>Modal Title</ModalHeader>
          <ModalCloseButton />
          <ModalBody>
            This is Niraj Chaudhary
          </ModalBody>

          <ModalFooter>
            <Button colorScheme='blue' mr={3} onClick={onClose}>
              Close
            </Button>
            <Button variant='ghost'>Secondary Action</Button>
          </ModalFooter>
        </ModalContent>
      </Modal>
    </>
  )
  }

  export default AddUserDisplayModel
