@if($editor)
    {{ $editor->render() }}
@endif

<div
        id="comment-area-{{ $target->getUid() }}"
        data-target_id="{{ $target->getUid() }}"
        data-instance_id="{{ $instanceId }}"
        data-target_type="{{ method_exists($target, 'getMorphType') ? $target->getMorphType() : get_class($target) }}"
        data-urls="{{ json_enc([
            'index' => route('comment::index'),
            'form' => route('comment::form'),
            'destroy' => route('comment::destroy'),
            'voteOn' => route('comment::vote.on'),
            'voteOff' => route('comment::vote.off'),
            'votedUser' => route('comment::voted.user')
        ]) }}"
        data-props="{{ json_enc($props) }}"
>
    {!! $inner !!}
</div>

<script>
jQuery(function ($) {
    window.comment.init($('#comment-area-{{ $target->getUid() }}')[0]);
});
</script>
