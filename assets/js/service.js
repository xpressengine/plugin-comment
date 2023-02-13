/* global Slick */
/* ES5 */
(function ($) {
  'use strict'
  var comment = {}
  var listeners = {}

  window.XE.Utils.eventify(comment)

  comment.init = function (container) {
    var obj = new Comment(container)

    obj.run()
  }

  /* @deprecated */
  comment.listen = function (eventName, callback) {
    if (typeof eventName === 'object') {
      for (var i in eventName) {
        addListener(i, eventName[i])
      }
    } else {
      addListener(eventName, callback)
    }
  }

  /* @deprecated */
  comment.listeners = listeners

  /* @deprecated */
  function addListener (eventName, callback) {
    if (typeof callback !== 'function') {
      return
    }

    listeners[eventName] = listeners[eventName] || []
    listeners[eventName].push(callback)
  }

  /* @deprecated */
  function fire (eventName, instance, args) {
    var list = listeners[eventName] || []
    for (var i = 0; i < list.length; i++) {
      list[i].apply(instance, args)
    }
  }

  function Comment (container) {
    var defaultProps = {
      config: {}
    }

    this.container = container
    this.props = $.extend({}, defaultProps, $(container).data('props'))
    this.state = {ing: false}
    this.items = []
  }

  Comment.prototype = {
    loading: false,
    items: [],
    run: function () {
      var that = this
      this.getListMore()

      this.getForm('create', null).then(function (data) {
        if (!data.html) {
          return false
        }

        var dom = $.parseHTML(data.html)
        var form = new CommentForm(dom, 'create', function (data) {
          that._assetLoad(data.XE_ASSET_LOAD)

          var items = that.makeItems($.parseHTML(data.items, document, true))
          var item = items[0]
          that.lastIn(item)
          that.renderItems()

          that.setTotalCnt(that.getTotalCnt() + 1)

          fire('created', that, [item])
          comment.$$emit('item.add', { item: item })
        }, that.props.config.editor)

        form.render(that.getFormBox())
      })

      this.eventBind()
    },
    /**
     * 댓글 추가 로드
     */
    getListMore: function () {
      if (this.loading === true) {
        return false
      }

      this.loading = true
      var that = this
      var data = {
        target_id: $(this.container).data('target_id'),
        instance_id: $(this.container).data('instance_id'),
        target_type: $(this.container).data('target_type')
      }

      if (this.items.length > 0) {
        var item = this.getLast()
        $.extend(data, {offsetHead: item.getHead(), offsetReply: item.getReply()})
      }

      window.XE.get($(this.container).data('urls').index, data).then(function (response) {
        that.items = [];
        that._assetLoad(response.data.XE_ASSET_LOAD)

        var items = that.makeItems($.parseHTML(response.data.items, document, true))

        for (var i in items) {
          if (that.props.config.reverse === true) {
            that.append(items[i])
          } else {
            that.prepend(items[i])
          }
        }
        that.renderItems()

        that.setTotalCnt(response.data.totalCount)

        if (response.data.hasMore === true) {
          $('.__xe_comment_btn_more', that.container).show()
          $('.__xe_comment_remain_cnt', that.container).text(response.data.totalCount - that.items.length)
        } else {
          $('.__xe_comment_btn_more', that.container).hide()
        }

        that.loading = false
        fire('loaded', that, [items])
        comment.$$emit('loaded', { items: items })
      })
    },
    _assetLoad: function (assets) {
      var cssList = assets.css || {}
      var jsList = assets.js || {}

      $.each(cssList, function (i, css) {
        window.XE.DynamicLoadManager.cssLoad(css)
      })
      $.each(jsList, function (i, js) {
        window.XE.DynamicLoadManager.jsLoad(js)
      })
    },
    makeItems: function (doms) {
      var items = []
      for (var i in doms) {
        if ($(doms[i]).is('.__xe_comment_list_item')) {
          items.push(new CommentItem(doms[i]))
        }
      }

      return items
    },
    prepend: function (item) {
      this.items.splice(0, 0, item)
    },
    append: function (item) {
      this.items.push(item)
    },
    replace: function (item) {
      var self = this
      $.each(this.items, function (i, o) {
        if (o.getId() == item.getId()) {
          self.items.splice(i, 1, item)
          return false
        }
      })
    },
    spotIn: function (item) {
      if (!item.getParentId()) {
        this.lastIn(item)
        return
      }

      var items = this.items
      var parentIndent = -1

      for (var i in items) {
        if (parentIndent > -1 && items[i].getIndent() <= parentIndent) {
          items.splice(i, 0, item)
          return
        }

        if (items[i].getId() == item.getParentId()) {
          parentIndent = items[i].getIndent()
        }
      }

      this.lastIn(item)
    },
    lastIn: function (item) {
      if (this.props.config.reverse === true) {
        this.prepend(item)
      } else {
        this.append(item)
      }
    },
    renderItems: function () {
      var prev = null
      $.each(this.items, function (i, item) {
        var dom = item.getDom()

        if (!this.rendered(item)) {
          if (prev !== null) {
            $(prev.getDom()).after(dom)
          } else {
            this.getListBox().prepend(dom)
          }
        } else if (item.isChanged()) {
          var old = this.getListBox().find('[data-id="' + item.getId() + '"]')[0]
          $(old).before(dom)
          $(old).remove()
          item.unsetChanged()
        }

        window.XE.$$emit('content.render', { element: $(dom).find('xe-content')[0] })

        prev = item
      }.bind(this))

      window.XE.Component.timeago()
    },
    removeItem: function (item) {
      var ing = false
      var targets = []
      var self = this

      $.each(this.items, function (i, o) {
        if (o.getId() == item.getId()) {
          ing = true
        } else if (ing === true && o.getIndent() <= item.getIndent()) {
          return false
        }

        if (ing === true) {
          targets.push(o)
        }
      })

      var $targets = null
      $.each(targets, function (i, o) {
        if ($targets === null) {
          $targets = $(o.getDom())
        } else {
          $targets.add(o)
        }
      })

      $targets.fadeOut('slow', function () {
        $.each(targets, function (i, o) {
          self.remove(o)
        })

        self.renderItems()

        self.setTotalCnt(self.getTotalCnt() - targets.length)
      })
    },
    rendered: function (item) {
      return this.getListBox().find('[data-id="' + item.getId() + '"]').length > 0
    },
    setTotalCnt: function (cnt) {
      $(this.container).data('total-cnt', cnt)

      $('.__xe_comment_cnt', this.container).text(cnt)
    },
    getTotalCnt: function () {
      return parseInt($(this.container).data('total-cnt') ? $(this.container).data('total-cnt') : 0)
    },
    getLast: function () {
      if (this.props.config.reverse === true) {
        return this.items[this.items.length - 1]
      } else {
        return this.items[0]
      }
    },
    /**
     *
     * @param {string} mode
     * @param {*} id
     * @param {*} callback
     * @returns {Promise}
     */
    getForm: function (mode, id, callback) {
      // mode is create, edit and reply
      var data = {
        target_id: $(this.container).data('target_id'),
        instance_id: $(this.container).data('instance_id'),
        target_type: $(this.container).data('target_type')
      }
      var url = $(this.container).data('urls').form
      $.extend(data, {mode: mode, id: id})

      return new Promise(function (resolve) {
        var editorForm = window.XE.get(url, data)
        editorForm.then(function (response) {
          if (callback) {
            callback(response.data)
          }
          resolve(response.data)
        })
      })
    },

    find: function (id) {
      var item = null
      $.each(this.items, function (i, o) {
        if (o.getId() == id) {
          item = o
          return false
        }
      })

      return item
    },
    remove: function (item) {
      $.each(this.items, function (i, o) {
        if (o.getId() == item.getId()) {
          this.items.splice(i, 1)
          item.remove()
          return false
        }
      }.bind(this))
    },
    reset: function () {
      $.each(this.items, function (i, o) {
        o.removeForm()
      })
    },
    eventBind: function () {
      var self = this
      // button list more
      $('.__xe_comment_btn_more', this.container).click(function (e) {
        e.preventDefault()

        self.getListMore()
      })

      // button submit
      $(this.container).on('click', '.__xe_comment_btn_submit', function (e) {
        e.preventDefault()

        $(this).closest('form').trigger('submit')
      })

      // button item reply
      $(this.container).on('click', '.__xe_comment_btn_reply', function (e) {
        e.preventDefault()

        var context = $(this).closest('.__xe_comment_list_item')
        var item = self.find(context.data('id'))
        var existsForm = item.getForm()

        if (existsForm !== null && existsForm.getMode() == 'reply') {
          item.removeForm()
          return false
        }

        if (self.state.ing === true) {
          return false
        }
        self.state.ing = true

        self.reset()

        self.getForm('reply', item.getId(), function (json) {
          item.setForm(new CommentForm($.parseHTML(json.html), 'reply', function (json) {
            self._assetLoad(json.XE_ASSET_LOAD)

            var items = self.makeItems($.parseHTML(json.items, document, true))
            var child = items[0]
            self.spotIn(child)
            item.removeForm()
            self.renderItems()

            self.setTotalCnt(self.getTotalCnt() + 1)

            fire('replied', self, [child, item])
            comment.$$emit('item.reply.add', { child: child, item: item })
          }, self.props.config.editor))

          self.state.ing = false
        })
      })

      // button item edit
      $(this.container).on('click', '.__xe_comment_btn_edit', function (e) {
        e.preventDefault()

        var context = $(this).closest('.__xe_comment_list_item')
        var item = self.find(context.data('id'))
        var oldForm = item.getForm()

        if (oldForm && oldForm.getMode() == 'edit') {
          item.removeForm()
          return false
        }

        if (self.state.ing === true) {
          return false
        }
        self.state.ing = true

        self.reset()

        var mode = 'edit'
        var callback = function (json) {
          var editor = $.extend(true, {}, self.props.config.editor)
          $.extend(editor.options, json.etc)
          var form = new CommentForm($.parseHTML(json.html), mode, function (json) {
            self._assetLoad(json.XE_ASSET_LOAD)

            var items = self.makeItems($.parseHTML(json.items, document, true))
            item = items[0]
            item.setChanged()
            self.replace(item)
            self.renderItems()

            fire('updated', self, [item])
            comment.$$emit('item.updated', { item: item })
          }, editor)

          item.setForm(form)
        }
        self.getForm(mode, item.getId(), function (json) {
          if (json.mode === 'certify') {
            var form = new Certify($.parseHTML(json.html), callback)
            item.setForm(form)
          } else {
            callback(json)
          }

          self.state.ing = false
        })
      })

      // button item destroy
      $(this.container).on('click', '.__xe_comment_btn_destroy', function (e) {
        e.preventDefault()

        if (self.state.ing === true) {
          return false
        }

        if (!window.confirm(window.XE.Lang.trans('xe::confirmDelete'))) {
          return false
        }

        self.state.ing = true

        var context = $(this).closest('.__xe_comment_list_item')
        var item = self.find(context.data('id'))

        self.reset()

        var callback = function (json) {
          if (!json.success) {
            window.XE.toast('warning', window.XE.Lang.trans('comment::msgRemoveUnable'))
            return
          }

          if(self.props.config.hasOwnProperty('removeType') && self.props.config.removeType === 'blind') {
            var _item = $(item.dom)
            _item.find('xe-content').html(window.XE.Lang.trans('comment::removeContent'))
            _item.find('.comment_entity_tool').remove()
            _item.find('.comment_action').remove()
          }else {
            self.removeItem(item)
          }

          fire('deleted', self, [item, null])
          comment.$$emit('item.deleted', { item: item })
        }

        fire('deleting', self, [item])

        window.XE.post($(self.container).data('urls').destroy, {
          instance_id: $(self.container).data('instance_id'),
          id: item.getId()
        }).then(function (response) {
          if (response.data.mode && response.data.mode === 'certify') {
            var dom = $.parseHTML(response.data.html)
            item.setForm(new Certify(dom, callback))
          } else {
            callback(response.data)
          }

          self.state.ing = false
        })
      })

      $(this.container).on('click', '.__xe_comment_btn_vote', function (e) {
        e.preventDefault()

        if (self.state.ing === true) {
          return false
        }
        self.state.ing = true

        var el = this
        var context = $(this).closest('.__xe_comment_list_item')
        var item = self.find(context.data('id'))
        var type = item.currentVoteType(this)
        var urlSuffix = $(this).hasClass('on') ? 'voteOff' : 'voteOn'

        window.XE.post($(self.container).data('urls')[urlSuffix], {
          instance_id: item.getInstanceId(),
          id: item.getId(),
          option: type.current
        }).then(function (response) {
          if (response.data.result === true) {
            item.setVoteCnt(response.data)
            $(el).toggleClass('on')
            $('.__xe_comment_count.__xe_' + type.current, context).trigger('click', [true])
          }

          self.state.ing = false
          $(el).blur()
        })
      })

      $(this.container).on('click', '.__xe_comment_count', function (e, noToggle) {
        e.preventDefault()

        var context = $(this).closest('.__xe_comment_list_item')
        var item = self.find(context.data('id'))

        var type = item.currentVoteType(this)

        if (!noToggle) {
          $('.__xe_comment_count.__xe_' + type.opposite, context).removeClass('on')
          $('.__xe_comment_voters.__xe_' + type.opposite, context).hide()

          $(this).toggleClass('on')
        }

        if (!$(this).hasClass('on')) {
          $('.__xe_comment_voters.__xe_' + type.current, context).hide()
          return
        }

        window.XE.page(
          $(self.container).data('urls').votedUser,
          $('.__xe_comment_voters.__xe_' + type.current, context),
          {
            data: {
              instance_id: item.getInstanceId(),
              id: item.getId(),
              option: type.current
            }
          },
          function () {
            $('.__xe_comment_voters.__xe_' + type.current, context).show()
          }
        )
      })

      $(this.container).on('click', '.__xe_comment_btn_toggle_file', function (e) {
        e.preventDefault()

        $(this).toggleClass('on')
      })
    },
    getListBox: function () {
      return $(this.container).find('.__xe_comment_list')
    },
    getFormBox: function () {
      return $('.__xe_comment_form', this.container)
    }
  }

  function CommentItem (dom) {
    this.dom = dom
    this.form = null
    this.state = {changed: false}
  }

  CommentItem.prototype = {
    getDom: function () {
      return this.dom
    },
    getInstanceId: function () {
      return $(this.dom).data('instance_id')
    },
    getId: function () {
      return $(this.dom).data('id')
    },
    getHead: function () {
      return $(this.dom).data('head')
    },
    getReply: function () {
      return $(this.dom).data('reply')
    },
    getParentId: function () {
      return $(this.dom).data('parent_id')
    },
    getIndent: function () {
      return $(this.dom).data('indent')
    },
    setForm: function (form) {
      this.removeForm()

      this.form = form
      this.renderForm()
    },
    getForm: function () {
      return this.form
    },
    renderForm: function () {
      if (this.form.getMode() == 'certify' && $('.__xe_comment_certify', this.dom).length > 0) {
        this.form.render($('.__xe_comment_certify', this.dom))
        return
      }

      if (this.form.getMode() == 'edit') {
        $('.__xe_comment_edit_toggle', this.dom).hide()
        if ($('.__xe_comment_edit_form', this.dom).length > 0) {
          this.form.render($('.__xe_comment_edit_form', this.dom))
          return
        }
      }

      if (this.form.getMode() == 'reply' && $('.__xe_comment_reply_form', this.dom).length > 0) {
        this.form.render($('.__xe_comment_reply_form', this.dom))
        return
      }

      this.form.render(this.dom, true)
    },
    removeForm: function () {
      if (this.form) {
        this.form.remove()
        this.form = null
      }

      $('.__xe_comment_edit_toggle', this.dom).show()
    },
    // fadeOut: function (callback) {
    //     $(this.dom).fadeOut('slow', callback);
    // },
    setVoteCnt: function (data) {
      $('.__xe_comment_count.__xe_assent', this.dom).text(data.assent)
      $('.__xe_comment_count.__xe_dissent', this.dom).text(data.dissent)
    },
    currentVoteType: function (elem) {
      if ($(elem).hasClass('__xe_assent')) {
        return {current: 'assent', opposite: 'dissent'}
      } else if ($(elem).hasClass('__xe_dissent')) {
        return {current: 'dissent', opposite: 'assent'}
      }

      console.error('unknown vote type')
    },
    remove: function () {
      $(this.dom).remove()
    },
    isChanged: function () {
      return this.state.changed
    },
    setChanged: function () {
      this.state.changed = true
    },
    unsetChanged: function () {
      this.state.changed = false
    }
  }

  function CommentForm (dom, mode, callback, editorData) {
    this.dom = dom
    this.mode = mode
    this.callback = callback
    this.editorData = editorData
    this.editor = null
    this.container = null
  }

  CommentForm.prototype = {
    render: function (container, noReplace) {
      var that = this

      if (noReplace === true) {
        $(container).append(this.dom)
      } else {
        $(container).html(this.dom)
      }

      this.container = container

      window.XE.app('Editor').then(function initEditor (appEditor) {
        if (that.editorData !== null) {
          that.initEditor()
        }

        that._eventBind()
      })
    },
    initEditor: function () {
      if (typeof (XEeditor) === 'undefined') {
        return
      }

      var that = this
      var id = 'comment_textarea_' + (new Date().getTime())
      $('textarea', this.dom).attr('id', id).css('width', '100%')

      window.XE.app('Editor').then(function renderEditor (appEditor) {
        appEditor.getEditor(that.editorData.name).then(function createEditor (editor) {
          that.editor = editor.create(id, that.editorData.options, that.editorData.customOptions, that.editorData.tools)

          if(that.editor instanceof Promise) {
            that.editor.then(editor => {
              that.editor = editor;
              that.editor.on('focus', function focusCallback () {
                $(id).triggerHandler('focus')
              })
              that.editor.on('change', function changeCallback () {
                $(id).triggerHandler('input')
              })
            })
          } else {
            that.editor.on('focus', function focusCallback () {
              $(id).triggerHandler('focus')
            })
            that.editor.on('change', function changeCallback () {
              $(id).triggerHandler('input')
            })
          }
        })
      })
    },
    editorSync: function () {
      if (this.editor) {
        this.editor.setContents($('textarea', this.dom).val())
      }
    },
    getDom: function () {
      return this.dom
    },
    getMode: function () {
      return this.mode
    },
    _getForm: function () {
      return $(this.dom).is('form') ? this.dom : $('form', this.dom)
    },
    remove: function () {
      $(this.container).off('submit', 'form')
      $(this.dom).remove()
    },
    _eventBind: function () {
      var self = this
      var submitting = false

      $(this.container).on('submit', 'form', function (e) {
        e.preventDefault()

        if (submitting) {
          return false
        }

        $('button, input[type=submit], input[type=button]', self.dom).prop('disabled', true)

        if (!submitting) {
          submitting = true
        }

        window.XE.post($(this).attr('action'), $(this).serialize())
          .then(function (response) {
            self.callback(response.data)

            $(self._getForm()).trigger('reset')
            if (self.editor && self.getMode() == 'create') {
              self.editor.reset()
            }

            submitting = false
            $('button, input[type=submit], input[type=button]', self.dom).prop('disabled', false)
          })
          .catch(function () {
            submitting = false
            $('button, input[type=submit], input[type=button]', self.dom).prop('disabled', false)
          })
      })
    }
  }

  function Certify (dom, callback) {
    this.dom = dom
    this.mode = 'certify'
    this.callback = callback
    this.editorData = null
    this.editor = null
    this.container = null
  }

  Certify.prototype = CommentForm.prototype

  window.comment = comment

  return comment
})(window.jQuery)

