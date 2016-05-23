@if($editor)
    {{ $editor->render() }}
@endif

<div id="comment-area-{{ $target->getUid() }}">
    {!! $inner !!}
</div>

<script type="text/javascript">
    $(function () {
        var props = {!! $props !!};
        comment.urlPrefix = '{{ Config::get('xe.routing.fixedPrefix') }}';
        comment.init(props, $('#comment-area-{{ $target->getUid() }}')[0]);
    });
</script>
