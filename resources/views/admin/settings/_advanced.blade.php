<div class="sub-section">
    <div class="row">
        <div class="col-md-12">
            <div class="scrollbar-boxx dim-box">
                <div class="ui-sortable">
                    <div class="pml-table-container">
                        <table class="table table-trans tbody-white">
                            <thead>
                                <tr>
                                    <th class="trans-upcase text-semibold">{{ trans('messages.setting.name') }}</th>
                                    <th class="trans-upcase text-semibold">{{ trans('messages.setting.value') }}</th>
                                    <th class="trans-upcase text-semibold"></th>
                                </tr>
                            </thead>
                            <tbody class="bg-white">
                                @foreach ($settings as $name => $setting)
                                    <tr>
                                        <td width="30%">
                                            <span class="list-status pull-left">
                                                <span class="text-semibold">{{ $name }}</span>
                                            </span>
                                        </td>
                                        <td width="20%">
                                            <a href="#" data-type="text"
                                              data-pk="1"
                                              data-url="{{ action('Admin\SettingController@advancedUpdate', ['name' => $name]) }}"
                                              data-title="{{ trans('messages.setting.enter', ['name' => $name]) }}"
                                              class="inline-editable editable editable-click">
                                                {{ $setting['value'] }}
                                            </a>
                                        </td>
                                        <td class="text-muted2">
                                            
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>                
            </div>
        </div>
    
    </div>
</div>