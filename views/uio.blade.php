<div id="comment-area-{{ $target->getUid() }}">
    {!! $inner !!}
</div>

<script type="text/javascript">
    $(function () {
        var props = {
            targetId: '{{ $target->getUid() }}',
            instanceId: '{{ $instanceId }}',
            targetAuthorId: '{{ $target->getAuthor()->getId() }}',
            config: {
                reverse: eval('({{ $config->get('reverse') ? 'true' : 'false' }})'),
                useWysiwyg: eval('({{ $config->get('useWysiwyg') ? 'true' : 'false' }})')
            }
        };
        comment.urlPrefix = '{{ Config::get('xe.routing.fixedPrefix') }}';
        comment.init(props, $('#comment-area-{{ $target->getUid() }}')[0]);
    });
</script>
