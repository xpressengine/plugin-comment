{{ XeFrontend::css(app('xe.plugin.comment')->assetPath() . '/css/default.css')->load() }}
{{ XeFrontend::css('/assets/core/common/css/temporary.css')->load() }}

{{ XeFrontend::js('/assets/core/common/js/toggleMenu.js')->appendTo('head')->load() }}

@if($config->get('useWysiwyg'))
{{ XeFrontend::css('/plugins/ckeditor/assets/ckeditor/xe3.css')->load() }}

{{ XeFrontend::js([
    '/plugins/ckeditor/assets/ckeditor/ckeditor.js',
    '/plugins/ckeditor/assets/ckeditor/styles.js',
    '/plugins/ckeditor/assets/ckeditor/xe3.js',
    '/plugins/ckeditor/assets/plugins/append.js'
    ])->appendTo('body')->load() }}
@endif

{{ XeFrontend::translation(['xe::autoSave', 'xe::tempSave']) }}


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
