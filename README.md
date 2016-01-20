# org.civicoop.emailapi
E-mail API for CiviCRM to send e-mails through the API

The entity for the E-mail API is Email and the action is Send.
Parameters for the api are specified below:
- contact_id: list of contacts IDs to create the PDF Letter (separated by ",")
- template_id: ID of the message template which will be used in the API.
- from_name: **optional** name of the sender (if you provide this value you have also to provice from_email) 
- from_email: **optional** e-mail of the sender (if you provide this value you have also to provice from_name)
- alternative_receiver_address: **optional** alternative receiver address of the e-mail. 

*It is not possible to specify your own message through the API.*

    
