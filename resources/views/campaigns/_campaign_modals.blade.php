<a style="display: none" type="button" class="btn btn-default btn-sm" data-toggle="modal" data-target="#file_delete_confirm_model">aa</a>
<!-- Basic modal -->
<div id="file_delete_confirm_model" class="modal fade new-modal">
  <div class="modal-dialog">
    <div class="modal-content">
      <form class="list-confirm-delete-form form-validate-jquery" onkeypress="return event.keyCode != 13;">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal">&times;</button>
          <h4 class="modal-title">{{ trans('messages.are_you_sure') }}</h4>
        </div>

        <div class="modal-body">

            <div class="content">

            </div>

            <div class="form-group">
              <label class="text-normal">{!! trans('messages.type_delete_to_confirm') !!}</label>
              <input class="form-control required" name="delete" />
            </div>

        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-link file_delete_cancel" data-dismiss="modal">{{ trans('messages.cancel') }}</button>
          <a class="btn btn-danger bg-grey list-delete-confirm-button ajax_link">{{ trans('messages.delete') }}</a>
        </div>
      </form>
    </div>
  </div>
</div>
<!-- /basic modal -->
