<table class="table table-trans tbody-white" class="table-layout:fixed">
    <thead>
        <tr>
            <th class="trans-upcase text-semibold">{{ trans('messages.type') }}</th>
            <th class="trans-upcase text-semibold">{{ trans('messages.host') }}</th>
            <th class="trans-upcase text-semibold">{{ trans('messages.value') }}</th>
            <th></th>
        </tr>
    </thead>
    <tbody class="bg-white">
        <tr>
            <td width="1%">
                <span class="text-muted2 list-status pull-left">
                    <span class="label label-flat bg-pending square-tag">TXT</span>
                </span>
            </td>
            <td width="20%">
                <a href="#" data-type="text" data-pk="1" data-url="{{ action('SendingDomainController@updateVerificationTxtName', $server->uid) }}" data-title="{{ trans('messages.sending_domain.verification_hostname.enter') }}" class="inline-editable">
                    {{ $server->getVerificationTxtName() }}
                </a>
            </td>
            <td>{{ $server->verification_token }}</td>
            <td class="text-right" width="1%">
                @if ($server->domainVerified())
                    <span class="text-muted2 list-status pull-left">
                        <span class="label label-flat bg-active">{{ trans('messages.sending_domain.verified') }}</span>
                    </span>
                @else
                    <span class="text-muted2 list-status pull-left">
                        <span class="label label-flat bg-inactive">{{ trans('messages.sending_domain.pending') }}</span>
                    </span>
                @endif
            </td>
        </tr>
        <tr>
            <td>
                <span class="text-muted2 list-status pull-left">
                    <span class="label label-flat bg-pending square-tag">TXT</span>
                </span>
            </td>
            <td>
                <a href="#" data-type="text" data-pk="1" data-url="{{ action('SendingDomainController@updateDkimSelector', $server->uid) }}" data-title="{{ trans('messages.sending_domain.dkim_selector.enter') }}" class="inline-editable">
                    {{ $server->getDkimSelectorParts()[0] }}
                </a>.{{ $server->getDkimSelectorParts()[1] }}
            </td>
            <td><textarea style="width:100%;border:0;height:100px;resize:none;">{{ $server->getDnsDkimConfig() }}</textarea></td>
            <td class="text-right">
                @if ($server->dkimVerified())
                    <span class="text-muted2 list-status pull-left">
                        <span class="label label-flat bg-active">{{ trans('messages.sending_domain.verified') }}</span>
                    </span>
                @else
                    <span class="text-muted2 list-status pull-left">
                        <span class="label label-flat bg-inactive">{{ trans('messages.sending_domain.pending') }}</span>
                    </span>
                @endif
            </td>
        </tr>
        <tr>
            <td>
                <span class="text-muted2 list-status pull-left">
                    <span class="label label-flat bg-pending square-tag">TXT</span>
                </span>
            </td>
            <td>@</td>
            <td>{{ $server->getSpf() }}</td>
            <td class="text-right">
                @if ($server->spfVerified())
                    <span class="text-muted2 list-status pull-left">
                        <span class="label label-flat bg-active">{{ trans('messages.sending_domain.verified') }}</span>
                    </span>
                @else
                    <span class="text-muted2 list-status pull-left">
                        <span class="label label-flat bg-inactive">{{ trans('messages.sending_domain.pending') }}</span>
                    </span>
                @endif
            </td>
        </tr>
    </tbody>
</table>
