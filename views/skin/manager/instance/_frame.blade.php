@section('page_title')
    <h2>{{xe_trans('comment::manage.detailSetting')}}</h2>
@endsection

@section('page_description')
    <small>{{ xe_trans('comment::manage.detailSettingDesc') }}</small>
@endsection

@section('content_bread_crumbs')
    <a href="{{ XeMenu::getInstanceSettingURI($menuItem) }}"><i class="xi-arrow-left"></i> {{xe_trans($menuItem->title)}}</a>
@endsection

<ul class="nav nav-tabs">
    <li @if($_active == 'config') class="active" @endif><a href="{{ route('comment::setting.config', $targetInstanceId) }}">{{xe_trans('comment::manage.detailSetting')}}</a></li>
    <li @if($_active == 'perm') class="active" @endif><a href="{{ route('comment::setting.perm', $targetInstanceId) }}">{{xe_trans('xe::permission')}}</a></li>
    <li @if($_active == 'skin') class="active" @endif><a href="{{ route('comment::setting.skin', $targetInstanceId) }}">{{xe_trans('xe::skin')}}</a></li>
    <li @if($_active == 'editor') class="active" @endif><a href="{{ route('comment::setting.editor', $targetInstanceId) }}">{{xe_trans('xe::editor')}}</a></li>
    <li @if($_active == 'df') class="active" @endif><a href="{{ route('comment::setting.df', $targetInstanceId) }}">{{xe_trans('xe::dynamicField')}}</a></li>
    <li @if($_active == 'tm') class="active" @endif><a href="{{ route('comment::setting.tm', $targetInstanceId) }}">{{xe_trans('xe::toggleMenu')}}</a></li>
</ul>

{!! $content !!}