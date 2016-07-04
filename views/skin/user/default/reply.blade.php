<div class="comment_action_area">
    <div class="comment_action_area_avatar">
        <!-- [D] 비로그인 및 프로필 이미지가 없을 경우 기본 이미지 적용 -->
        <img src="{{ Auth::user()->getProfileImage() }}" alt="default profile">
    </div>
    <form action="{{ route('plugin.comment.store') }}" class="comment_form">
        <!-- input hidden area start -->
        <input type="hidden" name="instanceId" value="{{ $comment->instanceId }}">
        <input type="hidden" name="targetId" value="{{ $comment->target->targetId }}">
        <input type="hidden" name="parentId" value="{{ $comment->id }}">
        <input type="hidden" name="targetAuthorId" value="{{ $comment->target->targetAuthorId }}">
        <!-- input hidden area end -->

        <div class="comment_form_editor">
            <div class="comment_form_ckeditor __xe_content">
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
                    <!-- [D] id, for 값 동일하게 적용 -->
                    <input type="checkbox" name="display" value="secret" id="private_text_reply"><label for="private_text_reply">{{ xe_trans('comment::secret') }}</label>
                </div>
                @endif
                <div class="comment_form_btn">
                    <a href="#" class="bd_btn btn_submit __xe_comment_btn_submit">{{ xe_trans('comment::save') }}</a>
                </div>
            </div>
        </div>
    </form>
</div>
