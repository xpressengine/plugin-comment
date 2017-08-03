@if($editor)
    {{ $editor->render() }}
@endif

<div
        id="comment-area-{{ $target->getUid() }}"
        data-target_id="{{ $target->getUid() }}"
        data-instance_id="{{ $instanceId }}"
        data-target_author_id="{{ $target->getAuthor()->getId() }}"
        data-urls="{{ json_enc([
            'index' => route('plugin.comment.index'),
            'form' => route('plugin.comment.form'),
            'destroy' => route('plugin.comment.destroy'),
            'voteOn' => route('plugin.comment.vote.on'),
            'voteOff' => route('plugin.comment.vote.off'),
            'votedUser' => route('plugin.comment.voted.user')
        ]) }}"
        data-props="{{ json_enc($props) }}"
>
    {!! $inner !!}
</div>

<script type="text/javascript">
    $(function () {
        comment.init($('#comment-area-{{ $target->getUid() }}')[0]);
    });
</script>
