<?php

namespace Fleetbase\Http\Controllers\Api\v1;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Fleetbase\Http\Controllers\Controller;
use Fleetbase\Http\Requests\CreateContactRequest;
use Fleetbase\Http\Requests\UpdateContactRequest;
use Fleetbase\Http\Resources\v1\Contact as ContactResource;
use Fleetbase\Http\Resources\v1\DeletedResource;
use Fleetbase\Models\Contact;

class ContactController extends Controller
{
    /**
     * Creates a new Fleetbase Contact resource.
     *
     * @param  \Fleetbase\Http\Requests\CreateContactRequest  $request
     * @return \Fleetbase\Http\Resources\Contact
     */
    public function create(CreateContactRequest $request)
    {
        // get request input
        $input = $request->only(['name', 'type', 'title', 'email', 'phone', 'meta']);

        // create the contact
        $contact = Contact::updateOrCreate(
            [
                'company_uuid' => session('company'),
                'name' => strtoupper($input['name']),
            ],
            $input
        );

        // response the driver resource
        return new ContactResource($contact);
    }

    /**
     * Updates a Fleetbase Contact resource.
     *
     * @param  string  $id
     * @param  \Fleetbase\Http\Requests\UpdateContactRequest  $request
     * @return \Fleetbase\Http\Resources\Contact
     */
    public function update($id, UpdateContactRequest $request)
    {
        // find for the contact
        try {
            $contact = Contact::findRecordOrFail($id);
        } catch (ModelNotFoundException $exception) {
            return response()->json(
                [
                    'error' => 'Contact resource not found.',
                ],
                404
            );
        }

        // get request input
        $input = $request->only(['name', 'type', 'title', 'email', 'phone', 'meta']);

        // update the contact
        $contact->update($input);
        $contact->flushAttributesCache();

        // response the contact resource
        return new ContactResource($contact);
    }

    /**
     * Query for Fleetbase Contact resources.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Fleetbase\Http\Resources\ContactCollection
     */
    public function query(Request $request)
    {
        $results = Contact::queryFromRequest($request);

        return ContactResource::collection($results);
    }

    /**
     * Finds a single Fleetbase Contact resources.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Fleetbase\Http\Resources\ContactCollection
     */
    public function find($id, Request $request)
    {
        // find for the contact
        try {
            $contact = Contact::findRecordOrFail($id);
        } catch (ModelNotFoundException $exception) {
            return response()->json(
                [
                    'error' => 'Contact resource not found.',
                ],
                404
            );
        }

        // response the contact resource
        return new ContactResource($contact);
    }

    /**
     * Deletes a Fleetbase Contact resources.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Fleetbase\Http\Resources\ContactCollection
     */
    public function delete($id, Request $request)
    {
        // find for the driver
        try {
            $contact = Contact::findRecordOrFail($id);
        } catch (ModelNotFoundException $exception) {
            return response()->json(
                [
                    'error' => 'Contact resource not found.',
                ],
                404
            );
        }

        // delete the contact
        $contact->delete();

        // response the contact resource
        return new DeletedResource($contact);
    }
}
