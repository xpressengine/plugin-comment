@foreach($users as $user)
    <li @if(!Auth::guest() && Auth::id() == $user->getId()) class="on" @endif><img src="{{ $user->getProfileImage() }}" alt="{{ $user->getDisplayName() }}" title="{{ $user->getDisplayName() }}"></li>
@endforeach