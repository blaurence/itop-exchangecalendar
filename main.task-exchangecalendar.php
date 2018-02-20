<?php
require_once 'vendor/autoload.php';

use \jamesiarmes\PhpEws\Client;
use \jamesiarmes\PhpEws\Request\UpdateItemType;
use \jamesiarmes\PhpEws\Request\GetItemType;
use \jamesiarmes\PhpEws\Request\FindItemType;
use \jamesiarmes\PhpEws\Request\CreateItemType;
use \jamesiarmes\PhpEws\Request\DeleteItemType;

use \jamesiarmes\PhpEws\ArrayType\NonEmptyArrayOfBaseFolderIdsType;
use \jamesiarmes\PhpEws\ArrayType\NonEmptyArrayOfPathsToElementType;
use \jamesiarmes\PhpEws\ArrayType\NonEmptyArrayOfAllItemsType;
use \jamesiarmes\PhpEws\ArrayType\NonEmptyArrayOfAttendeesType;
use \jamesiarmes\PhpEws\ArrayType\NonEmptyArrayOfBaseItemIdsType;
use \jamesiarmes\PhpEws\ArrayType\NonEmptyArrayOfItemChangeDescriptionsType;

use \jamesiarmes\PhpEws\Enumeration\BodyTypeType;
use \jamesiarmes\PhpEws\Enumeration\CalendarItemCreateOrDeleteOperationType;
use \jamesiarmes\PhpEws\Enumeration\DefaultShapeNamesType;
use \jamesiarmes\PhpEws\Enumeration\DistinguishedFolderIdNameType;
use \jamesiarmes\PhpEws\Enumeration\ResponseClassType;
use \jamesiarmes\PhpEws\Enumeration\ItemQueryTraversalType;
use \jamesiarmes\PhpEws\Enumeration\RoutingType;
use \jamesiarmes\PhpEws\Enumeration\MessageDispositionType;
use \jamesiarmes\PhpEws\Enumeration\DisposalType;
use \jamesiarmes\PhpEws\Enumeration\MapiPropertyTypeType;
use \jamesiarmes\PhpEws\Enumeration\DistinguishedPropertySetType;
use \jamesiarmes\PhpEws\Enumeration\ConflictResolutionType;
use \jamesiarmes\PhpEws\Enumeration\CalendarItemUpdateOperationType;
use \jamesiarmes\PhpEws\Enumeration\UnindexedFieldURIType;

use \jamesiarmes\PhpEws\Type\ItemChangeType;
use \jamesiarmes\PhpEws\Type\PathToUnindexedFieldType;
use \jamesiarmes\PhpEws\Type\SetItemFieldType;
use \jamesiarmes\PhpEws\Type\CalendarViewType;
use \jamesiarmes\PhpEws\Type\DistinguishedFolderIdType;
use \jamesiarmes\PhpEws\Type\ItemResponseShapeType;
use \jamesiarmes\PhpEws\Type\AttendeeType;
use \jamesiarmes\PhpEws\Type\BodyType;
use \jamesiarmes\PhpEws\Type\CalendarItemType;
use \jamesiarmes\PhpEws\Type\EmailAddressType;
use \jamesiarmes\PhpEws\Type\CancelCalendarItemType;
use \jamesiarmes\PhpEws\Type\ItemIdType;
use \jamesiarmes\PhpEws\Type\PathToExtendedFieldType;

use \jamesiarmes\PhpEws\Request;
use \jamesiarmes\PhpEws\Type;

