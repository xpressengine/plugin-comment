<div class="panel-group" role="tablist" aria-multiselectable="true">
    <!-- Comment dynamic field box -->
    <div class="panel">
        <div class="panel-heading">
            <div class="pull-left">
                <h3 class="panel-title">{{xe_trans('comment::manage.globalSetting')}}</h3>
            </div>
        </div>
        <div class="panel-collapse collapse in">
            <form method="post" action="{{ route('manage.comment.setting.global.perm') }}">
                <input type="hidden" name="_token" value="{{ csrf_token() }}">

                <div class="panel-body">

                    <div class="row">
                        <div class="col-sm-12">
                            <div class="form-group">
                                <h4>{{ xe_trans('comment::manage.permission.create') }}</h4>
                                <div class="well">
                                    {!! uio('permission', $permArgs['create']) !!}
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="panel-footer">
                    <div class="pull-right">
                        <button type="submit" class="btn btn-primary btn-lg">{{ xe_trans('xe::save') }}</button>
                    </div>
                </div>
            </form>

        </div>
    </div>

</div>