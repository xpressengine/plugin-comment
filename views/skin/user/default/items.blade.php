@foreach($items as $item)
    <div class="comment_entity depth{{ $item->getDepth() }} __xe_comment_list_item" id="#comment-{{ $item->id }}" data-instanceid="{{ $item->instanceId }}" data-id="{{ $item->id }}" data-head="{{ $item->head }}" data-reply="{{ $item->reply }}" data-parentid="{{ $item->parentId }}" data-indent="{{ $item->getDepth() }}">
        <div class="comment_entity_avatar">
            <img src="{{ $item->getAuthor()->getProfileImage() }}" alt="{{ $item->writer }}">
            <!-- [D] 소셜로그인시 클래스 kakao, google, facebook, github, naver, twitter -->
            {{--<span class="social_badge facebook"></span>--}}
        </div>
        <div class="comment_entity_body">
            <div class="comment_entity_body_meta">
                <!-- [D] 클릭시 클래스 on 적용 -->
                <a href="#" class="author {{ $item->getAuthor()->getRating() !== Xpressengine\User\Rating::GUEST ? '__xe_member' : '' }}" data-id="{{ $item->userId }}">{{ $item->writer }}</a>
                <span class="date" data-xe-timeago="{{ $item->createdAt }}" title="{{ $item->createdAt }}">{{ $item->createdAt }}</span>
                <div class="ly_popup">
                    <ul>
                        <li><a href="#">신고</a></li>
                        <li><a href="#">스패머관리</a></li>
                        <li><a href="#">휴지통</a></li>
                        <li><a href="#">등등</a></li>
                    </ul>
                </div>
                <div class="comment_entity_tool">
                    @can('update-visible', $item)
                    <a href="#" class="comment_modify __xe_comment_btn_edit"><i class="xi-eraser"></i><span class="bd_hidden">수정</span></a>
                    @endcan
                    @can('delete-visible', $item)
                    <a href="#" class="comment_delete __xe_comment_btn_destroy"><i class="xi-trash"></i><span class="bd_hidden">삭제</span></a>
                    @endcan
                    <!-- [D] 클릭시 클래스 on 적용 -->
                    <a href="#" class="comment_more_view" data-toggle="xe-page-toggle-menu" data-url="{{route('toggleMenuPage')}}" data-data='{!! json_encode(['id'=>$item->id,'type'=>'comment', 'instanceId'=>$item->instanceId]) !!}' data-side="dropdown-menu-right"><i class="xi-ellipsis-h"></i><span class="xe-sr-only">{{ xe_trans('xe::more') }}</span></a>
                </div>
            </div>
            <div class="xe_content __xe_comment_edit_toggle">
                @can('read', $item)
                {!! compile($item->instanceId, $item->getContent(), $item->format === Xpressengine\Plugins\Comment\Models\Comment::FORMAT_HTML) !!}
                @else
                    @if($item->display == Xpressengine\Plugins\Comment\Models\Comment::DISPLAY_SECRET)
                        {{ xe_trans('comment::SecretContent') }}
                    @else
                        {{ xe_trans('comment::NotAllowContent') }}
                    @endif
                @endcan

                <p>
                    @foreach($fieldTypes as $fieldType)
                        {!! $fieldType->getSkin()->show($item->getAttributes()) !!}
                    @endforeach
                </p>
            </div>
            <div class="comment_action __xe_comment_edit_toggle">
                @if(count($item->files) > 0)
                    <div class="comment_file_list">
                        <!-- [D] 클릭시 클래스 on 적용 -->
                        <a href="#" class="btn_file __xe_comment_btn_toggle_file">첨부파일 <strong class="file_num">{{ count($item->files) }}</strong></a>
                        <ul>
                            @foreach($item->files as $file)
                                <li>
                                    <a href="{{ route('editor.file.download', ['instanceId' => $item->instanceId, 'fileId' => $file->id]) }}">
                                        <i class="xi-download"></i> {{ $file->clientname }} <span class="file_size">({{ bytes($file->size) }})</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                @if($config->get('useAssent') === true)
                <div class="vote">
                    <!-- [D] 클릭시 클래스 on 적용 -->
                    <a href="#" class="btn_share like __xe_comment_btn_vote __xe_assent @if($item->isAssented()) on @endif">
                        <i class="xi-heart"></i>
                        <span class="bd_hidden">좋아요</span>
                    </a>
                    <!-- [D] 클릭시 클래스 on 적용 및 vote_list 영역 활성화 -->
                    <a href="#" class="btn_share like_num __xe_comment_count __xe_assent">{{ $item->assentCount }}</a>
                </div>
                @endif
                @if($config->get('useDissent') === true)
                <div class="vote">
                    <!-- [D] 클릭시 클래스 on 적용 -->
                    <a href="#" class="btn_share like __xe_comment_btn_vote __xe_dissent @if($item->isDissented()) on @endif">
                        <i class="xi-heart rotate-180"></i>
                        <span class="bd_hidden">좋아요</span>
                    </a>
                    <!-- [D] 클릭시 클래스 on 적용 및 vote_list 영역 활성화 -->
                    <a href="#" class="btn_share like_num __xe_comment_count __xe_dissent">{{ $item->dissentCount }}</a>

                </div>
                @endif
                @can('create', $instance)
                        <!-- [D] 클릭시 클래스 on 적용 및 comment_action_area 활성화 -->
                <a href="#" class="btn_share reply __xe_comment_btn_reply"><i class="xi-reply"></i> {{ xe_trans('comment::reply') }}</a>
                @endcan
                <div class="vote_list __xe_comment_voters __xe_assent __xe_dissent">
                    {{--<ul class="__xe_comment_voters __xe_assent __xe_dissent" style="display: none;"></ul>--}}
                </div>
            </div>
            <div class="__xe_comment_edit_form __xe_comment_reply_form __xe_comment_certify"></div>
        </div>
    </div>
@endforeach
