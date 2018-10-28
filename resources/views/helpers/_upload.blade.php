<div class="form-group control-text">
    <div class="sub_section">
        <h3 class="text-semibold text-teal-800">{{ trans('messages.profile_photo') }}</h3>
        <div class="media profile-image">
            <div class="preview col-md-2" id="{{$dragId}}">
                <a href="#" class="upload-media-container radius-0 pre-upload-photo">
                    <img preview-for="image" empty-src="{{ URL::asset('assets/images/placeholder.jpg') }}" src="{{ $src }}" class="img-circle" alt="">

                </a>
                <span onclick="$('input[name=image]').trigger('click')"  class="edit-photo text-center"><i class="icon-pencil"></i></span>
                <input type="file" name="image" id="{{$preview}}" accept="image/*" class="file-styled previewable hide">
                <input type="hidden" name="_remove_image" value='' />
            </div>
            <div class="col-md-10 padding-l0">
                <h5 class="media-heading text-semibold">{{ trans('messages.upload_your_photo') }}</h5>
                {{ trans('messages.photo_at_least', ["size" => "300px x 300px"]) }}
                <a href="#remove" class=" remove-profile-image"> {{ trans('messages.remove_current_photo') }}</a>
                <br />
                <a href="#upload" onclick="$('input[name=image]').trigger('click')" class="btn btn-xs  background-gray">{{ trans('messages.upload_photo') }}</a>
            </div>
        </div>
    </div>
</div>
<script>
    $(function() {
        var element = document.getElementById("{{$dragId}}");
        var image = document.getElementById("{{$preview}}");
        element.addEventListener('dragover', function(e) {
            e.preventDefault();
            e.stopPropagation();
            element.style="background: #c7dade"

        });

        element.addEventListener('dragleave', function(e) {
            e.preventDefault();
            e.stopPropagation();
            element.style="background: white"

        });
        element.addEventListener('drop', function(e) {
            e.preventDefault();
            e.stopPropagation();
            element.style="background: white";
            var imageType = /image.*/;
            if (e.dataTransfer.files[0].type.match(imageType)) {
                image.files = e.dataTransfer.files
            }

        });
    })
</script>