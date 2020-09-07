{{ XeFrontend::css('plugins/comment/assets/css/xe-comment-default.css')->load() }}

<div class="xf-comment-box">
    <div class="xf-comment-title-box">
        <div class="xf-comment-title">
            <span class="xf-comment-number __xe_comment_cnt">0</span>
            <span class="xf-comment-title-text">{{ xe_trans('xe::ea') }} {{ xe_trans('xe::comment') }}</span>
        </div>
    </div>

    @if($config->get('reverse') === true)
        <div class="__xe_comment_form"></div>

        <div class="xf-comment-contents __xe_comment_list comment_list">

        </div>

        <div class="pagination_seemore __xe_comment_btn_more" style="display: none;">
            <a href="#">{{ xe_trans('comment::viewMore') }} (<span
                    class="__xe_comment_remain_cnt">0</span>{{ xe_trans('comment::remainCount') }})</a>
        </div>
    @else
        <div class="pagination_seemore __xe_comment_btn_more" style="display: none;">
            <a href="#">{{ xe_trans('comment::viewMore') }} (<span
                    class="__xe_comment_remain_cnt">0</span>{{ xe_trans('comment::remainCount') }})</a>
        </div>

        <div class="xf-comment-contents __xe_comment_list comment_list">

        </div>
        <div class="__xe_comment_form"></div>
    @endif
</div>
