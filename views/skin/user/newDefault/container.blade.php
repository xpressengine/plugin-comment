{{ XeFrontend::css('plugins/comment/assets/css/new-comment.css')->load() }}

<div class="xe-list-comment">
    <div class="xe-list-comment--header">
        <div class="xe-list-comment--header-left-box">
            <span class="xe-list-comment--header-count-number __xe_comment_cnt">0</span><span class="xe-list-comment--header-count-text">{{ xe_trans('xe::ea') }} {{ xe_trans('xe::comment') }}</span>
        </div>
    </div>

    @if($config->get('reverse') === true)
        <div class="__xe_comment_form"></div>

        <div class="xe-list-comment--body">
            <ul class="xe-list-comment--list __xe_comment_list comment_list"></ul>
        </div>
    
        <div class="pagination_seemore __xe_comment_btn_more" style="display: none;">
            <a href="#">{{ xe_trans('comment::viewMore') }} (<span class="__xe_comment_remain_cnt">0</span>{{ xe_trans('comment::remainCount') }})</a>
        </div>
    @else
        <div class="pagination_seemore __xe_comment_btn_more" style="display: none;">
            <a href="#">{{ xe_trans('comment::viewMore') }} (<span class="__xe_comment_remain_cnt">0</span>{{ xe_trans('comment::remainCount') }})</a>
        </div>

        <div class="xe-list-comment--body">
            <ul class="xe-list-comment--list __xe_comment_list comment_list"></ul>
        </div>

        <div class="__xe_comment_form"></div>
    @endif
</div>
