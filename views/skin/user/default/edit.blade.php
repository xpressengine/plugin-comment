<div class="comment_action_area modify">
    <form action="{{ route('plugin.comment.update', ['instanceId' => $comment->instanceId, 'id' => $comment->id]) }}" class="comment_form">
        {{ method_field('PUT') }}

        <div class="comment_form_editor">
            <div class="comment_form_ckeditor __xe_content">
                <textarea name="content" placeholder="Write your opinion to here">
                    {!! $comment->content !!}
                </textarea>
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
                        <input type="password" class="bd_input" name="certifyKey" placeholder="Password">
                        <input type="email" class="bd_input v2" name="email" placeholder="E-mail" value="{{ $comment->email }}">
                    </div>
                @endif
                @if($config->get('secret') === true && !Auth::guest())
                    <div class="comment_form_option">
                        <input type="checkbox" name="display" value="secret" id="private_text"><label for="private_text">{{ xe_trans('comment::secret') }}</label>
                    </div>
                @endif
                <div class="comment_form_btn">
                    <a href="#" class="bd_btn btn_submit __xe_comment_btn_submit">{{ xe_trans('comment::save') }}</a>
                </div>
            </div>
        </div>
    </form>
</div>