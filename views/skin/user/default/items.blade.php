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
                    @can('update', $item)
                    <a href="#" class="comment_modify __xe_comment_btn_edit"><i class="xi-eraser"></i><span class="bd_hidden">수정</span></a>
                    @endcan
                    @can('delete', $item)
                    <a href="#" class="comment_delete __xe_comment_btn_destroy"><i class="xi-trash"></i><span class="bd_hidden">삭제</span></a>
                    @endcan
                    <!-- [D] 클릭시 클래스 on 적용 -->
                    <a href="#" class="comment_more_view __xe_comment_menu"><i class="xi-ellipsis-h"></i><span class="bd_hidden">더보기</span></a>

                </div>
            </div>
            <div class="xe_content __xe_comment_edit_toggle">
                @can('read', $item)
                {!! compile($item->instanceId, $item->content, $item->format === Xpressengine\Plugins\Comment\Models\Comment::FORMAT_HTML, $item->id) !!}
                @else
                    @if ($item->display == 'hidden' && $item->status != 'public')
                        {{ xe_trans('comment::RemoveContent') }}
                    @elseif($item->display == 'hidden')
                        {{ xe_trans('comment::BlindContent') }}
                    @elseif($item->display == 'secret')
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
