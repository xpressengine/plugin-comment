<form action="{{ route('plugin.comment.store') }}" class="comment_form">
    <!-- input hidden file area start -->
    <input type="hidden" name="targetId" value="{{ $targetId }}">
    <input type="hidden" name="instanceId" value="{{ $instanceId }}">
    <input type="hidden" name="targetAuthorId" value="{{ $targetAuthorId }}">
    <!-- input hidden file area end -->

    <div class="comment_form_editor">
        <div class="comment_form_ckeditor __xe_content __xe_temp_container">
            <textarea name="content" placeholder="Write your opinion to here"></textarea>
        </div>

        <p>
            <!-- dynamic field area start -->
            @foreach($fieldTypes as $fieldType)
            {!! $fieldType->getSkin()->create([]) !!}
            @endforeach
            <!-- dynamic field area end -->
        </p>

        <div class="comment_form_controller">
            @if(Auth::guest())
            <div class="comment_form_input">
                <input type="text" class="bd_input" name="writer" placeholder="Name">
                <input type="password" class="bd_input" name="certifyKey" placeholder="Password">
                <input type="email" class="bd_input v2" name="email" placeholder="E-mail">
            </div>
            @endif
            @if($config->get('secret') === true && !Auth::guest())
            <div class="comment_form_option">
                <input type="checkbox" name="display" value="secret" id="private_text"><label for="private_text">{{ xe_trans('comment::secret') }}</label>
            </div>
            @endif
            <div class="comment_form_btn">
                @if (Auth::guest() !== true)
                {{--<a href="#" class="bd_btn btn_default __xe_temp_btn_load">{{ xe_trans('comment::tempLoad') }}</a>--}}
                {{--<a href="#" class="bd_btn btn_default __xe_temp_btn_save">{{ xe_trans('comment::tempSave') }}</a>--}}
                @endif
                <a href="#" class="bd_btn btn_submit __xe_comment_btn_submit">{{ xe_trans('comment::save') }}</a>
            </div>
        </div>
    </div>
</form>