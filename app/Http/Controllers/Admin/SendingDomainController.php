<?php

namespace Acelle\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Acelle\Http\Controllers\Controller;

class SendingDomainController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if (!$request->user()->admin->can('read', new \Acelle\Model\SendingDomain())) {
            return $this->notAuthorized();
        }

        // If admin can view all sending domains
        if (!$request->user()->admin->can("readAll", new \Acelle\Model\SendingDomain())) {
            $request->merge(array("admin_id" => $request->user()->admin->id));
        }

        // exlude customer seding domain
        $request->merge(array("no_customer" => true));

        $items = \Acelle\Model\SendingDomain::search($request);

        return view('admin.sending_domains.index', [
            'items' => $items,
        ]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function listing(Request $request)
    {
        if (!$request->user()->admin->can('read', new \Acelle\Model\SendingDomain())) {
            return $this->notAuthorized();
        }

        // If admin can view all sending domains
        if (!$request->user()->admin->can("readAll", new \Acelle\Model\SendingDomain())) {
            $request->merge(array("admin_id" => $request->user()->admin->id));
        }

        // exlude customer seding domain
        $request->merge(array("no_customer" => true));

        $items = \Acelle\Model\SendingDomain::search($request)->paginate($request->per_page);

        return view('admin.sending_domains._list', [
            'items' => $items,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $server = new \Acelle\Model\SendingDomain([
            'signing_enabled' => true,
        ]);
        $server->status = 'active';
        $server->uid = '0';
        $server->fill($request->old());

        // authorize
        if (!$request->user()->admin->can('create', $server)) {
            return $this->notAuthorized();
        }

        return view('admin.sending_domains.create', [
            'server' => $server,
            'readonly' => '0',
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Get current user
        $current_user = $request->user();
        $server = new \Acelle\Model\SendingDomain();

        // authorize
        if (!$request->user()->admin->can('create', $server)) {
            return $this->notAuthorized();
        }

        // save posted data
        if ($request->isMethod('post')) {
            $this->validate($request, \Acelle\Model\SendingDomain::rules());

            // Save current user info
            $server->fill($request->all());
            $server->admin_id = $request->user()->admin->id;
            $server->status = 'active';

            if ($server->save()) {
                $request->session()->flash('alert-success', trans('messages.sending_domain.created'));

                return redirect()->action('Admin\SendingDomainController@show', $server->uid);
            }
        }
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $server = \Acelle\Model\SendingDomain::findByUid($id);
        
        return view('admin.sending_domains.show', [
            'server' => $server,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, $id)
    {
        $server = \Acelle\Model\SendingDomain::findByUid($id);

        // authorize
        if (!$request->user()->admin->can('update', $server)) {
            return $this->notAuthorized();
        }

        $server->fill($request->old());

        return view('admin.sending_domains.edit', [
            'server' => $server,
            'readonly' => 'readonly'
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int                      $id
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        // Get current user
        $current_user = $request->user();
        $server = \Acelle\Model\SendingDomain::findByUid($id);

        // authorize
        if (!$request->user()->admin->can('update', $server)) {
            return $this->notAuthorized();
        }

        // save posted data
        if ($request->isMethod('patch')) {
            $this->validate($request, \Acelle\Model\SendingDomain::rules());

            // Save current user info
            $server->fill($request->all());

            if ($server->save()) {
                $request->session()->flash('alert-success', trans('messages.sending_domain.updated'));

                return redirect()->action('Admin\SendingDomainController@show', $server->uid);
            }
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
    }

    /**
     * Custom sort items.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function sort(Request $request)
    {
        $sort = json_decode($request->sort);
        foreach ($sort as $row) {
            $item = \Acelle\Model\SendingDomain::findByUid($row[0]);

            // authorize
            if (!$request->user()->admin->can('update', $item)) {
                return $this->notAuthorized();
            }

            $item->custom_order = $row[1];
            $item->save();
        }

        echo trans('messages.sending_domain.custom_order.updated');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function delete(Request $request)
    {
        $items = \Acelle\Model\SendingDomain::whereIn('uid', explode(',', $request->uids));

        foreach ($items->get() as $item) {
            // authorize
            if ($request->user()->admin->can('delete', $item)) {
                $item->delete();
            }
        }

        // Redirect to my lists page
        echo trans('messages.sending_domains.deleted');
    }
    
    /**
     * Verify sending domain.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function verify($id)
    {
        $server = \Acelle\Model\SendingDomain::findByUid($id);
        $server->verify();
    }
    
    /**
     * sending domain's records.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function records($id)
    {
        $server = \Acelle\Model\SendingDomain::findByUid($id);

        return view('admin.sending_domains._records', [
            'server' => $server,
        ]);
    }
    
    /**
     * update VerificationTxtName.
     *
     * @param int $id
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function updateVerificationTxtName($id, Request $request)
    {
        $server = \Acelle\Model\SendingDomain::findByUid($id);
        
        // authorize
        if (!$request->user()->admin->can('update', $server)) {
            return $this->notAuthorized();
        }

        if (!$server->setVerificationTxtName($request->value)) {
            return response(trans('messages.sending_domain.verification_hostname.not_valid'), 404)
                ->header('Content-Type', 'text/plain');
        }
    }
    
    /**
     * update VerificationTxtName.
     *
     * @param int $id
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function updateDkimSelector($id, Request $request)
    {
        $server = \Acelle\Model\SendingDomain::findByUid($id);
        
        // authorize
        if (!$request->user()->admin->can('update', $server)) {
            return $this->notAuthorized();
        }

        if (!$server->setDkimSelector($request->value)) {
            return response(trans('messages.sending_domain.dkim_selector.not_valid'), 404)
                ->header('Content-Type', 'text/plain');
        }
    }
}
