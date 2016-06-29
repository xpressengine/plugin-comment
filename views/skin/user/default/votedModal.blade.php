<div class="xe-modal-header">
    <button type="button" class="btn-close" data-dismiss="xe-modal" aria-label="Close"><i class="xi-close"></i></button>
    <strong class="xe-modal-title">{{ $count }}명이 이 글을 좋아합니다.</strong>
</div>

<div class="xe-list-group" data-url="{{ route('plugin.comment.voted.list') }}" data-data="{{ json_enc($data, JSON_HEX_QUOT) }}"></div>

<div class="xe-modal-footer">
    <button type="button" class="xe-btn xe-btn-secondary" data-dismiss="xe-modal">닫기</button>
</div>
