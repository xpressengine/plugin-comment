

(function (window, $) {
    'use strict'

    var comment = {};

    comment.urlPrefix = '';
    comment.init = function (props, container) {
        var obj = new Comment(props, container);

        obj.run();
    };

    /**
     * 코어에서 제공함 . XE.Request 참고
     * @param jqXHR
     * @deprecated
     */
    var jqXHRError = function (jqXHR) {
        var responseText = $.parseJSON(jqXHR.responseText);
        var errorMessage = responseText.message;
        XE.toast('xe-warning', errorMessage ? errorMessage : responseText.exception);
    };

    var url = function (relativePath) {
        var prefix = comment.urlPrefix == '' ? '/' : '/' + comment.urlPrefix;
        return xeBaseURL + prefix + '/comment' + (relativePath.charAt(0) == '/' ? '' : '/') + relativePath;
    };

    function Comment(props, container)
    {
        var defaultProps = {
            targetId: null,
            instanceId: null,
            targetAuthorId: null,
            config: {}
        };

        this.props = $.extend({}, defaultProps, props);
        this.state = {ing: false};
        this.container = container;
    }

    Comment.prototype = {
        loading: false,
        items: [],
        run: function () {
            this.getListMore();

            this.getForm('create', null, function (json) {
                if (!json.html) {
                    return false;
                }

                var self = this,
                    dom = $.parseHTML(json.html),
                    form = new Form(dom, 'create', function (json) {
                        this._assetLoad(json.XE_ASSET_LOAD);

                        var items = this.makeItems($.parseHTML(json.items, document, true)),
                            item = items[0];
                        this.lastIn(item);
                        this.renderItems();

                        this.setTotalCnt(this.getTotalCnt() + 1);
                    }.bind(this), self.props.config.editor);

                form.render(this._getFormBox());

                // todo: 임시 비 사용 처리
                // var temporary = $('textarea', form.getDom()).temporary({
                //     key: 'comment|' + this.props.targetId,
                //     btnLoad: $('.__xe_temp_btn_load', form.getDom()),
                //     btnSave: $('.__xe_temp_btn_save', form.getDom()),
                //     container: $('.__xe_temp_container', form.getDom()),
                //     withForm: true,
                //     callback: function (data) { form.editorSync(); }
                // });

            }.bind(this));

            this.eventBind();
        },
        getListMore: function () {
            if (this.loading === true) {
                return false;
            }
            this.loading = true;
            var data = {
                targetId: this.props.targetId,
                instanceId: this.props.instanceId,
                targetAuthorId: this.props.targetAuthorId
            };

            if (this.items.length > 0) {
                var item = this.getLast();
                $.extend(data, {offsetHead: item.getHead(), offsetReply: item.getReply()});
            }

            XE.ajax({
                url: url('/index'),
                type: 'get',
                dataType: 'json',
                data: data,
                success: function (json) {
                    this._assetLoad(json.XE_ASSET_LOAD);

                    var items = this.makeItems($.parseHTML(json.items, document, true));
                    for (var i in items) {
                        if (this.props.config.reverse === true) {
                            this.append(items[i]);
                        } else {
                            this.prepend(items[i]);
                        }
                    }
                    this.renderItems();

                    this.setTotalCnt(json.totalCount);

                    if (json.hasMore === true) {
                        $('.__xe_comment_btn_more', this.container).show();
                        $('.__xe_comment_remain_cnt', this.container).text(json.totalCount - this.items.length);
                    } else {
                        $('.__xe_comment_btn_more', this.container).hide();
                    }
                }.bind(this)
            }).always(function () {
                this.loading = false;
            }.bind(this));
        },
        _assetLoad: function (assets) {
            var cssList = assets.css || {},
                jsList = assets.js || {};

            $.each(cssList, function (i, css) {
                DynamicLoadManager.cssLoad(css);
            });
            $.each(jsList, function (i, js) {
                DynamicLoadManager.jsLoad(js);
            });
        },
        makeItems: function (doms) {
            var items = [];
            for (var i in doms) {
                if ($(doms[i]).is('.__xe_comment_list_item')) {
                    items.push(new Item(doms[i]));
                }
            }

            return items;
        },
        prepend: function (item) {
            this.items.splice(0, 0, item);
        },
        append: function (item) {
            this.items.push(item);
        },
        replace: function (item) {
            var self = this;
            $.each(this.items, function (i, o) {
                if (o.getId() == item.getId()) {
                    self.items.splice(i, 1, item);
                    return false;
                }
            });
        },
        spotIn: function (item) {
            if (!item.getParentId()) {
                this.lastIn(item);
                return;
            }

            var items = this.items, len = items.length, parentIndent = -1;

            for (var i in items) {
                if (parentIndent > -1 && items[i].getIndent() <= parentIndent) {
                    items.splice(i, 0, item);
                    return;
                }

                if (items[i].getId() == item.getParentId()) {
                    parentIndent = items[i].getIndent();
                }
            }

            this.lastIn(item);
        },
        lastIn: function (item) {
            if (this.props.config.reverse === true) {
                this.prepend(item);
            } else {
                this.append(item);
            }
        },
        renderItems: function () {
            //this._getListBox().empty();

            var prev = null;
            $.each(this.items, function (i, item) {
                var dom = item.getDom();

                if (!this.rendered(item)) {
                    if (prev !== null) {
                        $(prev.getDom()).after(dom);
                    } else {
                        this._getListBox().prepend(dom);
                    }
                } else if (item.isChanged()) {
                    var old = this._getListBox().find('[data-id="' + item.getId() + '"]')[0];
                    $(old).before(dom);
                    $(old).remove();
                    item.unsetChanged();
                }

                prev = item;

            }.bind(this));

            XE.Component.timeago();
        },
        removeItem: function (item) {
            var ing = false, targets = [], self = this;
            $.each(this.items, function (i, o) {
                if (o.getId() == item.getId()) {
                    ing = true;
                } else if (ing === true && o.getIndent() <= item.getIndent()) {
                    return false;
                }

                if (ing === true) {
                    targets.push(o);
                }
            });

            var $targets = null;
            $.each(targets, function (i, o) {
                if ($targets === null) {
                    $targets = $(o.getDom());
                } else {
                    $targets.add(o);
                }
            });

            $targets.fadeOut('slow', function () {
                $.each(targets, function (i, o) {
                    self.remove(o);
                });

                self.renderItems();

                self.setTotalCnt(self.getTotalCnt() - targets.length);
            });
        },
        rendered: function (item) {
            return this._getListBox().find('[data-id="' + item.getId() + '"]').length > 0;
        },
        setTotalCnt: function (cnt) {
            $(this.container).data('total-cnt', cnt);

            $('.__xe_comment_cnt', this.container).text(cnt);
        },
        getTotalCnt: function () {
            return parseInt($(this.container).data('total-cnt') ? $(this.container).data('total-cnt') : 0);
        },
        getLast: function () {
            if (this.props.config.reverse === true) {
                return this.items[this.items.length - 1];
            } else {
                return this.items[0];
            }
        },
        getForm: function (mode, id, callback) {
            // mode is create, edit and reply
            var data = {
                targetId: this.props.targetId,
                instanceId: this.props.instanceId,
                targetAuthorId: this.props.targetAuthorId
            };
            $.extend(data, {mode: mode, id: id});

            XE.ajax({
                url: url('/form'),
                type: 'get',
                dataType: 'json',
                data: data,
                success: function (json) {
                    callback(json);
                }.bind(this)
            });
        },

        find: function (id) {
            var item = null;
            $.each(this.items, function (i, o) {
                if (o.getId() == id) {
                    item = o;
                    return false;
                }
            });

            return item;
        },
        remove: function (item) {
            $.each(this.items, function (i, o) {
                if (o.getId() == item.getId()) {
                    this.items.splice(i, 1);
                    item.remove();
                    return false;
                }
            }.bind(this));
        },
        reset: function () {
            $.each(this.items, function (i, o) {
                o.removeForm();
            });
        },
        eventBind: function () {
            var self = this;
            // button list more
            $('.__xe_comment_btn_more', this.container).click(function (e) {
                e.preventDefault();

                self.getListMore();
            });

            // button submit
            $(this.container).on('click', '.__xe_comment_btn_submit', function (e) {
                e.preventDefault();

                $(this).closest('form').trigger('submit');
            });

            // button item reply
            $(this.container).on('click', '.__xe_comment_btn_reply', function (e) {
                e.preventDefault();

                var context = $(this).closest('.__xe_comment_list_item'),
                    item = self.find(context.data('id')),
                    existsForm = item.getForm();

                if (existsForm !== null && existsForm.getMode() == 'reply') {
                    item.removeForm();
                    return false;
                }

                if (self.state.ing === true) {
                    return false;
                }
                self.state.ing = true;

                self.reset();

                self.getForm('reply', item.getId(), function (json) {
                    item.setForm(new Form($.parseHTML(json.html), 'reply', function (json) {
                        self._assetLoad(json.XE_ASSET_LOAD);

                        var items = self.makeItems($.parseHTML(json.items, document, true)),
                            child = items[0];
                        self.spotIn(child);
                        item.removeForm();
                        self.renderItems();
                    }, self.props.config.editor));

                    self.state.ing = false;
                });
            });

            // button item edit
            $(this.container).on('click', '.__xe_comment_btn_edit', function (e) {
                e.preventDefault();

                var context = $(this).closest('.__xe_comment_list_item'),
                    item = self.find(context.data('id'));

                var oldForm = item.getForm();
                if (oldForm && oldForm.getMode() == 'edit') {
                    item.removeForm();
                    return false;
                }

                if (self.state.ing === true) {
                    return false;
                }
                self.state.ing = true;

                self.reset();

                var mode = 'edit',
                    callback = function (json) {
                        var editor = $.extend(true, {}, self.props.config.editor);
                        $.extend(editor.options, json.etc);
                        var form = new Form($.parseHTML(json.html), mode, function (json) {
                            self._assetLoad(json.XE_ASSET_LOAD);

                            var items = self.makeItems($.parseHTML(json.items, document, true)),
                                item = items[0];
                            item.setChanged();
                            self.replace(item);
                            self.renderItems();
                        }, editor);

                        item.setForm(form);
                    };
                self.getForm(mode, item.getId(), function (json) {
                    if (json.mode === 'certify') {
                        var form = new Certify($.parseHTML(json.html), callback);
                        item.setForm(form);
                    } else {
                        callback(json);
                    }

                    self.state.ing = false;
                });
            });

            // button item destroy
            $(this.container).on('click', '.__xe_comment_btn_destroy', function (e) {
                e.preventDefault();

                if (self.state.ing === true) {
                    return false;
                }
                self.state.ing = true;

                var context = $(this).closest('.__xe_comment_list_item'),
                    item = self.find(context.data('id'));

                self.reset();

                var callback = function (json) {
                    if (!json.success) {
                        XE.toast('warning', XE.Lang.trans('comment::msgRemoveUnable'));
                    } else if (json.items) {
                        var items = self.makeItems($.parseHTML(json.items)),
                            nitem = items[0];
                        self.replace(nitem);
                        self.renderItems();
                    } else {
                        self.removeItem(item);
                        // item.fadeOut(function () {
                        //     self.remove(item);
                        //     self.renderItems();
                        //
                        //     self.setTotalCnt(self.getTotalCnt() - 1);
                        // });
                    }
                };

                XE.ajax({
                    url: url('/destroy'),
                    type: 'post',
                    dataType: 'json',
                    data: {instanceId: self.props.instanceId, id: item.getId()},
                    success: function (json) {
                        if (json.mode && json.mode === 'certify') {
                            var dom = $.parseHTML(json.html);
                            item.setForm(new Certify(dom, callback));
                        } else {
                            callback(json);
                        }
                    }
                }).always(function () {
                    self.state.ing = false;
                });
            });

            $(this.container).on('click', '.__xe_comment_btn_toggle_file', function (e) {
                e.preventDefault();

                $(this).toggleClass('on');
            });
        },
        _getListBox: function () {
            return $('.__xe_comment_list', this.container);
        },
        _getFormBox: function () {
            return $('.__xe_comment_form', this.container);
        }
    };

    function Item(dom)
    {
        this.dom = dom;
        this.form = null;
        this.state = {ing: false, changed: false};

        this.eventBind();
    }

    Item.prototype = {
        getDom: function () {
            return this.dom;
        },
        getInstanceId: function () {
            return $(this.dom).data('instanceid');
        },
        getId: function () {
            return $(this.dom).data('id');
        },
        getHead: function () {
            return $(this.dom).data('head');
        },
        getReply: function () {
            return $(this.dom).data('reply');
        },
        getParentId: function () {
            return $(this.dom).data('parentid');
        },
        getIndent: function () {
            return $(this.dom).data('indent');
        },
        setForm: function (form) {
            this.removeForm();

            this.form = form;
            this.renderForm();
        },
        getForm: function () {
            return this.form;
        },
        renderForm: function () {
            if (this.form.getMode() == 'certify' && $('.__xe_comment_certify', this.dom).length > 0) {
                this.form.render($('.__xe_comment_certify', this.dom));
                return;
            }

            if (this.form.getMode() == 'edit') {
                $('.__xe_comment_edit_toggle', this.dom).hide();
                if ($('.__xe_comment_edit_form', this.dom).length > 0) {
                    this.form.render($('.__xe_comment_edit_form', this.dom));
                    return;
                }
            }

            if (this.form.getMode() == 'reply' && $('.__xe_comment_reply_form', this.dom).length > 0) {
                this.form.render($('.__xe_comment_reply_form', this.dom));
                return;
            }

            this.form.render(this.dom, true);
        },
        removeForm: function() {
            if (this.form) {
                this.form.remove();
                this.form = null;
            }

            $('.__xe_comment_edit_toggle', this.dom).show();
        },
        // fadeOut: function (callback) {
        //     $(this.dom).fadeOut('slow', callback);
        // },
        setVoteCnt: function (type, cnt){
            $('.__xe_comment_count.__xe_' + type, this.dom).text(cnt);
        },
        eventBind: function () {
            var self = this;

            $('.__xe_comment_btn_vote', this.dom).click(function (e) {
                e.preventDefault();

                if (self.state.ing === true) {
                    return false;
                }
                self.state.ing = true;

                var type = self._currentVoteType(this),
                    urlSuffix = $(this).hasClass('on') ? 'voteOff' : 'voteOn';

                XE.ajax({
                    url: url('/' + urlSuffix),
                    type: 'post',
                    dataType: 'json',
                    data: {instanceId: self.getInstanceId(), id: self.getId(), option: type.current},
                    success: function (json) {
                        if (json[type.current] || json[type.current] === 0) {
                            self.setVoteCnt(type.current, json[type.current]);
                            $(this).toggleClass('on');
                            $('.__xe_comment_count.__xe_' + type.current, self.dom).trigger('click', [true]);
                        }
                    }.bind(this)
                }).always(function () {
                    self.state.ing = false;
                });
            });

            $('.__xe_comment_count', this.dom).click(function (e, noToggle) {
                e.preventDefault();

                var type = self._currentVoteType(this);

                if (!noToggle) {
                    $('.__xe_comment_count.__xe_' + type.opposite, self.dom).removeClass('on');
                    $('.__xe_comment_voters.__xe_' + type.opposite, self.dom).hide();

                    $(this).toggleClass('on');
                }

                if (!$(this).hasClass('on')) {
                    $('.__xe_comment_voters.__xe_' + type.current, self.dom).hide();
                    return;
                }

                XE.page(url('/votedUser'), $('.__xe_comment_voters.__xe_' + type.current, self.dom), {
                    data: {
                        instanceId: self.getInstanceId(),
                        id: self.getId(),
                        option: type.current
                    }
                }, function () {
                    $('.__xe_comment_voters.__xe_' + type.current, self.dom).show();
                });
            });
        },
        _currentVoteType: function (elem) {
            if ($(elem).hasClass('__xe_assent')) {
                return {current: 'assent', opposite: 'dissent'};
            } else if ($(elem).hasClass('__xe_dissent')) {
                return {current: 'dissent', opposite: 'assent'};
            }

            console.error('unknown vote type');
        },
        remove: function () {
            $(this.dom).remove();
        },
        isChanged: function () {
            return this.state.changed;
        },
        setChanged: function () {
            this.state.changed = true;
        },
        unsetChanged: function () {
            this.state.changed = false;
        }
    };

    function Form(dom, mode, callback, editorData)
    {
        this.dom = dom;
        this.mode = mode;
        this.callback = callback;
        this.editorData = editorData;
        this.editor = null;
        this.container = null;
    }

    Form.prototype = {
        render: function (container, noReplace) {
            if (noReplace === true) {
                $(container).append(this.dom);
            } else {
                $(container).html(this.dom);
            }

            this.container = container;

            if (this.editorData !== null) {
                this.initEditor();
            }

            this._eventBind();
        },
        initEditor: function () {
            if (typeof(XEeditor) == 'undefined') {
                return ;
            }

            var id = 'comment_textarea_' + (new Date().getTime());
            $('textarea', this.dom).attr('id', id).css('width', '100%');
            var editor = XEeditor.getEditor(this.editorData.name).create(id, this.editorData.options, this.editorData.customOptions, this.editorData.tools);

            editor.on('focus', function () {
                $(id).triggerHandler('focus');
            });
            editor.on('change', function () {
                $(id).triggerHandler('input');
            });

            this.editor = editor;
        },
        editorSync: function () {
            if (this.editor) {
                this.editor.setContents($('textarea', this.dom).val());
            }
        },
        getDom: function () {
            return this.dom;
        },
        getMode: function () {
            return this.mode;
        },
        _getForm: function () {
            return $(this.dom).is('form') ? this.dom : $('form', this.dom);
        },
        remove: function () {
            $(this.container).off('submit', 'form');
            $(this.dom).remove();
        },
        _eventBind: function () {
            var self = this;
            var submitting = false;

            $(this.container).on('submit', 'form', function (e) {
                e.preventDefault();

                if(submitting) {
                    return false;
                }

                $('button', self.dom)
                    .add('input[type=submit]', self.dom)
                    .add('input[type=button]', self.dom)
                    .prop('disabled', true);

                if(!submitting) {
                    submitting = true;
                }

                XE.ajax({
                    url: $(this).attr('action'),
                    type: 'post',
                    dataType: 'json',
                    data: $(this).serialize(),
                    success: function (json) {
                        self.callback(json);
                        $(self._getForm()).trigger('reset');
                        if (self.editor && self.getMode() == 'create') {
                            self.editor.reset();
                        }
                    }
                }).always(function () {
                    $('button', self.dom)
                        .add('input[type=submit]', self.dom)
                        .add('input[type=button]', self.dom)
                        .prop('disabled', false);

                    submitting = false;
                });
            });
        }
    };

    function Certify(dom, callback)
    {
        this.dom = dom;
        this.mode = 'certify';
        this.callback = callback;
        this.editorData = null;
        this.editor = null;
        this.container = null;
    }

    Certify.prototype = Form.prototype;


    window.comment = comment;

    return comment;
})(typeof window !== "undefined" ? window : this, jQuery);


