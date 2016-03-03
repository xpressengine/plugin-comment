{{ Frontend::css('https://gitcdn.github.io/bootstrap-toggle/2.2.0/css/bootstrap-toggle.min.css')->before('https://maxcdn.bootstrapcdn.com/bootstrap/3.3.1/css/bootstrap.min.css')->load() }}
{{ Frontend::js('https://gitcdn.github.io/bootstrap-toggle/2.2.0/js/bootstrap-toggle.min.js')->appendTo('head')->before('https://maxcdn.bootstrapcdn.com/bootstrap/3.2.0/js/bootstrap.min.js')->load() }}

<div class="panel-group" id="accordion-comment" role="tablist" aria-multiselectable="true">
    <!-- Comment dynamic field box -->
    <div class="panel collapsed-box __xe_section_box">
        <div class="panel-heading">
            <div class="row">
                <p class="txt_tit">{{ xe_trans('comment::manage.setting.basic') }}</p>

                <div class="right_btn pull-right" role="button" data-toggle="collapse" data-parent="#accordion-comment" data-target="#commentBasic">
                    <!-- [D] 메뉴 닫기 시 버튼 클래스에 card_close 추가 및 item_container none/block 처리-->
                    <button class="btn_clse ico_gray pull-left"><i class="xi-angle-down"></i><i class="xi-angle-up"></i><span class="blind">{{xe_trans('xe::menuClose')}}</span></button>
                </div>

            </div>
        </div>

        <div id="commentBasic" class="panel-collapse collapse in" role="tabpanel">
            <div class="panel-body panel-collapse collapse in">
                <ul class="list-group list-group-unbordered">
                    <li class="list-group-item">
                        <form id="fCommentSetting" class="form-horizontal" method="post" action="{{ route('manage.comment.setting') }}">
                            <input type="hidden" name="_token" value="{{ csrf_token() }}">
                            <input type="hidden" name="instanceId" value="{{ $instanceId }}">

                            <div class="form-group">
                                <label class="col-sm-2 control-label">{{ xe_trans('comment::manage.division') }}</label>
                                <div class="col-sm-10">
                                    @if($config->get('division'))
                                        Used
                                    @else
                                        Unused
                                    @endif
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-sm-2 control-label">{{ xe_trans('comment::manage.useApprove') }}</label>
                                <div class="col-sm-4">
                                    <input type="checkbox" name="useApprove" value="true" @if($config->get('useApprove')) checked @endif data-toggle="toggle" data-size="small" data-onstyle="info">
                                </div>

                                <label class="col-sm-2 control-label">{{ xe_trans('comment::manage.secret') }}</label>
                                <div class="col-sm-4">
                                    <input type="checkbox" name="secret" value="true" @if($config->get('secret')) checked @endif data-toggle="toggle" data-size="small" data-onstyle="info">
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-sm-2 control-label">{{ xe_trans('comment::manage.assent') }}</label>
                                <div class="col-sm-4">
                                    <input type="checkbox" name="useAssent" value="true" @if($config->get('useAssent')) checked @endif data-toggle="toggle" data-size="small" data-onstyle="info">
                                </div>

                                <label class="col-sm-2 control-label">{{ xe_trans('comment::manage.dissent') }}</label>
                                <div class="col-sm-4">
                                    <input type="checkbox" name="useDissent" value="true" @if($config->get('useDissent')) checked @endif data-toggle="toggle" data-size="small" data-onstyle="info">
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-sm-2 control-label">{{ xe_trans('comment::manage.perPage') }}</label>
                                <div class="col-sm-4">
                                    <input type="text" class="form-control" name="perPage" value="{{ $config->get('perPage') }}">
                                </div>

                                <label class="col-sm-2 control-label">{{ xe_trans('comment::manage.wysiwyg') }}</label>
                                <div class="col-sm-4">
                                    <input type="checkbox" name="useWysiwyg" value="true" @if($config->get('useWysiwyg')) checked @endif data-toggle="toggle" data-size="small" data-onstyle="info">
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-sm-2 control-label">{{ xe_trans('comment::manage.removeType') }}</label>
                                <div class="col-sm-4">
                                    <select name="removeType" class="form-control">
                                        <option value="batch" @if($config->get('removeType') == 'batch') selected @endif>일괄 삭제</option>
                                        <option value="blind" @if($config->get('removeType') == 'blind') selected @endif>해당 글 가리기</option>
                                    </select>
                                </div>

                                <label class="col-sm-2 control-label">{{ xe_trans('comment::manage.ordering') }}</label>
                                <div class="col-sm-4">
                                    <select name="reverse" class="form-control">
                                        <option value="false" @if($config->get('reverse') !== true) selected @endif>정순</option>
                                        <option value="true" @if($config->get('reverse') === true) selected @endif>역순</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-sm-2 control-label">{{ xe_trans('comment::manage.permission.create') }}</label>
                                <div class="col-sm-10">
                                    <div class="well">
                                        {!! uio('permission', $permArgs['create']) !!}
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-sm-2 control-label">{{ xe_trans('comment::manage.permission.download') }}</label>
                                <div class="col-sm-10">
                                    <div class="well">
                                        {!! uio('permission', $permArgs['download']) !!}
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <div class="col-sm-offset-2 col-sm-10">
                                    <button type="submit" class="btn btn-primary">Save</button>
                                </div>
                            </div>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Skin config box -->
    <div class="panel collapsed-box __xe_section_box">
        <div class="panel-heading">
            <div class="row">
                <p class="txt_tit">{{ xe_trans('comment::manage.setting.skin') }}</p>

                <div class="right_btn pull-right" role="button" data-toggle="collapse" data-parent="#accordion-comment" data-target="#commentSkinSection">
                    <!-- [D] 메뉴 닫기 시 버튼 클래스에 card_close 추가 및 item_container none/block 처리-->
                    <button class="btn_clse ico_gray pull-left"><i class="xi-angle-down"></i><i class="xi-angle-up"></i><span class="blind">{{xe_trans('xe::menuClose')}}</span></button>
                </div>

            </div>
        </div>

        <div id="commentSkinSection" class="panel-collapse collapse" role="tabpanel">
            <div class="panel-body panel-collapse collapse in">
                {!! $skinSection !!}
            </div>
        </div>
    </div>


    <!-- Comment dynamic field box -->
    <div class="panel collapsed-box __xe_section_box">
        <div class="panel-heading">
            <div class="row">
                <p class="txt_tit">{{ xe_trans('comment::manage.setting.dynamicField') }}</p>

                <div class="right_btn pull-right" role="button" data-toggle="collapse" data-parent="#accordion-comment" data-target="#commentDynamicField">
                    <!-- [D] 메뉴 닫기 시 버튼 클래스에 card_close 추가 및 item_container none/block 처리-->
                    <button class="btn_clse ico_gray pull-left"><i class="xi-angle-down"></i><i class="xi-angle-up"></i><span class="blind">{{xe_trans('xe::menuClose')}}</span></button>
                </div>

            </div>
        </div>

        <div id="commentDynamicField" class="panel-collapse collapse" role="tabpanel">
            <div class="panel-body panel-collapse collapse in">
                {!! $dynamicFieldSection !!}
            </div>
        </div>
    </div>

    <!-- Comment toggle menu box -->
    <div class="panel collapsed-box __xe_section_box">
        <div class="panel-heading">
            <div class="row">
                <p class="txt_tit">{{ xe_trans('comment::manage.setting.toggleMenu') }}</p>

                <div class="right_btn pull-right" role="button" data-toggle="collapse" data-parent="#accordion-comment" data-target="#commentToggleMenu">
                    <!-- [D] 메뉴 닫기 시 버튼 클래스에 card_close 추가 및 item_container none/block 처리-->
                    <button class="btn_clse ico_gray pull-left"><i class="xi-angle-down"></i><i class="xi-angle-up"></i><span class="blind">{{xe_trans('xe::menuClose')}}</span></button>
                </div>

            </div>
        </div>

        <div id="commentToggleMenu" class="panel-collapse collapse" role="tabpanel">
            <div class="panel-body panel-collapse collapse in">
                {!! $toggleMenuSection !!}
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(function () {
        $('input[name=useWysiwyg]', '#fCommentSetting').change(function () {
            $('#commentEditor').toggleClass('hidden');
        });

        $('#fCommentSetting').submit(function () {
            $('<input>').attr('type', 'hidden').attr('name', 'redirect').val(location.href).appendTo(this);
        });

    });
</script>
