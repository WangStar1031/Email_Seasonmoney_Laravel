<?php

namespace Acelle\Http\Controllers\Api;

use Illuminate\Http\Request;
use Acelle\Http\Controllers\Controller;

/**
 * /api/v1/lists - API controller for managing lists.
 */
class MailListController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        parent::__construct();

        $user = \Auth::guard('api')->user();

        if (!$user->customer->canUseApi()) {
            echo json_encode(array('message' => 'Unauthorized'));
            exit;
        }
    }

    /**
     * Display all user's lists.
     *
     * GET /api/v1/lists
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $user = \Auth::guard('api')->user();

        $lists = \Acelle\Model\MailList::getAll()
            ->select('uid', 'name', 'default_subject', 'from_email', 'from_name', 'status', 'created_at', 'updated_at')
            ->where('customer_id', '=', $user->customer->id)
            ->get();

        return \Response::json($lists, 200);
    }

    /**
     * Display the specified list information.
     *
     * GET /api/v1/lists/{id}
     *
     * @param int $id List's id
     *
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $user = \Auth::guard('api')->user();

        $item = \Acelle\Model\MailList::
            where('uid', '=', $id)
            ->first();

        // check if item exists
        if (!is_object($item)) {
            return \Response::json(array('message' => 'Item not found'), 404);
        }

        // authorize
        if (!$user->can('read', $item)) {
            return \Response::json(array('message' => 'Unauthorized'), 401);
        }

        // list info
        $list = [
            'uid' => $item->uid,
            'name' => $item->name,
            'default_subject' => $item->default_subject,
            'from_email' => $item->from_email,
            'from_name' => $item->from_name,
            'remind_message' => $item->remind_message,
            'status' => $item->status,
            'created_at' => $item->created_at,
            'updated_at' => $item->updated_at,
        ];

        // List fields
        $list['fields'] = [];
        foreach ($item->getFields as $key => $field) {
            $list['fields'][] = [
                'label' => $field->label,
                'type' => $field->type,
                'tag' => $field->tag,
                'default_value' => $field->default_value,
                'visible' => $field->visible,
                'required' => $field->required,
                'custom_order' => $field->custom_order,
            ];
        }

        // Contact information
        $contact = null;
        if (isset($item->contact)) {
            // contact information
            $contact = [
                'company' => $item->contact->company,
                'address_1' => $item->contact->address_1,
                'address_2' => $item->contact->address_2,
                'country' => $item->contact->country->name,
                'state' => $item->contact->state,
                'zip' => $item->contact->zip,
                'phone' => $item->contact->phone,
                'url' => $item->contact->url,
                'email' => $item->contact->email,
                'city' => $item->contact->city,
            ];
        }

        // statistics
        $statistics = [
            'subscriber_count' => $item->subscribersCount(),
            'open_uniq_rate' => $item->openUniqRate(),
            'click_rate' => $item->clickRate(),
            'subscribe_rate' => $item->subscribeRate(),
            'unsubscribe_rate' => $item->unsubscribeRate(),
            'unsubscribe_count' => $item->unsubscribeCount(),
            'unconfirmed_count' => $item->unconfirmedCount(),
        ];

        return \Response::json(['list' => $list, 'contact' => $contact, 'statistics' => $statistics], 200);
    }

    /**
     * Create new list.
     *
     * POST /api/v1/lists/store
     *
     * @param \Illuminate\Http\Request $request All list information.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $user = \Auth::guard('api')->user();
        $list = new \Acelle\Model\MailList();

        // authorize
        if (!$user->can('create', $list)) {
            return \Response::json(array('status' => 0, 'message' => trans('no_more_item')), 403);
        }

        // validate and save posted data
        if ($request->isMethod('post')) {
            $validator = \Validator::make($request->all(), \Acelle\Model\MailList::$rules);
            if ($validator->fails()) {
                return response()->json($validator->messages(), 403);
            }

            // Save contact
            $contact = \Acelle\Model\Contact::create($request->all()['contact']);
            $list->fill($request->all());
            $list->customer_id = $user->customer->id;
            $list->contact_id = $contact->id;
            $list->save();

            // Log
            $list->log('created', $user->customer);

            // Trigger updating related campaigns cache
            $list->updateCachedInfo();

            return \Response::json(array(
                'status' => 1, 
                'message' => trans('messages.list.created'),
                'list_uid' => $list->uid
            ), 200);
        }
    }
}