var CommentVotedVirtualGrid = (function() {

    var self, grid, dataView;

    var ajaxRunning = false;    //ajax중인지

    var startId,
        limit,
        isLastRow = false;      //마지막 row인지

    return {
        init: function() {

            var self = CommentVotedVirtualGrid;
            var columns = [{
                //selectable: false,
                formatter: function(row, cell, value, columnDef, dataContext) {
                    var tmpl = [
                        '<!--[D] 링크가 아닌 경우 div 로 교체 -->',
                        '<a href="__profilePage__" class="list-inner-item">',
                        '<!--[D] 실제 이미지 사이즈는 모바일 대응 위해 일대일 비율로 96*96 이상-->',
                        '<div class="img-thumbnail"><img src="__src__" width="48" height="48" alt="__alt__" /></div>',
                        '<div class="list-text">',
                        '<p>__alt__</p>',
                        '</div>',
                        '</a>',
                    ].join("\n");

                    return tmpl.replace(/__src__/g, dataContext.profileImage).replace(/__alt__/g, dataContext.displayName).replace(/__profilePage__/g, dataContext.profilePage);
                }
            }];

            var options = {
                editable: false,
                enableAddRow: true,
                enableColumnReorder: false,
                enableCellNavigation: false,
                // asyncEditorLoading: false,
                // autoEdit: false,
                rowHeight: 80,
                headerHeight: 0,
                showHeaderRow: false
            };

            // var data = [];
            $(".xe-list-group").css("height", "365px");
            dataView = new Slick.Data.DataView();
            grid = new Slick.Grid(".xe-list-group", dataView, columns, options);
            grid.setHeaderRowVisibility(false);

            $(".slick-header").hide();


            id= 0;
            ajaxRunning = false;
            isLastRow = false;
            startId = 0;
            limit = 10;

            self.getRows();
            self.bindEvent();

            return self;
        },
        bindEvent: function() {
            grid.onScroll.subscribe(function(e, args) {

                var $viewport = $(".xe-modal").find(".slick-viewport"),
                    loadBlockCnt = 3;   //3 page 정도 남으면 reload함, 1page - modal body height 기준.

                if(!ajaxRunning && !isLastRow && ($viewport[0].scrollHeight - $viewport.scrollTop()) < ($viewport.outerHeight() * loadBlockCnt)) {
                    CommentVotedVirtualGrid.getRows();
                }

            });

            dataView.onRowCountChanged.subscribe(function (e, args) {
                grid.updateRowCount();
                grid.render();
            });

            dataView.onRowsChanged.subscribe(function (e, args) {
                grid.invalidateRows(args.rows);
                grid.render();
            });
        },
        getRows: function() {

            ajaxRunning = true;
            
            var data = $(".xe-list-group").data('data');
            data = data ? (typeof(data) !== 'object' ? JSON.parse(data) : data) : {};
            data['limit'] = limit;

            if(startId !== 0) {
                data['startId'] = startId;
            }

            XE.ajax({
                url: $(".xe-list-group").data('url'),
                type: 'get',
                dataType: 'json',
                data: data,
                success: function(data) {

                    if(data.nextStartId === 0) {
                        isLastRow = true;
                    }

                    startId = data.nextStartId;

                    for(var k = 0, max = data.list.length; k < max; k += 1) {
                        dataView.addItem(data.list[k]);
                    }

                }
            }).done(function() {
                ajaxRunning = false;
            });
        }
    }
})();