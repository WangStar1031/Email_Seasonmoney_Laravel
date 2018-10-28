<?php

namespace Acelle\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Acelle\Http\Controllers\Controller;
use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;
use Acelle\Model\Template;

class TemplateController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if (!$request->user()->admin->can('read', new \Acelle\Model\Template())) {
            return $this->notAuthorized();
        }

        // If admin can view all sending domains
        if (!$request->user()->admin->can("read_all", new \Acelle\Model\Template())) {
            $request->merge(array("admin_id" => $request->user()->admin->id));
        }

        $templates = \Acelle\Model\Template::search($request);

        return view('admin.templates.index', [
            'templates' => $templates,
        ]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function listing(Request $request)
    {
        if (!$request->user()->admin->can('read', new \Acelle\Model\Template())) {
            return $this->notAuthorized();
        }

        // If admin can view all sending domains
        if (!$request->user()->admin->can("read_all", new \Acelle\Model\Template())) {
            $request->merge(array("admin_id" => $request->user()->admin->id));
        }

        $templates = \Acelle\Model\Template::search($request)->paginate($request->per_page);

        return view('admin.templates._list', [
            'templates' => $templates,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        // Generate info
        $user = $request->user();
        $template = new \Acelle\Model\Template();

        // authorize
        if (!$request->user()->admin->can('create', $template)) {
            return $this->notAuthorized();
        }

        // Get old post values
        if (null !== $request->old()) {
            $template->fill($request->old());
        }

        return view('admin.templates.create', [
            'template' => $template,
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
        // Generate info
        $user = $request->user();
        $template = new \Acelle\Model\Template();
        $template->admin_id = $request->user()->admin->id;

        // authorize
        if (!$request->user()->admin->can('create', $template)) {
            return $this->notAuthorized();
        }

        // validate and save posted data
        if ($request->isMethod('post')) {
            $rules = array(
                'name' => 'required',
                'content' => 'required',
            );

            $this->validate($request, $rules);

            // Save template
            $template->fill($request->all());
            $template->source = 'editor';
            if(isset($request->source)) {
                $template->source = $request->source;
            }
            $template->save();

            // Redirect to my lists page
            $request->session()->flash('alert-success', trans('messages.template.created'));

            return redirect()->action('Admin\TemplateController@index');
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, $uid)
    {
        // Generate info
        $user = $request->user();
        $template = \Acelle\Model\Template::findByUid($uid);

        // authorize
        if (!$request->user()->admin->can('update', $template)) {
            return $this->notAuthorized();
        }

        // Get old post values
        if (null !== $request->old()) {
            $template->fill($request->old());
        }

        return view('admin.templates.edit', [
            'template' => $template,
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
        // Generate info
        $user = $request->user();
        $template = \Acelle\Model\Template::findByUid($request->uid);

        // authorize
        if (!$request->user()->admin->can('update', $template)) {
            return $this->notAuthorized();
        }

        // validate and save posted data
        if ($request->isMethod('patch')) {
            $rules = array(
                'name' => 'required',
                'content' => 'required',
            );

            $this->validate($request, $rules);

            // Save template
            $template->fill($request->all());
            $template->save();

            // Redirect to my lists page
            $request->session()->flash('alert-success', trans('messages.template.updated'));

            return redirect()->action('Admin\TemplateController@index');
        }
    }

    /**
     * Upload template.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function upload(Request $request)
    {
        // authorize
        if (!$request->user()->admin->can('create', Template::class)) {
            return $this->notAuthorized();
        }

        // validate and save posted data
        if ($request->isMethod('post')) {
            $asAdmin = true;
            $template = Template::upload($request, $asAdmin);
            $request->session()->flash('alert-success', trans('messages.template.uploaded'));
            return redirect()->action('Admin\TemplateController@index');
        }

        return view('admin.templates.upload');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function delete(Request $request)
    {
        $items = \Acelle\Model\Template::whereIn('uid', explode(',', $request->uids));

        foreach ($items->get() as $item) {
            // authorize
            if ($request->user()->admin->can('delete', $item)) {
                $item->delete();
            }
        }

        // Redirect to my lists page
        echo trans('messages.templates.deleted');
    }

    /**
     * Buiding email template.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function build(Request $request)
    {
        $template = new \Acelle\Model\Template();
        $template->name = trans('messages.untitled_template');

        // authorize
        if (!$request->user()->admin->can('create', $template)) {
            return $this->notAuthorized();
        }

        $elements = [];
        if(isset($request->style)) {
            $elements = \Acelle\Model\Template::templateStyles()[$request->style];
        }

        return view('admin.templates.build', [
            'template' => $template,
            'elements' => $elements
        ]);
    }

    /**
     * Buiding email template.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function rebuild(Request $request)
    {
        // Generate info
        $user = $request->user();
        $template = \Acelle\Model\Template::findByUid($request->uid);

        // authorize
        if (!$request->user()->admin->can('update', $template)) {
            return $this->notAuthorized();
        }

        return view('admin.templates.rebuild', [
            'template' => $template,
        ]);
    }

    /**
     * Select template style.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function buildSelect(Request $request)
    {
        $template = new \Acelle\Model\Template();

        return view('admin.templates.build_start', [
            'template' => $template,
        ]);
    }

    /**
     * Preview template.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function preview(Request $request, $id)
    {
        $template = \Acelle\Model\Template::findByUid($id);

        // authorize
        if (!$request->user()->admin->can('preview', $template)) {
            return $this->not_authorized();
        }

        // Convert to inline css if template source is builder
        if ($template->source == 'builder') {
            $cssToInlineStyles = new CssToInlineStyles();
            $html = $template->content;
            $css = file_get_contents(public_path("css/res_email.css"));

            // output
            $template->content = $cssToInlineStyles->convert(
                $html,
                $css
            );
        }

        return view('admin.templates.preview', [
            'template' => $template,
        ]);
    }

    /**
     * Save template screenshot.
     *
     * @param \Illuminate\Http\Request $request
     * @param int                      $id
     *
     * @return \Illuminate\Http\Response
     */
    public function saveImage(Request $request, $id)
    {
        $template = \Acelle\Model\Template::findByUid($id);

        // authorize
        if (!$request->user()->admin->can('saveImage', $template)) {
            return $this->not_authorized();
        }

        $upload_loca = 'app/email_templates/';
        $upload_path = storage_path($upload_loca);
        if (!file_exists($upload_path)) {
            mkdir($upload_path, 0777, true);
        }
        $filename = 'screenshot-'.$id.'.png';

        // remove "data:image/png;base64,"
        $uri = substr($request->data, strpos($request->data, ',') + 1);

        // save to file
        file_put_contents($upload_path.$filename, base64_decode($uri));

        // create thumbnails
        $img = \Image::make($upload_path.$filename);
        $img->fit(178, 200)->save($upload_path.$filename.'.thumb.jpg');

        // save
        $template->image = $upload_loca.$filename;
        $template->save();
    }

    /**
     * Template screenshot.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function image(Request $request)
    {
        // Get current user
        $template = \Acelle\Model\Template::findByUid($request->uid);

        // authorize
        if (!$request->user()->admin->can('image', $template)) {
            return $this->notAuthorized();
        }

        if (!empty($template->image)) {
            $img = \Image::make(storage_path($template->image).'.thumb.jpg');
        } else {
            $img = \Image::make(public_path('assets/images/placeholder.jpg'));
        }

        return $img->response();
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
            $item = \Acelle\Model\Template::findByUid($row[0]);

            // authorize
            if (!$request->user()->admin->can('update', $item)) {
                return $this->notAuthorized();
            }

            $item->custom_order = $row[1];
            $item->save();
        }

        echo trans('messages.templates.custom_order.updated');
    }

    /**
     * Copy template.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function copy(Request $request)
    {
        $template = \Acelle\Model\Template::findByUid($request->uid);

        if ($request->isMethod('post')) {
            // authorize
            if (!$request->user()->admin->can('copy', $template)) {
                return $this->notAuthorized();
            }

            $template->copy($request->name, NULL, $request->user()->admin);

            echo trans('messages.template.copied');
            return;
        }

        return view('admin.templates.copy', [
            'template' => $template,
        ]);
    }
}
