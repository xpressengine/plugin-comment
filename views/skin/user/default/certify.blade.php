<form class="comment_form" action="{{ route('plugin.comment.certify') }}">
    <input type="hidden" name="mode" value="{{ $mode }}">
    <input type="hidden" name="instanceId" value="{{ $comment->instanceId }}">
    <input type="hidden" name="targetId" value="{{ $comment->targetId }}">
    <input type="hidden" name="id" value="{{ $comment->id }}">
    <div class="comment_form_controller">
        <input type="email" class="bd_input v2" name="email" placeholder="E-mail">
        <input type="password" class="bd_input" name="certifyKey" placeholder="Password">
        <button class="bd_btn btn_default">{{ xe_trans('comment::submit') }}</button>
    </div>
</form>
