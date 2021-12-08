<div class="comment_action_area modify">
    <form action="{{ route('comment::update') }}" class="comment_form">
        <!-- input hidden area start -->
        <input type="hidden" name="instance_id" value="{{ $instanceId }}">
        <input type="hidden" name="id" value="{{ $comment->id }}">
        <!-- input hidden area end -->

        <div class="comment_form_editor">
            <div class="comment_form_ckeditor __xe_content">
                <textarea name="content" placeholder="Write your opinion to here">{!! $comment->content !!}</textarea>
            </div>
            <p>
                <!-- dynamic field area start -->
                @foreach($fieldTypes as $fieldType)
                {!! $fieldType->getSkin()->edit($comment->getAttributes()) !!}
                @endforeach
                <!-- dynamic field area end -->
            </p>
            <div class="comment_form_controller">
                @if(Auth::guest())
                    <div class="comment_form_input">
                        <input type="text" class="bd_input" name="writer" placeholder="Name" value="{{ $comment->writer }}">
                        <input type="password" class="bd_input" name="certify_key" placeholder="Password">
                        <input type="email" class="bd_input v2" name="email" placeholder="E-mail" value="{{ $comment->email }}">
                    </div>
                @endif
                @if($config->get('secret') === true && !Auth::guest())
                    <div class="comment_form_option">
                        <input type="checkbox" name="display" value="secret" id="private_text--{{ $instanceId }}--{{ $comment->id }}" {{ $comment->display === Xpressengine\Plugins\Comment\Models\Comment::DISPLAY_SECRET ? 'checked' : '' }}>
                        <label for="private_text--{{ $instanceId }}--{{ $comment->id }}">{{ xe_trans('comment::secret') }}</label>
                    </div>
                @endif
                <div class="comment_form_btn">
                    <button type="submit" class="xe-btn xe-btn-primary bd_btn btn_submit __xe_comment_btn_submit">{{ xe_trans('comment::save') }}</button>
                </div>
            </div>
        </div>
    </form>
</div>