class phpews {

private $sHost;
private $sUsername;
private $sPassword;
private $sVersion;
private $sTimezone;

/**
 *
 * @var \jamesiarmes\PhpEws\Client 
 */
private $client;


/**
 * 
 */
public function __construct(){
    
    //$this->host = phpEwsConf::$host;
    $this->sHost = MetaModel::GetModuleSetting('task-exchangecalendar', 'ews_host', '');
    $this->sUsername = MetaModel::GetModuleSetting('task-exchangecalendar', 'ews_username', '');
    $this->sPassword = MetaModel::GetModuleSetting('task-exchangecalendar', 'ews_password', '');
    $this->sVersion = MetaModel::GetModuleSetting('task-exchangecalendar', 'ews_version', '');
    $this->sTimezone = MetaModel::GetModuleSetting('task-exchangecalendar', 'ews_timezone', '');
    
    $this->client = new Client($this->sHost, $this->sUsername, $this->sPassword, $this->sVersion);
    $this->client->setCurlOptions(array(CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => false)); 
    //$this->client->setTimezone($this->sTimezone);
}


/**
 * 
 * @param String $event_id
 * @return CalendarItemType[]
 */
public function findByEventId($event_id){

    // Replace this with the ids of actual events.
    $event_ids = array(
        $event_id,
    );
    
    // Build the request.
    $request = new GetItemType();
    $request->ItemShape = new ItemResponseShapeType();
    $request->ItemShape->BaseShape = DefaultShapeNamesType::ALL_PROPERTIES;
    $request->ItemIds = new NonEmptyArrayOfBaseItemIdsType();

    // We want to get the online meeting link in the response. Note that if this
    // property is not set on the event, it will not be included in the response.
    $property = new PathToExtendedFieldType();
    $property->PropertyName = 'OnlineMeetingExternalLink';
    $property->PropertyType = MapiPropertyTypeType::STRING;
    $property->DistinguishedPropertySetId = DistinguishedPropertySetType::PUBLIC_STRINGS;

    $additional_properties = new NonEmptyArrayOfPathsToElementType();
    $additional_properties->ExtendedFieldURI[] = $property;
    $request->ItemShape->AdditionalProperties = $additional_properties;

    // Iterate over the event ids, setting each one on the request.
    foreach ($event_ids as $event_id) {
        $item = new ItemIdType();
        $item->Id = $event_id;
        $request->ItemIds->ItemId[] = $item;
    }

    $response = $this->client->GetItem($request);

    // Iterate over the results, printing any error messages or event names.
    $response_messages = $response->ResponseMessages->GetItemResponseMessage;
    foreach ($response_messages as $response_message) {
        // Make sure the request succeeded.
        if ($response_message->ResponseClass != ResponseClassType::SUCCESS) {
            $message = $response_message->ResponseCode;
            //fwrite(STDERR, "Failed to get event with \"$message\"\n");
            return false;
            //continue;
        }
        
        /**
        // Iterate over the events, printing the title for each.
        foreach ($response_message->Items->CalendarItem as $item) {
            $subject = $item->Subject;
            fwrite(STDOUT, "Retrieved event $subject\n");
            //var_dump($item);
        }*/
    }
    
    return $response_message->Items->CalendarItem;
}


/**
 * 
 * @param DateTime $start
 * @param DateTime $end
 * @return \jamesiarmes\PhpEws\Type\CalendarItemType[]
 */
public function find($start, $end){
    
    $start_date = new DateTime($start);
    $end_date = new DateTime($end);

    $request = new FindItemType();
    $request->Traversal = ItemQueryTraversalType::SHALLOW;
    $request->ParentFolderIds = new NonEmptyArrayOfBaseFolderIdsType();

    // Return all event properties.
    $request->ItemShape = new ItemResponseShapeType();
    $request->ItemShape->BaseShape = DefaultShapeNamesType::ALL_PROPERTIES;

    $folder_id = new DistinguishedFolderIdType();
    $folder_id->Id = DistinguishedFolderIdNameType::CALENDAR;
    $request->ParentFolderIds->DistinguishedFolderId[] = $folder_id;

    $request->CalendarView = new CalendarViewType();
    $request->CalendarView->StartDate = $start_date->format('c');
    $request->CalendarView->EndDate = $end_date->format('c');

    $response = $this->client->FindItem($request);

    // Iterate over the results, printing any error messages or event ids.
    $response_messages = $response->ResponseMessages->FindItemResponseMessage;
    foreach ($response_messages as $response_message) {
        // Make sure the request succeeded.
        if ($response_message->ResponseClass != ResponseClassType::SUCCESS) {
            $code = $response_message->ResponseCode;
            $message = $response_message->MessageText;
            fwrite(
                STDERR,
                "Failed to search for events with \"$code: $message\"\n"
            );
            continue;
        }

        // Iterate over the events that were found, printing some data for each.
        $items = $response_message->RootFolder->Items->CalendarItem;
        return $items;
        
        foreach ($items as $item) {
            $id = $item->ItemId->Id;
            $start = new DateTime($item->Start);
            $end = new DateTime($item->End);
            $output = 'Found event ' . $item->ItemId->Id . "\n"
                . '  Change Key: ' . $item->ItemId->ChangeKey . "\n"
                . '  Title: ' . $item->Subject . "\n"
                . '  Start: ' . $start->format('l, F jS, Y g:ia') . "\n"
                . '  End:   ' . $end->format('l, F jS, Y g:ia') . "\n\n";

            //fwrite(STDOUT, $output);
        }

    }
}



/**
 * 
 * @param string $event_id
 * @param string $change_id
 * @return boolean
 */
public function deleteEvent($event_id, $change_id){
    
    // Define the delete item class
    $request = new DeleteItemType();
    // Send to trash can, or use EWSType_DisposalType::HARD_DELETE instead to bypass the bin directly
    $request->SendMeetingCancellations = CalendarItemCreateOrDeleteOperationType::SEND_ONLY_TO_ALL;   
    
    $request->ItemIds = new NonEmptyArrayOfBaseItemIdsType();
    $request->ItemIds->ItemId = new ItemIdType();
    $request->ItemIds->ItemId->Id = $event_id;
    $request->ItemIds->ItemId->ChangeKey = $change_id; 
    
    $request->DeleteType = DisposalType::MOVE_TO_DELETED_ITEMS;
 
    $response = $this->client->DeleteItem($request);
    //var_dump($response);
     
    if ( $response->ResponseMessages->DeleteItemResponseMessage[0]->ResponseClass == "Success" ) {
        return true;
    } else {
        return false;
    }
    
}
 
/**
 * 
 * @param type $event_id
 * @param type $change_key
 * @param type $start
 * @param type $end
 * @param type $guests
 * @param type $subject
 * @param type $body
 * @return boolean
 */
public function updateEvent($event_id, $change_key, $start, $end, $guests, $subject, $body){
    
    $start = DateTime::createFromFormat('Y-m-d H:i:s', $start);
    $end = DateTime::createFromFormat('Y-m-d H:i:s', $end);
    
    // Replace with the events to be updated along with their new start and end
    // times.
    $event_updates = 
        array(
            'id' => $event_id,
            'start' => $start,
            'end' => $end,
        
    );    
    
    $request = new UpdateItemType();
    $request->ConflictResolution = ConflictResolutionType::ALWAYS_OVERWRITE;
    $request->SendMeetingInvitationsOrCancellations = CalendarItemUpdateOperationType::SEND_TO_ALL_AND_SAVE_COPY;
    
    $change = new ItemChangeType();
    $change->ItemId = new ItemIdType();
    $change->ItemId->Id = $event_id;
    $change->ItemId->ChangeKey = $change_key;
    $change->Updates = new NonEmptyArrayOfItemChangeDescriptionsType();
    
    // Set the updated start time.
    $field = new SetItemFieldType();
    $field->FieldURI = new PathToUnindexedFieldType();
    $field->FieldURI->FieldURI = UnindexedFieldURIType::CALENDAR_START;
    $field->CalendarItem = new CalendarItemType();
    $field->CalendarItem->Start = $start->format('c');
    $change->Updates->SetItemField[] = $field;

    // Set the updated end time.
    $field = new SetItemFieldType();
    $field->FieldURI = new PathToUnindexedFieldType();
    $field->FieldURI->FieldURI = UnindexedFieldURIType::CALENDAR_END;
    $field->CalendarItem = new CalendarItemType();
    $field->CalendarItem->End = $end->format('c');
    $change->Updates->SetItemField[] = $field;

    // Updating BODY
    $field = new SetItemFieldType();
    $field->FieldURI = new PathToUnindexedFieldType();
    $field->FieldURI->FieldURI = new UnindexedFieldURIType();
    $field->FieldURI->FieldURI->_ = UnindexedFieldURIType::ITEM_BODY;
    $field->CalendarItem = new CalendarItemType();
    $field->CalendarItem->Body = new BodyType();
    $field->CalendarItem->Body->_ = $body;
    $field->CalendarItem->Body->BodyType = BodyTypeType::HTML;
    $change->Updates->SetItemField[] = $field;
    
    // Updating Guests
    $field = new SetItemFieldType();
    $field->FieldURI = new PathToUnindexedFieldType();
    $field->FieldURI->FieldURI = 'calendar:RequiredAttendees';
    $field->CalendarItem = new CalendarItemType();
    $field->CalendarItem->RequiredAttendees = new NonEmptyArrayOfAttendeesType();
    
    foreach ($guests as $guest) {
        $attendee = new AttendeeType();
        $attendee->Mailbox = new EmailAddressType();
        $attendee->Mailbox->EmailAddress = $guest['email'];
        $attendee->Mailbox->Name = $guest['name'];
        $attendee->Mailbox->RoutingType = RoutingType::SMTP;
        $field->CalendarItem->RequiredAttendees->Attendee[] = $attendee;
    }
    $change->Updates->SetItemField[] = $field;
    
    // Updating title
    $field = new SetItemFieldType();
    $field->FieldURI = new PathToUnindexedFieldType();
    $field->FieldURI->FieldURI = UnindexedFieldURIType::ITEM_SUBJECT;
    $field->CalendarItem = new CalendarItemType();
    $field->CalendarItem->Subject = $subject;
    
    $change->Updates->SetItemField[] = $field;
    
    $request->ItemChanges[] = $change;
    
    
    
    
    $response = $this->client->UpdateItem($request);

    // Iterate over the results, printing any error messages or ids of events that
    // were updated.
    $response_messages = $response->ResponseMessages->UpdateItemResponseMessage;
    foreach ($response_messages as $response_message) {
        // Make sure the request succeeded.
        if ($response_message->ResponseClass != ResponseClassType::SUCCESS) {
            $code = $response_message->ResponseCode;
            $message = $response_message->MessageText;
            Logger::JobError("Failed to update event with \"$code: $message\"");
            return false;
        }

        // Iterate over the updated events, printing the id of each.
        foreach ($response_message->Items->CalendarItem as $item) {
            $id = $item->ItemId->Id;
            $changeKey = $item->ItemId->ChangeKey;
            //fwrite(STDOUT, "Updated event id : $id\n");
            //fwrite(STDOUT, "Updated event change_key : $changeKey\n");
        }
    }    
    
    return $response_message->Items->CalendarItem[0];
    
}

/**
 * 
 * @param type $start
 * @param type $end
 * @param type $guests
 * @param type $subject
 * @param type $body
 * @return CalendarItemType
 */
public function createEvent($start, $end, $guests, $subject, $body){
    
    $start = DateTime::createFromFormat('Y-m-d H:i:s', $start);
    $end = DateTime::createFromFormat('Y-m-d H:i:s', $end);
    //if ( $date_errors = DateTime::getLastErrors() ) {
        //var_dump($date_errors);
        //return false;
    //}
    
    // Build the request,
    $request = new CreateItemType();
    $request->SendMeetingInvitations = CalendarItemCreateOrDeleteOperationType::SEND_ONLY_TO_ALL;
    $request->Items = new NonEmptyArrayOfAllItemsType();

    // Build the event to be added.
    $event = new CalendarItemType();
    $event->RequiredAttendees = new NonEmptyArrayOfAttendeesType();
    $event->Start = $start->format('c');
    $event->End = $end->format('c');
    $event->Subject = $subject;

    // Set the event body.
    $event->Body = new BodyType();
    $event->Body->_ = $body;
    $event->Body->BodyType = BodyTypeType::HTML;

    // Iterate over the guests, adding each as an attendee to the request.
    foreach ($guests as $guest) {
        $attendee = new AttendeeType();
        $attendee->Mailbox = new EmailAddressType();
        $attendee->Mailbox->EmailAddress = $guest['email'];
        $attendee->Mailbox->Name = $guest['name'];
        $attendee->Mailbox->RoutingType = RoutingType::SMTP;
        $event->RequiredAttendees->Attendee[] = $attendee;
    }

    // Add the event to the request. You could add multiple events to create more
    // than one in a single request.
    $request->Items->CalendarItem[] = $event;

    $response = $this->client->CreateItem($request);

    // Iterate over the results, printing any error messages or event ids.
    $response_messages = $response->ResponseMessages->CreateItemResponseMessage;
    //var_dump($response_messages);
    
    foreach ($response_messages as $response_message) {
        // Make sure the request succeeded.
        if ($response_message->ResponseClass != ResponseClassType::SUCCESS) {
            $code = $response_message->ResponseCode;
            $message = $response_message->MessageText;
            return false;
        }
    }

    return $response_message->Items->CalendarItem[0];

    
}


public function cancelEvent($event_id, $change_key){

    //$event_id = 'AAMkADA5ZGQ2YmJiLTc1YzYtNDZiZS1iZjU0LWEwNjQwNzg4M2YzOQBGAAAAAADD3UO0gdTgQLIV/STyQUjFBwD0Ue/Q/YsgRq4QaYOiMfKjAAAAKRUDAAD0Ue/Q/YsgRq4QaYOiMfKjAAAALUrpAAA=';
    //$change_key = 'DwAAABYAAAD0Ue/Q/YsgRq4QaYOiMfKjAAAALVD3';
    
    $request = new CreateItemType();
    $request->MessageDisposition = MessageDispositionType::SEND_AND_SAVE_COPY;
    $request->Items = new NonEmptyArrayOfAllItemsType();

    $cancellation = new CancelCalendarItemType();
    $cancellation->ReferenceItemId = new ItemIdType();
    $cancellation->ReferenceItemId->Id = $event_id;
    $cancellation->ReferenceItemId->ChangeKey = $change_key;
    $request->Items->CancelCalendarItem[] = $cancellation;

    $response = $this->client->CreateItem($request);

    // Iterate over the results, printing any error messages.
    $response_messages = $response->ResponseMessages->CreateItemResponseMessage;
    foreach ($response_messages as $response_message) {
        // Make sure the request succeeded.
        if ($response_message->ResponseClass != ResponseClassType::SUCCESS) {
            $code = $response_message->ResponseCode;
            $message = $response_message->MessageText;
            fwrite(
                STDERR,
                "Cancellation failed to create with \"$code: $message\"\n"
            );
            continue;
        }
    }
    
}

    
}

?>
