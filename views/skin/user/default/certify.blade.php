<form class="comment_form" action="{{ route('comment::certify') }}">
    <input type="hidden" name="mode" value="{{ $mode }}">
    <input type="hidden" name="instance_id" value="{{ $comment->instance_id }}">
    <input type="hidden" name="target_id" value="{{ $comment->target_id }}">
    <input type="hidden" name="id" value="{{ $comment->id }}">
    <div class="comment_form_controller">
        <input type="email" class="bd_input v2" name="email" placeholder="E-mail">
        <input type="password" class="bd_input" name="certify_key" placeholder="Password">
        <button class="bd_btn btn_default">{{ xe_trans('comment::submit') }}</button>
    </div>
</form>
