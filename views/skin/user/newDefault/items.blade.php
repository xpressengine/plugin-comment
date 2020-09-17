<?php

use Xpressengine\Plugins\Comment\Models\Comment;
use Xpressengine\User\Rating;

?>

@foreach($items as $item)
    <div class="xf-comment-item xf-comment __xe_comment_list_item @if ($item->getDepth() > 0) xf-comment-reply @endif"
         id="#comment-{{ $item->id }}" data-instance_id="{{ $item->instance_id }}" data-id="{{ $item->id }}"
         data-head="{{ $item->head }}" data-reply="{{ $item->reply }}" data-parent_id="{{ $item->parent_id }}"
         data-indent="{{ $item->getDepth() }}">
        <div class="xf-profile-img-box">
            <div class="xf-profile-img"
                 style="background-image: url({{ $item->user->getProfileImage() }});"></div>
        </div>
        <div class="xf-comment-contents-box">
            <div class="xf-comment-info-box">
                <div class="xf-profile-box">
                    <span class="xf-comment-nickname">{{ $item->writer }}</span>
                    <span class="xf-comment-date">{{ $item->created_at->format('Y.m.d') }}</span>
                </div>
                <div class="xf-comment-edit-box xf-list">
                    <ul class="xf-edit-list xf-list">
                        @can('update-visible', $item)
                            <li class="xf-edit-item">
                                <a href="#" class="xf-edit__link xf-a __xe_comment_btn_edit">수정</a>
                            </li>
                        @endcan
                        @can('delete-visible', $item)
                            <li class="xf-edit-item">
                                <a href="#" class="xf-edit__link xf-a __xe_comment_btn_destroy">삭제</a>
                            </li>
                        @endcan
                    </ul>
                    <div class="xf-report-box">
                        <button type="button" class="xf-report-icon xf-comment-btn" data-toggle="xe-page-toggle-menu"
                                data-url="{{route('toggleMenuPage')}}"
                                data-data='{!! json_encode(['id'=>$item->id,'type'=>'comment', 'instanceId'=>$item->instance_id]) !!}'
                                data-side="dropdown-menu-right">
                            <i class="xi-ellipsis-h"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="xf-comment-text-box">
                <p class="xf-p xf-comment-text">
                @if($item->display === Comment::DISPLAY_SECRET)
                    <div class="xf-secret-icon">
                        <span class="xe-sr-only">secret</span>
                    </div>

                @endif

                @can('read', $item)
                    {!! compile($item->instance_id, $item->getContent(), $item->format === Comment::FORMAT_HTML) !!}

                    @foreach($fieldTypes as $fieldType)
                        {!! $fieldType->getSkin()->show($item->getAttributes()) !!}
                    @endforeach
                @else
                    @if($item->display == Comment::DISPLAY_SECRET)
                        <span class="xf-secret-text">
                            {{ xe_trans('comment::secretContent') }}
                        </span>
                        @else
                        {{ xe_trans('comment::NotAllowContent') }}
                        @endif
                        @endcan
                        </p>
            </div>

            @if(count($item->files) > 0)
                <div class="xf-comment-file-box">
                    <!-- [D] 클릭시 클래스 on 적용 -->
                    <a href="#" class="xf-comment-file__toggle __xe_comment_btn_toggle_file xf-a">
                        <span class="xf-comment-file-text">첨부파일</span>
                        <strong class="xf-comment-file__number">{{ count($item->files) }}</strong>
                    </a>
                    <ul class="xf-comment-file-list xf-list">
                        @foreach($item->files as $file)
                            <li class="xf-comment-file-item">
                                <a href="{{ route('editor.file.download', ['instanceId' => $item->instance_id, 'fileId' => $file->id]) }}"
                                   class="xf-a xf-comment-file__link">
                                    <i class="xi-download"></i> {{ $file->clientname }} <span
                                        class="xf-comment-file__size">({{ bytes($file->size) }})</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <div class="xf-community-box">
                @if ($config->get('useAssent') === true || $config->get('useDissent') === true)
                    <div class="xf-assent-box">
                        <ul class="xf-assent-list xf-list">
                            @if ($config->get('useAssent') === true)
                                <li class="xf-assent-item">
                                    <button type="button"
                                            class="xf-assent-btn xf-comment-btn __xe_comment_btn_vote __xe_assent @if($item->isAssented()) on @endif">
                                        <div class="xf-assent xf-assent-icon"></div>
                                        <div
                                            class="xf-assent-text __xe_comment_count __xe_assent">{{ number_format($item->assent_count) }}</div>
                                    </button>
                                </li>
                            @endif

                            @if ($config->get('useDissent') === true)
                                <li class="xf-assent-item">
                                    <button type="button"
                                            class="xf-assent-btn xf-comment-btn __xe_comment_btn_vote __xe_dissent @if($item->isDissented()) on @endif">
                                        <div class="xf-dissent xf-assent-icon"></div>
                                        <div
                                            class="xf-assent-text __xe_comment_count __xe_dissent">{{ number_format($item->dissent_count) }}</div>
                                    </button>
                                </li>
                            @endif
                        </ul>
                    </div>
                @endif
                @can('create', $instance)
                    <div class="xf-reply-btn-box">
                        <button type="button" class="xf-reply-btn xf-comment-btn __xe_comment_btn_reply">
                            <span class="xf-reply__text">답글달기</span>
                        </button>
                    </div>
                @endcan
            </div>
        </div>
    </div>
@endforeach
