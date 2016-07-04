{{ XeFrontend::css(app('xe.plugin.comment')->assetPath() . '/css/editor_skin.css')->load() }}
{{ XeFrontend::css(app('xe.plugin.comment')->assetPath() . '/css/editor_skin.css')->appendTo('async')->load() }}
<div class="comment_file_list">
    <!-- [D] 클릭시 클래스 on 적용 -->
    <a href="#" class="btn_file on">첨부파일 <strong class="file_num">{{ count($files) }}</strong></a>
    <ul>
        @foreach($files as $file)
            <li>
                <a href="{{ route('editor.file.download', ['instanceId' => $instanceId, 'id' => $file->id])}}">
                    <i class="xi-download"></i> {{ $file->clientname }} <span class="file_size">({{ bytes($file->size) }})</span>
                </a>
            </li>
        @endforeach
    </ul>
</div>