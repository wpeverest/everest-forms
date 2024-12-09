import { AddIcon, InfoIcon } from '@chakra-ui/icons'
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
	Text,
	FormControl,
	FormLabel,
	Input,
	Tooltip,
	Icon,
	Stack,
  } from '@chakra-ui/react'
import { __ } from '@wordpress/i18n'
import { Select } from 'chakra-react-select'

  import React, { useState } from 'react'
import { addManagerRole } from './RoleAndPermissionAPI'

  const AddUserDisplayModel = ( { wp_roles }) => {
	const { isOpen, onOpen, onClose } = useDisclosure();
	const [ userEmail, setUserEmail ] = useState("");
	const [ permissions, setPermissions ] = useState([]);


	const roles = Object.entries( wp_roles ).map( ( [ key, value ] ) => ( {
		label: value,
		value: key,
	  } ) );

	  const handleMultiplePermission = (selectedOptions) => {
		const selectedValues = selectedOptions ? selectedOptions.map(option => option.value) : [];
		setPermissions(selectedValues);
	};

	const handleAddManager = ( email, assignedPermission )=> {
		addManagerRole( email, assignedPermission ).then( (res) => {
			console.log(res);
		})
	}

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

      <Modal isOpen={isOpen} onClose={onClose} isCentered size={"lg"}>
        <ModalOverlay
			bg='none'
			backdropFilter='auto'
			backdropInvert='0%'
			backdropBlur='2px'
		/>
        <ModalContent>
          <ModalHeader>Add User
		 	<Text>View and manage the list of current managers, their assigned roles, and permissions.</Text>
		  </ModalHeader>
          <ModalCloseButton />
          <ModalBody paddingTop={"0"}>
			<FormControl>
				<Stack gap={"28px"}>
					<Stack>
						<FormLabel display="flex" alignItems="center" fontSize="14px">
							User Email
							<Tooltip label="User email" fontSize="sm">
								<Icon
								as={InfoIcon}
								ml={2}
								boxSize={4}
								background="#BABABA"
								color="#FFFFFF"
								borderRadius="50%"
								padding="2px"
								border={"none"}
								_hover={{ cursor: "pointer" }}
								/>
							</Tooltip>
						</FormLabel>
						<Input
							type='email'
							placeholder='User Email Address'
							onChange={(e) => setUserEmail(e.target.value)}
						/>

					</Stack>

					<Stack>
						<FormLabel display="flex" alignItems="center" fontSize="14px">
							User Permission
							<Tooltip label="User permission" fontSize="sm">
								<Icon
								as={InfoIcon}
								ml={2}
								boxSize={4}
								background="#BABABA"
								color="#FFFFFF"
								borderRadius="50%"
								padding="2px"
								border={"none"}
								_hover={{ cursor: "pointer" }}
								/>
							</Tooltip>
						</FormLabel>
						<Select
								isMulti
								size="md"
								placeholder={__(
									"Select user permission",
									"everest-forms",
								)}
								options={roles}
								onChange={handleMultiplePermission}
								isClearable
								isSearchable={false}
							/>
					</Stack>
				</Stack>
			</FormControl>
          </ModalBody>

          <ModalFooter>
            <Button _hover={{backgroundColor:"#FFFFF"}} color={"#6B6B6B"} fontWeight={"600"} fontSize={"16px"} lineHeight={"24px"} mr={3} onClick={onClose}>
              Back
            </Button>
            <Button color={"#FFFFFF"} fontWeight={"500"} fontSize={"16px"} backgroundColor={"#7545BB"} padding={"10px 16px"} borderRadius={"4px"} border={"1px solid #7545BB"} width={"94px"} height={"39px"} _hover={{backgroundColor:"#7545BB"}}
				onClick={(e) => { handleAddManager( userEmail, permissions) }}
			>Confirm</Button>
          </ModalFooter>
        </ModalContent>
      </Modal>
    </>
  )
  }

  export default AddUserDisplayModel
