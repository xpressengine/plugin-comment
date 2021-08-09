<div class="panel-group" role="tablist" aria-multiselectable="true">
    <div class="panel">
        <div class="panel-heading">
            <div class="pull-left">
                <h3 class="panel-title">{{ xe_trans('xe::toggleMenu') }}</h3>

                <small>
                    <a href="{{ route('comment::setting.global.tm') }}" target="_blank">
                        {{xe_trans('xe::moveToParentSettingPage')}}
                    </a>
                </small>
            </div>
        </div>
        <div id="commentToggleMenu" class="panel-collapse collapse in">
            <div class="panel-body">
                {!! $section !!}
            </div>
        </div>
    </div>
</div>
