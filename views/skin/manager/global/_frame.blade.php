@section('page_title')
    <h2>{{xe_trans('comment::manage.globalSetting')}}</h2>
@endsection

@section('page_description')
    <small>{{ xe_trans('comment::manage.globalSettingDesc') }}</small>
@endsection

<ul class="nav nav-tabs">
    <li @if($_active == 'config') class="active" @endif><a href="{{ route('comment::setting.global.config') }}">{{xe_trans('comment::manage.globalSetting')}}</a></li>
    <li @if($_active == 'perm') class="active" @endif><a href="{{ route('comment::setting.global.perm') }}">{{xe_trans('xe::permission')}}</a></li>
</ul>

{!! $content !!}