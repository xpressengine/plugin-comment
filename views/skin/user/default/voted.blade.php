<ul>
@foreach($users as $user)
    <li @if(!Auth::guest() && Auth::id() == $user->getId()) class="on" @endif><img src="{{ $user->getProfileImage() }}" alt="{{ $user->getDisplayName() }}" title="{{ $user->getDisplayName() }}"></li>
@endforeach
</ul>
@if ($users->hasMorePages())
    <p class="bd_like_more_text">
        <a href="#" data-toggle="xe-page-modal" data-url="{{ route('plugin.comment.voted.modal') }}" data-data="{{ json_enc($data, JSON_HEX_QUOT) }}" data-callback="CommentVotedVirtualGrid.init">외 {{ $users->total() - $users->perPage() }} 명이 좋아합니다.</a>
    </p>
@endif

