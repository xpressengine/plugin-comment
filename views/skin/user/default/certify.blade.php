<form class="comment_form" action="{{ route('plugin.comment.certify', ['instanceId' => $comment->instanceId, 'id' => $comment->id]) }}">
    <input type="hidden" name="mode" value="{{ $mode }}">
    <div class="comment_form_controller">
        <input type="email" class="bd_input v2" name="email" placeholder="E-mail">
        <input type="password" class="bd_input" name="certifyKey" placeholder="Password">
        <button class="bd_btn btn_default">{{ xe_trans('comment::submit') }}</button>
    </div>
</form>
