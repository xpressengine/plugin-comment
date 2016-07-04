<div class="comment_header">
    <p class="total_count"><span class="total_count_num __xe_comment_cnt">0</span>{{ xe_trans('xe::ea') }} {{ xe_trans('xe::comment') }}</p>
</div>

@if($config->get('reverse') === true)
<div class="__xe_comment_form"></div>

<div class="__xe_comment_list comment_list"></div>

<div class="pagination_seemore __xe_comment_btn_more" style="display: none;">
    <a href="#">{{ xe_trans('comment::viewMore') }} (<span class="__xe_comment_remain_cnt">0</span>{{ xe_trans('comment::remainCount') }})</a>
</div>
@else
<div class="pagination_seemore __xe_comment_btn_more" style="display: none;">
    <a href="#">{{ xe_trans('comment::viewMore') }} (<span class="__xe_comment_remain_cnt">0</span>{{ xe_trans('comment::remainCount') }})</a>
</div>

<div class="__xe_comment_list comment_list"></div>

<div class="__xe_comment_form"></div>
@endif