var CommentVotedVirtualGrid = (function ($) {
  var self
  var grid
  var dataView
  var ajaxRunning = false // ajax중인지
  var startId
  var limit
  var isLastRow = false // 마지막 row인지

  return {
    init: function () {
      self = CommentVotedVirtualGrid
      var columns = [{
        // selectable: false,
        formatter: function (row, cell, value, columnDef, dataContext) {
          var tmpl = [
            '<!--[D] 링크가 아닌 경우 div 로 교체 -->',
            '<a href="__profilePage__" class="list-inner-item">',
            '<!--[D] 실제 이미지 사이즈는 모바일 대응 위해 일대일 비율로 96*96 이상-->',
            '<div class="img-thumbnail"><img src="__src__" width="48" height="48" alt="__alt__" /></div>',
            '<div class="list-text">',
            '<p>__alt__</p>',
            '</div>',
            '</a>'
          ].join('\n')

          return tmpl.replace(/__src__/g, dataContext.profileImage).replace(/__alt__/g, dataContext.displayName).replace(/__profilePage__/g, dataContext.profilePage)
        }
      }]

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
      }

      // var data = [];
      $('.xe-list-group').css('height', '365px')
      dataView = new Slick.Data.DataView()
      grid = new Slick.Grid('.xe-list-group', dataView, columns, options)
      grid.setHeaderRowVisibility(false)

      $('.slick-header').hide()

      id = 0
      ajaxRunning = false
      isLastRow = false
      startId = 0
      limit = 10

      self.getRows()
      self.bindEvent()

      return self
    },
    bindEvent: function () {
      grid.onScroll.subscribe(function (e, args) {
        var $viewport = $('.xe-modal').find('.slick-viewport')
        var loadBlockCnt = 3 // 3 page 정도 남으면 reload함, 1page - modal body height 기준.

        if (!ajaxRunning && !isLastRow && ($viewport[0].scrollHeight - $viewport.scrollTop()) < ($viewport.outerHeight() * loadBlockCnt)) {
          CommentVotedVirtualGrid.getRows()
        }
      })

      dataView.onRowCountChanged.subscribe(function (e, args) {
        grid.updateRowCount()
        grid.render()
      })

      dataView.onRowsChanged.subscribe(function (e, args) {
        grid.invalidateRows(args.rows)
        grid.render()
      })
    },
    getRows: function () {
      ajaxRunning = true

      var data = $('.xe-list-group').data('data')
      data = data ? (typeof (data) !== 'object' ? JSON.parse(data) : data) : {}
      data['limit'] = limit

      if (startId !== 0) {
        data['startId'] = startId
      }

      window.XE.get($('.xe-list-group').data('url'), data).then(function (response) {
        if (response.data.nextStartId === 0) {
          isLastRow = true
        }

        startId = response.data.nextStartId

        for (var k = 0, max = response.data.list.length; k < max; k += 1) {
          dataView.addItem(response.data.list[k])
        }
        ajaxRunning = false
      })
    }
  }
})(window.jQuery)
