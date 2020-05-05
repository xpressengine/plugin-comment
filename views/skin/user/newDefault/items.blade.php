<?php
use Xpressengine\Plugins\Comment\Models\Comment;
use Xpressengine\User\Rating;
?>

@foreach($items as $item)
    <li class="xe-list-comment--list-item @if ($item->getDepth() > 0) xe-list-comment--reply-list-item @endif comment_entity depth{{ $item->getDepth() }} __xe_comment_list_item" id="#comment-{{ $item->id }}" data-instance_id="{{ $item->instance_id }}" data-id="{{ $item->id }}" data-head="{{ $item->head }}" data-reply="{{ $item->reply }}" data-parent_id="{{ $item->parent_id }}" data-indent="{{ $item->getDepth() }}">
        <div class="xe-list-comment--list-item-wrapper">
            <div class="xe-list-comment-list--left-box">
                <a href="#" class="xe-list-comment__link">
                    <span class="xe-list-comment-list__user-image" style="background-image: url({{ $item->getAuthor()->getProfileImage() }})"><span class="blind">유저 이미지</span></span>
                </a>
            </div>
            <div class="xe-list-comment-list--right-box">
                <div class="xe-list-comment-list--title">
                    <div class="xe-list-comment-list--left-box">
                        @if ($item->author != null)
                            <span class="xe-list__display_name">
                                <a href="{{ sprintf('/@%s', $item->user_id) }}" class="xe-list-comment__link {{ $item->getAuthor()->getRating() !== Rating::GUEST ? '__xe_user' : '' }}"
                                   data-toggle="xe-page-toggle-menu"
                                   data-url="{{ route('toggleMenuPage') }}"
                                   data-data='{!! json_encode(['id'=>$item->user_id, 'type'=>'user']) !!}'>{{ $item->writer }}</a>
                           </span>
                        @else
                            <span class="xe-list__display_name">{{ $item->writer }}</span>
                        @endif
                        
                        <span class="xe-list-item___detail xe-list-item___detail-read_count xe-list__mobile-style">
                            <span class="xe-list-item___detail-label">{{ $item->created_at->format('Y.m.d. H:i:s') }}</span>
                        </span>
                    </div>
                    <div class="xe-list-comment-list--right-box">
                        <div class="xe-list-body__edit-box">
                            @can('update-visible', $item)
                                <span class="xe-list-body__edit-item xe-list-body__edit"><a href="#" class="xe-list-comment-list__link comment_modify __xe_comment_btn_edit">수정</a></span>
                            @endcan
                            @can('delete-visible', $item)
                                <span class="xe-list-body__edit-item xe-list-body__delete"><a href="#" class="xe-list-comment-list__link comment_delete __xe_comment_btn_destroy">삭제</a></span>
                            @endcan
                        </div>
                        <div class="xe-list__icon-box">
                            <span class="xe-list__icon xe-list__more"><a href="#" class="comment_more_view xe-list-comment-list__link" data-toggle="xe-page-toggle-menu" data-url="{{route('toggleMenuPage')}}" data-data='{!! json_encode(['id'=>$item->id,'type'=>'comment', 'instanceId'=>$item->instance_id]) !!}' data-side="dropdown-menu-right"><i class="xi-ellipsis-h"></i></a></span>
                        </div>
                    </div>
                </div>
                <div class="xe-list-comment-list--body-text">
{{--                    TODO 비밀 아이콘 스타일--}}
                    @if($item->display == Comment::DISPLAY_SECRET)
                        <span class="bd_ico_lock"><i class="xi-lock"></i><span class="xe-sr-only">secret</span></span>
                    @endif
                    <p class="xe-list-comment-list--body-text-paragraph">
                        @can('read', $item)
                            {!! compile($item->instance_id, $item->getContent(), $item->format === Comment::FORMAT_HTML) !!}
                        @else
                            @if($item->display == Comment::DISPLAY_SECRET)
                                {{ xe_trans('comment::secretContent') }}
                            @else
                                {{ xe_trans('comment::NotAllowContent') }}
                            @endif
                        @endcan

                        @foreach($fieldTypes as $fieldType)
                            {!! $fieldType->getSkin()->show($item->getAttributes()) !!}
                        @endforeach
                    </p>
                </div>
                <div class="xe-list-comment-list--more-info">
{{--                    TODO 첨부파일 스타일 필요--}}
                    @if(count($item->files) > 0)
                        <div class="comment_file_list">
                            <!-- [D] 클릭시 클래스 on 적용 -->
                            <a href="#" class="btn_file __xe_comment_btn_toggle_file">첨부파일 <strong class="file_num">{{ count($item->files) }}</strong></a>
                            <ul>
                                @foreach($item->files as $file)
                                    <li>
                                        <a href="{{ route('editor.file.download', ['instanceId' => $item->instance_id, 'fileId' => $file->id]) }}">
                                            <i class="xi-download"></i> {{ $file->clientname }} <span class="file_size">({{ bytes($file->size) }})</span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    
                    @if ($config->get('useAssent') === true)
                        <div class="xe-list-comment-list__box-assent_count xe-list-comment-list__more-info-item">
                            <span class="blind">추천</span>
                            <a href="#" class="xe-list-comment-list__link btn_share like __xe_comment_btn_vote __xe_assent @if($item->isAssented()) on @endif">
                                <img src="{{ url('plugins/comment/assets/img/assent.svg') }}" alt="추천 아이콘" class="xe-list-comment-list-img">
                            </a>
                            <span class="xe-list-comment-list__assent_count">
                                <a href="#" class="xe-list-comment-list__link btn_share like_num __xe_comment_count __xe_assent">{{ number_format($item->assent_count) }}</a>
                            </span>
                        </div>
                    @endif
                    
                    @if ($config->get('useDissent') === true)
                        <div class="xe-list-comment-list__box-dissent_count xe-list-comment-list__more-info-item">
                            <span class="blind">비추천</span>
                            <a href="#" class="xe-list-comment-list__link btn_share dissent __xe_comment_btn_vote __xe_dissent @if($item->isDissented()) on @endif">
                                <img src="{{ url('plugins/comment/assets/img/dissent.svg') }}" alt="비추천 아이콘" class="xe-list-comment-list-img">
                            </a>
                            <span class="xe-list-comment-list__dissent_count">
                                <a href="#" class="xe-list-comment-list__link btn_share dissent_num __xe_comment_count __xe_dissent">{{ number_format($item->dissent_count) }}</a>
                            </span>
                        </div>
                    @endif
                    @can('create', $instance)
                        <div class="xe-list-comment-list__more-info-item">
                            <span><a href="#" class="xe-list-comment__link btn_share reply __xe_comment_btn_reply">답글달기</a></span>
                        </div>
                    @endcan
                    <div class="vote_list __xe_comment_voters __xe_assent __xe_dissent"></div>
                </div>
            </div>
        </div>
        <div class="__xe_comment_edit_form __xe_comment_reply_form __xe_comment_certify"></div>
    </li>
@endforeach
